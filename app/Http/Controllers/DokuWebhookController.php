<?php

namespace App\Http\Controllers;

use App\Models\HotelReservation;
use App\Models\TicketBooking;
use App\Services\BookingCancellationService;
use App\Services\DokuReconciliationService;
use App\Services\DokuSignatureService;
use App\Services\DokuStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class DokuWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        // Catat payload yang sudah disamarkan: tanpa data pelanggan (nama,
        // email, telepon, alamat) dan tanpa nomor VA. Tanpa secret/signature.
        Log::info('DOKU Notification Received:', self::redactNotificationPayload($payload));

        // 2. Verifikasi signature notifikasi (wajib). Tolak bila tidak valid.
        // Nilai header kredensial (Client-Id/Signature) tidak dicatat ke log.
        if (! app(DokuSignatureService::class)->verifyIncoming($request)) {
            Log::warning('DOKU notification REJECTED: signature tidak valid');

            return response()->json([
                'status' => 'REJECTED',
                'message' => 'Invalid signature',
            ], 401);
        }

        // 3. Ambil field kunci untuk investigasi
        $originalRequestId = $payload['transaction']['original_request_id'] ?? null;
        $transactionStatus = $payload['transaction']['status']
                  ?? $payload['transaction_status']
                  ?? $payload['status']
                  ?? $payload['service']['status']
                  ?? $payload['payment']['status']
                  ?? null;
        $orderStatus = $payload['order']['status'] ?? null;

        $invoiceNumber = $payload['order']['invoice_number']
                         ?? $payload['order']['invoice_no']
                         ?? $payload['invoice_number']
                         ?? $payload['payment']['invoice_number']
                         ?? $payload['merchant_ref']
                         ?? $payload['reference_number']
                         ?? $payload['reference_no']
                         ?? null;

        $customerId = $payload['customer']['id']
                      ?? $payload['customer_id']
                      ?? $payload['customer']['customer_id']
                      ?? null;

        // 4. Susun label metode pembayaran spesifik dari channel & method DOKU
        $paymentMethod = $this->composePaymentMethod($payload);

        // 5. Keputusan tunggal berbasis kombinasi order.status + transaction.status.
        // Status lokal hanya berubah lewat DokuStatus::outcome() di bawah.
        $outcome = DokuStatus::outcome($transactionStatus, $orderStatus);

        Log::info('DOKU notification evidence', [
            'invoice_number' => $invoiceNumber,
            'transaction_status' => $transactionStatus,
            'order_status' => $orderStatus,
            'channel' => $paymentMethod,
            'outcome' => $outcome,
        ]);

        // 6a. PAID -> tandai lunas (jangan pernah turunkan status).
        if ($outcome === DokuStatus::PAID) {
            return $this->handlePaid($invoiceNumber, $customerId, $paymentMethod, $originalRequestId, $payload);
        }

        // 6b. EXPIRED -> batalkan pending secara idempoten (bila ada).
        if ($outcome === DokuStatus::EXPIRED) {
            return $this->handleExpired($invoiceNumber, $paymentMethod, $originalRequestId);
        }

        // 6c. Selain itu (PENDING/FAILED/TIMEOUT/REDIRECT/ORDER_GENERATED/ORDER_RECOVERED)
        //     -> jangan ubah apa pun, tunggu status final.
        Log::info('DOKU non-final notification diabaikan', [
            'invoice_number' => $invoiceNumber,
            'transaction_status' => $transactionStatus,
            'order_status' => $orderStatus,
            'channel' => $paymentMethod,
            'reason' => self::ignoreReason($transactionStatus, $orderStatus),
        ]);

        return response()->json([
            'status' => 'IGNORED',
            'message' => 'Notification processed',
        ], 200);
    }

    /**
     * Alasan manusiawi mengapa sebuah notifikasi non-final diabaikan.
     */
    private static function ignoreReason(?string $transactionStatus, ?string $orderStatus): string
    {
        $order = strtoupper(trim((string) $orderStatus));

        if ($order === 'ORDER_RECOVERED') {
            return 'order dipulihkan (recover abandoned cart): pelanggan masih bisa membayar, jangan batalkan';
        }

        $transaction = strtoupper(trim((string) $transactionStatus));

        if (in_array($transaction, ['PENDING', 'REDIRECT', 'TIMEOUT'], true)) {
            return 'transaction menunggu verifikasi/pembayaran: bukan status final DOKU';
        }

        if ($transaction === 'FAILED') {
            return 'transaction FAILED pada Checkout bukan final: pelanggan masih bisa ganti channel';
        }

        if ($transaction === '' && $order === '') {
            return 'status tidak dikenali/kosong: tahan untuk pemeriksaan ulang via Check Status API';
        }

        return 'bukan kombinasi final PAID/EXPIRED: tahan untuk pemeriksaan ulang via Check Status API';
    }

    /**
     * Samarkan payload notifikasi untuk log: hapus PII pelanggan dan nomor
     * akun pembayaran. Struktur & status tetap utuh untuk investigasi.
     */
    private static function redactNotificationPayload(array $payload): array
    {
        $redacted = $payload;

        if (isset($redacted['customer']) && is_array($redacted['customer'])) {
            foreach ($redacted['customer'] as $key => $value) {
                if (is_scalar($value)) {
                    $redacted['customer'][$key] = '[REDACTED]';
                }
            }
        }

        $sensitiveKeys = ['email', 'phone', 'address', 'account_number', 'account_name', 'phone_number', 'ovo_id'];
        array_walk_recursive($redacted, function (&$value, $key) use ($sensitiveKeys) {
            if (is_string($key) && in_array(strtolower((string) $key), $sensitiveKeys, true) && is_scalar($value)) {
                $value = '[REDACTED]';
            }
        });

        return $redacted;
    }

    private function handlePaid(?string $invoiceNumber, ?string $customerId, ?string $paymentMethod, ?string $originalRequestId, array $payload)
    {
        if (blank($invoiceNumber)) {
            Log::warning('DOKU PAID notification tanpa invoice_number, diabaikan.');

            return response()->json([
                'status' => 'IGNORED',
                'message' => 'Notification processed',
            ], 200);
        }

        $reconciler = app(DokuReconciliationService::class);

        $booking = TicketBooking::where('invoice_number', $invoiceNumber)->first();
        if ($booking) {
            return $this->handleTicketPaid($booking, $paymentMethod, $originalRequestId, $payload, $reconciler);
        }

        $reservation = HotelReservation::where('invoice_number', $invoiceNumber)->first();
        if ($reservation) {
            return $this->handleHotelPaid($reservation, $paymentMethod, $originalRequestId, $reconciler);
        }

        Log::warning("GAGAL: Invoice {$invoiceNumber} / customer {$customerId} tidak ditemukan di tabel ticket_bookings / hotel_reservations. original_request_id: {$originalRequestId} | Channel: {$paymentMethod}");

        return response()->json([
            'status' => 'IGNORED',
            'message' => 'Notification processed',
        ], 200);
    }

    private function handleTicketPaid(TicketBooking $booking, ?string $paymentMethod, ?string $originalRequestId, array $payload, DokuReconciliationService $reconciler)
    {
        Log::info('WEBHOOK: Booking found', [
            'booking_id' => $booking->id,
            'invoice_number' => $booking->invoice_number,
            'status_before' => $booking->status,
            'original_request_id' => $originalRequestId,
            'channel' => $paymentMethod,
        ]);

        // Sudah lunas -> idempoten; status PAID tidak pernah diturunkan.
        if ($booking->status === 'paid') {
            Log::info("IGNORED: Booking ID {$booking->id} (Invoice: {$booking->invoice_number}) sudah PAID.");

            return response()->json([
                'status' => 'IGNORED',
                'message' => 'Booking already paid',
            ], 200);
        }

        // Status final non-pending (cancelled/expired/deleted) -> JANGAN diaktifkan ulang.
        // Pembayaran yang tetap sukses setelah pembatalan dicatat untuk penanganan manual.
        if ($booking->status !== 'pending') {
            $this->recordPaidAfterCancel($booking, $originalRequestId, $paymentMethod);

            return response()->json([
                'status' => 'HELD',
                'message' => 'Paid notification for non-pending booking held for manual review',
            ], 200);
        }

        $paidAt = $this->extractTransactionDate($payload) ?? now();

        try {
            $transitioned = $reconciler->markTicketPaid($booking, $paidAt);
        } catch (\Throwable $e) {
            Log::error('WEBHOOK: DB update paid FAILED', [
                'booking_id' => $booking->id,
                'invoice_number' => $booking->invoice_number,
                'original_request_id' => $originalRequestId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        if (! $transitioned) {
            // Kalah balapan / sudah diproses proses lain -> jangan picu notifikasi.
            Log::info("IGNORED: Booking ID {$booking->id} (Invoice: {$booking->invoice_number}) transisi PAID dilewati (sudah diproses / race).");

            return response()->json([
                'status' => 'IGNORED',
                'message' => 'Booking already processed',
            ], 200);
        }

        Log::info("SUKSES: Booking ID {$booking->id} (Invoice: {$booking->invoice_number}) berhasil diubah menjadi PAID.");

        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'Booking status updated to paid successfully',
        ], 200);
    }

    private function handleHotelPaid(HotelReservation $reservation, ?string $paymentMethod, ?string $originalRequestId, DokuReconciliationService $reconciler)
    {
        Log::info('WEBHOOK: Reservation found', [
            'reservation_id' => $reservation->id,
            'invoice_number' => $reservation->invoice_number,
            'status_before' => $reservation->status,
            'original_request_id' => $originalRequestId,
            'channel' => $paymentMethod,
        ]);

        if ($reservation->status === 'paid') {
            Log::info("IGNORED: Reservasi Hotel ID {$reservation->id} (Invoice: {$reservation->invoice_number}) sudah PAID.");

            return response()->json([
                'status' => 'IGNORED',
                'message' => 'Reservation already paid',
            ], 200);
        }

        // Status final non-pending (cancelled/expired) -> JANGAN diaktifkan ulang.
        if ($reservation->status !== 'pending') {
            // Catatan: tabel hotel_reservations tidak memiliki kolom catatan bebas
            // seperti `notes` pada tiket, sehingga tindak lanjut hanya dapat dilacak
            // lewat log terstruktur berikut. Status & kuota sengaja TIDAK diubah.
            Log::warning('DOKU PAID notification untuk reservasi hotel non-pending: PERLU TINDAK LANJUT MANUAL (status & kuota tidak diubah, tanpa refund otomatis)', [
                'needs_manual_review' => true,
                'reservation_id' => $reservation->id,
                'booking_code' => $reservation->booking_code,
                'invoice_number' => $reservation->invoice_number,
                'amount' => (float) $reservation->total_price,
                'status' => $reservation->status,
                'original_request_id' => $originalRequestId,
                'channel' => $paymentMethod,
            ]);

            return response()->json([
                'status' => 'HELD',
                'message' => 'Paid notification for non-pending reservation held for manual review',
            ], 200);
        }

        $attributes = [];
        if (filled($paymentMethod)) {
            $attributes['payment_method'] = $paymentMethod;
        }

        try {
            $transitioned = $reconciler->markHotelPaid($reservation, $attributes);
        } catch (\Throwable $e) {
            Log::error('WEBHOOK: DB update paid FAILED', [
                'reservation_id' => $reservation->id,
                'invoice_number' => $reservation->invoice_number,
                'original_request_id' => $originalRequestId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        if (! $transitioned) {
            Log::info("IGNORED: Reservasi Hotel ID {$reservation->id} (Invoice: {$reservation->invoice_number}) transisi PAID dilewati (sudah diproses / race).");

            return response()->json([
                'status' => 'IGNORED',
                'message' => 'Reservation already processed',
            ], 200);
        }

        Log::info("SUKSES: Reservasi Hotel ID {$reservation->id} (Invoice: {$reservation->invoice_number}) berhasil diubah menjadi PAID.");

        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'Hotel reservation status updated to paid successfully',
        ], 200);
    }

    /**
     * Catat pembayaran sukses yang datang SETELAH booking dibatalkan/kedaluwarsa.
     *
     * Kebijakan: status TIDAK diubah (tidak ada pengaktifan ulang, tidak ada refund
     * otomatis, dan kuota tidak diubah). Kondisi dicatat pada catatan booking + log
     * agar panitia dapat memverifikasi dan menindaklanjuti secara manual.
     */
    private function recordPaidAfterCancel(TicketBooking $booking, ?string $originalRequestId, ?string $paymentMethod): void
    {
        $noteLine = now()->format('d M Y H:i').' — [PERLU TINDAK LANJUT] DOKU melaporkan pembayaran SUKSES setelah booking berstatus '.$booking->status.'. Status tidak diubah otomatis (kebijakan tanpa refund otomatis); mohon verifikasi manual.';
        $booking->notes = trim(($booking->notes ? $booking->notes."\n" : '').$noteLine);
        $booking->notes_updated_at = now();
        $booking->save();

        Log::warning('DOKU PAID notification untuk booking non-pending: DITAHAN untuk penanganan manual (status & kuota tidak diubah)', [
            'booking_id' => $booking->id,
            'invoice_number' => $booking->invoice_number,
            'status' => $booking->status,
            'original_request_id' => $originalRequestId,
            'channel' => $paymentMethod,
        ]);
    }

    private function handleExpired(?string $invoiceNumber, ?string $paymentMethod, ?string $originalRequestId)
    {
        if (blank($invoiceNumber)) {
            Log::info('DOKU EXPIRED notification tanpa invoice_number, diabaikan.');

            return response()->json([
                'status' => 'IGNORED',
                'message' => 'Notification processed',
            ], 200);
        }

        $canceller = app(BookingCancellationService::class);
        $reason = 'Dibatalkan otomatis oleh sistem (order kedaluwarsa di DOKU)';
        $cancelled = false;

        $booking = TicketBooking::where('invoice_number', $invoiceNumber)->first();
        if ($booking) {
            $cancelled = $canceller->cancelTicket($booking, $reason) || $cancelled;
        }

        $reservation = HotelReservation::where('invoice_number', $invoiceNumber)->first();
        if ($reservation) {
            $cancelled = $canceller->cancelHotel($reservation, $reason) || $cancelled;
        }

        Log::info('DOKU EXPIRED notification diproses', [
            'invoice_number' => $invoiceNumber,
            'cancelled' => $cancelled,
            'original_request_id' => $originalRequestId,
            'channel' => $paymentMethod,
        ]);

        return response()->json([
            'status' => $cancelled ? 'CANCELLED' : 'IGNORED',
            'message' => 'Expired notification processed',
        ], 200);
    }

    /**
     * Ekstrak tanggal/waktu transaksi yang DILAKUKAN pelanggan dari payload DOKU
     * (berbeda dengan waktu webhook diterima). Mengembalikan Carbon dalam zona
     * waktu aplikasi, atau null jika payload tidak memuat informasi tanggal.
     */
    private function extractTransactionDate(array $payload): ?Carbon
    {
        $raw = $payload['transaction']['date']
            ?? $payload['transaction']['transaction_time']
            ?? $payload['transaction']['time']
            ?? $payload['transaction_time']
            ?? $payload['payment']['transaction_date']
            ?? $payload['payment']['date']
            ?? $payload['date']
            ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            if (is_numeric($raw)) {
                // Epoch dalam milidetik (13 digit) atau detik (10 digit)
                if (strlen((string) $raw) >= 13) {
                    $date = Carbon::createFromTimestampMs((int) $raw);
                } else {
                    $date = Carbon::createFromTimestamp((int) $raw);
                }
            } else {
                $date = Carbon::parse($raw);
            }

            // Simpan dalam zona waktu aplikasi (UTC) agar konsisten dengan created_at
            return $date->setTimezone(config('app.timezone'));
        } catch (\Throwable $e) {
            Log::warning("DOKU Tanggal transaksi gagal diparse: {$raw}");

            return null;
        }
    }

    /**
     * Susun label metode pembayaran spesifik dari data channel & method DOKU.
     * Contoh output: "Virtual Account BSI", "Wallet OVO", "Credit Card".
     */
    private function composePaymentMethod(array $payload): ?string
    {
        $channelName = null;
        if (isset($payload['payment']['channel_name'])) {
            $channelName = $payload['payment']['channel_name'];
        } elseif (isset($payload['payment']['channel'])) {
            $channelName = $payload['payment']['channel'];
        } elseif (isset($payload['channel']['id'])) {
            $channelName = $payload['channel']['id'];
        } elseif (isset($payload['channel']) && is_string($payload['channel'])) {
            $channelName = $payload['channel'];
        } elseif (isset($payload['acquirer']['id'])) {
            $channelName = $payload['acquirer']['id'];
        }

        $method = strtoupper(trim((string) (
            $payload['payment']['method']
            ?? $payload['payment_method']
            ?? $payload['method']
            ?? ''
        )));

        $methodLabels = [
            'VIRTUAL_ACCOUNT' => 'Virtual Account',
            'VA' => 'Virtual Account',
            'WALLET' => 'Wallet',
            'EWALLET' => 'Wallet',
            'CREDIT_CARD' => 'Credit Card',
            'OVER_THE_COUNTER' => 'Over the Counter',
            'CASH_ON_DELIVERY' => 'Cash on Delivery',
            'KLIKPAY' => 'KlikPay',
            'PAYLATER' => 'PayLater',
            'QUICK_PAY' => 'Quick Pay',
        ];

        $methodLabel = $methodLabels[$method] ?? ($method ?: null);

        if ($channelName && $methodLabel) {
            return $methodLabel.' '.$channelName;
        }

        return $channelName ?: $methodLabel;
    }
}
