# Tambahan riwayat tanpa mengganti seluruh routes/web.php

UI Riwayat Data Dihapus sudah menyatu langsung di `resources/views/partials/arsip-hapus.blade.php`.
Untuk membuat riwayat benar-benar tersimpan, migration dan recorder di paket ini perlu dipasang, lalu ubah hanya dua blok dalam `routes/web.php`.

## A. Route `arsip.hapus.tanggal`

Di route `Route::delete('/dashboard/{witel}/arsip-hapus/tanggal', ...)`, cari bagian yang sekarang mengambil `$batchIds` lewat `pluck('batch_id')` lalu menghapus data dengan `whereDate(...)->delete()`.
Ganti dua bagian itu dengan kode berikut. Biarkan loop sinkronisasi batch setelahnya tetap seperti semula.

```php
[$deleted, $batchIds] = \Illuminate\Support\Facades\DB::transaction(
    function () use ($witel, $request) {
        $rowsToDelete = \App\Models\FalloutData::forWitel($witel)
            ->whereDate('tanggal', $request->tanggal)
            ->lockForUpdate()
            ->get();

        $batchIds = $rowsToDelete->pluck('batch_id')->filter()->unique()->values();

        \App\Support\FalloutDeletionHistoryRecorder::record(
            $rowsToDelete,
            $witel,
            'arsip_tanggal'
        );

        $deleted = \App\Models\FalloutData::forWitel($witel)
            ->whereDate('tanggal', $request->tanggal)
            ->delete();

        return [$deleted, $batchIds];
    }
);
```

## B. Route `arsip.hapus.tahun`

Di route `Route::delete('/dashboard/{witel}/arsip-hapus/tahun', ...)`, lakukan hal yang sama pada query `whereYear(...)`.

```php
[$deleted, $batchIds] = \Illuminate\Support\Facades\DB::transaction(
    function () use ($witel, $request) {
        $rowsToDelete = \App\Models\FalloutData::forWitel($witel)
            ->whereYear('tanggal', $request->tahun)
            ->lockForUpdate()
            ->get();

        $batchIds = $rowsToDelete->pluck('batch_id')->filter()->unique()->values();

        \App\Support\FalloutDeletionHistoryRecorder::record(
            $rowsToDelete,
            $witel,
            'arsip_tahun'
        );

        $deleted = \App\Models\FalloutData::forWitel($witel)
            ->whereYear('tanggal', $request->tahun)
            ->delete();

        return [$deleted, $batchIds];
    }
);
```

Jangan mengganti seluruh `routes/web.php`, dan jangan menghapus loop sinkronisasi `upload_batches` yang berjalan setelahnya.

## Pasang
1. Ganti `resources/views/partials/arsip-hapus.blade.php` dengan file Blade lengkap dari paket.
2. Salin migration dan recorder ke folder yang sama dengan struktur di paket.
3. Terapkan dua penggantian kecil pada route tanggal dan tahun di atas.
4. Jalankan `php artisan migrate`, kemudian `php artisan view:clear`.
5. Tes hapus per tanggal/tahun dengan data uji terlebih dahulu.

Riwayat hanya mencatat penghapusan yang dilakukan setelah fitur ini aktif; data yang sudah terhapus sebelum migrasi tidak dapat dipulihkan dari `fallout_data`.

## C. Agar hapus satu baris dari Edit Data juga masuk ke riwayat

Karena video temanmu menampilkan arsip satu data yang dihapus, tambahkan event berikut pada `app/Models/FalloutData.php`. Jika model tersebut **sudah memiliki `booted()`**, gabungkan isi `static::deleted(...)` ke method yang ada; jangan membuat `booted()` kedua.

```php
protected static function booted(): void
{
    static::deleted(function (self $row) {
        $batch = $row->batch;
        $branch = $batch ? $batch->branch : null;
        $witelSlug = $branch ? ($branch->kode_cabang ?? null) : null;

        if (!$witelSlug) {
            return;
        }

        \App\Support\FalloutDeletionHistoryRecorder::record(
            [$row],
            (string) $witelSlug,
            'edit_data'
        );
    });
}
```

Event ini mencatat penghapusan satu baris melalui Eloquent (`$row->delete()`). Dua route mass-delete per tanggal dan per tahun tetap harus memakai blok A/B, karena query `->delete()` massal tidak memicu event model untuk setiap baris.

