<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

final class FalloutDeletionHistoryRecorder
{
    private static bool $schemaReady = false;

    private const REQUIRED_COLUMNS = [
        'original_row_id',
        'witel_slug',
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
        'deleted_by',
        'deleted_by_name',
        'delete_source',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Fail before deleting source rows when the audit table is incomplete.
     * This keeps a missing migration from turning a permanent delete into
     * an unaudited operation.
     */
    public static function assertReady(): void
    {
        if (self::$schemaReady) {
            return;
        }

        if (!Schema::hasTable('deleted_fallout_histories')) {
            throw new RuntimeException(
                'Penghapusan dibatalkan: tabel deleted_fallout_histories belum tersedia. Jalankan migration terlebih dahulu.'
            );
        }

        $available = Schema::getColumnListing('deleted_fallout_histories');
        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, $available));

        if ($missing !== []) {
            throw new RuntimeException(
                'Penghapusan dibatalkan: kolom riwayat belum lengkap ('
                . implode(', ', $missing)
                . '). Jalankan php artisan migrate --force pada database yang benar.'
            );
        }

        self::$schemaReady = true;
    }

    /** Store a snapshot for each source row before the original is deleted. */
    public static function record(iterable $rows, string $witelSlug, string $source): void
    {
        self::assertReady();

        $user = Auth::user();
        $deletedByName = $user
            ? ($user->username ?? $user->name ?? $user->email ?? 'System')
            : 'System';
        $now = now();
        $payload = [];

        foreach ($rows as $row) {
            $payload[] = [
                'original_row_id' => $row->row_id ?? null,
                'witel_slug' => Str::lower(trim($witelSlug)),
                'batch_id' => $row->batch_id ?? null,
                'order_id' => $row->order_id ?? null,
                'deskripsi' => $row->deskripsi ?? null,
                'sto' => $row->sto ?? null,
                'tanggal' => $row->tanggal ?? null,
                'pic' => $row->pic ?? null,
                'resolved_eskalasi' => $row->resolved_eskalasi ?? null,
                'status' => $row->status ?? null,
                'ket' => $row->ket ?? null,
                'tiket_eskalasi' => $row->tiket_eskalasi ?? null,
                'deleted_by' => $user?->getAuthIdentifier(),
                'deleted_by_name' => Str::limit((string) $deletedByName, 191, ''),
                'delete_source' => Str::limit($source, 40, ''),
                'deleted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($payload, 250) as $chunk) {
            DB::table('deleted_fallout_histories')->insert($chunk);
        }
    }
}
