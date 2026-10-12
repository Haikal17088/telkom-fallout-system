$ErrorActionPreference = 'Stop'

function Backup-And-Read([string]$Path) {
    if (-not (Test-Path $Path)) {
        throw "File tidak ditemukan: $Path"
    }
    $backup = "$Path.before-tf-navigation.bak"
    if (-not (Test-Path $backup)) {
        Copy-Item $Path $backup -Force
    }
    return Get-Content -Raw -Encoding UTF8 $Path
}

function Write-Utf8([string]$Path, [string]$Content) {
    [System.IO.File]::WriteAllText($Path, $Content, [System.Text.UTF8Encoding]::new($false))
}

function Add-TfNavigation([string]$RelativePath, [string]$Mode) {
    $Path = Join-Path (Get-Location) $RelativePath
    $Content = Backup-And-Read $Path

    if ($Content -notmatch 'tf-navigation\.js') {
        $tag = "`r`n<script src=`"{{ asset('js/tf-navigation.js') }}`" defer></script>`r`n"
        $Content = $Content.Replace('</head>', $tag + '</head>')
    }

    if ($Content -notmatch 'data-tf-nav=') {
        $bodyReplacement = '<body data-tf-nav="' + $Mode + '">'
        $Content = $Content -replace '(?i)<body>', $bodyReplacement
    }

    Write-Utf8 $Path $Content
}

$root = Get-Location
$jsDir = Join-Path $root 'public/js'
New-Item -ItemType Directory -Force -Path $jsDir | Out-Null

$helperSource = Get-Content -Raw -Encoding UTF8 (Join-Path $PSScriptRoot '..\public\js\tf-navigation.js')
Write-Utf8 (Join-Path $jsDir 'tf-navigation.js') $helperSource

Add-TfNavigation 'resources/views/welcome.blade.php' 'public'
Add-TfNavigation 'resources/views/login.blade.php' 'public'
Add-TfNavigation 'resources/views/forgot-password.blade.php' 'public'
Add-TfNavigation 'resources/views/reset-password.blade.php' 'public'
Add-TfNavigation 'resources/views/rekap-fallout.blade.php' 'public'
Add-TfNavigation 'resources/views/dashboard.blade.php' 'dashboard'

# Rekap: viewport desktop paksa diubah menjadi responsive; loader minimum dipangkas.
$rekapPath = Join-Path $root 'resources/views/rekap-fallout.blade.php'
$rekap = Backup-And-Read $rekapPath
$rekap = $rekap -replace '<meta\s+name="viewport"\s+content="width=1280"\s*>', '<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">'
$rekap = $rekap -replace '(?s)(html,\s*body\s*\{\s*height:100%;\s*)min-width:1180px;', '${1}min-width:0;'
$rekap = $rekap -replace 'const minVisible = 1800;', 'const minVisible = 250;'
Write-Utf8 $rekapPath $rekap

Write-Host ""
Write-Host "Patch selesai diterapkan." -ForegroundColor Green
Write-Host "Backup *.before-tf-navigation.bak dibuat untuk setiap file yang disentuh." -ForegroundColor Yellow
Write-Host "Yang sengaja TIDAK disentuh: routes/web.php, routes/api.php, controller, model, import, database." -ForegroundColor Cyan
