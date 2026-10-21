<?php

namespace App\Domain\Users;

final readonly class UserSummary
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public bool $pending = false,
    ) {}

    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email];
    }
}
