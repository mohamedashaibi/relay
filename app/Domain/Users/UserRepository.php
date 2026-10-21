<?php

namespace App\Domain\Users;

interface UserRepository
{
    public function find(int $id): ?UserSummary;

    public function findByEmail(string $email): ?UserSummary;

    public function emailTaken(string $email): bool;

    /** Persists a new user; the implementation is responsible for hashing the password. */
    public function create(string $name, string $email, string $plainPassword): UserSummary;

    public function rename(int $id, string $name): void;

    public function passwordMatches(int $id, string $plainPassword): bool;

    public function setPassword(int $id, string $plainPassword): void;

    /** @return array<string,bool> every category, defaults applied */
    public function emailPreferences(int $id): array;

    /** @param array<string,bool> $preferences */
    public function setEmailPreferences(int $id, array $preferences): void;
}
