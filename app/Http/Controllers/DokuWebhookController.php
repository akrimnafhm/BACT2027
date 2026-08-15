<?php

namespace App\Http\Controllers;

use App\Models\TicketBooking;
use App\Models\HotelReservation;
use App\Services\HotelNotificationService;
use App\Services\TicketNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class DokuWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        
        // 1. Catat seluruh data yang masuk ke storage/logs/laravel.log
        Log::info('DOKU Notification Received:', $payload);

        // 2. Ambil status transaksi dari berbagai kemungkinan struktur DOKU
        $status = $payload['transaction']['status'] 
                  ?? $payload['transaction_status']
                  ?? $payload['status'] 
                  ?? $payload['service']['status'] 
                  ?? $payload['payment']['status']
                  ?? null;

        // 3. Ambil invoice_number dari berbagai kemungkinan struktur DOKU
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

        // 3b. Susun label metode pembayaran spesifik dari channel & method DOKU
        $paymentMethod = $this->composePaymentMethod($payload);

        Log::info("Parsed DOKU Data -> Invoice: {$invoiceNumber} | Customer: {$customerId} | Status: {$status} | Channel: {$paymentMethod}");

        // 4. Jika status SUCCESS / BERHASIL / PAID / SETTLEMENT
        $normalizedStatus = strtoupper(trim((string) $status));
        $paidStatuses = ['SUCCESS', 'PAID', 'COMPLETED', 'SETTLED', 'SETTLEMENT', 'CAPTURED'];

        if (in_array($normalizedStatus, $paidStatuses, true)) {
            $booking = null;
            $reservation = null;

            if ($invoiceNumber) {
                // Cari booking berdasarkan invoice_number
                $booking = TicketBooking::where('invoice_number', $invoiceNumber)->first();
                $reservation = HotelReservation::where('invoice_number', $invoiceNumber)->first();
            }

            if (!$booking && $customerId !== null) {
                $booking = TicketBooking::where('user_id', $customerId)
                    ->where('status', 'pending')
                    ->latest()
                    ->first();
            }

            if ($booking) {
                $paidAt = $this->extractTransactionDate($payload) ?? now();

                $booking->update([
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                ]);

                Log::info("SUKSES: Booking ID {$booking->id} (Invoice: {$invoiceNumber}) berhasil diubah menjadi PAID.");

                // Kirim notifikasi WA & Email ke peserta
                app(TicketNotificationService::class)->sendTicketPaid($booking);

                return response()->json([
                    'status' => 'SUCCESS',
                    'message' => 'Booking status updated to paid successfully'
                ], 200);
            }

            if ($reservation) {
                $reservation->update([
                    'status' => 'paid',
                    'payment_method' => $paymentMethod ?: $reservation->payment_method,
                ]);

                Log::info("SUKSES: Reservasi Hotel ID {$reservation->id} (Invoice: {$invoiceNumber}) berhasil diubah menjadi PAID.");

                // Kirim notifikasi WA & Email ke pemesan hotel
                app(HotelNotificationService::class)->sendHotelPaid($reservation);

                return response()->json([
                    'status' => 'SUCCESS',
                    'message' => 'Hotel reservation status updated to paid successfully'
                ], 200);
            }

            Log::warning("GAGAL: Invoice {$invoiceNumber} / customer {$customerId} tidak ditemukan di tabel ticket_bookings / hotel_reservations.");
        }

        return response()->json([
            'status' => 'IGNORED',
            'message' => 'Notification processed'
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
            return $methodLabel . ' ' . $channelName;
        }

        return $channelName ?: $methodLabel;
    }
}