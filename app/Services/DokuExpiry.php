<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Sumber tunggal untuk waktu kedaluwarsa pembayaran DOKU Checkout.
 *
 * Membedakan secara eksplisit antara:
 *  1. Expiry RESMI dari respons DOKU (disimpan di `payment_expired_at`).
 *  2. Fallback LOKAL yang dipakai hanya bila DOKU tidak menyertakan expiry,
 *     dihitung dari `created_at` + sejumlah jam konfigurasi.
 *
 * Dengan pemisahan ini, `payment_expired_at` TIDAK pernah diisi dengan nilai
 * fallback, sehingga tidak lagi disalahartikan sebagai expiry resmi DOKU.
 */
class DokuExpiry
{
    /**
     * Durasi kedaluwarsa (menit) yang dikirim ke create-order DOKU.
     *
     * Aturan pengaman: nilai kecil (< 10 menit) hanya diizinkan di luar
     * production (untuk uji sandbox A/B). Di production nilai kecil
     * diabaikan/di-clamp ke 1440 menit dan dicatat di log agar tenggat
     * pendek tidak lolos ke transaksi nyata.
     */
    public function paymentDueDateMinutes(): int
    {
        $minutes = (int) config('services.doku.payment_due_date_minutes', 1440);

        if ($minutes <= 0) {
            $minutes = 1440;
        }

        $isProduction = filter_var(config('services.doku.is_production', false), FILTER_VALIDATE_BOOL);

        if ($isProduction && $minutes < 10) {
            Log::warning('DOKU: payment_due_date terlalu kecil di production, di-clamp ke 1440 menit', [
                'requested_minutes' => $minutes,
            ]);

            return 1440;
        }

        return $minutes;
    }

    /**
     * Fallback lokal (jam) bila DOKU tidak mengirimkan waktu expired.
     */
    public function localFallbackHours(): int
    {
        return (int) config('services.doku.local_expiry_fallback_hours', 24);
    }

    /**
     * Masa tenggang HOLD (menit) setelah payment_expired_at lewat.
     */
    public function holdGraceMinutes(): int
    {
        return max(0, (int) config('services.doku.hold_grace_minutes', 15));
    }

    /**
     * Buffer (detik) setelah payment_expired_at untuk keputusan eksplisit
     * "checkout_lapsed_no_channel".
     */
    public function lapseBufferSeconds(): int
    {
        return max(0, (int) config('services.doku.lapse_buffer_seconds', 90));
    }

    /**
     * Batas waktu (payment_expired_at + buffer) untuk keputusan lapsed.
     * Null bila tidak ada expiry resmi DOKU (tidak boleh dipakai untuk cancel).
     */
    public function lapseCutoff(Model $booking): ?Carbon
    {
        if (! $booking->payment_expired_at) {
            return null;
        }

        return Carbon::parse($booking->payment_expired_at)->addSeconds($this->lapseBufferSeconds());
    }

    /**
     * Apakah booking sudah melewati payment_expired_at + masa tenggang HOLD
     * namun (berdasarkan pemeriksaan terakhir) belum ada status final DOKU.
     *
     * Dipakai untuk menampilkan pesan eksplisit + tombol batalkan kepada user,
     * BUKAN untuk auto-cancel: keputusan cancel tetap milik DokuStatus dan
     * aksi eksplisit user/panitia.
     */
    public function heldBeyondGrace(Model $booking): bool
    {
        if (! $booking->payment_expired_at) {
            return false;
        }

        $deadline = Carbon::parse($booking->payment_expired_at)->addMinutes($this->holdGraceMinutes());

        return $deadline->lt(now());
    }

    /**
     * Ambil waktu expired RESMI dari respons DOKU.
     *
     * Mengembalikan null bila DOKU tidak menyertakan expiry atau tidak dapat
     * diparse — pemanggil harus memakai fallback lokal secara eksplisit.
     *
     * Mendukung format:
     *  - array `response.payment.expired_datetime` (format utama DOKU Checkout)
     *  - `response.payment.expired_date`
     *  - `response.payment.expiredDate`
     *  - format `yyyyMMddHHmmss` (UTC+7) maupun ISO 8601.
     */
    public function parse(array $dokuResult): ?Carbon
    {
        $raw = $dokuResult['response']['payment']['expired_datetime']
            ?? $dokuResult['response']['payment']['expired_date']
            ?? $dokuResult['response']['payment']['expiredDate']
            ?? null;

        if (blank($raw)) {
            return null;
        }

        try {
            // Format DOKU `yyyyMMddHHmmss` dikirim dalam UTC+7 (Asia/Jakarta).
            if (preg_match('/^\d{14}$/', (string) $raw)) {
                return Carbon::createFromFormat('YmdHis', (string) $raw, 'Asia/Jakarta')
                    ->setTimezone(config('app.timezone'));
            }

            // Format lain (ISO 8601) umumnya sudah membawa offset zona waktu.
            return Carbon::parse((string) $raw)->setTimezone(config('app.timezone'));
        } catch (\Throwable $e) {
            Log::warning('DOKU: gagal parse waktu expired, fallback lokal akan dipakai', [
                'raw_length' => strlen((string) $raw),
            ]);

            return null;
        }
    }

    /**
     * Expiry efektif yang dipakai untuk keputusan lokal:
     * expiry resmi DOKU bila ada, selain itu fallback lokal dari created_at.
     */
    public function effectiveExpiry(Model $booking): ?Carbon
    {
        if ($booking->payment_expired_at) {
            return Carbon::parse($booking->payment_expired_at);
        }

        $createdAt = $booking->created_at;

        return $createdAt ? Carbon::parse($createdAt)->addHours($this->localFallbackHours()) : null;
    }

    /**
     * Apakah timer pembayaran (expiry DOKU atau fallback lokal) sudah habis.
     */
    public function isExpired(Model $booking): bool
    {
        $expiry = $this->effectiveExpiry($booking);

        return $expiry !== null && $expiry->lte(now());
    }
}
