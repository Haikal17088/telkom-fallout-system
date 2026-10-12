TELKOM FALLOUT SYSTEM - SPA PERFORMANCE FIX

Tujuan:
- Mempercepat perpindahan menu dashboard.
- Tidak menampilkan loader global dashboard.
- Tidak membuat navigasi menunggu CDN/script partial selesai.
- Menghapus delay buatan 1 detik dari loader partial, tetapi loader partial tetap ada.

Perubahan:
1. public/js/tf-navigation.js
   - Navigasi dashboard tetap fetch HTML dan swap .content.
   - Script partial dijalankan asynchronous di background.
   - Perpindahan UI tidak menunggu Chart.js / SheetJS selesai load.

2. Partial dashboard target:
   - total-rekap.blade.php
   - detail-fallout.blade.php
   - upload-data.blade.php
   - edit-data.blade.php
   - arsip-hapus.blade.php
   - export-data-v2.blade.php

   Hanya pola `setTimeout(hideLoading, 1000);` yang diganti menjadi `requestAnimationFrame(hideLoading);`.

Yang TIDAK disentuh:
- routes/web.php
- routes/api.php
- controller
- model
- import
- database

Cara pakai PowerShell:

powershell -ExecutionPolicy Bypass -File "C:\lokasi\hasil-extract\patches\APPLY-SPA-PERFORMANCE-FIX.ps1" -ProjectRoot "C:\laragon\www\telkom-fallout-system-baru"

Setelah berhasil:

php artisan view:clear

Lalu refresh dengan Ctrl+F5.

Sebelum commit, cek:

git status
git diff --check
git diff --stat

Jangan gunakan git add -A. Stage hanya file aplikasi yang memang berubah.
