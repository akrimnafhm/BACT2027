<?php

namespace App\Console\Commands;

use App\Models\TicketBooking;
use App\Services\DokuReconciliationService;
use Illuminate\Console\Command;

class CancelExpiredBookings extends Command
{
    /**
     * Batalkan otomatis booking pending yang melewati batas waktu pembayaran
     * atau tiketnya sudah kedaluwarsa.
     *
     * Keputusan pembatalan TIDAK lagi murni lokal: setiap kandidat direkonsiliasi
     * dengan DOKU Check Status API. Bila status belum final / tidak dapat dipastikan,
     * booking DITAHAN untuk pemeriksaan ulang (revisi #3 & #4).
     *
     * @var string
     */
    protected $signature = 'bookings:cancel-expired';

    protected $description = 'Rekonsiliasi booking pending dengan DOKU, batalkan hanya yang benar-benar kedaluwarsa, lalu kembalikan kuota';

    public function handle(DokuReconciliationService $reconciler): int
    {
        $deadline = now()->subHours((int) config('services.doku.local_expiry_fallback_hours', 24));
        $cancelled = 0;
        $paid = 0;
        $held = 0;

        TicketBooking::where('status', 'pending')
            ->where(function ($query) use ($deadline) {
                $query->where('payment_expired_at', '<=', now())
                    ->orWhere(function ($query) use ($deadline) {
                        $query->whereNull('payment_expired_at')
                            ->where('created_at', '<=', $deadline);
                    })
                    ->orWhereHas('ticket', function ($ticket) {
                        $ticket->whereNotNull('end_date')
                            ->where('end_date', '<', now());
                    });
            })
            ->chunkById(100, function ($bookings) use (&$cancelled, &$paid, &$held, $reconciler) {
                foreach ($bookings as $booking) {
                    $action = $reconciler->reconcileTicket($booking);

                    if ($action === DokuReconciliationService::ACTION_CANCELLED
                        || $action === DokuReconciliationService::ACTION_CANCELLED_LAPSED) {
                        $cancelled++;
                        $lapsed = $action === DokuReconciliationService::ACTION_CANCELLED_LAPSED ? ' (lapsed tanpa channel)' : '';
                        $this->info("Booking #{$booking->id} dibatalkan otomatis{$lapsed}.");
                    } elseif ($action === DokuReconciliationService::ACTION_PAID) {
                        $paid++;
                        $this->info("Booking #{$booking->id} ditandai PAID dari rekonsiliasi DOKU.");
                    } elseif ($action === DokuReconciliationService::ACTION_HELD) {
                        $held++;
                        $this->info("Booking #{$booking->id} DITAHAN (status DOKU belum final).");
                    }
                }
            });

        $this->info("Selesai. {$cancelled} dibatalkan, {$paid} ditandai lunas, {$held} ditahan (menunggu status final DOKU).");

        return self::SUCCESS;
    }
}
