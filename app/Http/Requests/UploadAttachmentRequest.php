<?php

namespace App\Http\Requests;

use App\Domain\Attachments\AllowedTypes;
use Illuminate\Validation\Validator;

class UploadAttachmentRequest extends StrictFormRequest
{
    /** What the server's content sniffing may report for each family of extensions. */
    private const SNIFFED = [
        'image' => ['image/png', 'image/jpeg', 'image/gif', 'image/webp'],
        'pdf' => ['application/pdf'],
        'text' => ['text/plain', 'text/markdown', 'text/x-markdown', 'text/csv', 'application/csv', 'text/xml', 'application/xml', 'application/json', 'text/yaml', 'application/x-yaml', 'text/x-yaml', 'application/x-empty', 'inode/x-empty'],
        'office' => ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
    ];

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:'.intdiv(self::maxBytes(), 1024), 'extensions:'.implode(',', array_keys(AllowedTypes::ALL))],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'That file is too large. The limit is '.round(self::maxBytes() / 1048576).' MB.',
            'file.extensions' => 'That file type isn’t supported. Upload images, PDFs, text, Markdown, CSV, JSON, or Word/Excel/PowerPoint files.',
            'file.uploaded' => 'The upload failed — the file may be larger than the server allows ('.round(self::maxBytes() / 1048576).' MB).',
        ];
    }

    public function after(): array
    {
        return [...parent::after(), function (Validator $validator) {
            $file = $this->file('file');
            if (! $file || $validator->errors()->has('file')) {
                return;
            }

            // The extension must agree with what the bytes actually are — sniffed here, never trusted from the client.
            $family = $this->family(strtolower($file->getClientOriginalExtension()));
            $sniffed = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
            $ok = in_array($sniffed, self::SNIFFED[$family] ?? [], true)
                // Very short text files sniff as octet-stream; accept them if there's no binary (NUL) content.
                || ($family === 'text' && $sniffed === 'application/octet-stream'
                    && ! str_contains((string) file_get_contents($file->getRealPath(), length: 8192), "\0"));
            if (! $ok) {
                $validator->errors()->add('file', 'The file’s contents don’t match its extension.');
            }
        }];
    }

    public function storedMime(): string
    {
        return AllowedTypes::mimeFor($this->file('file')->getClientOriginalExtension());
    }

    /** The real ceiling: our own limit, or PHP's upload limits if those are lower. */
    public static function maxBytes(): int
    {
        $toBytes = function (string $value): int {
            $value = trim($value);
            $number = (int) $value;

            return match (strtolower(substr($value, -1))) {
                'g' => $number * 1024 ** 3,
                'm' => $number * 1024 ** 2,
                'k' => $number * 1024,
                default => $number,
            };
        };

        return min(
            config('relay.attachments.max_kb') * 1024,
            $toBytes((string) ini_get('upload_max_filesize')) ?: PHP_INT_MAX,
            $toBytes((string) ini_get('post_max_size')) ?: PHP_INT_MAX,
        );
    }

    private function family(string $ext): string
    {
        return match (true) {
            in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true) => 'image',
            $ext === 'pdf' => 'pdf',
            in_array($ext, ['docx', 'xlsx', 'pptx'], true) => 'office',
            default => 'text',
        };
    }
}
