<?php

namespace App\Domain\Tasks;

use App\Domain\Shared\Actor;

interface TaskRepository
{
    public function find(int $id): ?TaskView;

    public function findByKey(string $projectKey, int $number): ?TaskView;

    /** @return list<TaskView> */
    public function search(TaskCriteria $criteria): array;

    public function create(TaskDraft $draft): TaskView;

    /**
     * Applies column changes. Callers pass only whitelisted keys: title, description, status,
     * priority, labels, assignee_id, reviewer_id, review_note, due_date, position, completed_at.
     *
     * @param  array<string,mixed>  $changes
     */
    public function update(int $id, array $changes): TaskView;

    public function delete(int $id): void;

    public function nextPosition(int $projectId, TaskStatus $status): float;

    /** @return list<CommentView> */
    public function comments(int $taskId): array;

    public function addComment(int $taskId, Actor $actor, string $body): CommentView;

    /** @return list<ActivityView> */
    public function activities(int $taskId, int $limit = 50): array;

    /**
     * @param  list<int>  $projectIds
     * @return list<ActivityView>
     */
    public function recentActivity(array $projectIds, int $limit = 20): array;

    public function log(int $taskId, Actor $actor, ActivityType $type, array $meta = []): void;

    /**
     * @param  list<int>  $projectIds
     * @return array{by_status:array<string,int>,awaiting_my_review:int,overdue:int,completed_this_week:int}
     */
    public function statsFor(int $userId, array $projectIds): array;
}
