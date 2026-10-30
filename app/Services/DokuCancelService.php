<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Klien DOKU Cancel Order API untuk DOKU Checkout.
 *
 * POST {base}/checkout/v3/cancellations
 * Dokumentasi: https://developers.doku.com/accept-payments/doku-checkout/order-and-notification-handling/cancel-order-api
 *
 * Catatan penting:
 *  - Hanya order yang masih PENDING (belum dibayar & belum expired) yang dapat dibatalkan.
 *  - Channel yang didukung terbatas (mis. Virtual Account, QRIS). Channel lain akan ditolak DOKU.
 *  - Fitur ini WAJIB diaktifkan di Dashboard DOKU:
 *    Settings > Checkout Appearance > System Settings > Order Settings >
 *    "Activate cancellation of unpaid orders".
 *  - Karena alasan di atas, pemanggilan dibuat best-effort dan TIDAK pernah menggagalkan
 *    pembatalan lokal; kegagalan dilaporkan sebagai hasil, bukan exception.
 *  - Order yang belum memilih channel/metode pembayaran TIDAK dikirim ke Cancel Order API.
 *    Pembatalan lokal tetap dilakukan, lalu DOKU dibiarkan memproses expiry secara alami.
 *    Pemilihan channel ditentukan dari Check Status (`service.id` / `channel.id`), bukan
 *    dari format invoice atau URL. Bila Check Status gagal, pemilihan channel dianggap
 *    belum pasti dan Cancel Order tidak dipanggil.
 */
class DokuCancelService
{
    public function __construct(
        private DokuSignatureService $signature,
        private DokuApiService $api,
    ) {}

    public function isEnabled(): bool
    {
        return filter_var(config('services.doku.cancel_order_enabled', false), FILTER_VALIDATE_BOOL);
    }

    /**
     * Wrapper best-effort untuk pemanggilan dari controller.
     *
     * Tidak pernah melempar exception: kegagalan tak terduga (mis. konfigurasi
     * signature tidak lengkap) dilaporkan sebagai hasil `exception` sehingga
     * pembatalan lokal yang sudah commit tetap berlaku dan response user tidak 500.
     *
     * @return array{attempted:bool, success:bool, reason:string, http_status:?int, confirmed:bool}
     */
    public function cancelInvoiceBestEffort(string $invoiceNumber, ?string $originalRequestId = null, string $note = 'cancel order'): array
    {
        try {
            return $this->cancelInvoice($invoiceNumber, $originalRequestId, $note);
        } catch (\Throwable $e) {
            Log::warning('DOKU Cancel Order unexpected exception', [
                'invoice_number' => $invoiceNumber,
                'error' => $e->getMessage(),
            ]);

            return $this->result(true, false, 'exception');
        }
    }

