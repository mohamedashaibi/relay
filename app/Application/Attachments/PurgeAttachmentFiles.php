<?php

namespace App\Application\Attachments;

use App\Domain\Attachments\AttachmentRepository;

/**
 * Database rows cascade when a task or project is deleted, but stored files don't —
 * call this first so deletions don't leave orphaned files behind.
 */
final class PurgeAttachmentFiles
{
    public function __construct(
        private readonly AttachmentRepository $attachments,
        private readonly AttachmentStorage $storage,
    ) {}

    public function forTask(int $taskId): void
    {
        foreach ($this->attachments->locationsForTask($taskId) as $location) {
            $this->storage->delete($location['disk'], $location['path']);
        }
    }

    public function forProject(int $projectId): void
    {
        foreach ($this->attachments->locationsForProject($projectId) as $location) {
            $this->storage->delete($location['disk'], $location['path']);
        }
    }
}
