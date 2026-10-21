<?php

namespace App\Application\Tasks;

use App\Application\Notifications\TaskNotifications;
use App\Application\Shared\ProjectAccess;
use App\Domain\Shared\Actor;
use App\Domain\Shared\RuleViolation;
use App\Domain\Tasks\ActivityType;
use App\Domain\Tasks\ReviewPolicy;
use App\Domain\Tasks\TaskRepository;
use App\Domain\Tasks\TaskStatus;
use App\Domain\Tasks\TaskView;

/** Hand-off: "I'm done, a human should check this." The summary is what the reviewer reads first. */
final class RequestReviewUseCase
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly TaskLocator $locator,
        private readonly ProjectAccess $access,
        private readonly TaskNotifications $notify,
    ) {}

    public function execute(Actor $actor, string|int $ref, string $summary, ?int $reviewerId = null): TaskView
    {
        $task = $this->locator->locate($actor, $ref)->task;

        if ($task->status === TaskStatus::Done) {
            throw new RuleViolation("{$task->key} is already done. Reopen it before requesting a review.");
        }

        $this->access->ensureUserIsMember($task->projectId(), $reviewerId, 'reviewer');

        $changes = [
            'status' => TaskStatus::InReview->value,
            'review_note' => trim($summary),
            'position' => $task->status === TaskStatus::InReview
                ? $task->position
                : $this->tasks->nextPosition($task->projectId(), TaskStatus::InReview),
        ] + ReviewPolicy::completionChange(TaskStatus::InReview);

        if ($reviewerId !== null) {
            $changes['reviewer_id'] = $reviewerId;
        }

        $updated = $this->tasks->update($task->id, $changes);

        $this->tasks->log($task->id, $actor, ActivityType::ReviewRequested, [
            'summary' => trim($summary),
            'reviewer' => $updated->reviewer['name'] ?? null,
        ]);

        $this->notify->reviewRequested($updated, $actor, trim($summary));

        return $updated;
    }
}
