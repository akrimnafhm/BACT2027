<?php

namespace App\Services;

use App\Mail\TicketPaidMail;
use App\Models\NotificationTemplate;
use App\Models\TicketBooking;
use App\Models\WaGroupLink;
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

        // Generate QR PNG di server sendiri (tidak bergantung api.qrserver.com)
        $qrPath = app(QrService::class)->generatePng($booking->checkin_token, 'ticket-' . $booking->id);

        // 1. KIRIM WHATSAPP VIA FONNTE (media dikirim dari file lokal -> cocok untuk localhost/private IP)
        if ($waTemplate && $waTemplate->is_active && $phone) {
            $body = $this->renderPlaceholders($waTemplate->body, $booking);

            if ($waTemplate->include_qr && $qrPath) {
                $note = '(QR tiket Anda terlampir pada pesan ini)';
                $body = $this->attachQrToBody($body, '{qr}', $note);
            } else {
                $body = str_replace('{qr}', '', $body);
            }

            try {
                $result = app(FonnteService::class)->sendMessageWithFile($phone, $body, $qrPath, 'qr-' . $booking->id . '.png');
                Log::info("Notifikasi WA tiket dikirim ke {$phone} (Booking {$booking->id})", [
                    'response' => $result,
                ]);
            } catch (\Throwable $e) {
                Log::error('Gagal kirim notifikasi WA tiket: ' . $e->getMessage());
            }
        }

        // 2. KIRIM EMAIL (QR di-embed sebagai CID attachment agar tampil di Gmail/Outlook)
        if ($emailTemplate && $emailTemplate->is_active && $email) {
            $body = $this->renderPlaceholders($emailTemplate->body, $booking);
            $subject = $this->renderPlaceholders($emailTemplate->subject ?? 'Konfirmasi Pembelian Tiket - BACT 2027', $booking);

            try {
                Mail::to($email)->send(new TicketPaidMail($subject, $body, $qrPath));
                Log::info("Notifikasi email tiket dikirim ke {$email} (Booking {$booking->id})");
            } catch (\Throwable $e) {
                Log::error('Gagal kirim notifikasi email tiket: ' . $e->getMessage());
            }
        }

        // Bersihkan file QR sementara setelah dipakai
        if ($qrPath) {
            app(QrService::class)->delete($qrPath);
        }

        // Tandai sudah dinotifikasi agar tidak terkirim ulang
        $booking->update(['notified_at' => now()]);
    }

    /**
     * Ganti placeholder {nama}, {tiket}, {id_pesanan}, {invoice}, {harga}, {email}, {kode_tiket}, {link_grup}.
     */
    protected function renderPlaceholders(string $template, TicketBooking $booking): string
    {
        // Link grup hanya dibedakan oleh kategori tiket (bukan gelombang Early Bird/Regular)
        $groupLink = WaGroupLink::linkFor($booking->ticket_category);

        $replacers = [
            '{nama}'        => $booking->name_with_title ?: $booking->full_name,
            '{tiket}'       => ($booking->ticket_name ? $booking->ticket_name . ' - ' : '') . $booking->ticket_category,
            '{id_pesanan}'  => (string) $booking->id,
            '{invoice}'     => $booking->invoice_number ?: '-',
            '{harga}'       => number_format($booking->amount, 0, ',', '.'),
            '{email}'       => $booking->gmail_account ?: $booking->user->email ?? '',
            '{kode_tiket}'  => $booking->checkin_token ?: '-',
        ];

        $body = str_replace(array_keys($replacers), array_values($replacers), $template);

        // {link_grup} hanya diisi jika admin sudah menetapkan link grup untuk tiket ini
        if ($groupLink) {
            $body = str_replace('{link_grup}', $groupLink, $body);
        } else {
            // Jika kosong, hapus seluruh baris yang memuat {link_grup}
            $body = preg_replace('/^.*\{link_grup\}.*$/mi', '', $body);
        }

        return $body;
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
}
