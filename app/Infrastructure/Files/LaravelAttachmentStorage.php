<?php

namespace App\Infrastructure\Files;

use App\Application\Attachments\AttachmentStorage;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Private storage (never under /public). Files get random names; the original name lives in
 * the database and is only used in the download's Content-Disposition.
 */
final class LaravelAttachmentStorage implements AttachmentStorage
{
    public function __construct(private readonly string $disk) {}

    public function put(string $localPath, string $extension): array
    {
        $extension = preg_replace('/[^a-z0-9]/', '', strtolower($extension)) ?: 'bin';
        $directory = 'attachments/'.now()->format('Y/m');

        $path = Storage::disk($this->disk)->putFileAs($directory, new File($localPath), Str::uuid().'.'.$extension);
        if ($path === false) {
            throw new RuntimeException('Could not store the uploaded file.');
        }

        return ['disk' => $this->disk, 'path' => $path];
    }

    public function read(string $disk, string $path): string
    {
        return (string) Storage::disk($disk)->get($path);
    }

    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }
}
