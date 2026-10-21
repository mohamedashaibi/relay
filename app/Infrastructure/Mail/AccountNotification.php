<?php

namespace App\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Welcome and security emails: a subject plus a markdown template. */
class AccountNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $subject,
        public string $template,
    ) {}

    public static function welcome(): self
    {
        return new self('Welcome to Relay', 'mail.welcome');
    }

    public static function passwordChanged(): self
    {
        return new self('Your Relay password was changed', 'mail.password-changed');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->subject)->markdown($this->template, [
            'name' => $notifiable->name,
            'email' => $notifiable->email,
            'appUrl' => url('/'),
            'connectionsUrl' => url('/connections'),
            'forgotUrl' => url('/forgot-password'),
            'changedAt' => now()->toDayDateTimeString().' UTC',
            'contact' => config('relay.contact_email'),
        ]);
    }
}
