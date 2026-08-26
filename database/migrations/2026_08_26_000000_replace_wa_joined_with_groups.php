<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mengganti kolom tunggal `wa_joined_at` + `wa_joined_group` menjadi
     * `wa_joined_groups` (JSON) agar satu user bisa menyimpan status join
     * untuk BANYAK grup WhatsApp sekaligus (mis. Basic dan Basic-Advanced).
     *
     * Struktur JSON per user:
     * [
     *     ['group' => 'Basic', 'joined_at' => '2026-08-26 10:00:00'],
     *     ['group' => 'Basic-Advanced', 'joined_at' => '2026-08-26 11:00:00'],
     * ]
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('wa_joined_groups')->nullable()->after('wa_joined_group');
        });

        // Migrasi data lama: satu grup lama menjadi satu entry di JSON baru.
        DB::table('users')
            ->whereNotNull('wa_joined_at')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $group = $user->wa_joined_group ?: 'manual';
                    $entries = [
                        [
                            'group' => $group,
                            'joined_at' => $user->wa_joined_at,
                        ],
                    ];

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['wa_joined_groups' => json_encode($entries)]);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wa_joined_at', 'wa_joined_group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('wa_joined_at')->nullable()->after('email_verified_at');
            $table->string('wa_joined_group')->nullable()->after('wa_joined_at');
        });

        // Kembalikan data lama (hanya grup pertama yang direstore).
        DB::table('users')
            ->whereNotNull('wa_joined_groups')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $entries = json_decode($user->wa_joined_groups, true) ?: [];
                    if (empty($entries)) {
                        continue;
                    }

                    $first = $entries[0];
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'wa_joined_at' => $first['joined_at'] ?? null,
                            'wa_joined_group' => $first['group'] ?? 'manual',
                        ]);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wa_joined_groups');
        });
    }
};
