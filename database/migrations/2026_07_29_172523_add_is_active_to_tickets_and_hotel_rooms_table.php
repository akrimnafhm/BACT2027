<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('quota');
        });

        Schema::table('hotel_rooms', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('quota');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('hotel_rooms', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};