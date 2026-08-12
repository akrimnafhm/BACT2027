<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tabel link grup WA per KATEGORI (bukan per jenis/gelombang tiket)
        Schema::create('wa_group_links', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_category')->unique();
            $table->string('wa_group_link')->nullable();
            $table->timestamps();
        });

        // Migrasi data yang sudah ada: gabungkan link per tiket ke satu link per kategori
        $rows = DB::table('tickets')
            ->select('ticket_category')
            ->selectRaw('MAX(wa_group_link) as link')
            ->whereNotNull('wa_group_link')
            ->groupBy('ticket_category')
            ->get();

        foreach ($rows as $row) {
            DB::table('wa_group_links')->insertOrIgnore([
                'ticket_category' => $row->ticket_category,
                'wa_group_link'   => $row->link,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Kolom per-tiket sudah tidak dipakai lagi (link kini hanya dibedakan oleh kategori)
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('wa_group_link');
        });
    }

    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('wa_group_link')->nullable()->after('end_date');
        });

        $rows = DB::table('wa_group_links')->get();
        foreach ($rows as $row) {
            DB::table('tickets')
                ->where('ticket_category', $row->ticket_category)
                ->update(['wa_group_link' => $row->wa_group_link]);
        }

        Schema::dropIfExists('wa_group_links');
    }
};