<?php

namespace App\Infrastructure\Mail;

use App\Application\Notifications\AccountMailer;
use App\Application\Notifications\EmailMessage;
use App\Application\Notifications\Notifier;
use App\Models\User;

/** Sends through Laravel notifications; every email is queued so requests never wait on SMTP. */
final class LaravelNotifier implements AccountMailer, Notifier
{
    public function send(int $userId, EmailMessage $message): void
    {
        User::find($userId)?->notify(new TaskUpdateNotification($message));
    }

    public function welcome(int $userId): void
    {
        User::find($userId)?->notify(AccountNotification::welcome());
    }

    public function passwordChanged(int $userId): void
    {
        User::find($userId)?->notify(AccountNotification::passwordChanged());
    }
}
