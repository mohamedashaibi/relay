<?php

namespace App\Application\Users;

use App\Domain\Users\NotificationCategory;
use App\Domain\Users\UserRepository;

final class UpdateEmailPreferencesUseCase
{
    public function __construct(private readonly UserRepository $users) {}

    /** @param array<string,bool> $changes only known categories are kept */
    public function execute(int $userId, array $changes): void
    {
        $preferences = $this->users->emailPreferences($userId);

        foreach (NotificationCategory::cases() as $category) {
            if (array_key_exists($category->value, $changes)) {
                $preferences[$category->value] = (bool) $changes[$category->value];
            }
        }

        $this->users->setEmailPreferences($userId, $preferences);
    }
}
