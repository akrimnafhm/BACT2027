<?php

namespace App\Services;

use App\Mail\HotelPaidMail;
use App\Models\HotelReservation;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HotelNotificationService
{
    /**
     * Kirim notifikasi WA & Email saat reservasi hotel dinyatakan lunas.
     * Dedup memakai kolom notified_at agar tidak terkirim dua kali.
     */
    public function sendHotelPaid(HotelReservation $reservation): void
    {
        if ($reservation->notified_at) {
            return;
        }

        $waTemplate = NotificationTemplate::where('key', 'hotel_paid_wa')->first();
        $emailTemplate = NotificationTemplate::where('key', 'hotel_paid_email')->first();

        $phone = $reservation->guest_phone ?: $reservation->user->phone_number ?? null;
        $email = $reservation->guest_email ?: $reservation->user->email ?? null;

        // 1. KIRIM WHATSAPP VIA FONNTE
        if ($waTemplate && $waTemplate->is_active && $phone) {
            $body = $this->renderPlaceholders($waTemplate->body, $reservation);

            try {
                $result = app(FonnteService::class)->sendMessage($phone, $body);
                Log::info("Notifikasi WA hotel dikirim ke {$phone} (Reservasi {$reservation->id})", [
                    'response' => $result,
                ]);
            } catch (\Throwable $e) {
                Log::error('Gagal kirim notifikasi WA hotel: ' . $e->getMessage());
            }
        }

        // 2. KIRIM EMAIL
        if ($emailTemplate && $emailTemplate->is_active && $email) {
            $body = $this->renderPlaceholders($emailTemplate->body, $reservation);
            $subject = $this->renderPlaceholders($emailTemplate->subject ?? 'Konfirmasi Reservasi Hotel - BACT 2027', $reservation);

            try {
                Mail::to($email)->send(new HotelPaidMail($subject, $body));
                Log::info("Notifikasi email hotel dikirim ke {$email} (Reservasi {$reservation->id})");
            } catch (\Throwable $e) {
                Log::error('Gagal kirim notifikasi email hotel: ' . $e->getMessage());
            }
        }

        // Tandai sudah dinotifikasi agar tidak terkirim ulang
        $reservation->update(['notified_at' => now()]);
    }

    /**
     * Ganti placeholder {nama}, {hotel}, {kode_booking}, {id_pesanan}, {invoice}, {check_in}, {check_out}, {malam}, {harga}.
     */
    protected function renderPlaceholders(string $template, HotelReservation $reservation): string
    {
        $room = $reservation->hotelRoom;

        $replacers = [
            '{nama}'        => $reservation->guest_name ?: $reservation->user->name ?? '',
            '{hotel}'       => $room ? $room->room_type : 'Hotel',
            '{kode_booking}' => $reservation->booking_code ?: '-',
            '{id_pesanan}'  => (string) $reservation->id,
            '{invoice}'     => $reservation->invoice_number ?: '-',
            '{check_in}'    => $reservation->check_in ? $reservation->check_in->format('d M Y') : '-',
            '{check_out}'   => $reservation->check_out ? $reservation->check_out->format('d M Y') : '-',
            '{malam}'       => (string) ($reservation->total_nights ?: 0),
            '{harga}'       => number_format((float) $reservation->total_price, 0, ',', '.'),
        ];

        return str_replace(array_keys($replacers), array_values($replacers), $template);
    }
}
