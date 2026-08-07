<?php

namespace App\Http\Controllers;

use App\Models\TicketBooking;
use App\Models\HotelReservation;
use Illuminate\Http\Request;
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

        Log::info("Parsed DOKU Data -> Invoice: {$invoiceNumber} | Customer: {$customerId} | Status: {$status}");

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
                $booking->update([
                    'status' => 'paid',
                ]);

                Log::info("SUKSES: Booking ID {$booking->id} (Invoice: {$invoiceNumber}) berhasil diubah menjadi PAID.");

                return response()->json([
                    'status' => 'SUCCESS',
                    'message' => 'Booking status updated to paid successfully'
                ], 200);
            }

            if ($reservation) {
                $reservation->update([
                    'status' => 'paid',
                ]);

                Log::info("SUKSES: Reservasi Hotel ID {$reservation->id} (Invoice: {$invoiceNumber}) berhasil diubah menjadi PAID.");

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
}