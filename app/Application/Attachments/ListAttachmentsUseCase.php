<?php

namespace App\Application\Attachments;

use App\Domain\Attachments\AttachmentRepository;
use App\Domain\Attachments\AttachmentView;
use App\Domain\Shared\Actor;

final class ListAttachmentsUseCase
{
    public function __construct(
        private readonly AttachmentRepository $attachments,
        private readonly AttachmentLocator $locator,
    ) {}

    /** @return list<AttachmentView> */
    public function execute(Actor $actor, AttachmentTarget $target): array
    {
        ['projectId' => $projectId, 'taskId' => $taskId] = $this->locator->target($actor, $target);

        return $taskId !== null ? $this->attachments->forTask($taskId) : $this->attachments->forProject($projectId);
    }
}
