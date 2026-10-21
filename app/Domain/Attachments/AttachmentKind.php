<?php

namespace App\Domain\Attachments;

enum AttachmentKind: string
{
    case Image = 'image';
    case Pdf = 'pdf';
    case Text = 'text';
    case Document = 'document';
    case Spreadsheet = 'spreadsheet';
    case Other = 'other';

    public static function detect(string $mime, string $name): self
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return match (true) {
            str_starts_with($mime, 'image/') => self::Image,
            $mime === 'application/pdf' => self::Pdf,
            in_array($ext, ['docx', 'pptx'], true) => self::Document,
            $ext === 'xlsx' => self::Spreadsheet,
            str_starts_with($mime, 'text/'), in_array($mime, ['application/json', 'application/xml', 'application/x-yaml'], true) => self::Text,
            default => self::Other,
        };
    }
}
