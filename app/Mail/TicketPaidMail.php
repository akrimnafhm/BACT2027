<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectText;
    public $bodyHtml;
    public $qrPath;

    public function __construct(string $subjectText, string $bodyHtml, ?string $qrPath = null)
    {
        $this->subjectText = $subjectText;
        $this->bodyHtml = $bodyHtml;
        $this->qrPath = $qrPath;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectText,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-paid',
            with: [
                'bodyHtml' => $this->bodyHtml,
                'qrPath' => $this->qrPath,
            ],
        );
    }
}
