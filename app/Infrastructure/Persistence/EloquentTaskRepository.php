<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Shared\Actor;
use App\Domain\Tasks\ActivityType;
use App\Domain\Tasks\ActivityView;
use App\Domain\Tasks\CommentView;
use App\Domain\Tasks\TaskCriteria;
use App\Domain\Tasks\TaskDraft;
use App\Domain\Tasks\TaskPriority;
use App\Domain\Tasks\TaskRepository;
use App\Domain\Tasks\TaskStatus;
use App\Domain\Tasks\TaskView;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class EloquentTaskRepository implements TaskRepository
{
    private const WRITABLE = [
        'title', 'description', 'status', 'priority', 'labels', 'assignee_id',
        'reviewer_id', 'review_note', 'due_date', 'position', 'completed_at',
    ];

    public function find(int $id): ?TaskView
    {
        $task = $this->query()->find($id);

        return $task ? $this->map($task) : null;
    }

    public function findByKey(string $projectKey, int $number): ?TaskView
    {
        $task = $this->query()->whereRelation('project', 'key', $projectKey)->where('number', $number)->first();

        return $task ? $this->map($task) : null;
    }

    public function search(TaskCriteria $c): array
    {
        return $this->query()
            ->whereIn('project_id', $c->projectIds)
            ->when($c->statuses, fn (Builder $q) => $q->whereIn('status', array_map(fn (TaskStatus $s) => $s->value, $c->statuses)))
            ->when($c->assigneeId, fn (Builder $q) => $q->where('assignee_id', $c->assigneeId))
            ->when($c->unassigned, fn (Builder $q) => $q->whereNull('assignee_id'))
            ->when($c->reviewerId, fn (Builder $q) => $q->where('reviewer_id', $c->reviewerId))
            ->when($c->priority, fn (Builder $q) => $q->where('priority', $c->priority->value))
            ->when($c->label, fn (Builder $q) => $q->whereJsonContains('labels', mb_strtolower($c->label)))
            ->when($c->overdueOnly, fn (Builder $q) => $q->whereDate('due_date', '<', today())->where('status', '!=', TaskStatus::Done->value))
            ->when($c->text, function (Builder $q) use ($c) {
                $like = '%'.addcslashes($c->text, '\\%_').'%';
                $q->where(fn (Builder $w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like));
            })
            ->orderBy('position')
            ->orderBy('id')
            ->limit($c->limit)
            ->get()
            ->map(fn (Task $t) => $this->map($t))
            ->all();
    }

    public function create(TaskDraft $d): TaskView
    {
        $id = DB::transaction(function () use ($d) {
            $project = Project::whereKey($d->projectId)->lockForUpdate()->firstOrFail();
            $project->increment('task_counter');

            return Task::create([
                'project_id' => $d->projectId,
                'number' => $project->task_counter,
                'title' => $d->title,
                'description' => $d->description,
                'status' => $d->status->value,
                'priority' => $d->priority->value,
                'labels' => $d->labels,
                'assignee_id' => $d->assigneeId,
                'reporter_id' => $d->reporterId,
                'reviewer_id' => $d->reviewerId,
                'due_date' => $d->dueDate,
                'position' => $this->nextPosition($d->projectId, $d->status),
                'completed_at' => $d->status === TaskStatus::Done ? now() : null,
            ])->id;
        });

        return $this->find($id);
    }

    public function update(int $id, array $changes): TaskView
    {
        Task::whereKey($id)->firstOrFail()->update(array_intersect_key($changes, array_flip(self::WRITABLE)));

        return $this->find($id);
    }

    public function delete(int $id): void
    {
        Task::whereKey($id)->delete();
    }

    public function nextPosition(int $projectId, TaskStatus $status): float
    {
        return (float) (Task::where('project_id', $projectId)->where('status', $status->value)->max('position') ?? 0) + 1024;
    }

    public function comments(int $taskId): array
    {
        return TaskComment::with('user:id,name')
            ->where('task_id', $taskId)
            ->orderBy('id')
            ->get()
            ->map(fn (TaskComment $c) => $this->mapComment($c))
            ->all();
    }

    public function addComment(int $taskId, Actor $actor, string $body): CommentView
    {
        $comment = TaskComment::create([
            'task_id' => $taskId,
            'user_id' => $actor->userId,
            'body' => $body,
            'channel' => $actor->channel->value,
            'client' => $actor->client,
        ]);

        return $this->mapComment($comment->load('user:id,name'));
    }

    public function activities(int $taskId, int $limit = 50): array
    {
        return TaskActivity::with('user:id,name')
            ->where('task_id', $taskId)
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (TaskActivity $a) => $this->mapActivity($a))
            ->all();
    }

    public function recentActivity(array $projectIds, int $limit = 20): array
    {
        if ($projectIds === []) {
            return [];
        }

        return TaskActivity::with(['user:id,name', 'task:id,project_id,number,title', 'task.project:id,key'])
            ->whereIn('task_id', Task::select('id')->whereIn('project_id', $projectIds))
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (TaskActivity $a) => $this->mapActivity($a, withTask: true))
            ->all();
    }

    public function log(int $taskId, Actor $actor, ActivityType $type, array $meta = []): void
    {
        TaskActivity::create([
            'task_id' => $taskId,
            'user_id' => $actor->userId,
            'type' => $type->value,
            'meta' => $meta,
            'channel' => $actor->channel->value,
            'client' => $actor->client,
        ]);
    }

    public function statsFor(int $userId, array $projectIds): array
    {
        $mine = fn () => Task::whereIn('project_id', $projectIds)->where('assignee_id', $userId);

        return [
            'by_status' => $mine()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')
                ->map(fn ($n) => (int) $n)->all(),
            'awaiting_my_review' => Task::whereIn('project_id', $projectIds)->where('reviewer_id', $userId)
                ->where('status', TaskStatus::InReview->value)->count(),
            'overdue' => $mine()->where('status', '!=', TaskStatus::Done->value)->whereDate('due_date', '<', today())->count(),
            'completed_this_week' => $mine()->where('completed_at', '>=', now()->startOfWeek())->count(),
        ];
    }

    private function query(): Builder
    {
        return Task::query()
            ->with(['project:id,key,name,color', 'assignee:id,name', 'reporter:id,name', 'reviewer:id,name'])
            ->withCount(['comments', 'attachments']);
    }

    private function person(?User $user): ?array
    {
        return $user ? ['id' => $user->id, 'name' => $user->name] : null;
    }

    private function map(Task $t): TaskView
    {
        return new TaskView(
            id: $t->id,
            key: "{$t->project->key}-{$t->number}",
            number: $t->number,
            project: ['id' => $t->project->id, 'key' => $t->project->key, 'name' => $t->project->name, 'color' => $t->project->color],
            title: $t->title,
            description: $t->description,
            status: TaskStatus::from($t->status),
            priority: TaskPriority::from($t->priority),
            labels: $t->labels ?? [],
            assignee: $this->person($t->assignee),
            reporter: $this->person($t->reporter),
            reviewer: $this->person($t->reviewer),
            reviewNote: $t->review_note,
            dueDate: $t->due_date?->format('Y-m-d'),
            position: $t->position,
            completedAt: $t->completed_at?->toIso8601String(),
            createdAt: $t->created_at->toIso8601String(),
            updatedAt: $t->updated_at->toIso8601String(),
            commentCount: (int) ($t->comments_count ?? 0),
            attachmentCount: (int) ($t->attachments_count ?? 0),
        );
    }

    private function mapComment(TaskComment $c): CommentView
    {
        return new CommentView(
            id: $c->id,
            taskId: $c->task_id,
            author: $this->person($c->user),
            body: $c->body,
            channel: $c->channel,
            client: $c->client,
            createdAt: $c->created_at->toIso8601String(),
        );
    }

    private function mapActivity(TaskActivity $a, bool $withTask = false): ActivityView
    {
        return new ActivityView(
            id: $a->id,
            type: ActivityType::from($a->type),
            actor: $this->person($a->user),
            meta: $a->meta ?? [],
            channel: $a->channel,
            client: $a->client,
            createdAt: $a->created_at->toIso8601String(),
            task: $withTask && $a->task ? [
                'id' => $a->task->id,
                'key' => "{$a->task->project->key}-{$a->task->number}",
                'title' => $a->task->title,
            ] : null,
        );
    }
}
