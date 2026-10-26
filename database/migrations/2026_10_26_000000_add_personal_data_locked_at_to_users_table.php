<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('personal_data_locked_at')->nullable()->after('gender');
        });

        // User lama yang sudah punya tiket dan data pribadinya lengkap langsung
        // dikunci agar tidak bisa mengganti identitas setelah fitur ini rilis.
        DB::table('users')
            ->whereIn('id', function ($query) {
                $query->select('user_id')
                    ->from('ticket_bookings')
                    ->whereNotNull('user_id')
                    ->where('status', '!=', 'cancelled');
            })
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->whereNotNull('nik')
            ->whereRaw('LENGTH(nik) = 16')
            ->whereIn('gender', ['Laki-laki', 'Perempuan'])
            ->update(['personal_data_locked_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('personal_data_locked_at');
        });
    }
};
