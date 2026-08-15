<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ticket_bookings', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable();
        });

        // Backfill data lama: tandai peserta manual yang sudah LUNAS sebagai terkonfirmasi.
        // Dijalankan bersyarat karena kolom `source` baru dibuat oleh migration
        // 2026_08_15_114207 yang dijalankan setelah migration ini pada database baru
        // (urutan migration mengikuti nama file). Di database baru tidak ada data lama,
        // sehingga backfill ini hanya berfungsi pada database yang sudah bermigrasi penuh.
        if (Schema::hasColumn('ticket_bookings', 'source')) {
            DB::table('ticket_bookings')
                ->where('source', 'manual')
                ->where('status', 'paid')
                ->whereNull('confirmed_at')
                ->update(['confirmed_at' => DB::raw('COALESCE(updated_at, created_at)')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_bookings', function (Blueprint $table) {
            $table->dropColumn('confirmed_at');
        });
    }
};