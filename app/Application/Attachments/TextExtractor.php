<?php

namespace App\Application\Attachments;

use App\Domain\Attachments\AttachmentKind;

interface TextExtractor
{
    /** Plain text for AI reading, or null when the format has none we can pull out. */
    public function extract(string $localPath, AttachmentKind $kind, string $name): ?string;
}
