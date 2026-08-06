<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Kita tambahkan ejaan 'Advance', 'Basic-Advance', dan 'Workshop' agar aman
            $table->enum('ticket_category', [
                'Basic', 
                'Advanced', 
                'Advance', 
                'Basic-Advanced', 
                'Basic-Advance', 
                'Online', 
                'Workshop',
                'Basic-Advance + Workshop',
            ])->after('ticket_name');
            
            $table->dateTime('start_date')->nullable()->after('quota');
        });
    }

    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['ticket_category', 'start_date']);
        });
    }
};