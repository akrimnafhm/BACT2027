<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');

            // Data Instansi yang diminta saat booking
            $table->string('institution_name');
            $table->string('institution_district'); // Kecamatan
            $table->string('institution_city');     // Kabupaten

            $table->string('status')->default('pending'); // pending, paid
            $table->timestamps();
        });
    }
};
