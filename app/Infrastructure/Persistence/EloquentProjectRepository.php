<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Projects\MemberView;
use App\Domain\Projects\ProjectRepository;
use App\Domain\Projects\ProjectRole;
use App\Domain\Projects\ProjectView;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

final class EloquentProjectRepository implements ProjectRepository
{
    public function find(int $id, int $viewerId): ?ProjectView
    {
        $project = $this->query($viewerId)->whereKey($id)->first();

        return $project ? $this->map($project, $this->statusCounts([$id])[$id] ?? []) : null;
    }

    public function listForUser(int $userId, bool $includeArchived): array
    {
        $projects = $this->query($userId)
            ->whereHas('memberships', fn (Builder $q) => $q->where('user_id', $userId))
            ->when(! $includeArchived, fn (Builder $q) => $q->whereNull('archived_at'))
            ->orderBy('name')
            ->get();

        $counts = $this->statusCounts($projects->modelKeys());

        return $projects->map(fn (Project $p) => $this->map($p, $counts[$p->id] ?? []))->all();
    }

    public function projectIdsForUser(int $userId): array
    {
        return ProjectMember::where('user_id', $userId)->pluck('project_id')->map(fn ($id) => (int) $id)->all();
    }

    public function roleOf(int $projectId, int $userId): ?ProjectRole
    {
        $role = ProjectMember::where('project_id', $projectId)->where('user_id', $userId)->value('role');

        return $role ? ProjectRole::from($role) : null;
    }

    public function keyExists(string $key): bool
    {
        return Project::where('key', $key)->exists();
    }

    public function idForKey(string $key): ?int
    {
        $id = Project::where('key', $key)->value('id');

        return $id === null ? null : (int) $id;
    }

    public function create(int $ownerId, string $name, string $key, ?string $description, string $color): int
    {
        return Project::create([
            'owner_id' => $ownerId,
            'name' => $name,
            'key' => $key,
            'description' => $description,
            'color' => $color,
        ])->id;
    }

    public function update(int $id, array $changes): void
    {
        $columns = array_intersect_key($changes, array_flip(['name', 'description', 'color']));

        if (array_key_exists('archived', $changes)) {
            $columns['archived_at'] = $changes['archived'] ? now() : null;
        }

        Project::whereKey($id)->firstOrFail()->update($columns);
    }

    public function delete(int $id): void
    {
        Project::whereKey($id)->delete();
    }

    public function members(int $projectId): array
    {
        return ProjectMember::with('user:id,name,email')
            ->where('project_id', $projectId)
            ->get()
            ->sortBy(fn (ProjectMember $m) => [array_search($m->role, ['owner', 'admin', 'member']), $m->user->name])
            ->map(fn (ProjectMember $m) => new MemberView($m->user_id, $m->user->name, $m->user->email, ProjectRole::from($m->role)))
            ->values()
            ->all();
    }

    public function upsertMember(int $projectId, int $userId, ProjectRole $role): void
    {
        ProjectMember::updateOrCreate(
            ['project_id' => $projectId, 'user_id' => $userId],
            ['role' => $role->value],
        );
    }

    public function removeMember(int $projectId, int $userId): void
    {
        ProjectMember::where('project_id', $projectId)->where('user_id', $userId)->delete();

        // Work assigned to someone who left goes back to the pool rather than pointing at a non-member.
        Task::where('project_id', $projectId)->where('assignee_id', $userId)->update(['assignee_id' => null]);
        Task::where('project_id', $projectId)->where('reviewer_id', $userId)->update(['reviewer_id' => null]);
    }

    private function query(int $viewerId): Builder
    {
        return Project::query()
            ->select('projects.*')
            ->addSelect(['viewer_role' => ProjectMember::select('role')
                ->whereColumn('project_id', 'projects.id')
                ->where('user_id', $viewerId)
                ->limit(1)])
            ->withCount('memberships as member_count');
    }

    /** @return array<int,array<string,int>> */
    private function statusCounts(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        $counts = [];
        Task::whereIn('project_id', $projectIds)
            ->selectRaw('project_id, status, count(*) as total')
            ->groupBy('project_id', 'status')
            ->get()
            ->each(function ($row) use (&$counts) {
                $counts[$row->project_id][$row->status] = (int) $row->total;
            });

        return $counts;
    }

    private function map(Project $p, array $counts): ProjectView
    {
        return new ProjectView(
            id: $p->id,
            key: $p->key,
            name: $p->name,
            description: $p->description,
            color: $p->color,
            ownerId: $p->owner_id,
            archived: $p->archived_at !== null,
            role: $p->viewer_role ? ProjectRole::from($p->viewer_role) : null,
            statusCounts: $counts,
            memberCount: (int) $p->member_count,
            createdAt: $p->created_at->toIso8601String(),
        );
    }
}
