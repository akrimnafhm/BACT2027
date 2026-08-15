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
            $table->timestamp('confirmed_at')->nullable()->after('cancelled_at');
        });

        // Peserta manual yang sudah LUNAS sebelum fitur ini dianggap sudah terkonfirmasi
        DB::table('ticket_bookings')
            ->where('source', 'manual')
            ->where('status', 'paid')
            ->update(['confirmed_at' => DB::raw('COALESCE(updated_at, created_at)')]);
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