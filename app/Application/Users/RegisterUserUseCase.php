<?php

namespace App\Application\Users;

use App\Application\Notifications\AccountMailer;
use App\Domain\Shared\RuleViolation;
use App\Domain\Users\UserRepository;
use App\Domain\Users\UserSummary;

final class RegisterUserUseCase
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AccountMailer $mailer,
    ) {}

    public function execute(string $name, string $email, string $password): UserSummary
    {
        $email = mb_strtolower(trim($email));

        if ($this->users->emailTaken($email)) {
            throw new RuleViolation('An account with that email already exists.');
        }

        $user = $this->users->create(trim($name), $email, $password);
        $this->mailer->welcome($user->id);

        return $user;
    }
}
