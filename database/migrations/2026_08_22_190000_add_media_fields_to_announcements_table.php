<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('content');
            $table->string('external_link')->nullable()->after('image_path');
            $table->string('attachment_path')->nullable()->after('external_link');
            $table->string('attachment_name')->nullable()->after('attachment_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn([
                'image_path',
                'external_link',
                'attachment_path',
                'attachment_name',
            ]);
        });
    }
};
