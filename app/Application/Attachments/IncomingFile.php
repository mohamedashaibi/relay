<?php

namespace App\Application\Attachments;

/** An uploaded file already validated by the delivery layer, sitting in a temp path. */
final readonly class IncomingFile
{
    public function __construct(
        public string $name,
        public string $mime,
        public int $size,
        public string $localPath,
    ) {}

    public function extension(): string
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }
}
