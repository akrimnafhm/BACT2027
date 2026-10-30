<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fonnte' => [
        'token' => env('FONNTE_API_TOKEN'),
        'url' => env('FONNTE_API_URL', 'https://api.fonnte.com/send'),
    ],

    'whatsapp' => [
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    ],

    'doku' => [
        'client_id' => env('DOKU_CLIENT_ID'),
        'secret_key' => env('DOKU_SECRET_KEY'),
        'is_production' => env('DOKU_IS_PRODUCTION', false),
        'notification_url' => env('DOKU_NOTIFICATION_URL'),
        // Throttle Check Status API agar scheduler tidak menghantam DOKU tiap menit.
        // Set 0 untuk menonaktifkan cache (dipakai saat pengujian).
        'check_status_cache_seconds' => env('DOKU_CHECK_STATUS_CACHE_SECONDS', 120),
        // Durasi kedaluwarsa link/VA DOKU (MENIT) yang dikirim ke create-order.
        // Default 1440 menit (24 jam). Jangan diisi nilai pendek di luar pengujian.
        'payment_due_date_minutes' => env('DOKU_PAYMENT_DUE_DATE_MINUTES', 1440),
        // Fallback LOKAL (jam) bila respons DOKU tidak menyertakan waktu expired.
        // Dipisahkan dari expiry resmi DOKU agar tidak dianggap expiry sesungguhnya.
        'local_expiry_fallback_hours' => env('DOKU_LOCAL_EXPIRY_FALLBACK_HOURS', 24),
        // Masa tenggang HOLD (menit) setelah payment_expired_at lewat: bila DOKU
        // masih PENDING/GENERATED tanpa channel, user diberi pesan eksplisit +
        // tombol batalkan (bukan menunggu tanpa akhir). TIDAK auto-cancel.
        'hold_grace_minutes' => env('DOKU_HOLD_GRACE_MINUTES', 15),
        // Buffer (detik) setelah payment_expired_at untuk keputusan eksplisit
        // "checkout_lapsed_no_channel": order TANPA channel yang timer-nya
        // habis + buffer dan Check Status tetap PENDING/tanpa status dibatalkan
        // lokal. Keputusan di luar DokuStatus::outcome() (tetap HOLD).
        'lapse_buffer_seconds' => env('DOKU_LAPSE_BUFFER_SECONDS', 90),
        // Cancel Order API DOKU Checkout (POST /checkout/v3/cancellations).
        // Memerlukan aktivasi di Dashboard DOKU (Settings > Checkout Appearance >
        // System Settings > Order Settings > "Activate cancellation of unpaid orders").
        // Default nonaktif agar tidak ada pemanggilan yang gagal bila belum diaktifkan.
        'cancel_order_enabled' => env('DOKU_CANCEL_ORDER_ENABLED', false),
    ],

];