    /**
     * Batalkan order DOKU Checkout yang masih PENDING.
     *
     * @return array{attempted:bool, success:bool, reason:string, http_status:?int, confirmed:bool}
     */
    public function cancelInvoice(string $invoiceNumber, ?string $originalRequestId = null, string $note = 'cancel order'): array
    {
        $invoiceNumber = trim($invoiceNumber);

        if (! $this->isEnabled()) {
            return $this->result(false, false, 'disabled');
        }

        if ($invoiceNumber === '') {
            return $this->result(false, false, 'no_invoice');
        }

        // Check Status adalah sumber yang ada untuk mengetahui apakah pelanggan
        // sudah memilih service/channel. Jangan menebak dari invoice/URL.
        $status = $this->api->checkStatus($invoiceNumber, fresh: true);
        $outcome = $status['outcome'] ?? DokuStatus::HOLD;
        $httpStatus = $status['http_status'] ?? null;

        // Jangan pernah membatalkan order yang sudah dibayar atau sudah kedaluwarsa.
        if ($outcome === DokuStatus::PAID) {
            return $this->result(false, false, 'already_paid', $httpStatus);
        }

        // Order yang sudah CANCELLED adalah status final; jangan coba ubah menjadi EXPIRED.
        if (strtoupper(trim((string) ($status['order_status'] ?? ''))) === 'CANCELLED') {
            return $this->result(false, true, 'already_cancelled', $httpStatus, true);
        }

        if ($outcome === DokuStatus::EXPIRED) {
            return $this->result(false, false, 'already_expired', $httpStatus);
        }

        $channelSelected = $this->hasSelectedPaymentChannel($status);

        // API gagal/timeout/404: pemilihan channel tidak dapat dipastikan.
        if ($channelSelected === null) {
            return $this->result(false, false, 'channel_unknown', $httpStatus);
        }

        // Tanpa channel: jangan panggil Cancel Order API; biarkan DOKU expiry alami.
        if (! $channelSelected) {
            return $this->result(false, false, 'no_channel', $httpStatus);
        }

        if (blank($originalRequestId)) {
            $originalRequestId = $status['original_request_id'] ?? null;
        }

        if (blank($originalRequestId)) {
            return $this->result(false, false, 'missing_original_request_id', $httpStatus);
        }

        $requestTarget = '/checkout/v3/cancellations';
        $body = json_encode([
            'order' => ['invoice_number' => $invoiceNumber],
            'payment' => ['original_request_id' => (string) $originalRequestId],
            'note' => $note,
        ]);

        $headers = $this->signature->signedHeaders('POST', $requestTarget, $body);

        try {
            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->acceptJson()
                ->withBody($body, 'application/json')
                ->post($this->api->baseUrl().$requestTarget);
        } catch (\Throwable $e) {
            Log::warning('DOKU Cancel Order request exception', [
                'invoice_number' => $invoiceNumber,
                'error' => $e->getMessage(),
            ]);

            return $this->result(true, false, 'exception');
        }

        $ok = $response->successful();

        // Verifikasi status akhir (best-effort). Kegagalan verifikasi TIDAK mengubah
        // hasil HTTP: hanya dilaporkan agar tidak salah mengklaim order dibatalkan.
        $confirmed = false;
        if ($ok) {
            $verify = $this->api->checkStatus($invoiceNumber, fresh: true);
            $finalOrderStatus = $verify['order_status'] ?? null;
            $confirmed = $finalOrderStatus === 'CANCELLED'
                || ($verify['outcome'] ?? null) === DokuStatus::EXPIRED;
        }

        Log::info('DOKU Cancel Order response', [
            'invoice_number' => $invoiceNumber,
            'original_request_id' => (string) $originalRequestId,
            'http_status' => $response->status(),
            'success' => $ok,
            'confirmed' => $confirmed,
        ]);

        $reason = ! $ok ? 'http_'.$response->status() : ($confirmed ? 'cancelled' : 'cancelled_unconfirmed');

        return [
            'attempted' => true,
            'success' => $ok,
            'reason' => $reason,
            'http_status' => $response->status(),
            'confirmed' => $confirmed,
        ];
    }

    /**
     * Tentukan apakah order sudah memilih channel, hanya dari Check Status 2xx.
     *
     * @return bool|null null bila status tidak dapat dipastikan (API gagal/non-2xx).
     */
    private function hasSelectedPaymentChannel(array $status): ?bool
    {
        $httpStatus = $status['http_status'] ?? null;

        if (! is_int($httpStatus) || $httpStatus < 200 || $httpStatus >= 300) {
            return null;
        }

        return ! empty($status['service_id']) || ! empty($status['channel_id']);
    }

    /**
     * @return array{attempted:bool, success:bool, reason:string, http_status:?int, confirmed:bool}
     */
    private function result(bool $attempted, bool $success, string $reason, ?int $httpStatus = null, bool $confirmed = false): array
    {
        return [
            'attempted' => $attempted,
            'success' => $success,
            'reason' => $reason,
            'http_status' => $httpStatus,
            'confirmed' => $confirmed,
        ];
    }
}
