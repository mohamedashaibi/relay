<?php

namespace App\Domain\Users;

interface UserRepository
{
    public function find(int $id): ?UserSummary;

    public function findByEmail(string $email): ?UserSummary;

    /** Persists an account that can't sign in until the invite is accepted. */
    public function createInvited(string $name, string $email): UserSummary;

    /** Accepting an invite: the person picks their own name and password. */
    public function activate(int $id, string $name, string $plainPassword): void;

    public function rename(int $id, string $name): void;

    public function passwordMatches(int $id, string $plainPassword): bool;

    public function setPassword(int $id, string $plainPassword): void;

    /** @return array<string,bool> every category, defaults applied */
    public function emailPreferences(int $id): array;

    /** @param array<string,bool> $preferences */
    public function setEmailPreferences(int $id, array $preferences): void;
}
