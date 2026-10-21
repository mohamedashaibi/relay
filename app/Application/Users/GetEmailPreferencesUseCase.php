<?php

namespace App\Application\Users;

use App\Domain\Users\NotificationCategory;
use App\Domain\Users\UserRepository;

final class GetEmailPreferencesUseCase
{
    public function __construct(private readonly UserRepository $users) {}

    /** @return list<array{key:string,label:string,description:string,enabled:bool}> */
    public function execute(int $userId): array
    {
        $preferences = $this->users->emailPreferences($userId);

        return array_map(fn (NotificationCategory $c) => [
            'key' => $c->value,
            'label' => $c->label(),
            'description' => $c->description(),
            'enabled' => $preferences[$c->value],
        ], NotificationCategory::cases());
    }
}
