<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('fallout_data', 'tiket_eskalasi')) {
            Schema::table('fallout_data', function (Blueprint $table) {
                $table->string('tiket_eskalasi', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fallout_data', 'tiket_eskalasi')) {
            Schema::table('fallout_data', function (Blueprint $table) {
                $table->dropColumn('tiket_eskalasi');
            });
        }
    }
};
