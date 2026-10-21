<?php

namespace App\Application\Users;

use App\Application\Notifications\AccountMailer;
use App\Domain\Users\UserRepository;

/** Runs after the reset token was verified — that proved ownership of the email address. */
final class CompletePasswordResetUseCase
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AccountMailer $mailer,
    ) {}

    public function execute(int $userId, string $newPassword): void
    {
        $this->users->setPassword($userId, $newPassword);
        $this->mailer->passwordChanged($userId);
    }
}
