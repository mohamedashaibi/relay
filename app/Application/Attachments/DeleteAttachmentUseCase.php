<?php

namespace App\Application\Attachments;

use App\Domain\Attachments\AttachmentRepository;
use App\Domain\Shared\Actor;
use App\Domain\Shared\Forbidden;

final class DeleteAttachmentUseCase
{
    public function __construct(
        private readonly AttachmentRepository $attachments,
        private readonly AttachmentLocator $locator,
        private readonly AttachmentStorage $storage,
    ) {}

    /** The uploader or a project admin may delete. */
    public function execute(Actor $actor, int $id): void
    {
        [$attachment, $role] = $this->locator->attachment($actor, $id);

        if (! $role->canManage() && ($attachment->uploader['id'] ?? null) !== $actor->userId) {
            throw new Forbidden('Only the person who uploaded this file or a project admin can delete it.');
        }

        $location = $this->attachments->location($id);
        $this->attachments->delete($id);
        $this->storage->delete($location['disk'], $location['path']);
    }
}
