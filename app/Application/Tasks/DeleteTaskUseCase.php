<?php

namespace App\Application\Tasks;

use App\Application\Attachments\PurgeAttachmentFiles;
use App\Domain\Shared\Actor;
use App\Domain\Shared\Forbidden;
use App\Domain\Tasks\TaskRepository;

final class DeleteTaskUseCase
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly TaskLocator $locator,
        private readonly PurgeAttachmentFiles $purge,
    ) {}

    public function execute(Actor $actor, string|int $ref): void
    {
        $located = $this->locator->locate($actor, $ref);
        $isReporter = ($located->task->reporter['id'] ?? null) === $actor->userId;

        if (! $located->role->canManage() && ! $isReporter) {
            throw new Forbidden('Only the person who created this task or a project admin can delete it.');
        }

        $this->purge->forTask($located->task->id);
        $this->tasks->delete($located->task->id);
    }
}
