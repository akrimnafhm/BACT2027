<?php

namespace App\Services;

use App\Models\HotelReservation;
use App\Models\TicketBooking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Rekonsiliasi status pembayaran DOKU untuk booking tiket & reservasi hotel.
 *
 * Aturan (revisi #3 & #4):
 * - invoice_number kosong  -> tidak pernah sampai ke DOKU -> aman dibatalkan lokal.
 * - Check Status = SUCCESS -> tandai PAID (jangan batalkan).
 * - Check Status = EXPIRED -> batalkan (idempoten).
 * - selain itu / 404 / API gagal -> TAHAN, biarkan diperiksa ulang.
 */
class DokuReconciliationService
{
    public const ACTION_PAID = 'paid';

    public const ACTION_CANCELLED = 'cancelled';

    /**
     * Dibatalkan via keputusan eksplisit "checkout_lapsed_no_channel"
     * (di luar DokuStatus::outcome yang tetap HOLD). Dihitung sebagai
     * pembatalan oleh scheduler.
     */
    public const ACTION_CANCELLED_LAPSED = 'cancelled_lapsed';

    public const ACTION_HELD = 'held';

    public const ACTION_SKIPPED = 'skipped';

    public function __construct(
        private DokuApiService $api,
        private BookingCancellationService $canceller,
    ) {}

    public function reconcileTicket(TicketBooking $booking): string
    {
        if ($booking->status !== 'pending') {
            return self::ACTION_SKIPPED;
        }

        if (blank($booking->invoice_number)) {
            $cancelled = $this->canceller->cancelTicket(
                $booking,
                'Dibatalkan otomatis oleh sistem (melewati batas waktu pembayaran)'
            );

            return $cancelled ? self::ACTION_CANCELLED : self::ACTION_SKIPPED;
        }

        $result = $this->api->checkStatus($booking->invoice_number);
        $this->api->rememberEvidence($booking, $result);

        if ($result['outcome'] === DokuStatus::PAID) {
            $transitioned = $this->markTicketPaid($booking, $result['transaction_date']);

            Log::info('DOKU reconciliation: tiket hasil PAID', [
                'booking_id' => $booking->id,
                'invoice_number' => $booking->invoice_number,
                'transaction_status' => $result['transaction_status'],
                'order_status' => $result['order_status'],
                'action' => $transitioned ? self::ACTION_PAID : self::ACTION_SKIPPED,
                'reason' => 'transaction lunas menang atas status apapun (termasuk ORDER_EXPIRED)',
            ]);

            return $transitioned ? self::ACTION_PAID : self::ACTION_SKIPPED;
        }

        if ($result['outcome'] === DokuStatus::EXPIRED) {
            $cancelled = $this->canceller->cancelTicket(
                $booking,
                'Dibatalkan otomatis oleh sistem (order kedaluwarsa di DOKU)'
            );

            Log::info('DOKU reconciliation: tiket hasil EXPIRED', [
                'booking_id' => $booking->id,
                'invoice_number' => $booking->invoice_number,
                'transaction_status' => $result['transaction_status'],
                'order_status' => $result['order_status'],
                'action' => $cancelled ? self::ACTION_CANCELLED : self::ACTION_SKIPPED,
                'reason' => 'checkout kedaluwarsa di DOKU dan tidak dipulihkan (ORDER_RECOVERED ditahan)',
            ]);

            return $cancelled ? self::ACTION_CANCELLED : self::ACTION_SKIPPED;
        }

        Log::info('DOKU reconciliation: tiket DITAHAN (status belum final)', [
            'booking_id' => $booking->id,
            'invoice_number' => $booking->invoice_number,
            'transaction_status' => $result['transaction_status'],
            'order_status' => $result['order_status'],
            'http_status' => $result['http_status'],
            'action' => self::ACTION_HELD,
            'reason' => 'PENDING/FAILED/ORDER_GENERATED/ORDER_RECOVERED/404/gagal API bukan status final',
        ]);

        // Keputusan EKSPILISIT "checkout_lapsed_no_channel" (pengecualian yang
        // disetujui, di luar DokuStatus::outcome yang tetap HOLD): order tanpa
        // channel yang timer-nya habis + buffer dan Check Status tetap
        // PENDING/tanpa status dibatalkan lokal agar kartu pending tidak
        // menggantung selamanya (kasus 141).
        if ($this->lapsedNoChannel($booking, $result) !== null) {
            $cancelled = $this->canceller->cancelTicket(
                $booking,
                'Dibatalkan otomatis oleh sistem (checkout kedaluwarsa tanpa channel pembayaran DOKU)'
            );

            Log::info('DOKU reconciliation: tiket cancelled_lapsed (keputusan eksplisit)', [
                'booking_id' => $booking->id,
                'invoice_number' => $booking->invoice_number,
                'transaction_status' => $result['transaction_status'],
                'order_status' => $result['order_status'],
                'action' => $cancelled ? self::ACTION_CANCELLED_LAPSED : self::ACTION_SKIPPED,
                'reason' => 'PENDING/tanpa status + tanpa service/channel + tanpa bukti sukses + lewat due date + buffer; outcome DokuStatus tetap HOLD',
            ]);

            return $cancelled ? self::ACTION_CANCELLED_LAPSED : self::ACTION_SKIPPED;
        }

        // Jejak eksplisit untuk HOLD yang melewati masa tenggang (kasus 141):
        // user diberi pesan + tombol batalkan di halaman checkout, bukan
        // menunggu tanpa akhir. Tetap HELD — bukan auto-cancel.
        if (app(DokuExpiry::class)->heldBeyondGrace($booking)) {
            Log::info('DOKU held_beyond_grace', [
                'booking_id' => $booking->id,
                'invoice_number' => $booking->invoice_number,
                'transaction_status' => $result['transaction_status'],
                'order_status' => $result['order_status'],
            ]);
        }

        return self::ACTION_HELD;
    }

    public function reconcileHotel(HotelReservation $reservation): string
    {
        if ($reservation->status !== 'pending') {
            return self::ACTION_SKIPPED;
        }

        if (blank($reservation->invoice_number)) {
            $cancelled = $this->canceller->cancelHotel(
                $reservation,
                'Dibatalkan otomatis oleh sistem (melewati batas waktu pembayaran)'
            );

            return $cancelled ? self::ACTION_CANCELLED : self::ACTION_SKIPPED;
        }

        $result = $this->api->checkStatus($reservation->invoice_number);
        $this->api->rememberEvidence($reservation, $result);

        if ($result['outcome'] === DokuStatus::PAID) {
            $transitioned = $this->markHotelPaid($reservation);

            Log::info('DOKU reconciliation: reservasi hotel hasil PAID', [
                'reservation_id' => $reservation->id,
                'invoice_number' => $reservation->invoice_number,
                'transaction_status' => $result['transaction_status'],
                'order_status' => $result['order_status'],
                'action' => $transitioned ? self::ACTION_PAID : self::ACTION_SKIPPED,
                'reason' => 'transaction lunas menang atas status apapun (termasuk ORDER_EXPIRED)',
            ]);

            return $transitioned ? self::ACTION_PAID : self::ACTION_SKIPPED;
        }

        if ($result['outcome'] === DokuStatus::EXPIRED) {
            $cancelled = $this->canceller->cancelHotel(
                $reservation,
                'Dibatalkan otomatis oleh sistem (order kedaluwarsa di DOKU)'
            );

            Log::info('DOKU reconciliation: reservasi hotel hasil EXPIRED', [
                'reservation_id' => $reservation->id,
                'invoice_number' => $reservation->invoice_number,
                'transaction_status' => $result['transaction_status'],
                'order_status' => $result['order_status'],
                'action' => $cancelled ? self::ACTION_CANCELLED : self::ACTION_SKIPPED,
                'reason' => 'checkout kedaluwarsa di DOKU dan tidak dipulihkan (ORDER_RECOVERED ditahan)',
            ]);

            return $cancelled ? self::ACTION_CANCELLED : self::ACTION_SKIPPED;
        }

        Log::info('DOKU reconciliation: reservasi hotel DITAHAN (status belum final)', [
            'reservation_id' => $reservation->id,
            'invoice_number' => $reservation->invoice_number,
            'transaction_status' => $result['transaction_status'],
            'order_status' => $result['order_status'],
            'http_status' => $result['http_status'],
            'action' => self::ACTION_HELD,
            'reason' => 'PENDING/FAILED/ORDER_GENERATED/ORDER_RECOVERED/404/gagal API bukan status final',
        ]);

        if ($this->lapsedNoChannel($reservation, $result) !== null) {
            $cancelled = $this->canceller->cancelHotel(
                $reservation,
                'Dibatalkan otomatis oleh sistem (checkout kedaluwarsa tanpa channel pembayaran DOKU)'
            );

            Log::info('DOKU reconciliation: reservasi hotel cancelled_lapsed (keputusan eksplisit)', [
                'reservation_id' => $reservation->id,
                'invoice_number' => $reservation->invoice_number,
                'transaction_status' => $result['transaction_status'],
                'order_status' => $result['order_status'],
                'action' => $cancelled ? self::ACTION_CANCELLED_LAPSED : self::ACTION_SKIPPED,
                'reason' => 'PENDING/tanpa status + tanpa service/channel + tanpa bukti sukses + lewat due date + buffer; outcome DokuStatus tetap HOLD',
            ]);

            return $cancelled ? self::ACTION_CANCELLED_LAPSED : self::ACTION_SKIPPED;
        }

        if (app(DokuExpiry::class)->heldBeyondGrace($reservation)) {
            Log::info('DOKU held_beyond_grace', [
                'reservation_id' => $reservation->id,
                'invoice_number' => $reservation->invoice_number,
                'transaction_status' => $result['transaction_status'],
                'order_status' => $result['order_status'],
            ]);
        }

        return self::ACTION_HELD;
    }

    /**
     * Predikat keputusan EKSPILISIT "checkout_lapsed_no_channel".
     *
     * Mengembalikan string alasan bila SEMUA syarat terpenuhi, null bila tidak.
     * Syarat (kumulatif):
     *  1. outcome DokuStatus adalah HOLD (PAID/EXPIRED sudah ditangani di atas;
     *     PAID selalu menang dan tidak pernah sampai ke sini).
     *  2. transaction PENDING atau belum ada (bukan FAILED/TIMEOUT/REDIRECT/
     *     EXPIRED — status itu menyiratkan interaksi channel).
     *  3. service_id DAN channel_id kosong (tidak ada channel terpilih).
     *  4. Tidak ada bukti sukses yang tercatat (paid_at masih null).
     *  5. Sekarang sudah lewat payment_expired_at + buffer (butuh expiry
     *     resmi DOKU; tanpa payment_expired_at tidak boleh cancel).
     *
     * Race webhook vs scheduler tetap aman: pembatalan memakai conditional
     * update pending -> cancelled; SUCCESS yang datang sesudahnya tetap lewat
     * recordPaidAfterCancel (tiket) / needs_manual_review (hotel).
     */
    private function lapsedNoChannel(TicketBooking|HotelReservation $booking, array $result): ?string
    {
        if (($result['outcome'] ?? null) !== DokuStatus::HOLD) {
            return null;
        }

        // Hanya respons Check Status yang BERHASIL (2xx) yang boleh dipakai untuk
        // menyimpulkan "tanpa channel". Saat API gagal/timeout/404 kita tidak tahu
        // apakah pelanggan sudah memilih channel atau bahkan sudah membayar, jadi
        // kegagalan API TIDAK boleh diperlakukan sebagai konfirmasi kedaluwarsa.
        $httpStatus = $result['http_status'] ?? null;

        if (! is_int($httpStatus) || $httpStatus < 200 || $httpStatus >= 300) {
            return null;
        }

        $transaction = strtoupper(trim((string) ($result['transaction_status'] ?? '')));

        if ($transaction !== '' && $transaction !== 'PENDING') {
            return null;
        }

        if (! empty($result['service_id']) || ! empty($result['channel_id'])) {
            return null;
        }

        if ($booking->paid_at !== null) {
            return null;
        }

        $cutoff = app(DokuExpiry::class)->lapseCutoff($booking);

        if ($cutoff === null || ! $cutoff->lt(now())) {
            return null;
        }

        return 'lapsed_no_channel';
    }

    /**
     * Transisi atomik `pending` -> `paid` untuk tiket.
     *
     * Memakai conditional UPDATE (hanya bila status masih `pending`) sehingga aman
     * terhadap request bersamaan: hanya satu proses yang berhasil transisi dan
     * berhak memicu notifikasi. Status selain `pending` (cancelled/expired/deleted/
     * paid) TIDAK PERNAH dinaikkan menjadi `paid` lewat jalur ini.
     */
    public function markTicketPaid(TicketBooking $booking, ?Carbon $paidAt = null): bool
    {
        $affected = TicketBooking::whereKey($booking->getKey())
            ->where('status', 'pending')
            ->update([
                'status' => 'paid',
                'paid_at' => $paidAt ?? now(),
            ]);

        if ($affected === 0) {
            Log::info('DOKU: transisi PAID tiket dilewati (status bukan pending / sudah diproses)', [
                'booking_id' => $booking->getKey(),
                'invoice_number' => $booking->invoice_number,
            ]);

            return false;
        }

        $booking->refresh();

        app(TicketNotificationService::class)->sendTicketPaid($booking);

        Log::info('DOKU: tiket ditandai PAID', [
            'booking_id' => $booking->id,
            'invoice_number' => $booking->invoice_number,
        ]);

        return true;
    }

    /**
     * Transisi atomik `pending` -> `paid` untuk reservasi hotel.
     *
     * @param  array<string, mixed>  $attributes  kolom tambahan (mis. payment_method)
     */
    public function markHotelPaid(HotelReservation $reservation, array $attributes = []): bool
    {
        $affected = HotelReservation::whereKey($reservation->getKey())
            ->where('status', 'pending')
            ->update(array_merge(['status' => 'paid'], $attributes));

        if ($affected === 0) {
            Log::info('DOKU: transisi PAID reservasi hotel dilewati (status bukan pending / sudah diproses)', [
                'reservation_id' => $reservation->getKey(),
                'invoice_number' => $reservation->invoice_number,
            ]);

            return false;
        }

        $reservation->refresh();

        app(HotelNotificationService::class)->sendHotelPaid($reservation);

        Log::info('DOKU: reservasi hotel ditandai PAID', [
            'reservation_id' => $reservation->id,
            'invoice_number' => $reservation->invoice_number,
        ]);

        return true;
    }
}
