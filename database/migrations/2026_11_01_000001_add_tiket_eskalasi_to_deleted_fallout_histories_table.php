<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('deleted_fallout_histories', 'tiket_eskalasi')) {
            Schema::table('deleted_fallout_histories', function (Blueprint $table) {
                $table->text('tiket_eskalasi')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('deleted_fallout_histories', 'tiket_eskalasi')) {
            Schema::table('deleted_fallout_histories', function (Blueprint $table) {
                $table->dropColumn('tiket_eskalasi');
            });
        }
    }
};
