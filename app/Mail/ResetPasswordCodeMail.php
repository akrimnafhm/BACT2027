<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $code;
    public int $expiresInMinutes;

    public function __construct(string $userName, string $code, int $expiresInMinutes = 10)
    {
        $this->userName = $userName;
        $this->code = $code;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode Reset Password - BACT 2027',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password-code',
            with: [
                'userName' => $this->userName,
                'code' => $this->code,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }
}
