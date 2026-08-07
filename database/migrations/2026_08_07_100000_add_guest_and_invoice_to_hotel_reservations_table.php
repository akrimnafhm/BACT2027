<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_reservations', function (Blueprint $table) {
            $table->string('guest_name')->nullable()->after('hotel_room_id');
            $table->string('guest_nik')->nullable()->after('guest_name');
            $table->string('guest_phone')->nullable()->after('guest_nik');
            $table->string('guest_email')->nullable()->after('guest_phone');
            $table->string('invoice_number')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_reservations', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'guest_nik', 'guest_phone', 'guest_email', 'invoice_number']);
        });
    }
};
