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
        // Menyimpan grup WhatsApp mana yang menjadi sumber tanda join,
        // agar scan ulang grup lain tidak menghapus tanda milik grup ini.
        Schema::table('users', function (Blueprint $table) {
            $table->string('wa_joined_group')->nullable()->after('wa_joined_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wa_joined_group');
        });
    }
};
