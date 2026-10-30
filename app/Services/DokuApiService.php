<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Klien DOKU Non-SNAP untuk Check Status API.
 *
 * GET {base}/orders/v1/status/{invoice_number}
 * Didokumentasikan untuk dipanggil minimal 60 detik setelah pembayaran.
 */
class DokuApiService
{
    public function __construct(private DokuSignatureService $signature) {}

    public function baseUrl(): string
    {
        $isProduction = filter_var(config('services.doku.is_production', false), FILTER_VALIDATE_BOOL);

        return $isProduction ? 'https://api.doku.com' : 'https://api-sandbox.doku.com';
    }

    /**
     * Panggil Check Status API dan terjemahkan hasilnya menjadi outcome.
     *
     * Murni read-only terhadap data bisnis: tidak pernah menulis ke database.
     * Setiap pengecekan dicatat sebagai satu baris log terstruktur
     * (`DOKU check-status`) untuk bukti uji A/B sandbox. Tidak ada kredensial,
     * signature, Client-Id, atau nomor VA yang dicatat.
     *
     * @return array{outcome:string, transaction_status:?string, order_status:?string, transaction_date:?Carbon, original_request_id:?string, http_status:?int, service_id:?string, channel_id:?string, acquirer_id:?string, expired_date:?string, cached:bool}
     */
    public function checkStatus(string $invoiceNumber, bool $fresh = false): array
    {
        $invoiceNumber = trim($invoiceNumber);

        if ($invoiceNumber === '') {
            return $this->unknown(null, false);
        }

        $cacheSeconds = (int) config('services.doku.check_status_cache_seconds', 60);
        $cacheKey = 'doku_check_status:'.sha1($invoiceNumber);

        // Cache dinonaktifkan (mis. DOKU_CHECK_STATUS_CACHE_SECONDS=0 saat uji
        // sandbox) atau pembacaan fresh diminta -> selalu tembak API langsung.
        if ($fresh || $cacheSeconds <= 0) {
            if ($fresh) {
                Cache::forget($cacheKey);
            }

            return $this->requestStatus($invoiceNumber, false);
        }

        $cached = Cache::has($cacheKey);

        $result = Cache::remember($cacheKey, $cacheSeconds, function () use ($invoiceNumber) {
            return $this->requestStatus($invoiceNumber, false);
        });

        // Tandai hasil yang disajikan dari cache agar mudah dibedakan di log uji.
        if ($cached) {
            $result['cached'] = true;
            $this->logEvidence($invoiceNumber, $result, 'cached');
        }

        return $result;
    }

    /**
     * @return array{outcome:string, transaction_status:?string, order_status:?string, transaction_date:?Carbon, original_request_id:?string, http_status:?int, service_id:?string, channel_id:?string, acquirer_id:?string, expired_date:?string, cached:bool}
     */
    private function requestStatus(string $invoiceNumber, bool $cached): array
    {
        $requestTarget = '/orders/v1/status/'.rawurlencode($invoiceNumber);
        $headers = $this->signature->signedHeaders('GET', $requestTarget);

        try {
            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->acceptJson()
                ->get($this->baseUrl().$requestTarget);
        } catch (\Throwable $e) {
            Log::warning('DOKU Check Status request exception', [
                'invoice_number' => $invoiceNumber,
                'error' => $e->getMessage(),
            ]);

            return $this->unknown(null, $cached);
        }

        // 404 / non-2xx: tidak dapat dipastikan -> tahan (revisi #3, jangan auto-cancel).
        if (! $response->successful()) {
            Log::warning('DOKU Check Status non-success response', [
                'invoice_number' => $invoiceNumber,
                'http_status' => $response->status(),
            ]);

            return $this->unknown($response->status(), $cached);
        }

        $body = $response->json() ?? [];

        $transactionStatus = isset($body['transaction']['status'])
            ? strtoupper(trim((string) $body['transaction']['status']))
            : null;
        $orderStatus = isset($body['order']['status'])
            ? strtoupper(trim((string) $body['order']['status']))
            : null;

        $result = [
            'outcome' => DokuStatus::outcome($transactionStatus, $orderStatus),
            'transaction_status' => $transactionStatus,
            'order_status' => $orderStatus,
            'transaction_date' => $this->parseTransactionDate($body['transaction']['date'] ?? null),
            'original_request_id' => isset($body['transaction']['original_request_id'])
                ? (string) $body['transaction']['original_request_id']
                : null,
            'http_status' => $response->status(),
            'service_id' => isset($body['service']['id']) ? (string) $body['service']['id'] : null,
            'channel_id' => isset($body['channel']['id']) ? (string) $body['channel']['id'] : null,
            'acquirer_id' => isset($body['acquirer']['id']) ? (string) $body['acquirer']['id'] : null,
            // Batas waktu channel (mis. VA) bila DOKU menyertakannya; mentah apa adanya.
            'expired_date' => $this->extractChannelExpiredDate($body),
            'cached' => $cached,
        ];

        $this->logEvidence($invoiceNumber, $result, 'fresh');

        return $result;
    }

