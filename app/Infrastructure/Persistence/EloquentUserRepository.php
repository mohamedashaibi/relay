<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Users\NotificationCategory;
use App\Domain\Users\UserRepository;
use App\Domain\Users\UserSummary;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class EloquentUserRepository implements UserRepository
{
    public function find(int $id): ?UserSummary
    {
        return $this->map(User::find($id));
    }

    public function findByEmail(string $email): ?UserSummary
    {
        return $this->map(User::where('email', mb_strtolower(trim($email)))->first());
    }

    public function emailTaken(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    public function create(string $name, string $email, string $plainPassword): UserSummary
    {
        // The model's "hashed" cast runs the configured hasher (bcrypt, 12 rounds).
        return $this->map(User::create(['name' => $name, 'email' => $email, 'password' => $plainPassword]));
    }

    public function rename(int $id, string $name): void
    {
        User::whereKey($id)->update(['name' => $name]);
    }

    public function passwordMatches(int $id, string $plainPassword): bool
    {
        return Hash::check($plainPassword, (string) User::whereKey($id)->value('password'));
    }

    public function setPassword(int $id, string $plainPassword): void
    {
        $user = User::findOrFail($id);
        $user->password = $plainPassword;
        $user->save();
    }

    public function emailPreferences(int $id): array
    {
        $raw = User::whereKey($id)->value('email_preferences');
        $stored = $raw ? (array) json_decode($raw, true) : [];

        $preferences = [];
        foreach (NotificationCategory::cases() as $category) {
            $preferences[$category->value] = (bool) ($stored[$category->value] ?? true);
        }

        return $preferences;
    }

    public function setEmailPreferences(int $id, array $preferences): void
    {
        User::whereKey($id)->update(['email_preferences' => json_encode($preferences)]);
    }

    private function map(?User $user): ?UserSummary
    {
        return $user ? new UserSummary($user->id, $user->name, $user->email) : null;
    }
}
