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
        Schema::table('users', function (Blueprint $table) {
            // Menghapus kolom gelar depan dan belakang jika ada
            if (Schema::hasColumn('users', 'degree_front')) {
                $table->dropColumn('degree_front');
            }
            if (Schema::hasColumn('users', 'degree_back')) {
                $table->dropColumn('degree_back');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Mengembalikan kolom jika migrasi di-rollback
            $table->string('degree_front', 50)->nullable();
            $table->string('degree_back', 50)->nullable();
        });
    }
};