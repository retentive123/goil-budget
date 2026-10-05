<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetByAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User   $user,
        public string $plainPassword,
        public string $loginUrl,
        public string $adminName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name') . ' — Your Password Has Been Reset',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-by-admin',
        );
    }
}
