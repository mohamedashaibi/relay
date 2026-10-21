<?php

namespace App\Infrastructure\Mail;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/** Points at the SPA's reset page instead of Laravel's default route. */
class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $url = url('/reset-password/'.$this->token).'?email='.urlencode($notifiable->getEmailForPasswordReset());
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reset your Relay password')
            ->markdown('mail.reset-password', ['url' => $url, 'minutes' => $minutes, 'name' => $notifiable->name]);
    }
}
