<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Izinkan file Excel yang sama diunggah lagi. Duplikasi ditangani per Order ID, Witel, dan tanggal Fallout, bukan dengan
     * menolak seluruh file.
     * Mendukung MySQL dan PostgreSQL tanpa mengubah isi data lama.
     */
    public function up(): void
    {
        if (!Schema::hasTable('upload_batches') || !Schema::hasColumn('upload_batches', 'file_hash')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $indexes = DB::select("SHOW INDEX FROM `upload_batches`");

            $uniqueNames = [];
            foreach ($indexes as $index) {
                if (
                    strtolower((string) $index->Column_name) === 'file_hash'
                    && (int) $index->Non_unique === 0
                    && $index->Key_name !== 'PRIMARY'
                ) {
                    $uniqueNames[$index->Key_name] = true;
                }
            }

            foreach (array_keys($uniqueNames) as $indexName) {
                $safeName = str_replace('`', '``', $indexName);
                DB::statement("ALTER TABLE `upload_batches` DROP INDEX `{$safeName}`");
            }

            return;
        }

        if ($driver === 'pgsql') {
            $constraints = DB::select(
                "SELECT c.conname
                 FROM pg_constraint c
                 JOIN pg_class t ON t.oid = c.conrelid
                 JOIN pg_namespace n ON n.oid = t.relnamespace
                 WHERE t.relname = ?
                   AND n.nspname = current_schema()
                   AND c.contype = 'u'
                   AND pg_get_constraintdef(c.oid) ILIKE '%file_hash%'",
                ['upload_batches']
            );

            foreach ($constraints as $constraint) {
                $safeName = str_replace('"', '""', $constraint->conname);
                DB::statement('ALTER TABLE "upload_batches" DROP CONSTRAINT "' . $safeName . '"');
            }

            // Hapus unique index langsung yang bukan index milik unique constraint.
            $indexes = DB::select(
                "SELECT indexname
                 FROM pg_indexes
                 WHERE schemaname = current_schema()
                   AND tablename = ?
                   AND indexdef ILIKE 'CREATE UNIQUE INDEX%'
                   AND indexdef ILIKE '%file_hash%'",
                ['upload_batches']
            );

            foreach ($indexes as $index) {
                $safeName = str_replace('"', '""', $index->indexname);
                DB::statement('DROP INDEX IF EXISTS "' . $safeName . '"');
            }
        }
    }

    public function down(): void
    {
        // Tidak otomatis membuat unique index kembali: setelah migration ini,
        // beberapa batch boleh menyimpan fingerprint file yang sama.
        // Mengembalikan unique constraint berisiko gagal jika file diunggah ulang.
    }
};
