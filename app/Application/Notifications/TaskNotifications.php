<?php

namespace App\Application\Notifications;

use App\Domain\Projects\MemberView;
use App\Domain\Projects\ProjectRepository;
use App\Domain\Projects\ProjectRole;
use App\Domain\Shared\Actor;
use App\Domain\Shared\Channel;
use App\Domain\Tasks\CommentView;
use App\Domain\Tasks\ReviewDecision;
use App\Domain\Tasks\TaskStatus;
use App\Domain\Tasks\TaskView;
use App\Domain\Users\NotificationCategory;
use App\Domain\Users\UserRepository;

/**
 * Decides who hears about what. Rules:
 *  - nobody is emailed about their own action (including their AI assistant acting as them);
 *  - each person's email preferences are respected per category.
 */
final class TaskNotifications
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly UserRepository $users,
        private readonly ProjectRepository $projects,
    ) {}

    public function assigned(TaskView $task, Actor $actor): void
    {
        $this->send([$task->assignee['id'] ?? null], $actor, new EmailMessage(
            NotificationCategory::Assigned,
            "{$task->key} is now assigned to you",
            "{$this->by($actor)} assigned you {$task->key}",
            $this->summary($task),
            null,
            'Open the task',
            $this->path($task),
        ));
    }

    public function statusChanged(TaskView $task, TaskStatus $from, TaskStatus $to, Actor $actor): void
    {
        $this->send([$task->assignee['id'] ?? null, $task->reporter['id'] ?? null], $actor, new EmailMessage(
            NotificationCategory::Status,
            "{$task->key} moved to {$to->label()}",
            "{$this->by($actor)} moved {$task->key} from {$from->label()} to {$to->label()}",
            $this->summary($task),
            null,
            'Open the task',
            $this->path($task),
        ));
    }

    /** Goes to the named reviewer, or to the project's owners and admins when nobody was named. */
    public function reviewRequested(TaskView $task, Actor $actor, ?string $summary): void
    {
        $recipients = $task->reviewer !== null
            ? [$task->reviewer['id']]
            : array_map(fn (MemberView $m) => $m->userId, array_filter(
                $this->projects->members($task->projectId()),
                fn (MemberView $m) => $m->role->canManage(),
            ));

        $this->send($recipients, $actor, new EmailMessage(
            NotificationCategory::Review,
            "Review needed: {$task->key} {$task->title}",
            "{$this->by($actor)} asked for your review on {$task->key}",
            $this->summary($task),
            $summary ? $this->excerpt($summary) : null,
            'Review the task',
            $this->path($task),
        ));
    }

    public function reviewResolved(TaskView $task, ReviewDecision $decision, ?string $comment, Actor $actor): void
    {
        $approved = $decision === ReviewDecision::Approve;

        $this->send([$task->assignee['id'] ?? null, $task->reporter['id'] ?? null], $actor, new EmailMessage(
            NotificationCategory::Review,
            $approved ? "{$task->key} was approved" : "Changes requested on {$task->key}",
            $approved
                ? "{$this->by($actor)} approved {$task->key} — it’s done"
                : "{$this->by($actor)} sent {$task->key} back with changes",
            $this->summary($task),
            $comment ? $this->excerpt($comment) : null,
            'Open the task',
            $this->path($task),
        ));
    }

    public function commented(TaskView $task, CommentView $comment, Actor $actor): void
    {
        $this->send(
            [$task->assignee['id'] ?? null, $task->reporter['id'] ?? null, $task->reviewer['id'] ?? null],
            $actor,
            new EmailMessage(
                NotificationCategory::Comments,
                "New comment on {$task->key} {$task->title}",
                "{$this->by($actor)} commented on {$task->key}",
                [$task->title],
                $this->excerpt($comment->body),
                'Reply',
                $this->path($task),
            ),
        );
    }

    public function addedToProject(int $projectId, int $userId, ProjectRole $role, Actor $actor): void
    {
        $project = $this->projects->find($projectId, $userId);
        if ($project === null) {
            return;
        }

        $this->send([$userId], $actor, new EmailMessage(
            NotificationCategory::Projects,
            "You’re now in {$project->name} on Relay",
            "{$this->by($actor)} added you to {$project->name} as ".($role === ProjectRole::Admin ? 'an admin' : 'a member'),
            array_values(array_filter([$project->description, "Tasks in this project are numbered {$project->key}-1, {$project->key}-2…"])),
            null,
            'Open the project',
            "/projects/{$project->key}",
        ));
    }

    /** @param list<int|null> $userIds */
    private function send(array $userIds, Actor $actor, EmailMessage $message): void
    {
        $recipients = array_unique(array_filter($userIds, fn ($id) => $id !== null && $id !== $actor->userId));

        foreach ($recipients as $userId) {
            if ($this->users->emailPreferences($userId)[$message->category->value] ?? true) {
                $this->notifier->send($userId, $message);
            }
        }
    }

    /** "Ada" or "Ada (via Claude)". */
    private function by(Actor $actor): string
    {
        $name = $this->users->find($actor->userId)?->name ?? 'Someone';

        return $actor->channel === Channel::Mcp ? "{$name} (via {$actor->client})" : $name;
    }

    /** @return list<string> */
    private function summary(TaskView $task): array
    {
        return array_values(array_filter([
            "**{$task->title}**",
            "{$task->project['name']} · {$task->status->label()} · ".ucfirst($task->priority->value).' priority',
            $task->dueDate ? 'Due '.date('M j, Y', strtotime($task->dueDate)) : null,
        ]));
    }

    private function excerpt(string $text): string
    {
        return mb_strimwidth(trim($text), 0, 600, '…');
    }

    private function path(TaskView $task): string
    {
        return "/tasks/{$task->key}";
    }
}
