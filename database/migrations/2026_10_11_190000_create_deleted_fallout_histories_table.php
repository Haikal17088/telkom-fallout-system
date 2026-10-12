<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deleted_fallout_histories', function (Blueprint $table) {
            $table->bigIncrements('history_id');
            $table->unsignedBigInteger('original_row_id')->nullable();
            $table->string('witel_slug', 32);
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('order_id', 191)->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('sto', 100)->nullable();
            $table->date('tanggal')->nullable();
            $table->string('pic', 191)->nullable();
            $table->string('resolved_eskalasi', 191)->nullable();
            $table->string('status', 191)->nullable();
            $table->text('ket')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->string('deleted_by_name', 191)->nullable();
            $table->string('delete_source', 40)->default('other');
            $table->timestamp('deleted_at')->useCurrent();
            $table->timestamps();

            $table->index(['witel_slug', 'deleted_at'], 'dfh_witel_deleted_at_idx');
            $table->index(['witel_slug', 'order_id'], 'dfh_witel_order_id_idx');
            $table->index('sto', 'dfh_sto_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deleted_fallout_histories');
    }
};
