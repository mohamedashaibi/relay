<?php

namespace App\Domain\Attachments;

final readonly class AttachmentView
{
    /** @param array{id:int,name:string}|null $uploader */
    public function __construct(
        public int $id,
        public int $projectId,
        public ?int $taskId,
        public string $name,
        public string $mime,
        public int $size,
        public AttachmentKind $kind,
        public int $textLength,
        public ?array $uploader,
        public string $channel,
        public ?string $client,
        public string $createdAt,
    ) {}

    public function hasText(): bool
    {
        return $this->textLength > 0;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->projectId,
            'task_id' => $this->taskId,
            'name' => $this->name,
            'mime' => $this->mime,
            'size' => $this->size,
            'kind' => $this->kind->value,
            'has_text' => $this->hasText(),
            'text_length' => $this->textLength,
            'uploader' => $this->uploader,
            'channel' => $this->channel,
            'client' => $this->client,
            'created_at' => $this->createdAt,
        ];
    }
}
