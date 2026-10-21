<?php

namespace App\Domain\Attachments;

/** The one list of file types Relay accepts. SVG and HTML are deliberately absent (script risk). */
final class AllowedTypes
{
    /** Extension => canonical MIME type we store. */
    public const ALL = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'log' => 'text/plain',
        'md' => 'text/markdown',
        'markdown' => 'text/markdown',
        'csv' => 'text/csv',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'yaml' => 'application/x-yaml',
        'yml' => 'application/x-yaml',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    /** What an assistant may write as a file: plain-text formats only. */
    public const TEXT = ['md', 'markdown', 'txt', 'log', 'csv', 'json', 'xml', 'yaml', 'yml'];

    public static function mimeFor(string $extension): ?string
    {
        return self::ALL[strtolower($extension)] ?? null;
    }
}
