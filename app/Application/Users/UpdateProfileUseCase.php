<?php

namespace App\Application\Users;

use App\Domain\Users\UserRepository;
use App\Domain\Users\UserSummary;

final class UpdateProfileUseCase
{
    public function __construct(private readonly UserRepository $users) {}

    public function execute(int $userId, string $name): UserSummary
    {
        $this->users->rename($userId, trim($name));

        return $this->users->find($userId);
    }
}
