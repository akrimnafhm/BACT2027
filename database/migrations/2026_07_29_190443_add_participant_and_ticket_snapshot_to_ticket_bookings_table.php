<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_bookings', function (Blueprint $table) {
            // 1. SNAPSHOT TIKET & HARGA (LOCKED DATA)
            $table->string('ticket_name')->after('ticket_id');              // Contoh: "Early Bird"
            $table->string('ticket_category')->after('ticket_name');        // Contoh: "Advance"
            $table->unsignedBigInteger('amount')->after('ticket_category'); // Harga yang HARUS dibayar (Locked Price)

            // 2. SNAPSHOT DATA PESERTA (LOCKED DATA)
            $table->string('full_name')->after('amount');                   // Nama tanpa gelar
            $table->string('name_with_title')->after('full_name');          // Nama dengan gelar (Sertifikat)
            $table->string('nik', 16)->after('name_with_title');            // NIK 16 digit
            $table->string('profession')->after('nik');                     // Pilihan dari 23 profesi
            $table->string('whatsapp_number')->after('profession');         // WA aktif
            $table->string('gmail_account')->after('whatsapp_number');      // Email Gmail
            $table->string('plataran_sehat_email')->after('gmail_account'); // Email Plataran Sehat
            $table->string('institution_province')->after('institution_city'); // Provinsi instansi
        });
    }

    public function down(): void
    {
        Schema::table('ticket_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'ticket_name',
                'ticket_category',
                'amount',
                'full_name',
                'name_with_title',
                'nik',
                'profession',
                'whatsapp_number',
                'gmail_account',
                'plataran_sehat_email',
                'institution_province'
            ]);
        });
    }
};