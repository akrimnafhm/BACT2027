<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectText;
    public $bodyHtml;
    public $qrPath;
    public $invoicePdf;
    public $invoiceFilename;

    public function __construct(string $subjectText, string $bodyHtml, ?string $qrPath = null, ?string $invoicePdf = null, ?string $invoiceFilename = null)
    {
        $this->subjectText = $subjectText;
        $this->bodyHtml = $bodyHtml;
        $this->qrPath = $qrPath;
        $this->invoicePdf = $invoicePdf;
        $this->invoiceFilename = $invoiceFilename;
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

    public function attachments(): array
    {
        if (!$this->invoicePdf) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->invoicePdf, $this->invoiceFilename ?? 'invoice.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
