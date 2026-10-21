<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Users\NotificationCategory;
use App\Domain\Users\UserRepository;
use App\Domain\Users\UserSummary;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

    public function createInvited(string $name, string $email): UserSummary
    {
        // Random unknown password: nobody can sign in until the invite link sets a real one.
        $user = new User(['name' => $name, 'email' => $email, 'password' => Str::random(64)]);
        $user->invite_pending = true;
        $user->save();

        return $this->map($user);
    }

    public function activate(int $id, string $name, string $plainPassword): void
    {
        $user = User::findOrFail($id);
        $user->name = $name;
        $user->password = $plainPassword;
        $user->invite_pending = false;
        $user->email_verified_at ??= now();
        $user->save();
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
        $user->invite_pending = false; // A reset link proves the email is theirs, same as an invite.
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
        return $user ? new UserSummary($user->id, $user->name, $user->email, $user->invite_pending) : null;
    }
}
