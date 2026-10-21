<?php

namespace App\Application\Attachments;

interface AttachmentStorage
{
    /** Copies a local temp file into private storage. @return array{disk:string,path:string} */
    public function put(string $localPath, string $extension): array;

    public function read(string $disk, string $path): string;

    public function delete(string $disk, string $path): void;
}
