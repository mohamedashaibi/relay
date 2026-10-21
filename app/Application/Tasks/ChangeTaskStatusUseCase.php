<?php

namespace App\Application\Tasks;

use App\Application\Notifications\TaskNotifications;
use App\Domain\Shared\Actor;
use App\Domain\Shared\Forbidden;
use App\Domain\Tasks\ActivityType;
use App\Domain\Tasks\ReviewDecision;
use App\Domain\Tasks\ReviewPolicy;
use App\Domain\Tasks\TaskRepository;
use App\Domain\Tasks\TaskStatus;
use App\Domain\Tasks\TaskView;

/** Moves a task between columns; also used for in-column reordering when a position is given. */
final class ChangeTaskStatusUseCase
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly TaskLocator $locator,
        private readonly TaskNotifications $notify,
    ) {}

    public function execute(Actor $actor, string|int $ref, TaskStatus $to, ?float $position = null): TaskView
    {
        $located = $this->locator->locate($actor, $ref);
        $task = $located->task;
        $from = $task->status;

        if ($from->transitionNeedsReviewer($to) && ! ReviewPolicy::canResolve($task, $actor->userId, $located->role)) {
            throw new Forbidden(sprintf(
                '%s is waiting on review by %s. Only the reviewer or a project admin can mark it done.',
                $task->key,
                $task->reviewer['name'] ?? 'someone else',
            ));
        }

        $changes = [
            'status' => $to->value,
            'position' => $position ?? ($from === $to ? $task->position : $this->tasks->nextPosition($task->projectId(), $to)),
        ];

        if ($from === $to) {
            return $this->tasks->update($task->id, $changes);
        }

        $updated = $this->tasks->update($task->id, $changes + ReviewPolicy::completionChange($to));

        $this->tasks->log(
            $task->id,
            $actor,
            $from->transitionNeedsReviewer($to) ? ActivityType::Approved : ActivityType::StatusChanged,
            ['from' => $from->value, 'to' => $to->value],
        );

        match (true) {
            $from->transitionNeedsReviewer($to) => $this->notify->reviewResolved($updated, ReviewDecision::Approve, null, $actor),
            $to === TaskStatus::InReview => $this->notify->reviewRequested($updated, $actor, null),
            default => $this->notify->statusChanged($updated, $from, $to, $actor),
        };

        return $updated;
    }
}
