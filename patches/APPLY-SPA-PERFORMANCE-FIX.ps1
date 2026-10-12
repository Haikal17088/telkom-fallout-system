param(
    [string]$ProjectRoot = "C:\laragon\www\telkom-fallout-system-baru"
)

$ErrorActionPreference = 'Stop'

function Backup-File([string]$Path) {
    if (-not (Test-Path -LiteralPath $Path)) {
        throw "File tidak ditemukan: $Path"
    }

    $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    $backup = "$Path.before-spa-performance-$stamp.bak"
    Copy-Item -LiteralPath $Path -Destination $backup -Force
    return $backup
}

function Read-Utf8([string]$Path) {
    return [System.IO.File]::ReadAllText($Path, [System.Text.UTF8Encoding]::new($false))
}

function Write-Utf8([string]$Path, [string]$Content) {
    [System.IO.File]::WriteAllText($Path, $Content, [System.Text.UTF8Encoding]::new($false))
}

if (-not (Test-Path -LiteralPath $ProjectRoot)) {
    throw "Project root tidak ditemukan: $ProjectRoot"
}

$navigationPath = Join-Path $ProjectRoot 'public\js\tf-navigation.js'
$navigationSource = Join-Path $PSScriptRoot '..\public\js\tf-navigation.js'

if (-not (Test-Path -LiteralPath $navigationSource)) {
    throw "File patch navigation tidak ditemukan: $navigationSource"
}

# -----------------------------------------------------------------------------
# 1. Ganti helper SPA dengan versi yang tidak memblokir perpindahan dashboard
#    hanya karena CDN/script partial belum selesai.
# -----------------------------------------------------------------------------
$navigationBackup = Backup-File $navigationPath
Copy-Item -LiteralPath $navigationSource -Destination $navigationPath -Force

Write-Host "Navigation diperbarui: public/js/tf-navigation.js" -ForegroundColor Green
Write-Host "Backup: $navigationBackup" -ForegroundColor DarkGray

# -----------------------------------------------------------------------------
# 2. Hapus delay buatan 1 detik pada loader partial.
#    Loader partial tetap ada, tetapi tidak dipaksa menunggu 1 detik.
# -----------------------------------------------------------------------------
$partialRelativePaths = @(
    'resources\views\partials\total-rekap.blade.php',
    'resources\views\partials\detail-fallout.blade.php',
    'resources\views\partials\upload-data.blade.php',
    'resources\views\partials\edit-data.blade.php',
    'resources\views\partials\arsip-hapus.blade.php',
    'resources\views\partials\export-data-v2.blade.php'
)

$changed = @()
$notFound = @()

foreach ($relative in $partialRelativePaths) {
    $path = Join-Path $ProjectRoot $relative

    if (-not (Test-Path -LiteralPath $path)) {
        $notFound += $relative
        continue
    }

    $content = Read-Utf8 $path

    # Hanya target delay buatan 1 detik untuk hideLoading.
    $updated = [regex]::Replace(
        $content,
        'setTimeout\(\s*hideLoading\s*,\s*1000\s*\)\s*;',
        'requestAnimationFrame(hideLoading);'
    )

    if ($updated -ne $content) {
        $backup = Backup-File $path
        Write-Utf8 $path $updated
        $changed += "$relative | backup: $backup"
    }
}

if ($changed.Count -gt 0) {
    Write-Host "" 
    Write-Host "Delay loader partial yang 1 detik berhasil dihapus dari:" -ForegroundColor Green
    foreach ($item in $changed) {
        Write-Host "  $item"
    }
}
else {
    Write-Host "" 
    Write-Host "Tidak ditemukan pola delay loader 1 detik pada partial target." -ForegroundColor Yellow
}

if ($notFound.Count -gt 0) {
    Write-Host "" 
    Write-Host "Partial yang tidak ditemukan (tidak diubah):" -ForegroundColor Yellow
    foreach ($item in $notFound) {
        Write-Host "  $item"
    }
}

Write-Host "" 
Write-Host "SPA performance fix selesai." -ForegroundColor Cyan
Write-Host "Yang diubah hanya helper navigasi + delay 1 detik pada partial target." -ForegroundColor Cyan
Write-Host "Route, controller, model, import, dan database tidak disentuh." -ForegroundColor Cyan
