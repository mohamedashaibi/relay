<?php

namespace App\Application\Tasks;

use App\Domain\Attachments\AttachmentRepository;
use App\Domain\Shared\Actor;
use App\Domain\Tasks\ReviewPolicy;
use App\Domain\Tasks\TaskRepository;

final class GetTaskUseCase
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly TaskLocator $locator,
        private readonly AttachmentRepository $attachments,
    ) {}

    public function execute(Actor $actor, string|int $ref): TaskDetail
    {
        $located = $this->locator->locate($actor, $ref);
        $task = $located->task;

        return new TaskDetail(
            task: $task,
            comments: $this->tasks->comments($task->id),
            activity: $this->tasks->activities($task->id),
            canReview: ReviewPolicy::canResolve($task, $actor->userId, $located->role),
            canDelete: $located->role->canManage() || ($task->reporter['id'] ?? null) === $actor->userId,
            attachments: $this->attachments->forTask($task->id),
        );
    }
}
