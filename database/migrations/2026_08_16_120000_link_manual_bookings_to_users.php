<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * 1) Jadikan user_id di ticket_bookings nullable agar peserta manual yang emailnya
     *    belum terdaftar bisa "mengambang" (orphan) dan dihubungkan saat peserta mendaftar.
     * 2) Relink data lama: peserta manual yang sebelumnya terikat ke akun admin
     *    dialihkan ke user dengan email yang cocok (gmail_account = users.email).
     *    Jika user belum ada, user_id di-set null agar tersambung saat registrasi.
     */
    public function up(): void
    {
        Schema::table('ticket_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        DB::table('ticket_bookings')
            ->where('source', 'manual')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunkById(200, function ($bookings) {
                foreach ($bookings as $booking) {
                    $user = DB::table('users')->where('email', $booking->gmail_account)->first(['id']);
                    DB::table('ticket_bookings')->where('id', $booking->id)->update([
                        'user_id' => $user ? $user->id : null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Kembalikan menjadi NOT NULL (tidak dapat memulihkan relink lama secara aman).
        Schema::table('ticket_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};