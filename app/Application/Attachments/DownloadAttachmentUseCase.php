<?php

namespace App\Application\Attachments;

use App\Domain\Attachments\AttachmentRepository;
use App\Domain\Attachments\AttachmentView;
use App\Domain\Shared\Actor;

final class DownloadAttachmentUseCase
{
    public function __construct(
        private readonly AttachmentRepository $attachments,
        private readonly AttachmentLocator $locator,
    ) {}

    /** @return array{attachment:AttachmentView,disk:string,path:string} */
    public function execute(Actor $actor, int $id): array
    {
        [$attachment] = $this->locator->attachment($actor, $id);

        return ['attachment' => $attachment, ...$this->attachments->location($id)];
    }
}
