<?php

namespace App\Services;

/**
 * Sumber tunggal aturan keputusan status pembayaran DOKU (Non-SNAP Checkout).
 *
 * Dipakai oleh webhook, Check Status API, dan scheduler agar semua jalur
 * pembatalan memakai aturan yang sama (revisi #4 & #6).
 */
class DokuStatus
{
    /** Transaksi sudah lunas -> JANGAN pernah dibatalkan. */
    public const PAID = 'paid';

    /** Order benar-benar kedaluwarsa & tidak dipulihkan -> aman dibatalkan. */
    public const EXPIRED = 'expired';

    /** Belum final / tidak dapat dipastikan -> tahan untuk pemeriksaan ulang. */
    public const HOLD = 'hold';

    /** Transaction-level status yang menandakan pembayaran sukses. */
    public const PAID_TRANSACTION_STATUSES = [
        'SUCCESS',
        'PAID',
        'COMPLETED',
        'SETTLED',
        'SETTLEMENT',
        'CAPTURED',
    ];

    public static function isPaidTransaction(?string $status): bool
    {
        return in_array(self::normalize($status), self::PAID_TRANSACTION_STATUSES, true);
    }

    /**
     * Tentukan outcome dari kombinasi order.status dan transaction.status.
     *
     * Urutan prioritas (disengaja, jangan dibalik):
     *  1. transaction SUCCESS/PAID/... -> PAID. Selalu menang, termasuk atas
     *     ORDER_EXPIRED dan ORDER_RECOVERED, serta dalam race antara webhook
     *     dan scheduler. Status PAID tidak pernah diturunkan menjadi
     *     expired/cancelled oleh jalur mana pun.
     *  2. ORDER_RECOVERED -> HOLD (keranjang dipulihkan, pelanggan masih
     *     bisa bayar; bahkan transaction EXPIRED pun ditahan di sini).
     *  3. ORDER_EXPIRED -> EXPIRED. Ini keputusan cancel-LOKAL yang
     *     diinginkan untuk checkout yang kedaluwarsa tanpa channel
     *     (kombinasi normal DOKU: transaction PENDING + ORDER_EXPIRED).
     *     Label `transaction.status` di dashboard DOKU TIDAK diubah oleh ini.
     *  4. transaction EXPIRED -> EXPIRED (channel yang dipilih kedaluwarsa).
     *  5. selain itu (PENDING/FAILED/TIMEOUT/REDIRECT/ORDER_GENERATED/
     *     unknown/404) -> HOLD.
     *
     * Catatan: untuk Checkout, transaction.status=FAILED TIDAK dianggap final
     * karena pelanggan masih dapat mengganti channel pembayaran.
     */
    public static function outcome(?string $transactionStatus, ?string $orderStatus): string
    {
        $transaction = self::normalize($transactionStatus);
        $order = self::normalize($orderStatus);

        if (self::isPaidTransaction($transaction)) {
            return self::PAID;
        }

        // Order dipulihkan: pelanggan masih dapat menyelesaikan pembayaran.
        if ($order === 'ORDER_RECOVERED') {
            return self::HOLD;
        }

        if ($order === 'ORDER_EXPIRED') {
            return self::EXPIRED;
        }

        if ($transaction === 'EXPIRED') {
            return self::EXPIRED;
        }

        return self::HOLD;
    }

    private static function normalize(?string $status): string
    {
        return strtoupper(trim((string) $status));
    }
}
