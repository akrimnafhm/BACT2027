<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_bookings', function (Blueprint $table) {
            $table->string('checkin_token')->nullable()->unique()->after('invoice_number');
            $table->timestamp('checked_in_at')->nullable()->after('checkin_token');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_bookings', function (Blueprint $table) {
            $table->dropColumn(['checkin_token', 'checked_in_at']);
        });
    }
};
