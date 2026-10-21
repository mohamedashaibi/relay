<?php

namespace App\Application\Users;

use App\Application\Notifications\AccountMailer;
use App\Domain\Users\UserRepository;
use App\Domain\Users\UserSummary;

/** Runs after the invite token was verified — that proved ownership of the email address. */
final class AcceptInvitationUseCase
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AccountMailer $mailer,
    ) {}

    public function execute(int $userId, string $name, string $password): UserSummary
    {
        $this->users->activate($userId, trim($name), $password);
        $this->mailer->welcome($userId);

        return $this->users->find($userId);
    }
}
