<?php

namespace App\Application\Attachments;

/** Where a file hangs: a task ("WEB-12") or a project ("WEB"). */
final readonly class AttachmentTarget
{
    private function __construct(
        public string $type,
        public string|int $ref,
    ) {}

    public static function task(string|int $ref): self
    {
        return new self('task', $ref);
    }

    public static function project(string|int $ref): self
    {
        return new self('project', $ref);
    }
}
