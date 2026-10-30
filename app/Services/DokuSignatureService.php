<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Signature HMAC-SHA256 DOKU (Non-SNAP).
 *
 * Komponen string yang ditandatangani (urut):
 *   Client-Id:{client_id}
 *   Request-Id:{request_id}
 *   Request-Timestamp:{timestamp}
 *   Request-Target:{path}
 *   Digest:{base64(sha256(raw_body))}   <-- hanya untuk request berbody (POST)
 *
 * Signature = "HMACSHA256=" . base64(hmac_sha256(raw, secret_key))
 */
class DokuSignatureService
{
    public function clientId(): ?string
    {
        return config('services.doku.client_id');
    }

    public function secretKey(): ?string
    {
        return config('services.doku.secret_key');
    }

    /**
     * Verifikasi signature notifikasi DOKU yang masuk.
     * Mengembalikan false bila header wajib tidak lengkap atau signature tidak cocok.
     */
    public function verifyIncoming(Request $request): bool
    {
        $secretKey = $this->secretKey();

        if (blank($secretKey)) {
            return false;
        }

        $headerClientId = $request->header('Client-Id');
        $requestId = $request->header('Request-Id');
        $requestTimestamp = $request->header('Request-Timestamp');
        $signature = $request->header('Signature');

        if (blank($headerClientId) || blank($requestId) || blank($requestTimestamp) || blank($signature)) {
            return false;
        }

        $configuredClientId = $this->clientId();

        if (filled($configuredClientId) && ! hash_equals($configuredClientId, (string) $headerClientId)) {
            return false;
        }

        $digest = base64_encode(hash('sha256', $request->getContent(), true));

        // Request-Target adalah path dari Notification URL yang dikirim ke DOKU.
        // Uji path request aktual, dan sebagai fallback path dari URL yang dikonfigurasi
        // (mis. saat aplikasi berada di belakang proxy yang menambah/menghapus prefix).
        $targets = array_values(array_unique(array_filter([
            $request->getPathInfo(),
            parse_url((string) config('services.doku.notification_url'), PHP_URL_PATH),
        ])));

        foreach ($targets as $requestTarget) {
            $raw = 'Client-Id:'.$headerClientId."\n"
                 .'Request-Id:'.$requestId."\n"
                 .'Request-Timestamp:'.$requestTimestamp."\n"
                 .'Request-Target:'.$requestTarget."\n"
                 .'Digest:'.$digest;

            $expected = 'HMACSHA256='.base64_encode(hash_hmac('sha256', $raw, $secretKey, true));

            if (hash_equals($expected, (string) $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bangun header bertanda tangan untuk request keluar ke DOKU (GET tanpa Digest).
     *
     * @return array<string, string>
     */
    public function signedHeaders(string $method, string $requestTarget, ?string $body = null): array
    {
        $clientId = $this->clientId();
        $secretKey = $this->secretKey();
        $requestId = (string) Str::uuid();
        $requestTimestamp = gmdate('Y-m-d\TH:i:s\Z');

        $raw = 'Client-Id:'.$clientId."\n"
             .'Request-Id:'.$requestId."\n"
             .'Request-Timestamp:'.$requestTimestamp."\n"
             .'Request-Target:'.$requestTarget;

        if (strtoupper($method) === 'POST') {
            $digest = base64_encode(hash('sha256', (string) $body, true));
            $raw .= "\nDigest:".$digest;
        }

        return [
            'Client-Id' => (string) $clientId,
            'Request-Id' => $requestId,
            'Request-Timestamp' => $requestTimestamp,
            'Signature' => 'HMACSHA256='.base64_encode(hash_hmac('sha256', $raw, $secretKey, true)),
        ];
    }
}
