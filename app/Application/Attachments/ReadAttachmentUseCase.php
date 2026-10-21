<?php

namespace App\Application\Attachments;

use App\Domain\Attachments\AttachmentKind;
use App\Domain\Attachments\AttachmentRepository;
use App\Domain\Shared\Actor;

/**
 * Reads an attachment for an AI assistant. Text comes in pages (offset + max chars) so large
 * documents don't flood the context window; images are returned as images for vision models.
 */
final class ReadAttachmentUseCase
{
    public const MAX_IMAGE_BYTES = 5 * 1024 * 1024; // model providers reject larger inline images

    public function __construct(
        private readonly AttachmentRepository $attachments,
        private readonly AttachmentLocator $locator,
        private readonly AttachmentStorage $storage,
    ) {}

    public function execute(Actor $actor, int $id, int $offset = 0, int $maxChars = 20_000): AttachmentContent
    {
        [$attachment] = $this->locator->attachment($actor, $id);

        if ($attachment->kind === AttachmentKind::Image) {
            if ($attachment->size > self::MAX_IMAGE_BYTES) {
                return new AttachmentContent($attachment, unavailableReason: 'This image is larger than 5 MB, too big to send to an assistant. Ask the user to upload a smaller version.');
            }
            $location = $this->attachments->location($id);

            return new AttachmentContent($attachment, imageBytes: $this->storage->read($location['disk'], $location['path']));
        }

        if (! $attachment->hasText()) {
            return new AttachmentContent($attachment, unavailableReason: match ($attachment->kind) {
                AttachmentKind::Spreadsheet => 'Spreadsheets have no text preview. Ask the user to export the sheet as CSV and attach that.',
                AttachmentKind::Pdf => 'No text could be extracted from this PDF (it may be scanned images). Ask the user for a text version.',
                default => 'This file type has no readable text.',
            });
        }

        $text = (string) $this->attachments->text($id);
        $total = mb_strlen($text);
        $offset = max(0, min($offset, $total));
        $slice = mb_substr($text, $offset, max(1000, min($maxChars, 50_000)));
        $end = $offset + mb_strlen($slice);

        return new AttachmentContent($attachment, text: $slice, offset: $offset, nextOffset: $end < $total ? $end : null);
    }
}
