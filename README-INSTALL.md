# Riwayat Data Dihapus — satu halaman Arsip & Hapus

Perubahan UI tidak membuat halaman/partial baru: tabel Riwayat Data Dihapus, pencarian, pagination, dan modal Detail sudah tertanam langsung di `resources/views/partials/arsip-hapus.blade.php`.

File pendukung yang memang dibutuhkan agar data audit tersimpan:
- `database/migrations/2026_10_11_190000_create_deleted_fallout_histories_table.php`
- `app/Support/FalloutDeletionHistoryRecorder.php`
- `patches/routes-web-patch.md` berisi dua blok route minimal untuk penghapusan tanggal/tahun, plus event model agar hapus satu baris dari Edit Data juga tercatat.

Setelah menyalin file dan menerapkan dua perubahan route, jalankan `php artisan migrate` lalu `php artisan view:clear`. Tidak ada perubahan UI atau route lain yang diminta.
