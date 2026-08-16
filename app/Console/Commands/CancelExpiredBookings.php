<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketBooking;
use Illuminate\Console\Command;

class CancelExpiredBookings extends Command
{
    /**
     * Batalkan otomatis booking pending yang melewati batas waktu pembayaran (24 jam)
     * atau tiketnya sudah kedaluwarsa. Kuota tiket dikembalikan.
     *
     * @var string
     */
    protected $signature = 'bookings:cancel-expired';

    protected $description = 'Batalkan booking pending yang melewati 24 jam atau tiketnya kedaluwarsa, lalu kembalikan kuota';

    public function handle(): int
    {
        $deadline = now()->subHours(24);
        $cancelled = 0;

        TicketBooking::where('status', 'pending')
            ->where(function ($query) use ($deadline) {
                $query->where('created_at', '<=', $deadline)
                    ->orWhereHas('ticket', function ($ticket) {
                        $ticket->whereNotNull('end_date')
                              ->where('end_date', '<', now());
                    });
            })
            ->chunkById(100, function ($bookings) use (&$cancelled) {
                foreach ($bookings as $booking) {
                    if ($booking->ticket_id) {
                        Ticket::where('id', $booking->ticket_id)->increment('quota');
                    }

                    $noteLine = now()->format('d M Y H:i') . ' — Dibatalkan otomatis oleh sistem (melewati batas waktu pembayaran / tiket kedaluwarsa)';
                    $booking->notes = trim(($booking->notes ? $booking->notes . "\n" : '') . $noteLine);
                    $booking->notes_updated_at = now();
                    $booking->cancelled_at = now();
                    $booking->status = 'cancelled';
                    $booking->save();

                    $cancelled++;
                    $this->info("Booking #{$booking->id} dibatalkan otomatis.");
                }
            });

        $this->info("Selesai. {$cancelled} booking pending dibatalkan.");

        return self::SUCCESS;
    }
}