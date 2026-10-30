<?php

namespace App\Console\Commands;

use App\Models\HotelReservation;
use App\Services\DokuReconciliationService;
use Illuminate\Console\Command;

class CancelExpiredHotelReservations extends Command
{
    /**
     * Rekonsiliasi reservasi hotel pending yang melewati batas waktu pembayaran
     * dengan DOKU Check Status API. Reservasi hanya dibatalkan bila DOKU
     * mengonfirmasi kedaluwarsa; selain itu DITAHAN untuk pemeriksaan ulang.
     *
     * @var string
     */
    protected $signature = 'hotel-reservations:cancel-expired';

    protected $description = 'Rekonsiliasi reservasi hotel pending dengan DOKU, batalkan hanya yang benar-benar kedaluwarsa, lalu kembalikan kuota';

    public function handle(DokuReconciliationService $reconciler): int
    {
        $deadline = now()->subHours((int) config('services.doku.local_expiry_fallback_hours', 24));
        $cancelled = 0;
        $paid = 0;
        $held = 0;

        HotelReservation::where('status', 'pending')
            ->where(function ($query) use ($deadline) {
                $query->where('payment_expired_at', '<=', now())
                    ->orWhere(function ($query) use ($deadline) {
                        $query->whereNull('payment_expired_at')
                            ->where('created_at', '<=', $deadline);
                    });
            })
            ->chunkById(100, function ($reservations) use (&$cancelled, &$paid, &$held, $reconciler) {
                foreach ($reservations as $reservation) {
                    $action = $reconciler->reconcileHotel($reservation);

                    if ($action === DokuReconciliationService::ACTION_CANCELLED
                        || $action === DokuReconciliationService::ACTION_CANCELLED_LAPSED) {
                        $cancelled++;
                        $lapsed = $action === DokuReconciliationService::ACTION_CANCELLED_LAPSED ? ' (lapsed tanpa channel)' : '';
                        $this->info("Reservasi hotel #{$reservation->id} dibatalkan otomatis{$lapsed}.");
                    } elseif ($action === DokuReconciliationService::ACTION_PAID) {
                        $paid++;
                        $this->info("Reservasi hotel #{$reservation->id} ditandai PAID dari rekonsiliasi DOKU.");
                    } elseif ($action === DokuReconciliationService::ACTION_HELD) {
                        $held++;
                        $this->info("Reservasi hotel #{$reservation->id} DITAHAN (status DOKU belum final).");
                    }
                }
            });

        $this->info("Selesai. {$cancelled} dibatalkan, {$paid} ditandai lunas, {$held} ditahan (menunggu status final DOKU).");

        return self::SUCCESS;
    }
}
