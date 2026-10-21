<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

// Sends immediately (not queued) so SMTP problems show up right here.
Artisan::command('relay:test-mail {email}', function (string $email) {
    $mailer = config('mail.default');
    $this->line("Sending a test email to {$email} via the \"{$mailer}\" mailer…");

    try {
        Mail::raw(
            'This is a test email from Relay ('.config('app.url').").\n\nIf you can read this, outgoing mail is configured correctly.",
            fn ($message) => $message->to($email)->subject('Relay test email'),
        );
    } catch (Throwable $e) {
        $this->error('Sending failed: '.$e->getMessage());

        return 1;
    }

    $this->info($mailer === 'log' ? 'Written to storage/logs/laravel.log (MAIL_MAILER=log).' : 'Sent. Check the inbox (and spam folder).');
})->purpose('Send a test email to check the mail settings');
