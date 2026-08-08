<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();                 // Contoh: ticket_paid_wa, ticket_paid_email
            $table->string('channel');                       // wa | email
            $table->string('label');                         // Label tampilan untuk admin
            $table->string('subject')->nullable();           // Subject email (khusus email)
            $table->text('body');                            // Isi pesan (mendukung placeholder)
            $table->boolean('include_qr')->default(false);   // Sertakan QR tiket di pesan
            $table->boolean('is_active')->default(true);     // Aktif / nonaktif
            $table->timestamps();
        });

        Schema::table('ticket_bookings', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('checked_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_bookings', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });

        Schema::dropIfExists('notification_templates');
    }
};
