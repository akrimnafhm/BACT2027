<?php

namespace App\Http\Controllers;

use App\Models\TicketBooking;
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
                  ?? $payload['status'] 
                  ?? $payload['service']['status'] 
                  ?? null;

        // 3. Ambil invoice_number dari berbagai kemungkinan struktur DOKU
        $invoiceNumber = $payload['order']['invoice_number'] 
                         ?? $payload['order']['invoice_no'] 
                         ?? $payload['invoice_number'] 
                         ?? null;

        Log::info("Parsed DOKU Data -> Invoice: {$invoiceNumber} | Status: {$status}");

        // 4. Jika status SUCCESS / BERHASIL / PAID
        if (in_array(strtoupper((string) $status), ['SUCCESS', 'PAID', 'COMPLETED'])) {
            if ($invoiceNumber) {
                // Cari booking berdasarkan invoice_number
                $booking = TicketBooking::where('invoice_number', $invoiceNumber)->first();

                if ($booking) {
                    $booking->update([
                        'status' => 'paid',
                    ]);

                    Log::info("SUKSES: Booking ID {$booking->id} (Invoice: {$invoiceNumber}) berhasil diubah menjadi PAID.");

                    return response()->json([
                        'status' => 'SUCCESS',
                        'message' => 'Booking status updated to paid successfully'
                    ], 200);
                } else {
                    Log::warning("GAGAL: Invoice {$invoiceNumber} tidak ditemukan di tabel ticket_bookings.");
                }
            }
        }

        return response()->json([
            'status' => 'IGNORED',
            'message' => 'Notification processed'
        ], 200);
    }
}