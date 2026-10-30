<?php

namespace App\Console\Commands;

use App\Models\HotelReservation;
use App\Models\TicketBooking;
use App\Services\DokuApiService;
use Illuminate\Console\Command;

class DokuInspect extends Command
{
    /**
     * Inspeksi read-only sebuah invoice DOKU untuk eksperimen A/B sandbox.
     *
     * Hanya MEMBACA: memanggil Check Status API resmi (GET
     * /orders/v1/status/{invoice}, selalu fresh) dan membaca status lokal.
     * Tidak mengubah database, tidak memanggil Cancel Order API, dan tidak
     * mencetak kredensial/signature/Client-Id.
     *
     * @var string
     */
    protected $signature = 'doku:inspect
        {invoice : Nomor invoice DOKU (mis. RC-BACT-138-1791979355)}';

    protected $description = 'Inspeksi read-only status DOKU sebuah invoice via Check Status API (tanpa mengubah data)';

    public function handle(DokuApiService $api): int
    {
        $invoice = trim((string) $this->argument('invoice'));

        if ($invoice === '') {
            $this->error('Nomor invoice tidak boleh kosong.');

            return self::FAILURE;
        }

        $result = $api->checkStatus($invoice, fresh: true);

        $ticket = TicketBooking::where('invoice_number', $invoice)->first();
        $hotel = HotelReservation::where('invoice_number', $invoice)->first();

        $this->line("Invoice ...........: {$invoice}");
        $this->line('transaction.status: '.($result['transaction_status'] ?? '-'));
        $this->line('order.status ......: '.($result['order_status'] ?? '-'));
        $this->line('service/channel ...: '.($result['service_id'] ?? '-').' / '.($result['channel_id'] ?? '-'));
        $this->line('channel expired ...: '.($result['expired_date'] ?? '-'));
        $this->line('outcome ...........: '.($result['outcome'] ?? '-').' (keputusan status lokal)');
        $this->line('http ..............: '.($result['http_status'] ?? '-'));
        $this->line('status lokal tiket : '.($ticket ? $ticket->status : '(tidak ditemukan di ticket_bookings)'));
        $this->line('status lokal hotel : '.($hotel ? $hotel->status : '(tidak ditemukan di hotel_reservations)'));
        $this->info('Read-only: tidak ada data yang diubah dan Cancel Order API tidak dipanggil.');

        return self::SUCCESS;
    }
}
