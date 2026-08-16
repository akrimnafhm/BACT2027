<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Menambah kolom untuk:
     * 1. Reuse link pembayaran DOKU (payment_url + payment_expired_at) di ticket_bookings
     * 2. Cooldown kirim ulang OTP (otp_sent_at) di users
     */
    public function up(): void
    {
        Schema::table('ticket_bookings', function (Blueprint $table) {
            $table->text('payment_url')->nullable()->after('invoice_number');
            $table->timestamp('payment_expired_at')->nullable()->after('payment_url');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('otp_sent_at')->nullable()->after('otp_expires_at');
            $table->timestamp('email_otp_sent_at')->nullable()->after('email_otp_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_url', 'payment_expired_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['otp_sent_at', 'email_otp_sent_at']);
        });
    }
};