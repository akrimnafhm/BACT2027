<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mencatat status DOKU terakhir per invoice agar halaman dapat menampilkan
 * "status lokal" dan "status DOKU terakhir" sebagai dua label terpisah.
 *
 * PENTING: file ini SENGAJA belum dijalankan. Jangan jalankan `php artisan
 * migrate` untuk file ini ke database production sebelum disetujui. Kode
 * aplikasi (`DokuApiService::rememberEvidence()`) memeriksa keberadaan kolom
 * ini dan melewati penulisan bila kolom belum ada, sehingga aman dijalankan
 * dengan maupun tanpa migrasi ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_bookings', function (Blueprint $table) {
            $table->string('last_doku_transaction_status', 32)->nullable()->after('payment_expired_at');
            $table->string('last_doku_order_status', 32)->nullable()->after('last_doku_transaction_status');
            $table->timestamp('last_doku_checked_at')->nullable()->after('last_doku_order_status');
        });

        Schema::table('hotel_reservations', function (Blueprint $table) {
            $table->string('last_doku_transaction_status', 32)->nullable()->after('payment_expired_at');
            $table->string('last_doku_order_status', 32)->nullable()->after('last_doku_transaction_status');
            $table->timestamp('last_doku_checked_at')->nullable()->after('last_doku_order_status');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_bookings', function (Blueprint $table) {
            $table->dropColumn(['last_doku_transaction_status', 'last_doku_order_status', 'last_doku_checked_at']);
        });

        Schema::table('hotel_reservations', function (Blueprint $table) {
            $table->dropColumn(['last_doku_transaction_status', 'last_doku_order_status', 'last_doku_checked_at']);
        });
    }
};
