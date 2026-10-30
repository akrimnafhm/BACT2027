<?php

namespace App\Services;

use App\Models\HotelReservation;
use App\Models\HotelRoom;
use App\Models\Ticket;
use App\Models\TicketBooking;
use Illuminate\Support\Facades\DB;

/**
 * Pembatalan booking tiket & reservasi hotel yang IDEMPOTEN dan aman dari race.
 *
 * Setiap pembatalan dijalankan dalam transaksi + row lock (lockForUpdate),
 * dan kuota HANYA dikembalikan bila transisi pending -> cancelled benar-benar
 * baru saja terjadi (revisi #5: transisi & pengembalian kuota tidak boleh berulang).
 */
class BookingCancellationService
{
    /**
     * @return bool true bila booking baru saja dibatalkan pada pemanggilan ini.
     */
    public function cancelTicket(TicketBooking $booking, string $reason): bool
    {
        return DB::transaction(function () use ($booking, $reason) {
            $fresh = TicketBooking::whereKey($booking->getKey())->lockForUpdate()->first();

            if (! $fresh || $fresh->status !== 'pending') {
                return false;
            }

            $noteLine = now()->format('d M Y H:i').' — '.$reason;
            $fresh->notes = trim(($fresh->notes ? $fresh->notes."\n" : '').$noteLine);
            $fresh->notes_updated_at = now();
            $fresh->cancelled_at = now();
            $fresh->status = 'cancelled';
            $fresh->save();

            if ($fresh->ticket_id) {
                Ticket::where('id', $fresh->ticket_id)->increment('quota');
            }

            return true;
        });
    }

    /**
     * @return bool true bila reservasi baru saja dibatalkan pada pemanggilan ini.
     */
    public function cancelHotel(HotelReservation $reservation, string $reason): bool
    {
        return DB::transaction(function () use ($reservation) {
            $fresh = HotelReservation::whereKey($reservation->getKey())->lockForUpdate()->first();

            // Catatan: tabel hotel_reservations tidak memiliki kolom notes,
            // jadi alasan pembatalan hanya dicatat di log pada pemanggil.

            if (! $fresh || $fresh->status !== 'pending') {
                return false;
            }

            $fresh->cancelled_at = now();
            $fresh->status = 'cancelled';
            $fresh->save();

            if ($fresh->hotel_room_id) {
                $quantity = max(1, (int) $fresh->quantity);
                HotelRoom::where('id', $fresh->hotel_room_id)->increment('quota', $quantity);
            }

            return true;
        });
    }
}
