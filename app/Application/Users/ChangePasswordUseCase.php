<?php

namespace App\Application\Users;

use App\Application\Notifications\AccountMailer;
use App\Domain\Shared\RuleViolation;
use App\Domain\Users\UserRepository;

final class ChangePasswordUseCase
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AccountMailer $mailer,
    ) {}

    /** From account settings: the current password must be known. */
    public function execute(int $userId, string $currentPassword, string $newPassword): void
    {
        if (! $this->users->passwordMatches($userId, $currentPassword)) {
            throw new RuleViolation('Your current password is incorrect.');
        }

        $this->users->setPassword($userId, $newPassword);
        $this->mailer->passwordChanged($userId);
    }
}
