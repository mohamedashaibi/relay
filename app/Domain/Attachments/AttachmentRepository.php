<?php

namespace App\Domain\Attachments;

use App\Domain\Shared\Actor;

interface AttachmentRepository
{
    public function create(
        int $projectId,
        ?int $taskId,
        Actor $actor,
        string $name,
        string $mime,
        int $size,
        string $disk,
        string $path,
        ?string $text,
    ): AttachmentView;

    public function find(int $id): ?AttachmentView;

    /** @return list<AttachmentView> newest first */
    public function forTask(int $taskId): array;

    /** Project-level files only (not the ones attached to its tasks). @return list<AttachmentView> */
    public function forProject(int $projectId): array;

    public function text(int $id): ?string;

    /** @return array{disk:string,path:string} */
    public function location(int $id): array;

    /** Every stored file belonging to the task. @return list<array{disk:string,path:string}> */
    public function locationsForTask(int $taskId): array;

    /** Every stored file in the project, including its tasks' files. @return list<array{disk:string,path:string}> */
    public function locationsForProject(int $projectId): array;

    public function delete(int $id): void;
}
