<?php

namespace App\Application\Projects\Dto;

final readonly class CreateProjectInput
{
    public function __construct(
        public string $name,
        public ?string $key = null,
        public ?string $description = null,
        public ?string $color = null,
    ) {}

    /** @param array<string,mixed> $data already-validated payload */
    public static function fromArray(array $data): self
    {
        return new self(
            name: trim($data['name']),
            key: isset($data['key']) ? strtoupper(trim($data['key'])) : null,
            description: $data['description'] ?? null,
            color: $data['color'] ?? null,
        );
    }
}
