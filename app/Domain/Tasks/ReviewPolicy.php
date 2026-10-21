<?php

namespace App\Domain\Tasks;

use App\Domain\Projects\ProjectRole;

final class ReviewPolicy
{
    /**
     * Who may approve or bounce a task in review: the named reviewer, a project manager,
     * or — when nobody was named as reviewer — any member.
     */
    public static function canResolve(TaskView $task, int $userId, ProjectRole $role): bool
    {
        return $role->canManage()
            || $task->reviewer === null
            || $task->reviewer['id'] === $userId;
    }

    /** completed_at follows the status: set when entering Done, cleared when leaving it. */
    public static function completionChange(TaskStatus $to): array
    {
        return ['completed_at' => $to === TaskStatus::Done ? date('Y-m-d H:i:s') : null];
    }
}
