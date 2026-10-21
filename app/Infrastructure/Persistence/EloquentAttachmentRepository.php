<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Attachments\AttachmentKind;
use App\Domain\Attachments\AttachmentRepository;
use App\Domain\Attachments\AttachmentView;
use App\Domain\Shared\Actor;
use App\Models\Attachment;

final class EloquentAttachmentRepository implements AttachmentRepository
{
    /** Never pull the (possibly huge) extracted text when listing. */
    private const LIST_COLUMNS = ['id', 'project_id', 'task_id', 'uploaded_by', 'name', 'mime', 'size', 'text_length', 'channel', 'client', 'created_at'];

    public function create(int $projectId, ?int $taskId, Actor $actor, string $name, string $mime, int $size, string $disk, string $path, ?string $text): AttachmentView
    {
        $attachment = Attachment::create([
            'project_id' => $projectId,
            'task_id' => $taskId,
            'uploaded_by' => $actor->userId,
            'name' => $name,
            'mime' => $mime,
            'size' => $size,
            'disk' => $disk,
            'path' => $path,
            'text_content' => $text,
            'text_length' => $text !== null ? mb_strlen($text) : 0,
            'channel' => $actor->channel->value,
            'client' => $actor->client,
        ]);

        return $this->find($attachment->id);
    }

    public function find(int $id): ?AttachmentView
    {
        $attachment = Attachment::with('uploader:id,name')->select(self::LIST_COLUMNS)->find($id);

        return $attachment ? $this->map($attachment) : null;
    }

    public function forTask(int $taskId): array
    {
        return Attachment::with('uploader:id,name')->select(self::LIST_COLUMNS)
            ->where('task_id', $taskId)->latest('id')->get()
            ->map(fn (Attachment $a) => $this->map($a))->all();
    }

    public function forProject(int $projectId): array
    {
        return Attachment::with('uploader:id,name')->select(self::LIST_COLUMNS)
            ->where('project_id', $projectId)->whereNull('task_id')->latest('id')->get()
            ->map(fn (Attachment $a) => $this->map($a))->all();
    }

    public function text(int $id): ?string
    {
        return Attachment::whereKey($id)->value('text_content');
    }

    public function location(int $id): array
    {
        $row = Attachment::whereKey($id)->firstOrFail(['disk', 'path']);

        return ['disk' => $row->disk, 'path' => $row->path];
    }

    public function locationsForTask(int $taskId): array
    {
        return Attachment::where('task_id', $taskId)->get(['disk', 'path'])
            ->map(fn ($r) => ['disk' => $r->disk, 'path' => $r->path])->all();
    }

    public function locationsForProject(int $projectId): array
    {
        return Attachment::where('project_id', $projectId)->get(['disk', 'path'])
            ->map(fn ($r) => ['disk' => $r->disk, 'path' => $r->path])->all();
    }

    public function delete(int $id): void
    {
        Attachment::whereKey($id)->delete();
    }

    private function map(Attachment $a): AttachmentView
    {
        return new AttachmentView(
            id: $a->id,
            projectId: $a->project_id,
            taskId: $a->task_id,
            name: $a->name,
            mime: $a->mime,
            size: $a->size,
            kind: AttachmentKind::detect($a->mime, $a->name),
            textLength: $a->text_length,
            uploader: $a->uploader ? ['id' => $a->uploader->id, 'name' => $a->uploader->name] : null,
            channel: $a->channel,
            client: $a->client,
            createdAt: $a->created_at->toIso8601String(),
        );
    }
}
