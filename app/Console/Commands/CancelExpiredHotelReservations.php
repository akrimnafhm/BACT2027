<?php

namespace App\Console\Commands;

use App\Models\HotelReservation;
use Illuminate\Console\Command;

class CancelExpiredHotelReservations extends Command
{
    /**
     * Batalkan otomatis reservasi hotel pending yang melewati batas waktu
     * pembayaran (payment_expired_at dari DOKU), atau fallback 24 jam sejak dibuat.
     * Kuota kamar dikembalikan sehingga user bisa reservasi ulang.
     *
     * @var string
     */
    protected $signature = 'hotel-reservations:cancel-expired';

    protected $description = 'Batalkan reservasi hotel pending yang melewati batas waktu pembayaran, lalu kembalikan kuota';

    public function handle(): int
    {
        $deadline = now()->subHours(24);
        $cancelled = 0;

        HotelReservation::where('status', 'pending')
            ->where(function ($query) use ($deadline) {
                $query->where('payment_expired_at', '<=', now())
                    ->orWhere(function ($query) use ($deadline) {
                        $query->whereNull('payment_expired_at')
                              ->where('created_at', '<=', $deadline);
                    });
            })
            ->chunkById(100, function ($reservations) use (&$cancelled) {
                foreach ($reservations as $reservation) {
                    if ($reservation->hotelRoom) {
                        $reservation->hotelRoom->increment('quota');
                    }

                    $reservation->cancelled_at = now();
                    $reservation->status = 'cancelled';
                    $reservation->save();

                    $cancelled++;
                    $this->info("Reservasi hotel #{$reservation->id} dibatalkan otomatis.");
                }
            });

        $this->info("Selesai. {$cancelled} reservasi hotel dibatalkan.");

        return self::SUCCESS;
    }
}