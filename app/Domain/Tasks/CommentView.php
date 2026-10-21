<?php

namespace App\Domain\Tasks;

final readonly class CommentView
{
    /** @param array{id:int,name:string}|null $author */
    public function __construct(
        public int $id,
        public int $taskId,
        public ?array $author,
        public string $body,
        public string $channel,
        public ?string $client,
        public string $createdAt,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->taskId,
            'author' => $this->author,
            'body' => $this->body,
            'channel' => $this->channel,
            'client' => $this->client,
            'created_at' => $this->createdAt,
        ];
    }
}
