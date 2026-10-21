<?php

namespace App\Application\Attachments;

use App\Domain\Attachments\AttachmentView;

/** What an assistant gets back: a slice of text, the image bytes, or neither (with a reason). */
final readonly class AttachmentContent
{
    public function __construct(
        public AttachmentView $attachment,
        public ?string $text = null,
        public int $offset = 0,
        public ?int $nextOffset = null,
        public ?string $imageBytes = null,
        public ?string $unavailableReason = null,
    ) {}
}
