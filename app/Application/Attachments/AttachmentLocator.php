<?php

namespace App\Application\Attachments;

use App\Application\Shared\ProjectAccess;
use App\Application\Tasks\TaskLocator;
use App\Domain\Attachments\AttachmentRepository;
use App\Domain\Attachments\AttachmentView;
use App\Domain\Projects\ProjectRole;
use App\Domain\Shared\Actor;
use App\Domain\Shared\NotFound;

/** Resolves targets and attachments, enforcing project membership. Outsiders always get "not found". */
final class AttachmentLocator
{
    public function __construct(
        private readonly AttachmentRepository $attachments,
        private readonly ProjectAccess $access,
        private readonly TaskLocator $tasks,
    ) {}

    /** @return array{projectId:int,taskId:?int} */
    public function target(Actor $actor, AttachmentTarget $target): array
    {
        if ($target->type === 'task') {
            $task = $this->tasks->locate($actor, $target->ref)->task;

            return ['projectId' => $task->projectId(), 'taskId' => $task->id];
        }

        return ['projectId' => $this->access->resolveProjectId($actor, $target->ref), 'taskId' => null];
    }

    /** @return array{0:AttachmentView,1:ProjectRole} */
    public function attachment(Actor $actor, int $id): array
    {
        $attachment = $this->attachments->find($id) ?? throw NotFound::of('Attachment', $id);

        try {
            $role = $this->access->role($actor, $attachment->projectId);
        } catch (NotFound) {
            throw NotFound::of('Attachment', $id);
        }

        return [$attachment, $role];
    }
}
