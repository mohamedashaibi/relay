<?php

namespace App\Domain\Projects;

final readonly class ProjectView
{
    /** @param array<string,int> $statusCounts */
    public function __construct(
        public int $id,
        public string $key,
        public string $name,
        public ?string $description,
        public string $color,
        public int $ownerId,
        public bool $archived,
        public ?ProjectRole $role,
        public array $statusCounts,
        public int $memberCount,
        public string $createdAt,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'owner_id' => $this->ownerId,
            'archived' => $this->archived,
            'role' => $this->role?->value,
            'status_counts' => $this->statusCounts,
            'member_count' => $this->memberCount,
            'created_at' => $this->createdAt,
        ];
    }
}
