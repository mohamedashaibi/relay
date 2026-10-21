<?php

namespace App\Application\Notifications;

/** Account emails. These always send — they're about security, not preferences. */
interface AccountMailer
{
    public function welcome(int $userId): void;

    public function passwordChanged(int $userId): void;

    /** Sends a fresh sign-up link; any earlier invite link for this person stops working. */
    public function invite(int $userId, string $inviterName, string $projectName): void;
}
