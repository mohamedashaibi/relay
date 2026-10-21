<?php

namespace App\Infrastructure\Mail;

use App\Application\Notifications\EmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public EmailMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->message->subject)
            ->markdown('mail.task-update', [
                'name' => $notifiable->name,
                'headline' => $this->message->headline,
                'lines' => $this->message->lines,
                'quote' => $this->message->quote,
                'actionText' => $this->message->actionText,
                'actionUrl' => url($this->message->actionPath),
                'settingsUrl' => url('/account'),
                'category' => $this->message->category->label(),
            ]);
    }
}
