<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Menandai kapan peserta bergabung ke grup WhatsApp
        // (terisi otomatis saat klik tombol Join Grup, atau manual oleh admin).
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('wa_joined_at')->nullable()->after('email_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wa_joined_at');
        });
    }
};
