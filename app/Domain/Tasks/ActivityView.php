<?php

namespace App\Domain\Tasks;

final readonly class ActivityView
{
    /**
     * @param  array{id:int,name:string}|null  $actor
     * @param  array{id:int,key:string,title:string}|null  $task
     */
    public function __construct(
        public int $id,
        public ActivityType $type,
        public ?array $actor,
        public array $meta,
        public string $channel,
        public ?string $client,
        public string $createdAt,
        public ?array $task = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'actor' => $this->actor,
            'meta' => $this->meta,
            'channel' => $this->channel,
            'client' => $this->client,
            'created_at' => $this->createdAt,
            'task' => $this->task,
        ];
    }
}
