<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationOtpMail extends Mailable
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
            subject: 'Verifikasi Email - BACT 2027',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-verification-otp',
            with: [
                'userName' => $this->userName,
                'code' => $this->code,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }
}
