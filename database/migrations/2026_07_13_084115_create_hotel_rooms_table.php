<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_type');      // Tipe Kamar (Deluxe King, dll)
            $table->integer('price_per_night'); // Harga per malam
            $table->integer('quota');          // Jumlah Kamar tersedia (diatur Admin)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_rooms');
    }
};