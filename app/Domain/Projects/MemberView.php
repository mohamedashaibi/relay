<?php

namespace App\Domain\Projects;

final readonly class MemberView
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $email,
        public ProjectRole $role,
    ) {}

    public function toArray(): array
    {
        return ['id' => $this->userId, 'name' => $this->name, 'email' => $this->email, 'role' => $this->role->value];
    }
}
