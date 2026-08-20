<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * [REVISI KLIENT] Perbaiki ejaan kategori tiket: "Advance" -> "Advanced".
 *
 * Data lama yang tersimpan dalam berbagai varian penulisan (Advance,
 * Basic-Advance, Basic - Advance, Basic-Advance + Workshop, dsb.) diubah
 * ke ejaan benar pada 3 tabel: tickets, ticket_bookings, wa_group_links.
 *
 * Migration ini hanya mengubah NILAI data, bukan skema.
 */
return new class extends Migration
{
    private array $tables = ['tickets', 'ticket_bookings', 'wa_group_links'];

    private array $forward = [
        'Advance'                     => 'Advanced',
        'Basic-Advance'               => 'Basic-Advanced',
        'Basic - Advance'             => 'Basic-Advanced',
        'Basic - Advanced'            => 'Basic-Advanced',
        'Basic-Advance + Workshop'    => 'Basic-Advanced + Workshop',
        'Basic-Advanced + Workshop'   => 'Basic-Advanced + Workshop',
        'Basic - Advance + Workshop'  => 'Basic-Advanced + Workshop',
        'Basic - Advanced + Workshop' => 'Basic-Advanced + Workshop',
    ];

    private array $reverse = [
        'Advanced'                    => 'Advance',
        'Basic-Advanced'              => 'Basic-Advance',
        'Basic-Advanced + Workshop'   => 'Basic-Advance + Workshop',
    ];

    public function up(): void
    {
        // 'Basic-Advanced + Workshop' (24 karakter) butuh kolom yang lebih lebar.
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'ticket_category')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('ticket_category', 60)->nullable()->change();
                });
            }
        }

        foreach ($this->tables as $table) {
            foreach ($this->forward as $from => $to) {
                DB::table($table)
                    ->where('ticket_category', $from)
                    ->update(['ticket_category' => $to]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            foreach ($this->reverse as $from => $to) {
                DB::table($table)
                    ->where('ticket_category', $from)
                    ->update(['ticket_category' => $to]);
            }
        }
    }
};