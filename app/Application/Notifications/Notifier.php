<?php

namespace App\Application\Notifications;

interface Notifier
{
    /** Queues the email; must never block or fail the request that triggered it. */
    public function send(int $userId, EmailMessage $message): void;
}
