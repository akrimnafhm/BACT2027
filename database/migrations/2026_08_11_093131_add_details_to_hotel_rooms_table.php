<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            $table->text('description')->nullable()->after('room_type');
            // Tipe JSON sangat cocok untuk menyimpan array path gambar (bisa 1, 4, atau 10 gambar sekaligus)
            $table->json('photos')->nullable()->after('description'); 
        });
    }

    public function down(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            $table->dropColumn(['description', 'photos']);
        });
    }
};