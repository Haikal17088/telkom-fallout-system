<?php

namespace App\Support;

use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class FalloutArchiveDeletionService
{
    private const VALID_WITELS = ['jaktim', 'jakpus', 'jaksel'];
    private const READ_CHUNK_SIZE = 250;
    private const DELETE_CHUNK_SIZE = 500;
    private const BATCH_CHUNK_SIZE = 300;

    public static function deleteByDate(string $witel, string $date): int
    {
        $parsed = Carbon::createFromFormat('!Y-m-d', $date);

        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            throw new RuntimeException('Tanggal penghapusan tidak valid.');
        }

        return self::deleteRange(
            $witel,
            $parsed->toDateString(),
            $parsed->copy()->addDay()->toDateString(),
            'arsip_tanggal'
        );
    }

    public static function deleteByYear(string $witel, int $year): int
    {
        if ($year < 1900 || $year > 2200) {
            throw new RuntimeException('Tahun penghapusan tidak valid.');
        }

        $from = Carbon::create($year, 1, 1)->startOfDay();
        $until = $from->copy()->addYear();

        return self::deleteRange(
            $witel,
            $from->toDateString(),
            $until->toDateString(),
            'arsip_tahun'
        );
    }

    private static function deleteRange(
        string $witel,
        string $fromDate,
        string $untilDateExclusive,
        string $source
    ): int {
        $witel = strtolower(trim($witel));

        if (!in_array($witel, self::VALID_WITELS, true)) {
            throw new RuntimeException('Witel tidak valid.');
        }

        $branch = Branch::bySlug($witel);

        if (!$branch) {
            throw new RuntimeException('Witel tidak ditemukan di database.');
        }

        self::assertSourceSchemaReady();
        FalloutDeletionHistoryRecorder::assertReady();

        return DB::transaction(function () use (
            $witel,
            $branch,
            $fromDate,
            $untilDateExclusive,
            $source
        ): int {
            $rowIds = [];
            $batchIds = [];

            // The branch constraint is applied in SQL, so rows belonging to
            // another Witel can never enter the snapshot or delete list.
            $rowsQuery = DB::table('fallout_data')
                ->whereIn('fallout_data.batch_id', function ($query) use ($branch) {
                    $query->select('batch_id')
                        ->from('upload_batches')
                        ->where('branch_id', $branch->branch_id);
                })
                ->where('fallout_data.tanggal', '>=', $fromDate)
                ->where('fallout_data.tanggal', '<', $untilDateExclusive)
                ->select([
                    'fallout_data.row_id',
                    'fallout_data.batch_id',
                    'fallout_data.order_id',
                    'fallout_data.deskripsi',
                    'fallout_data.sto',
                    'fallout_data.tanggal',
                    'fallout_data.pic',
                    'fallout_data.resolved_eskalasi',
                    'fallout_data.status',
                    'fallout_data.ket',
                    'fallout_data.tiket_eskalasi',
                ])
                ->orderBy('fallout_data.row_id')
                ->lockForUpdate();

            // Read/snapshot a bounded number of records at a time. Only IDs
            // and affected batch IDs remain in memory for the actual delete.
            $rowsQuery->chunkById(
                self::READ_CHUNK_SIZE,
                function ($rows) use (&$rowIds, &$batchIds, $witel, $source): void {
                    if ($rows->isEmpty()) {
                        return;
                    }

                    FalloutDeletionHistoryRecorder::record($rows, $witel, $source);

                    foreach ($rows as $row) {
                        $rowIds[] = $row->row_id;

                        if ($row->batch_id !== null) {
                            $batchIds[(string) $row->batch_id] = $row->batch_id;
                        }
                    }
                },
                'fallout_data.row_id',
                'row_id'
            );

            if ($rowIds === []) {
                return 0;
            }

            // Delete exactly the row IDs that were locked and snapshotted.
            $deletedTotal = 0;

            foreach (array_chunk($rowIds, self::DELETE_CHUNK_SIZE) as $idChunk) {
                $deletedTotal += DB::table('fallout_data')
                    ->whereIn('row_id', $idChunk)
                    ->delete();
            }

            if ($deletedTotal !== count($rowIds)) {
                throw new RuntimeException(
                    'Jumlah data yang dihapus tidak cocok dengan snapshot riwayat. Seluruh transaksi dibatalkan.'
                );
            }

            // Keep batch counts accurate with one aggregate subquery per
            // bounded group, inside the same transaction as the delete.
            $affectedBatchIds = array_values($batchIds);

            foreach (array_chunk($affectedBatchIds, self::BATCH_CHUNK_SIZE) as $batchChunk) {
                DB::table('upload_batches')
                    ->whereIn('batch_id', $batchChunk)
                    ->update([
                        'total_data' => DB::raw(
                            '(SELECT COUNT(*) FROM fallout_data WHERE fallout_data.batch_id = upload_batches.batch_id)'
                        ),
                    ]);

                // Empty batches can be removed only after their Fallout rows
                // are gone. Cascading FK behavior then remains predictable.
                DB::table('upload_batches')
                    ->whereIn('batch_id', $batchChunk)
                    ->where('total_data', 0)
                    ->delete();
            }

            return $deletedTotal;
        }, 3);
    }

    private static function assertSourceSchemaReady(): void
    {
        foreach (['fallout_data', 'upload_batches', 'branches'] as $table) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException(
                    "Penghapusan dibatalkan: tabel {$table} belum tersedia pada database aktif."
                );
            }
        }

        $falloutColumns = Schema::getColumnListing('fallout_data');
        $required = [
            'row_id',
            'batch_id',
            'order_id',
            'deskripsi',
            'sto',
            'tanggal',
            'pic',
            'resolved_eskalasi',
            'status',
            'ket',
            'tiket_eskalasi',
        ];
        $missing = array_values(array_diff($required, $falloutColumns));

        if ($missing !== []) {
            throw new RuntimeException(
                'Penghapusan dibatalkan: kolom fallout_data belum lengkap ('
                . implode(', ', $missing)
                . '). Jalankan migration pada database yang benar.'
            );
        }

        $batchColumns = Schema::getColumnListing('upload_batches');
        $missingBatch = array_values(array_diff(
            ['batch_id', 'branch_id', 'total_data'],
            $batchColumns
        ));

        if ($missingBatch !== []) {
            throw new RuntimeException(
                'Penghapusan dibatalkan: struktur upload_batches belum lengkap ('
                . implode(', ', $missingBatch)
                . ').'
            );
        }
    }
}
