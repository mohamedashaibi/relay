<?php

namespace App\Application\Tasks;

use App\Application\Notifications\TaskNotifications;
use App\Domain\Shared\Actor;
use App\Domain\Shared\Forbidden;
use App\Domain\Shared\RuleViolation;
use App\Domain\Tasks\ReviewDecision;
use App\Domain\Tasks\ReviewPolicy;
use App\Domain\Tasks\TaskRepository;
use App\Domain\Tasks\TaskStatus;
use App\Domain\Tasks\TaskView;

final class ResolveReviewUseCase
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly TaskLocator $locator,
        private readonly TaskNotifications $notify,
    ) {}

    public function execute(Actor $actor, string|int $ref, ReviewDecision $decision, ?string $comment = null): TaskView
    {
        $located = $this->locator->locate($actor, $ref);
        $task = $located->task;

        if ($task->status !== TaskStatus::InReview) {
            throw new RuleViolation("{$task->key} is not waiting for review.");
        }

        if (! ReviewPolicy::canResolve($task, $actor->userId, $located->role)) {
            throw new Forbidden("Only {$task->reviewer['name']} or a project admin can review {$task->key}.");
        }

        $to = $decision->resultingStatus();
        $updated = $this->tasks->update($task->id, [
            'status' => $to->value,
            'position' => $this->tasks->nextPosition($task->projectId(), $to),
        ] + ReviewPolicy::completionChange($to));

        $comment = $comment !== null ? trim($comment) : null;
        if ($comment) {
            $this->tasks->addComment($task->id, $actor, $comment);
        }

        $this->tasks->log($task->id, $actor, $decision->activity(), ['comment' => $comment ?: null]);
        $this->notify->reviewResolved($updated, $decision, $comment ?: null, $actor);

        return $updated;
    }
}
