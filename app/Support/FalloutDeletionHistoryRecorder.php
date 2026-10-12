<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class FalloutDeletionHistoryRecorder
{
    /** Store a snapshot for each deleted Fallout row. */
    public static function record(iterable $rows, string $witelSlug, string $source): void
    {
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
                'deleted_by' => $user?->getAuthIdentifier(),
                'deleted_by_name' => Str::limit((string) $deletedByName, 191, ''),
                'delete_source' => Str::limit($source, 40, ''),
                'deleted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Small batches keep parameter counts reasonable for PostgreSQL.
        foreach (array_chunk($payload, 300) as $chunk) {
            DB::table('deleted_fallout_histories')->insert($chunk);
        }
    }
}
