<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();           // Kode booking unik, misal: HTL-202708-XXXX
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('hotel_room_id')->constrained('hotel_rooms')->onDelete('cascade');
            $table->date('check_in');                           // Tanggal masuk
            $table->date('check_out');                          // Tanggal keluar
            $table->integer('total_nights');                    // Durasi malam
            $table->integer('total_price');                     // Total harga yang harus dibayar
            $table->text('special_request')->nullable();        // Catatan tambahan dari peserta
            $table->string('status')->default('pending');       // Status: pending, paid, cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_reservations');
    }
};