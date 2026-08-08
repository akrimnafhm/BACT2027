<?php

namespace App\Services;

use App\Mail\TicketPaidMail;
use App\Models\NotificationTemplate;
use App\Models\TicketBooking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TicketNotificationService
{
    /**
     * Kirim notifikasi WA & Email saat tiket dinyatakan lunas.
     * Dedup memakai kolom notified_at agar tidak terkirim dua kali.
     */
    public function sendTicketPaid(TicketBooking $booking): void
    {
        if ($booking->notified_at) {
            return;
        }

        // Pastikan token QR tersedia (QR check-in)
        if (empty($booking->checkin_token)) {
            $booking->update(['checkin_token' => 'BACT-' . $booking->id . '-' . strtoupper(Str::random(10))]);
            $booking->refresh();
        }

        $waTemplate = NotificationTemplate::where('key', 'ticket_paid_wa')->first();
        $emailTemplate = NotificationTemplate::where('key', 'ticket_paid_email')->first();

        $phone = $booking->whatsapp_number ?: $booking->user->phone_number ?? null;
        $email = $booking->gmail_account ?: $booking->user->email ?? null;

        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($booking->checkin_token);

        // 1. KIRIM WHATSAPP VIA FONNTE
        if ($waTemplate && $waTemplate->is_active && $phone) {
            $body = $this->renderPlaceholders($waTemplate->body, $booking);
            $imageUrl = null;

            if ($waTemplate->include_qr) {
                $note = '(QR tiket Anda terlampir pada pesan ini)';
                $body = $this->attachQrToBody($body, '{qr}', $note);
                $imageUrl = $qrUrl;
            } else {
                $body = str_replace('{qr}', '', $body);
            }

            try {
                $result = app(FonnteService::class)->sendMessage($phone, $body, $imageUrl);
                Log::info("Notifikasi WA tiket dikirim ke {$phone} (Booking {$booking->id})", [
                    'response' => $result,
                ]);
            } catch (\Throwable $e) {
                Log::error('Gagal kirim notifikasi WA tiket: ' . $e->getMessage());
            }
        }

        // 2. KIRIM EMAIL (MAIL_MAILER=log -> tercatat di storage/logs/laravel.log)
        if ($emailTemplate && $emailTemplate->is_active && $email) {
            $body = $this->renderPlaceholders($emailTemplate->body, $booking);
            $subject = $this->renderPlaceholders($emailTemplate->subject ?? 'Konfirmasi Pembelian Tiket - BACT 2027', $booking);

            // Sertakan QR sebagai gambar base64 di dalam email
            $qrImageHtml = null;
            if ($emailTemplate->include_qr) {
                $qrImageHtml = $this->buildQrImageHtml($qrUrl);
            }

            if ($qrImageHtml) {
                $body = $this->attachQrToBody($body, '{qr}', $qrImageHtml);
            } else {
                $body = str_replace('{qr}', '', $body);
            }

            try {
                Mail::to($email)->send(new TicketPaidMail($subject, $body));
                Log::info("Notifikasi email tiket dikirim ke {$email} (Booking {$booking->id})");
            } catch (\Throwable $e) {
                Log::error('Gagal kirim notifikasi email tiket: ' . $e->getMessage());
            }
        }

        // Tandai sudah dinotifikasi agar tidak terkirim ulang
        $booking->update(['notified_at' => now()]);
    }

    /**
     * Ganti placeholder {nama}, {tiket}, {id_pesanan}, {invoice}, {harga}, {email}.
     */
    protected function renderPlaceholders(string $template, TicketBooking $booking): string
    {
        $replacers = [
            '{nama}'        => $booking->name_with_title ?: $booking->full_name,
            '{tiket}'       => ($booking->ticket_name ? $booking->ticket_name . ' - ' : '') . $booking->ticket_category,
            '{id_pesanan}'  => (string) $booking->id,
            '{invoice}'     => $booking->invoice_number ?: '-',
            '{harga}'       => number_format($booking->amount, 0, ',', '.'),
            '{email}'       => $booking->gmail_account ?: $booking->user->email ?? '',
        ];

        return str_replace(array_keys($replacers), array_values($replacers), $template);
    }

    /**
     * Letakkan konten (QR) pada posisi {qr}; jika placeholder tidak ada, tempel di akhir.
     */
    protected function attachQrToBody(string $body, string $placeholder, string $replacement): string
    {
        if (str_contains($body, $placeholder)) {
            return str_replace($placeholder, $replacement, $body);
        }

        return trim($body) . "\n\n" . $replacement;
    }

    /**
     * Ambil gambar QR dari qrserver dan embed sebagai data URI (base64) agar aman di email.
     */
    protected function buildQrImageHtml(string $qrUrl): ?string
    {
        try {
            $response = Http::timeout(15)->get($qrUrl);
            if ($response->successful()) {
                $base64 = base64_encode($response->body());
                return '<img src="data:image/png;base64,' . $base64 . '" alt="QR Tiket" style="max-width:220px;height:auto;">';
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mengambil gambar QR untuk email: ' . $e->getMessage());
        }

        // Fallback: pakai URL langsung dari qrserver
        return '<img src="' . e($qrUrl) . '" alt="QR Tiket" style="max-width:220px;height:auto;">';
    }
}
