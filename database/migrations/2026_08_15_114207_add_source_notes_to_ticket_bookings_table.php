<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ticket_bookings', function (Blueprint $table) {
            // Pembeda asal pendaftaran: melalui website / ditambahkan manual oleh admin
            $table->string('source')->default('website')->after('status');
            // Catatan bebas admin (alasan ganti nama, pembatalan, dsb.)
            $table->text('notes')->nullable()->after('source');
            // Waktu terakhir catatan diubah
            $table->timestamp('notes_updated_at')->nullable()->after('notes');
            // Waktu pemesanan dibatalkan
            $table->timestamp('cancelled_at')->nullable()->after('notes_updated_at');
        });

        // Tandai peserta manual yang sudah LUNAS sebagai terkonfirmasi (data lama dari
        // sebelum fitur konfirmasi manual). Kolom `confirmed_at` sudah dibuat oleh migration
        // 2026_08_15_045205 yang dijalankan lebih dulu.
        DB::table('ticket_bookings')
            ->where('source', 'manual')
            ->where('status', 'paid')
            ->whereNull('confirmed_at')
            ->update(['confirmed_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    public function down()
    {
        Schema::table('ticket_bookings', function (Blueprint $table) {
            $table->dropColumn(['source', 'notes', 'notes_updated_at', 'cancelled_at']);
        });
    }
};