    /**
     * Satu baris log bukti per pengecekan Check Status (untuk uji A/B sandbox).
     * Hanya status & identitas non-sensitif; tanpa secret/signature/Client-Id/VA.
     */
    private function logEvidence(string $invoiceNumber, array $result, string $source): void
    {
        Log::info('DOKU check-status', [
            'invoice_number' => $invoiceNumber,
            'transaction_status' => $result['transaction_status'] ?? null,
            'order_status' => $result['order_status'] ?? null,
            'service_id' => $result['service_id'] ?? null,
            'channel_id' => $result['channel_id'] ?? null,
            'acquirer_id' => $result['acquirer_id'] ?? null,
            'expired_date' => $result['expired_date'] ?? null,
            'outcome' => $result['outcome'] ?? DokuStatus::HOLD,
            'source' => $source,
            'http_status' => $result['http_status'] ?? null,
        ]);
    }

    /**
     * Ambil batas waktu channel pembayaran bila ada (format mentah dari DOKU).
     */
    private function extractChannelExpiredDate(array $body): ?string
    {
        $raw = $body['virtual_account_info']['expired_date']
            ?? $body['online_to_offline_info']['expired_date']
            ?? null;

        return $raw === null || $raw === '' ? null : (string) $raw;
    }

    /**
     * @return array{outcome:string, transaction_status:?string, order_status:?string, transaction_date:?Carbon, original_request_id:?string, http_status:?int, service_id:?string, channel_id:?string, acquirer_id:?string, expired_date:?string, cached:bool}
     */
    private function unknown(?int $httpStatus, bool $cached): array
    {
        return [
            'outcome' => DokuStatus::HOLD,
            'transaction_status' => null,
            'order_status' => null,
            'transaction_date' => null,
            'original_request_id' => null,
            'http_status' => $httpStatus,
            'service_id' => null,
            'channel_id' => null,
            'acquirer_id' => null,
            'expired_date' => null,
            'cached' => $cached,
        ];
    }

    /**
     * Catat status DOKU terakhir pada booking/reservasi bila kolomnya tersedia.
     *
     * Kolom (`last_doku_transaction_status`, `last_doku_order_status`,
     * `last_doku_checked_at`) bersifat opsional: bila migrasi belum dijalankan
     * (mis. production), penulisan dilewati tanpa error agar tidak merusak alur.
     * Tidak pernah mengubah `status` lokal — itu wewenang DokuStatus::outcome().
     */
    public function rememberEvidence(Model $booking, array $result): void
    {
        try {
            if (! self::evidenceColumnsAvailable($booking->getTable())) {
                return;
            }

            $booking->forceFill([
                'last_doku_transaction_status' => $result['transaction_status'] ?? null,
                'last_doku_order_status' => $result['order_status'] ?? null,
                'last_doku_checked_at' => now(),
            ])->save();
        } catch (\Throwable) {
            Log::warning('DOKU: gagal mencatat status terakhir, dilewati (status lokal tidak diubah)', [
                'invoice_number' => $booking->invoice_number ?? null,
            ]);
        }
    }

    /** @var array<string, bool> */
    private static array $evidenceColumnsCache = [];

    private static function evidenceColumnsAvailable(string $table): bool
    {
        if (! array_key_exists($table, self::$evidenceColumnsCache)) {
            try {
                self::$evidenceColumnsCache[$table] = Schema::hasColumns($table, [
                    'last_doku_transaction_status',
                    'last_doku_order_status',
                    'last_doku_checked_at',
                ]);
            } catch (\Throwable) {
                self::$evidenceColumnsCache[$table] = false;
            }
        }

        return self::$evidenceColumnsCache[$table];
    }

    private function parseTransactionDate(mixed $raw): ?Carbon
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            if (is_numeric($raw)) {
                $date = strlen((string) $raw) >= 13
                    ? Carbon::createFromTimestampMs((int) $raw)
                    : Carbon::createFromTimestamp((int) $raw);
            } else {
                $date = Carbon::parse((string) $raw);
            }

            return $date->setTimezone(config('app.timezone'));
        } catch (\Throwable $e) {
            Log::warning('DOKU Check Status: gagal parse transaction.date', ['raw' => $raw]);

            return null;
        }
    }
}
