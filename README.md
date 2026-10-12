# Perbaikan SPA halaman Rekap Fallout

Paket ini mengganti dua file saja:

1. `resources/views/rekap-fallout.blade.php`
2. `public/js/tf-navigation.js`

## Perubahan

- Filter tabel, tombol status, Reset, Refresh, dan filter Witel grafik memakai navigasi SPA untuk halaman Rekap.
- Saat berpindah filter di dalam `/rekap-fallout`, yang diganti hanya header yang dinamis, statistik, tabel, dan panel grafik. Header navigasi/footer tetap terpasang; tidak melakukan full document reload.
- Fungsi pagination bisa diinisialisasi ulang setelah bagian tabel diperbarui.
- Opsi `Semua Data` bekerja dengan benar bersama pagination; CSV download tetap memakai navigasi browser biasa.
- Pindah tab Tabel/Grafik tetap lokal dan tidak perlu request baru.

## Pemasangan aman

1. Backup dulu dua file lama.
2. Salin file dalam ZIP ke path yang sama pada proyek Laravel.
3. Jalankan:

```powershell
php artisan view:clear
php -l routes/web.php
php artisan test
```

4. Karena file JavaScript memakai cache browser, lakukan `Ctrl+F5` setelah halaman dibuka ulang.
5. Tes Semua Witel (314), Jakarta Timur (30), Jakarta Pusat (284), setiap tombol status, filter STO/tanggal/pencarian, filter Witel Grafik, dan pilihan Semua Data. Angka ini sesuai hasil database yang sebelumnya dikirim; filter aktif akan mengubah jumlah yang terlihat.

## Catatan

- Jangan menyalin `tf-navigation.js` bila file lokal kamu sudah memiliki perubahan lebih baru yang belum ada di file sumber paket ini. Bandingkan terlebih dahulu.
- Patch ini tidak mengubah database, route, atau data Fallout. Bila server tetap lambat sesudah navigasi partial, optimasi berikutnya adalah memindahkan filter Collection PHP menjadi query SQL terfilter; itu langkah terpisah agar perilaku status tidak berubah tanpa verifikasi.
