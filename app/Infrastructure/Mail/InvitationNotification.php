<?php

namespace App\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The only way into Relay: a one-time link to pick a name and password. */
class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        #[\SensitiveParameter] public string $token,
        public string $inviterName,
        public string $projectName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->inviterName} invited you to {$this->projectName} on Relay")
            ->markdown('mail.invitation', [
                'url' => url('/invite/'.$this->token).'?email='.urlencode($notifiable->email),
                'inviter' => $this->inviterName,
                'project' => $this->projectName,
                'days' => intdiv((int) config('auth.passwords.invites.expire'), 60 * 24),
            ]);
    }
}
