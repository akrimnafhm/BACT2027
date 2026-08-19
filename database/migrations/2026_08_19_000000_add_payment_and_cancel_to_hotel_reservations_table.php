<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_reservations', function (Blueprint $table) {
            $table->text('payment_url')->nullable()->after('invoice_number');
            $table->timestamp('payment_expired_at')->nullable()->after('payment_url');
            $table->timestamp('cancelled_at')->nullable()->after('payment_expired_at');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_reservations', function (Blueprint $table) {
            $table->dropColumn(['payment_url', 'payment_expired_at', 'cancelled_at']);
        });
    }
};
