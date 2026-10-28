@php

    /*
    |--------------------------------------------------------------------------
    | DATA DARI ROUTE — FILTER TABEL & GRAFIK TERPISAH
    |--------------------------------------------------------------------------
    | tableWitel    -> hanya mengontrol tabel + statistik tabel
    | graphWitel    -> hanya mengontrol grafik + statistik grafik
    | filterTanggal / filterSto / filterStatus -> hanya untuk tabel
    */

    $witelLabel = $witelLabel ?? [
        'semua'  => 'Semua Witel',
        'jaktim' => 'Witel Jakarta Timur',
        'jakpus' => 'Witel Jakarta Pusat',
        'jaksel' => 'Witel Jakarta Selatan',
    ];

    $routeWitel = $witel ?? 'semua';

    if (!array_key_exists($routeWitel, $witelLabel)) {
        $routeWitel = 'semua';
    }

    $activeView = request()->query('view', $activeView ?? 'tabel');

    if (!in_array($activeView, ['tabel', 'grafik'], true)) {
        $activeView = 'tabel';
    }

    $tableWitel = $tableWitel ?? $routeWitel;
    $graphWitel = $graphWitel ?? $routeWitel;

    if (!array_key_exists($tableWitel, $witelLabel)) {
        $tableWitel = $routeWitel;
    }

    if (!array_key_exists($graphWitel, $witelLabel)) {
        $graphWitel = $routeWitel;
    }

    $filterTanggal = trim((string) request()->query('tanggal', $filterTanggal ?? ''));

    $filterSearch = $filterSearch ?? trim((string) request()->query('q', ''));
    $filterPeriode = $filterPeriode ?? strtolower(trim((string) request()->query('periode', 'harian')));
    if (!in_array($filterPeriode, ['harian', 'mingguan', 'bulanan', 'tahunan'], true)) {
        $filterPeriode = 'harian';
    }

    $filterSto = strtolower(trim((string) request()->query('sto', $filterSto ?? 'semua')));
    if ($filterSto === '') {
        $filterSto = 'semua';
    }

    $filterStatus = strtolower(trim((string) request()->query('status', $filterStatus ?? 'semua')));
    if ($filterStatus === '') {
        $filterStatus = 'semua';
    }

    $statusLabel = $statusLabel ?? [
        'semua'        => 'Semua',
        'resolved'     => 'RESOLVED',
        'cancel'       => 'Cancel',
        'eskalasi_dit' => 'Eskalasi DIT',
        'close'        => 'Close',
    ];

    if (!array_key_exists($filterStatus, $statusLabel)) {
        $filterStatus = 'semua';
    }

    $filtered = ($filtered ?? collect())->values();
    $total = $total ?? $filtered->count();
    $resolved = $resolved ?? 0;
    $eskalasi = $eskalasi ?? 0;
    $stoAktif = $stoAktif ?? 0;
    $picText = $picText ?? '-';

    $chartRows = $chartRows ?? collect();
    $chartTotal = $chartTotal ?? $chartRows->count();
    $chartResolved = $chartResolved ?? 0;
    $chartEskalasi = $chartEskalasi ?? 0;
    $chartStoAktif = $chartStoAktif ?? 0;
    $chartPicList = $chartPicList ?? collect();
    $perSto = $perSto ?? collect();
    $maxSto = max((int) ($maxSto ?? 0), 1);
    $processCount = $processCount ?? 0;
    $completedCount = $completedCount ?? 0;
    $stoOptions = $stoOptions ?? collect();

    $rekapAction = $routeWitel === 'semua'
        ? route('rekap-fallout')
        : route('rekap-fallout.witel', ['witel' => $routeWitel]);

    $tableAction = $rekapAction;
    $graphAction = $rekapAction;

@endphp

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

<title>
    Telkom Fallout System — Data Rekap Fallout
</title>

<link rel="icon" type="image/png" href="{{ asset('images/image.png') }}">

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>


<link
    href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>


<style>

/* ==========================================================
   ROOT
   ========================================================== */

:root{

    --maroon-0:#3A0410;
    --maroon-1:#5C0A1B;
    --maroon-2:#8A0F26;

    --red:#C8102E;

    --green:#1F8A4C;

    --green-dark:#2F7058;

    --blue:#2563C8;

    --pink-soft:#E8A0A0;

    --paper:#FBF9F7;

    --ink:#20161A;

    --ink-lo:#7A6B6F;

    --line:#E7DEDD;

    --bg:#F4F0EE;

}


/* ==========================================================
   RESET
   ========================================================== */

*{
    box-sizing:border-box;
    margin:0;
    padding:0;
}


html,
body{
    height:100%;
    min-width:0;
}


body.rf-detail-open,
body.rf-order-open{
    overflow:hidden;
}


body{

    font-family:'Inter',sans-serif;

    background:var(--bg);

    color:var(--ink);

    min-height:100vh;

}



/* ==========================================================
   TOPBAR
   ========================================================== */

.topbar{

    background:#7A0C0C;

    padding:16px 32px;

    display:flex;

    align-items:center;

    justify-content:space-between;

}


.topbar-brand{

    display:flex;

    align-items:center;

    gap:12px;

}


.topbar-brand img{

    height:32px;

    width:auto;

}


.topbar-brand span{

    color:#fff;

    font-family:
        'Space Grotesk',
        sans-serif;

    font-size:15px;

    font-weight:600;

}


.topbar-actions{

    display:flex;

    align-items:center;

    gap:10px;

}


.btn-ghost,
.btn-solid{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    padding:9px 18px;

    border-radius:9px;

    font-size:13px;

    font-weight:700;

    text-decoration:none;

    transition:
        background .15s ease,
        color .15s ease,
        border-color .15s ease;

}


.btn-ghost{

    background:
        rgba(255,255,255,0.1);

    color:#fff;

    border:
        1px solid
        rgba(255,255,255,0.2);

}


.btn-ghost:hover{

    background:
        rgba(255,255,255,0.18);

}


.btn-solid{

    background:#fff;

    color:var(--red);

    border:
        1px solid #fff;

}


.btn-solid:hover{

    background:#F7F3F1;

}



/* ==========================================================
   WRAP
   ========================================================== */

.wrap{

    width:100%;

    max-width:1280px;

    margin:0 auto;

    padding:32px;

}



/* ==========================================================
   PAGE HEADER
   ========================================================== */

.page-head{

    display:flex;

    align-items:flex-start;

    justify-content:space-between;

    gap:20px;

    margin-bottom:24px;

}


.page-head-left{

    min-width:0;

}


.page-head h1{

    font-family:
        'Space Grotesk',
        sans-serif;

    font-size:24px;

    font-weight:600;

    color:var(--red);

    line-height:1.2;

}


.page-head p{

    margin-top:7px;

    font-size:13px;

    line-height:1.6;

    color:var(--ink-lo);

}


.btn-download{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:8px;

    padding:12px 20px;

    border-radius:10px;

    background:var(--maroon-0);

    color:#fff;

    font-size:13.5px;

    font-weight:600;

    text-decoration:none;

    white-space:nowrap;

}


.btn-download:hover{

    background:var(--maroon-1);

}



/* ==========================================================
   STATISTICS
   ========================================================== */

.stats{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:14px;

    margin-bottom:22px;

}


.stat-card{

    background:#fff;

    border:
        1px solid
        var(--line);

    border-radius:12px;

    padding:16px 18px;

    display:flex;

    align-items:center;

    gap:12px;

}


.stat-bar{

    width:4px;

    height:34px;

    border-radius:4px;

    flex:none;

}


.stat-card .lbl{

    font-size:11.5px;

    color:var(--ink-lo);

    font-weight:600;

}


.stat-card .val{

    margin-top:2px;

    font-family:
        'Space Grotesk',
        sans-serif;

    font-size:20px;

    font-weight:700;

    color:var(--ink);

}



/* ==========================================================
   TABS
   ========================================================== */

.tabs{

    display:flex;

    align-items:center;

    gap:6px;

    margin-bottom:18px;

}


.tab-btn{

    padding:10px 18px;

    border-radius:9px;

    border:
        1.4px solid
        var(--line);

    background:#fff;

    color:var(--ink-lo);

    font-family:
        'Inter',
        sans-serif;

    font-size:13px;

    font-weight:600;

    cursor:pointer;

    transition:
        background .15s ease,
        color .15s ease,
        border-color .15s ease;

}


.tab-btn:hover{

    border-color:var(--red);

    color:var(--red);

}


.tab-btn.active{

    background:
        linear-gradient(
            120deg,
            var(--maroon-1),
            var(--maroon-0)
        );

    color:#fff;

    border-color:
        var(--maroon-0);

}



/* ==========================================================
   FILTER TABEL
   ========================================================== */

.filter-card{

    background:#fff;

    border:
        1px solid
        var(--line);

    border-radius:14px;

    padding:20px 22px;

    margin-bottom:18px;

}


.filter-title{

    display:flex;

    align-items:center;

    gap:8px;

    margin-bottom:16px;

    font-size:13.5px;

    font-weight:700;

}


.filter-title .bar{

    width:3px;

    height:16px;

    border-radius:3px;

    background:var(--red);

}


.filter-row{

    display:flex;

    align-items:center;

    flex-wrap:wrap;

    gap:12px;

}


.filter-row select,
.filter-row input[type="date"]{

    min-width:150px;

    padding:10px 14px;

    border:
        1.3px solid
        var(--line);

    border-radius:9px;

    background:#fff;

    color:var(--ink);

    font-family:
        'Inter',
        sans-serif;

    font-size:13px;

    outline:none;

    cursor:pointer;

}


.filter-row select:focus,
.filter-row input[type="date"]:focus{

    border-color:var(--red);

}


.status-pills{

    display:flex;

    align-items:center;

    flex-wrap:wrap;

    gap:8px;

    margin-top:14px;

}


.pill{

    padding:9px 18px;

    border:
        1.4px solid
        var(--line);

    border-radius:999px;

    background:#fff;

    color:var(--ink-lo);

    font-family:
        'Inter',
        sans-serif;

    font-size:12.5px;

    font-weight:700;

    cursor:pointer;

    transition:
        background .15s ease,
        color .15s ease,
        border-color .15s ease,
        box-shadow .15s ease;

}


.pill:hover{

    border-color:var(--red);

    color:var(--red);

    background:
        rgba(200,16,46,0.04);

}


.pill.active{

    background:
        linear-gradient(
            120deg,
            var(--maroon-1),
            var(--maroon-0)
        );

    color:#fff;

    border-color:
        var(--maroon-0);

    box-shadow:
        0 8px 18px -8px
        rgba(58,4,16,0.5);

}


.result-count{

    margin-top:14px;

    padding-top:12px;

    border-top:
        1px solid
        var(--line);

    color:var(--ink-lo);

    font-size:12px;

}


.result-count b{

    color:var(--red);

}



/* ==========================================================
   TABLE
   ========================================================== */

.table-wrap{

    width:100%;

    overflow-x:auto;

}


table{

    width:100%;

    min-width:1120px;

    border:
        1px solid
        var(--line);

    border-radius:14px;

    border-collapse:collapse;

    background:#fff;

    overflow:hidden;

}


thead tr{

    background:#7A0C0C;

}


th{

    padding:13px 16px;

    text-align:left;

    color:#fff;

    font-size:11px;

    font-weight:700;

    white-space:nowrap;

    letter-spacing:.02em;

}


td{

    padding:13px 16px;

    color:var(--ink);

    font-size:12.5px;

    vertical-align:top;

    border-bottom:
        1px solid
        var(--line);

}


tbody tr:nth-child(even){

    background:#FBF7F6;

}


tbody tr:hover{

    background:#F9EFEE;

}


.order-id{

    color:var(--red);

    font-family:monospace;

    font-size:11.5px;

    font-weight:700;

}


.sto-badge{

    display:inline-block;

    padding:3px 10px;

    border-radius:999px;

    background:
        rgba(200,16,46,0.08);

    color:var(--red);

    font-size:10.5px;

    font-weight:700;

}


.status-resolved{

    color:var(--green);

    font-weight:700;

}


.status-cancel{

    color:var(--ink-lo);

    font-weight:700;

}


.status-eskalasi_dit{

    color:var(--red);

    font-weight:700;

}


.status-close{

    color:var(--blue);

    font-weight:700;

}


.data-empty{

    padding:35px 15px;

    text-align:center;

    color:var(--ink-lo);

}



/* ==========================================================
   GRAPH FILTER
   ========================================================== */

.graph-filter{

    background:#fff;

    border:
        1px solid
        var(--line);

    border-radius:14px;

    padding:18px 20px;

    margin-bottom:16px;

}


.graph-filter-title{

    margin-bottom:12px;

    color:var(--ink);

    font-size:13.5px;

    font-weight:700;

}


.graph-filter select{

    min-width:240px;

    padding:10px 14px;

    border:
        1.3px solid
        var(--line);

    border-radius:9px;

    background:#fff;

    color:var(--ink);

    font-family:
        'Inter',
        sans-serif;

    font-size:13px;

    cursor:pointer;

    outline:none;

}


.graph-filter select:focus{

    border-color:var(--red);

}



/* ==========================================================
   GRAPH CARD
   ========================================================== */

.graf-card{

    background:#fff;

    border:
        1px solid
        var(--line);

    border-radius:14px;

    padding:22px;

    overflow:hidden;

}


.graf-card h3{

    margin-bottom:7px;

    color:var(--red);

    font-family:
        'Space Grotesk',
        sans-serif;

    font-size:15px;

    font-weight:700;

}


.graf-subtitle{

    margin-bottom:17px;

    color:var(--ink-lo);

    font-size:11.5px;

}



/* ==========================================================
   DONUT LAYOUT
   ========================================================== */

.donut-layout{

    display:grid;

    grid-template-columns:
        250px 1fr;

    align-items:center;

    gap:40px;

}


.donut-wrap{

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;

}


.donut-chart{

    width:190px;

    height:190px;

    border-radius:50%;

    position:relative;

    display:grid;

    place-items:center;

}


.donut-hole{

    width:108px;

    height:108px;

    border-radius:50%;

    background:#fff;

    display:flex;

    flex-direction:column;

    justify-content:center;

    align-items:center;

}


.donut-hole strong{

    color:var(--ink);

    font-family:
        'Space Grotesk',
        sans-serif;

    font-size:28px;

    line-height:1;

}


.donut-hole span{

    margin-top:5px;

    color:var(--ink-lo);

    font-size:11px;

}



/* ==========================================================
   DONUT LEGEND
   ========================================================== */

.donut-legend{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:18px;

    flex-wrap:wrap;

    margin-top:12px;

}


.donut-legend-item{

    display:flex;

    align-items:center;

    gap:7px;

    color:var(--ink-lo);

    font-size:11px;

}


.donut-legend-dot{

    width:9px;

    height:9px;

    border-radius:50%;

    display:inline-block;

}



/* ==========================================================
   DONUT INDICATOR
   ========================================================== */

.donut-indicators{

    display:flex;

    flex-direction:column;

    gap:12px;

}


.donut-indicator{

    min-height:58px;

    padding:15px 18px;

    border:
        1px solid
        #F0E8E7;

    border-radius:13px;

    background:#FBFAFA;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

}


.donut-indicator-left{

    display:flex;

    align-items:center;

    gap:10px;

    color:#5E5558;

    font-size:13px;

}


.indicator-dot{

    width:11px;

    height:11px;

    border-radius:50%;

    display:inline-block;

    flex:none;

}


.donut-indicator-value{

    font-size:17px;

    font-weight:700;

}



/* ==========================================================
   BAR CHART
   ========================================================== */

.chart-scroll{

    width:100%;

    overflow-x:auto;

}


.chart-svg{

    width:100%;

    min-width:620px;

    overflow:visible;

}



/* ==========================================================
   STATUS PENYELESAIAN
   ========================================================== */

.status-resolution-layout{

    display:grid;

    grid-template-columns:
        250px 1fr;

    align-items:center;

    gap:45px;

}


.status-resolution-chart{

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;

}


.status-resolution-donut{

    width:190px;

    height:190px;

    border-radius:50%;

    display:grid;

    place-items:center;

}


.status-resolution-donut-hole{

    width:108px;

    height:108px;

    border-radius:50%;

    background:#fff;

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;

}


.status-resolution-donut-hole strong{

    font-family:
        'Space Grotesk',
        sans-serif;

    font-size:28px;

    line-height:1;

    color:var(--ink);

}


.status-resolution-donut-hole span{

    margin-top:5px;

    font-size:11px;

    color:var(--ink-lo);

}


.status-resolution-legend{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:18px;

    flex-wrap:wrap;

    margin-top:12px;

}


.status-resolution-legend-item{

    display:flex;

    align-items:center;

    gap:7px;

    font-size:11px;

    color:var(--ink-lo);

}


.status-resolution-legend-dot{

    width:9px;

    height:9px;

    border-radius:50%;

    display:inline-block;

}


.status-resolution-indicators{

    display:flex;

    flex-direction:column;

    gap:12px;

}


.status-resolution-indicator{

    min-height:58px;

    padding:15px 18px;

    border:
        1px solid
        #F0E8E7;

    border-radius:13px;

    background:#FBFAFA;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

}


.status-resolution-indicator-left{

    display:flex;

    align-items:center;

    gap:10px;

    color:#5E5558;

    font-size:13px;

}


.status-resolution-indicator-dot{

    width:11px;

    height:11px;

    border-radius:50%;

    display:inline-block;

    flex:none;

}


.status-resolution-indicator-value{

    font-size:17px;

    font-weight:700;

}



/* ==========================================================
   FOOTER
   ========================================================== */

footer{

    margin-top:24px;

    padding:32px;

    border-top:
        1px solid
        var(--line);

    color:var(--ink-lo);

    font-size:12px;

    text-align:center;

}


footer .brand{

    margin-bottom:4px;

    color:var(--ink);

    font-weight:700;

}


/* ==========================================================
   ORDER ID LENGKAP MODAL
   ========================================================== */

.rf-order-modal{
    position:fixed;
    inset:0;
    z-index:2147482990;
    display:none;
    align-items:center;
    justify-content:center;
    padding:16px;
}

.rf-order-modal.is-open{
    display:flex;
}

.rf-order-backdrop{
    position:absolute;
    inset:0;
    background:rgba(25,10,14,.52);
    backdrop-filter:blur(2px);
    -webkit-backdrop-filter:blur(2px);
}

.rf-order-dialog{
    position:relative;
    z-index:1;
    width:min(520px,94vw);
    background:#fff;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 28px 80px rgba(35,8,14,.28);
}

.rf-order-head{
    min-height:56px;
    padding:0 18px 0 24px;
    display:flex;
    align-items:center;
    justify-content:space-between;
}

.rf-order-title{
    font-family:'Space Grotesk',sans-serif;
    color:#263247;
    font-size:17px;
    font-weight:700;
}

.rf-order-close{
    width:34px;
    height:34px;
    border:0;
    border-radius:8px;
    background:transparent;
    color:#99A4B7;
    font-size:28px;
    line-height:1;
    cursor:pointer;
}

.rf-order-close:hover{
    background:#F4F5F7;
    color:#6F7887;
}

.rf-order-body{
    padding:8px 24px 24px;
}

.rf-order-box{
    padding:16px;
    border-radius:13px;
    background:#F7F8FA;
    color:#4A5669;
    font-size:13px;
    line-height:1.65;
    word-break:break-word;
    white-space:pre-wrap;
    min-height:84px;
}

.rf-order-copy-btn{
    width:100%;
    margin-top:16px;
    padding:12px 16px;
    border:0;
    border-radius:12px;
    background:#690000;
    color:#fff;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    transition:background .15s ease,transform .05s ease;
}

.rf-order-copy-btn:hover{
    background:#520000;
}

.rf-order-copy-btn:active{
    transform:translateY(1px);
}

@media(max-width:700px){
    .rf-order-dialog{
        width:100%;
        border-radius:15px;
    }

    .rf-order-body{
        padding-left:18px;
        padding-right:18px;
    }
}


/* ==========================================================
   DETAIL FALLOUT MODAL
   ========================================================== */

.rf-detail-modal{
    position:fixed;
    inset:0;
    z-index:2147483000;
    display:none;
    align-items:center;
    justify-content:center;
    padding:8px;
}

.rf-detail-modal.is-open{
    display:flex;
}

.rf-detail-backdrop{
    position:absolute;
    inset:0;
    background:rgba(30,10,14,.52);
    backdrop-filter:blur(2px);
    -webkit-backdrop-filter:blur(2px);
}

.rf-detail-dialog{
    position:relative;
    z-index:1;
    width:min(520px, 96vw);
    max-height:96vh;
    display:flex;
    flex-direction:column;
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 28px 80px rgba(35,8,14,.30);
}

.rf-detail-head{
    min-height:53px;
    padding:0 18px 0 24px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    background:#690000;
    color:#fff;
    flex:none;
}

.rf-detail-title{
    font-family:'Space Grotesk',sans-serif;
    font-size:15px;
    font-weight:700;
}

.rf-detail-close{
    width:34px;
    height:34px;
    border:0;
    background:transparent;
    color:#fff;
    border-radius:8px;
    display:grid;
    place-items:center;
    cursor:pointer;
    font-size:30px;
    line-height:1;
}

.rf-detail-close:hover{
    background:rgba(255,255,255,.12);
}

.rf-detail-body{
    overflow:auto;
    padding:7px 20px 5px;
}

.rf-detail-row{
    display:grid;
    grid-template-columns:145px minmax(0,1fr);
    gap:18px;
    padding:13px 8px;
    border-bottom:1px solid #F0EAEB;
    align-items:start;
}

.rf-detail-label{
    color:#8A97AD;
    font-size:12px;
    line-height:1.45;
}

.rf-detail-value{
    color:#1D2330;
    font-size:13px;
    line-height:1.5;
    font-weight:500;
    text-align:right;
    word-break:break-word;
}

.rf-detail-section{
    padding:14px 8px 4px;
}

.rf-detail-section-label{
    color:#8A97AD;
    font-size:12px;
    margin-bottom:8px;
}

.rf-detail-box{
    padding:12px;
    border-radius:11px;
    background:#F6F7F8;
    color:#27405F;
    font-size:12px;
    line-height:1.55;
    word-break:break-word;
    white-space:pre-wrap;
}

.rf-detail-box.ket{
    background:#FFF0F2;
    color:#8D1630;
}

.rf-detail-footer{
    display:flex;
    justify-content:flex-end;
    padding:14px 20px 16px;
    border-top:1px solid #EEE7E7;
    background:#fff;
    flex:none;
}

.rf-detail-close-btn{
    min-width:78px;
    padding:10px 17px;
    border:0;
    border-radius:12px;
    background:#690000;
    color:#fff;
    font-family:'Inter',sans-serif;
    font-size:12px;
    font-weight:700;
    cursor:pointer;
}

.rf-detail-close-btn:hover{
    background:#500000;
}

.rf-detail-icon{
    width:26px;
    height:26px;
    border:0;
    padding:0;
    background:transparent;
    color:#B10E2E;
    display:inline-grid;
    place-items:center;
    border-radius:7px;
    cursor:pointer;
    flex:none;
}

.rf-detail-icon:hover{
    background:rgba(177,14,46,.08);
}

.rf-order-cell{
    display:flex;
    align-items:center;
    gap:7px;
    min-width:0;
}

@media(max-width:700px){
    .rf-detail-dialog{
        width:100%;
        max-height:96vh;
        border-radius:16px;
    }

    .rf-detail-row{
        grid-template-columns:112px minmax(0,1fr);
        gap:12px;
    }

    .rf-detail-value{
        font-size:12px;
    }
}

/* ==========================================================
   RESPONSIVE
   ========================================================== */

@media(max-width:1000px){

    .donut-layout{

        grid-template-columns:1fr;

        gap:25px;

    }


    .status-resolution-layout{

        grid-template-columns:1fr;

        gap:25px;

    }

}


@media(max-width:850px){

    .stats{

        grid-template-columns:
            repeat(2,1fr);

    }

}


@media(max-width:700px){

    html,
    body{

        min-width:0;

    }


    .stats{

        grid-template-columns:1fr;

    }


    .page-head{

        flex-direction:column;

    }

}


/* ==========================================================
   FILTER UX + CLEANER REKAP PRESENTATION
   ========================================================== */
body{background:#F5F6F8;}
.topbar{position:sticky;top:0;z-index:1000;box-shadow:0 5px 18px rgba(58,4,16,.10);}
.wrap{max-width:1400px;padding:28px clamp(16px,2.5vw,34px) 36px;}
.page-head{align-items:center;}
.stats .stat-card,.filter-card,.graf-card{box-shadow:0 5px 18px rgba(35,20,24,.035);}
.stat-card{min-height:78px;}
.filter-row-labeled{align-items:flex-end;gap:12px;}
.filter-field{display:flex;flex:1 1 155px;min-width:150px;flex-direction:column;gap:7px;}
.filter-field label{font-size:11px;font-weight:700;color:#6F6266;}
.filter-field select,.filter-field input{width:100%;min-width:0;min-height:42px;padding:10px 12px;border:1px solid var(--line);border-radius:9px;background:#fff;color:var(--ink);font:13px 'Inter',sans-serif;outline:none;}
.filter-field select:focus,.filter-field input:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(200,16,46,.07);}
.filter-search-field{flex:1.5 1 220px;}
.filter-actions{display:flex;align-items:center;gap:8px;flex:0 0 auto;}
.filter-actions .btn-download{border:0;cursor:pointer;font-family:inherit;}
.filter-reset,.btn-refresh{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:41px;padding:10px 14px;border:1px solid var(--line);border-radius:9px;background:#fff;color:#69595E;text-decoration:none;font-size:12px;font-weight:700;white-space:nowrap;}
.filter-reset:hover,.btn-refresh:hover{border-color:var(--red);color:var(--red);}
.filter-hint{margin-top:12px;color:var(--ink-lo);font-size:11.5px;line-height:1.5;}
.btn-refresh{background:#fff;color:#7A0C0C;}
.page-head-actions{display:flex;align-items:center;gap:9px;flex-wrap:wrap;}
.rf-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 2px 4px;color:var(--ink-lo);font-size:12px;}
.rf-pagination-controls{display:flex;align-items:center;gap:8px;}
.rf-pagination select,.rf-pagination button{min-height:34px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--ink);padding:7px 10px;font:600 12px 'Inter',sans-serif;}
.rf-pagination button{cursor:pointer;}
.rf-pagination button:disabled{opacity:.42;cursor:not-allowed;}
.table-wrap table{border-radius:12px;}
@media(max-width:900px){.page-head{align-items:stretch;}.filter-actions{width:100%;}.filter-reset{flex:1;}.page-head-actions{width:100%;}.btn-download,.btn-refresh{flex:1;}}


/* Kolom Tiket Eskalasi berada setelah Status, sebelum Detail; nilai panjang tidak merusak layout. */
.table-wrap > table .rf-ticket-cell{
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    max-width:180px;
}

/* Lebar tabel publik dibuat eksplisit agar kolom tiket stabil dan tidak terdorong keluar. */
#viewTabel .table-wrap > table{
    width:100%;
    min-width:1180px;
    table-layout:fixed;
}
#viewTabel .table-wrap > table th:nth-child(1),
#viewTabel .table-wrap > table td:nth-child(1){width:4%;}
#viewTabel .table-wrap > table th:nth-child(2),
#viewTabel .table-wrap > table td:nth-child(2){width:13%;}
#viewTabel .table-wrap > table th:nth-child(3),
#viewTabel .table-wrap > table td:nth-child(3){width:8%;}
#viewTabel .table-wrap > table th:nth-child(4),
#viewTabel .table-wrap > table td:nth-child(4){width:21%;}
#viewTabel .table-wrap > table th:nth-child(5),
#viewTabel .table-wrap > table td:nth-child(5){width:6%;}
#viewTabel .table-wrap > table th:nth-child(6),
#viewTabel .table-wrap > table td:nth-child(6){width:10%;}
#viewTabel .table-wrap > table th:nth-child(7),
#viewTabel .table-wrap > table td:nth-child(7){width:8%;}
#viewTabel .table-wrap > table th:nth-child(8),
#viewTabel .table-wrap > table td:nth-child(8){width:10%;}
#viewTabel .table-wrap > table th:nth-child(9),
#viewTabel .table-wrap > table td:nth-child(9){width:8%;}
#viewTabel .table-wrap > table th:nth-child(10),
#viewTabel .table-wrap > table td:nth-child(10){width:8%;}
#viewTabel .table-wrap > table th:nth-child(11),
#viewTabel .table-wrap > table td:nth-child(11){width:4%;}
#viewTabel .table-wrap > table th,
#viewTabel .table-wrap > table td{overflow:hidden;text-overflow:ellipsis;}
</style>


<script src="{{ asset('js/tf-navigation.js') }}" defer></script>
</head>


<body data-tf-nav="public">

<!-- ========================================================
     TOPBAR
     ======================================================== -->

<div class="topbar">


    <div class="topbar-brand">


        <img
            src="{{ asset('images/logo-telkom-white.png') }}"
            alt="Telkom Indonesia"
        >


        <span>
            Telkom Fallout System
        </span>


    </div>



    <div class="topbar-actions">


        <a
            class="btn-ghost"
            href="{{ route('welcome') }}"
        >

            Beranda

        </a>


        <a
            class="btn-solid"
            href="{{ route('login') }}"
        >

            Login

        </a>


    </div>


</div>



<div class="wrap">



<!-- ========================================================
     PAGE HEADER
     ======================================================== -->

<div class="page-head">


    <div class="page-head-left">


        <h1>
            Data Rekap Fallout
        </h1>


        @if(
            $activeView === 'grafik'
        )


            <p>

                {{ $witelLabel[$tableWitel] }}

                · Grafik berdasarkan
                seluruh data Witel yang dipilih

            </p>


        @else


            <p>

                @if($filterTanggal !== '')
                    {{
                        \Carbon\Carbon::parse(
                            $filterTanggal
                        )->translatedFormat(
                            'd F Y'
                        )
                    }}
                @else
                    Semua tanggal
                @endif

                · PIC:
                {{ $picText }}

                · Data tersedia untuk umum
                (view &amp; download)

            </p>


        @endif


    </div>



    <div class="page-head-actions">
        <a class="btn-refresh" href="{{ request()->fullUrl() }}" title="Muat ulang data">↻ Refresh</a>
        <a
        class="btn-download"
        aria-label="Unduh CSV sesuai filter tabel yang aktif"
        data-no-spa
        href="{{ route('rekap-fallout.download', [
            'witel'   => $tableWitel,
            'tanggal' => $filterTanggal,
            'sto'     => $filterSto,
            'status'  => $filterStatus,
            'q'       => $filterSearch,
            'periode' => $filterPeriode,
        ]) }}"
    >


        <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
        >

            <path
                d="
                    M12 4v10
                    M8 8l4-4 4 4
                    M5 15v3
                    a2 2 0 0 0 2 2
                    h10
                    a2 2 0 0 0 2-2
                    v-3
                "
                stroke="#fff"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
            />

        </svg>


        Download Data


    </a>
    </div>


</div>



<!-- ========================================================
     STATISTICS
     ======================================================== -->

<div class="stats">


    @if(
        $activeView === 'grafik'
    )


        <div class="stat-card">


            <span
                class="stat-bar"
                style="
                    background:var(--maroon-0)
                "
            ></span>


            <div>


                <div class="lbl">
                    Total Fallout
                </div>


                <div class="val">
                    {{ $chartTotal }}
                </div>


            </div>


        </div>



        <div class="stat-card">


            <span
                class="stat-bar"
                style="
                    background:var(--green)
                "
            ></span>


            <div>


                <div class="lbl">
                    RESOLVED
                </div>


                <div class="val">
                    {{ $chartResolved }}
                </div>


            </div>


        </div>



        <div class="stat-card">


            <span
                class="stat-bar"
                style="
                    background:var(--red)
                "
            ></span>


            <div>


                <div class="lbl">
                    ESKALASI
                </div>


                <div class="val">
                    {{ $chartEskalasi }}
                </div>


            </div>


        </div>



        <div class="stat-card">


            <span
                class="stat-bar"
                style="
                    background:var(--blue)
                "
            ></span>


            <div>


                <div class="lbl">
                    STO Aktif
                </div>


                <div class="val">
                    {{ $chartStoAktif }}
                </div>


            </div>


        </div>


    @else


        <div class="stat-card">


            <span
                class="stat-bar"
                style="
                    background:var(--maroon-0)
                "
            ></span>


            <div>


                <div class="lbl">
                    Total Fallout
                </div>


                <div class="val">
                    {{ $total }}
                </div>


            </div>


        </div>



        <div class="stat-card">


            <span
                class="stat-bar"
                style="
                    background:var(--green)
                "
            ></span>


            <div>


                <div class="lbl">
                    RESOLVED
                </div>


                <div class="val">
                    {{ $resolved }}
                </div>


            </div>


        </div>



        <div class="stat-card">


            <span
                class="stat-bar"
                style="
                    background:var(--red)
                "
            ></span>


            <div>


                <div class="lbl">
                    ESKALASI
                </div>


                <div class="val">
                    {{ $eskalasi }}
                </div>


            </div>


        </div>



        <div class="stat-card">


            <span
                class="stat-bar"
                style="
                    background:var(--blue)
                "
            ></span>


            <div>


                <div class="lbl">
                    STO Aktif
                </div>


                <div class="val">
                    {{ $stoAktif }}
                </div>


            </div>


        </div>


    @endif


</div>



<!-- ========================================================
     TABS
     ======================================================== -->

<div class="tabs">


    <button

        type="button"

        id="tabBtnTabel"

        class="
            tab-btn
            {{
                $activeView === 'tabel'
                    ? 'active'
                    : ''
            }}
        "

        onclick="rfSwitchView('tabel')"

    >

        📄 Tabel Data

    </button>



    <button

        type="button"

        id="tabBtnGrafik"

        class="
            tab-btn
            {{
                $activeView === 'grafik'
                    ? 'active'
                    : ''
            }}
        "

        onclick="rfSwitchView('grafik')"

    >

        📊 Grafik

    </button>


</div>



<!-- ========================================================
     TABEL VIEW
     ======================================================== -->

<div

    id="viewTabel"

    style="
        display:
        {{
            $activeView === 'tabel'
                ? 'block'
                : 'none'
        }};
    "

>


    <!-- FILTER TABEL -->

    <form

        class="filter-card"

        method="GET"

        action="{{ $tableAction }}"

    >


        <input type="hidden" name="view" value="tabel">
        <input type="hidden" name="graph_witel" value="{{ $graphWitel }}">


        <div class="filter-title">


            <span class="bar"></span>


            Filter Tabel


        </div>



        <div class="filter-row filter-row-labeled">
            <div class="filter-field">
                <label for="tableWitel">Witel / Cabang</label>
                <select id="tableWitel" name="table_witel" onchange="rfTableWitelChanged(this.form, this.value)">
                    @foreach($witelLabel as $key => $label)
                        <option value="{{ $key }}" {{ $tableWitel === $key ? 'selected' : '' }}>{{ $key === 'semua' ? 'Semua Witel' : $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="filterPeriode">Periode</label>
                <select id="filterPeriode" name="periode" onchange="rfSubmitFilter(this.form)">
                    <option value="harian" {{ $filterPeriode === 'harian' ? 'selected' : '' }}>Harian</option>
                    <option value="mingguan" {{ $filterPeriode === 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                    <option value="bulanan" {{ $filterPeriode === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                    <option value="tahunan" {{ $filterPeriode === 'tahunan' ? 'selected' : '' }}>Tahunan</option>
                </select>
            </div>
            <div class="filter-field">
                <label for="filterTanggal">Tanggal acuan</label>
                <input id="filterTanggal" type="date" name="tanggal" value="{{ $filterTanggal }}" onchange="rfSubmitFilter(this.form)">
            </div>
            <div class="filter-field">
                <label for="filterSto">Wilayah STO</label>
                <select id="filterSto" name="sto" onchange="rfSubmitFilter(this.form)">
                    <option value="semua" {{ $filterSto === 'semua' ? 'selected' : '' }}>Semua STO</option>
                    @foreach($stoOptions as $sto)
                        <option value="{{ $sto }}" {{ strtoupper($filterSto) === strtoupper($sto) ? 'selected' : '' }}>{{ $sto }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field filter-search-field">
                <label for="filterSearch">Cari Order ID, PIC, atau deskripsi</label>
                <input id="filterSearch" type="search" name="q" value="{{ $filterSearch }}" maxlength="100" placeholder="Ketik kata kunci…">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-download" style="min-height:42px;padding:10px 14px">Cari data</button>
                <a class="filter-reset" href="{{ $rekapAction }}?view=tabel">Reset</a>
            </div>
        </div>

        <p class="filter-hint">Pilih tanggal acuan untuk menerapkan periode harian, mingguan, bulanan, atau tahunan. Kosongkan tanggal untuk melihat seluruh tanggal.</p>
        <input type="hidden" name="status" id="tableStatus" value="{{ $filterStatus }}">


        <!-- STATUS -->

        <div class="status-pills">


            @foreach(

                $statusLabel
                as $key => $label

            )


                <button onclick="rfSetStatusAndSubmit(this)"

                    type="button"

                    data-status="{{ $key }}"

                    class="
                        pill
                        {{
                            $filterStatus === $key
                                ? 'active'
                                : ''
                        }}
                    "

                >

                    {{ $label }}

                </button>


            @endforeach


        </div>



        <div class="result-count">


            Menampilkan


            <b>
                {{ $total }}
            </b>


            data sesuai filter —


            {{ $witelLabel[$tableWitel] }}


        </div>


    </form>



    <!-- TABLE -->

    <div class="table-wrap">


        <table>


            <thead>


                <tr>

                    <th>
                        NO
                    </th>

                    <th>
                        NO/ORDER ID
                    </th>

                    <th>
                        CABANG
                    </th>

                    <th>
                        DESKRIPSI
                    </th>

                    <th>
                        STO
                    </th>

                    <th>
                        TGL FALLOUT
                    </th>

                    <th>
                        PIC
                    </th>

                    <th>
                        RESOLVED/ESKALASI
                    </th>

                    <th>
                        STATUS
                    </th>

                    <th>
                        TIKET ESKALASI
                    </th>

                    <th>
                        DETAIL
                    </th>

                </tr>


            </thead>



            <tbody>


                @forelse(

                    $filtered
                    as $i => $row

                )


                    @php


                        $resolvedStatus = strtolower(
                            trim((string) ($row->resolved_eskalasi ?? ''))
                        );

                        $resolvedStatus = preg_replace('/[\\s-]+/u', '_', $resolvedStatus);



                        $resolvedLabel =

                            $statusLabel[

                                $resolvedStatus

                            ]

                            ??

                            ucwords(

                                str_replace(

                                    '_',

                                    ' ',

                                    $resolvedStatus

                                )

                            );



                        $detailStatus =

                            strtolower(

                                trim(

                                    (string)

                                    (

                                        $row->status

                                        ??

                                        ''

                                    )

                                )

                            );



                        $detailLabel =

                            $detailStatus

                                ? ucwords(

                                    str_replace(

                                        '_',

                                        ' ',

                                        $detailStatus

                                    )

                                )

                                : '-';


                    @endphp



                    <tr>


                        <td>
                            {{ $i + 1 }}
                        </td>


                        <td>

                            <div class="rf-order-cell">

                                <span class="order-id">
                                    {{
                                        \Illuminate\Support\Str::limit(
                                            $row->order_id
                                            ??
                                            '-',
                                            22
                                        )
                                    }}
                                </span>

                                <button
                                    type="button"
                                    class="rf-detail-icon"
                                    title="Lihat Order ID lengkap"
                                    aria-label="Lihat Order ID lengkap"
                                    data-order-id="{{ $row->order_id ?? '-' }}"
                                    onclick="rfOpenOrderId(this)"
                                >
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                        <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/>
                                    </svg>
                                </button>

                            </div>

                        </td>


                        <td>
                            {{ optional(optional($row->batch)->branch)->nama_cabang ?? '-' }}
                        </td>

                        <td>


                            {{
                                \Illuminate\Support\Str::limit(

                                    $row->deskripsi
                                    ??
                                    '-',

                                    90

                                )
                            }}


                        </td>


                        <td>


                            @if(
                                $row->sto
                            )


                                <span
                                    class="sto-badge"
                                >

                                    {{
                                        strtoupper(

                                            trim(

                                                (string)

                                                $row->sto

                                            )

                                        )
                                    }}

                                </span>


                            @else

                                -

                            @endif


                        </td>


                        <td>


                            @if(
                                $row->tanggal
                            )


                                {{
                                    \Carbon\Carbon::parse(

                                        $row->tanggal

                                    )->translatedFormat(

                                        'd F Y'

                                    )
                                }}


                            @else

                                -

                            @endif


                        </td>


                        <td>

                            {{
                                $row->pic
                                ??
                                '-'
                            }}

                        </td>


                        <td

                            class="
                                status-{{
                                    $resolvedStatus
                                }}
                            "

                        >

                            {{
                                $resolvedLabel
                            }}


                        </td>


                        <td>

                            {{
                                $detailLabel
                            }}


                        </td>


                        <td class="rf-ticket-cell" title="{{ trim((string) ($row->tiket_eskalasi ?? '')) ?: '-' }}">
                            {{ trim((string) ($row->tiket_eskalasi ?? '')) ?: '-' }}
                        </td>

                        <td style="text-align:center;">

                            <button
                                type="button"
                                class="rf-detail-icon"
                                title="Lihat detail fallout"
                                aria-label="Lihat detail fallout"
                                data-order-id="{{ $row->order_id ?? '-' }}"
                                data-sto="{{ $row->sto ?? '-' }}"
                                data-date="{{ $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->translatedFormat('d F Y') : '-' }}"
                                data-pic="{{ $row->pic ?? '-' }}"
                                data-resolved="{{ $resolvedLabel ?: '-' }}"
                                data-status="{{ $detailLabel ?: '-' }}"
                                data-description="{{ $row->deskripsi ?? '-' }}"
                                data-ket="{{ $row->ket ?? '-' }}"
                                onclick="rfOpenDetail(this)"
                            >
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </button>

                        </td>

                    </tr>


                @empty


                    <tr>


                        <td

                            colspan="11"

                            class="data-empty"

                        >

                            Tidak ada data
                            untuk filter ini.


                        </td>


                    </tr>


                @endforelse


            </tbody>


        </table>

    </div>

    <div class="rf-pagination" id="rfTablePagination">
        <div id="rfPageSummary">Memuat data…</div>
        <div class="rf-pagination-controls">
            <label for="rfPageSize">Baris</label>
            <select id="rfPageSize" aria-label="Jumlah baris per halaman">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="all">Semua Data ({{ number_format($total) }})</option>
            </select>
            <button type="button" id="rfPagePrev">Sebelumnya</button>
            <span id="rfPageLabel">Halaman 1</span>
            <button type="button" id="rfPageNext">Berikutnya</button>
        </div>
    </div>

</div>



<!-- ========================================================
     ORDER ID LENGKAP MODAL
     ======================================================== -->

<div
    class="rf-order-modal"
    id="rfOrderModal"
    aria-hidden="true"
>

    <div
        class="rf-order-backdrop"
        onclick="rfCloseOrderId()"
    ></div>

    <div
        class="rf-order-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rfOrderTitle"
    >

        <div class="rf-order-head">

            <div
                class="rf-order-title"
                id="rfOrderTitle"
            >
                No/Order ID Lengkap
            </div>

            <button
                type="button"
                class="rf-order-close"
                aria-label="Tutup Order ID"
                onclick="rfCloseOrderId()"
            >
                &times;
            </button>

        </div>

        <div class="rf-order-body">

            <div
                class="rf-order-box"
                id="rfOrderIdText"
            >
                -
            </div>

            <button
                type="button"
                class="rf-order-copy-btn"
                id="rfOrderCopyBtn"
                onclick="rfCopyOrderId()"
            >
                Copy ID
            </button>

        </div>

    </div>

</div>



<!-- ========================================================
     DETAIL FALLOUT MODAL
     ======================================================== -->

<div
    class="rf-detail-modal"
    id="rfDetailModal"
    aria-hidden="true"
>

    <div
        class="rf-detail-backdrop"
        onclick="rfCloseDetail()"
    ></div>

    <div
        class="rf-detail-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rfDetailTitle"
    >

        <div class="rf-detail-head">

            <div
                class="rf-detail-title"
                id="rfDetailTitle"
            >
                Detail Fallout
            </div>

            <button
                type="button"
                class="rf-detail-close"
                aria-label="Tutup detail"
                onclick="rfCloseDetail()"
            >
                &times;
            </button>

        </div>


        <div class="rf-detail-body">

            <div class="rf-detail-row">
                <div class="rf-detail-label">No/Order ID</div>
                <div class="rf-detail-value" id="rfDetailOrderId">-</div>
            </div>

            <div class="rf-detail-row">
                <div class="rf-detail-label">STO</div>
                <div class="rf-detail-value" id="rfDetailSto">-</div>
            </div>

            <div class="rf-detail-row">
                <div class="rf-detail-label">Tanggal Fallout</div>
                <div class="rf-detail-value" id="rfDetailDate">-</div>
            </div>

            <div class="rf-detail-row">
                <div class="rf-detail-label">PIC</div>
                <div class="rf-detail-value" id="rfDetailPic">-</div>
            </div>

            <div class="rf-detail-row">
                <div class="rf-detail-label">RESOLVED/ESKALASI</div>
                <div class="rf-detail-value" id="rfDetailResolved">-</div>
            </div>

            <div class="rf-detail-row">
                <div class="rf-detail-label">Status</div>
                <div class="rf-detail-value" id="rfDetailStatus">-</div>
            </div>

            <div class="rf-detail-section">
                <div class="rf-detail-section-label">Deskripsi Lengkap</div>
                <div class="rf-detail-box" id="rfDetailDescription">-</div>
            </div>

            <div class="rf-detail-section">
                <div class="rf-detail-section-label">Keterangan (KET)</div>
                <div class="rf-detail-box ket" id="rfDetailKet">-</div>
            </div>

        </div>

        <div class="rf-detail-footer">
            <button
                type="button"
                class="rf-detail-close-btn"
                onclick="rfCloseDetail()"
            >
                Tutup
            </button>
        </div>

    </div>

</div>



<!-- ========================================================
     GRAFIK VIEW
     ======================================================== -->

<div

    id="viewGrafik"

    style="
        display:
        {{
            $activeView === 'grafik'
                ? 'block'
                : 'none'
        }};
    "

>


    <!-- ====================================================
         FILTER GRAFIK
         ==================================================== -->

    <form

        class="graph-filter"

        method="GET"

        action="{{ $graphAction }}"

    >


        <input type="hidden" name="view" value="grafik">
        <input type="hidden" name="table_witel" value="{{ $tableWitel }}">
        <input type="hidden" name="tanggal" value="{{ $filterTanggal }}">
        <input type="hidden" name="periode" value="{{ $filterPeriode }}">
        <input type="hidden" name="sto" value="{{ $filterSto }}">
        <input type="hidden" name="status" value="{{ $filterStatus }}">
        <input type="hidden" name="q" value="{{ $filterSearch }}">


        <div class="graph-filter-title">

            Filter Grafik

        </div>


        <select

            name="graph_witel"

            onchange="rfSubmitFilter(this.form)"

        >


            @foreach(

                $witelLabel
                as $key => $label

            )


                <option

                    value="{{ $key }}"

                    {{
                        $graphWitel === $key
                            ? 'selected'
                            : ''
                    }}

                >

                    {{ $label }}

                </option>


            @endforeach


        </select>


    </form>



    <!-- ====================================================
         RESOLVED / ESKALASI
         ==================================================== -->

    <div class="graf-card">


        <h3>
            RESOLVED / ESKALASI
        </h3>


        <p class="graf-subtitle">


            {{ $witelLabel[$graphWitel] }}


        </p>



        @php


            $chartResolvedTotal =

                $chartResolved

                +

                $chartEskalasi;



            $chartResolvedPercent =

                $chartResolvedTotal > 0

                    ? (

                        $chartResolved
                        /
                        $chartResolvedTotal

                    ) * 100

                    : 0;


        @endphp



        @if(

            $chartResolvedTotal <= 0

        )


            <div class="data-empty">

                Tidak ada data
                untuk Witel ini.

            </div>


        @else


            <div class="donut-layout">


                <!-- DONUT -->

                <div class="donut-wrap">


                    <div

                        class="donut-chart"

                        style="

                            background:

                                conic-gradient(

                                    #8A0F26

                                    0

                                    {{ $chartResolvedPercent }}%,

                                    #E8A0A0

                                    {{ $chartResolvedPercent }}%

                                    100%

                                );

                        "

                    >


                        <div class="donut-hole">


                            <strong>

                                {{
                                    $chartResolvedTotal
                                }}

                            </strong>


                            <span>

                                Total

                            </span>


                        </div>


                    </div>



                    <!-- LEGEND -->

                    <div class="donut-legend">


                        <span
                            class="donut-legend-item"
                        >


                            <i

                                class="donut-legend-dot"

                                style="
                                    background:#8A0F26;
                                "

                            ></i>


                            RESOLVED


                        </span>



                        <span
                            class="donut-legend-item"
                        >


                            <i

                                class="donut-legend-dot"

                                style="
                                    background:#E8A0A0;
                                "

                            ></i>


                            ESKALASI


                        </span>


                    </div>


                </div>



                <!-- INDIKATOR -->

                <div
                    class="donut-indicators"
                >


                    <!-- RESOLVED -->

                    <div
                        class="donut-indicator"
                    >


                        <div
                            class="
                                donut-indicator-left
                            "
                        >


                            <span

                                class="indicator-dot"

                                style="
                                    background:#8A0F26;
                                "

                            ></span>


                            <span>

                                RESOLVED

                            </span>


                        </div>


                        <strong

                            class="
                                donut-indicator-value
                            "

                            style="
                                color:#8A0F26;
                            "

                        >

                            {{ $chartResolved }}

                        </strong>


                    </div>



                    <!-- ESKALASI -->

                    <div
                        class="donut-indicator"
                    >


                        <div
                            class="
                                donut-indicator-left
                            "
                        >


                            <span

                                class="indicator-dot"

                                style="
                                    background:#E8A0A0;
                                "

                            ></span>


                            <span>

                                ESKALASI

                            </span>


                        </div>


                        <strong

                            class="
                                donut-indicator-value
                            "

                            style="
                                color:#8A0F26;
                            "

                        >

                            {{ $chartEskalasi }}

                        </strong>


                    </div>


                </div>


            </div>


        @endif


    </div>



    <!-- ====================================================
         FALLOUT PER STO
         ==================================================== -->

    <div

        class="graf-card"

        style="
            margin-top:16px;
        "

    >


        <h3>
            Fallout per STO
        </h3>


        <p class="graf-subtitle">

            {{ $witelLabel[$graphWitel] }}

        </p>



        @if(

            $perSto->isEmpty()

        )


            <div
                class="data-empty"
            >

                Tidak ada data STO
                untuk Witel ini.

            </div>


        @else


            @php

                $barHeight =
                    26;

                $barGap =
                    46;

            @endphp



            <div
                class="chart-scroll"
            >


                <svg

                    class="chart-svg"

                    viewBox="

                        0 0 720

                        {{
                            max(
                                $perSto->count(),
                                1
                            )

                            *

                            $barGap

                            +

                            35
                        }}

                    "

                >


                    @foreach(

                        $perSto
                        as $sto => $count

                    )


                        @php


                            $y =

                                $loop->index

                                *

                                $barGap;



                            $barWidth =

                                (

                                    $count
                                    /
                                    $maxSto

                                )

                                *

                                500;


                        @endphp



                        <!-- LABEL STO -->

                        <text

                            x="0"

                            y="
                                {{
                                    $y + 19
                                }}
                            "

                            font-size="12"

                            font-weight="600"

                            fill="#20161A"

                        >

                            {{ $sto }}

                        </text>



                        <!-- BAR -->

                        <rect

                            x="62"

                            y="
                                {{
                                    $y + 3
                                }}
                            "

                            width="{{ $barWidth }}"

                            height="{{ $barHeight }}"

                            rx="4"

                            fill="#8A0F26"

                        />



                        <!-- NILAI -->

                        <text

                            x="
                                {{
                                    62
                                    +
                                    $barWidth
                                    +
                                    12
                                }}
                            "

                            y="
                                {{
                                    $y + 20
                                }}
                            "

                            font-size="12"

                            font-weight="700"

                            fill="#8A0F26"

                        >

                            {{ $count }}

                        </text>


                    @endforeach


                </svg>


            </div>


        @endif


    </div>



    <!-- ====================================================
         STATUS PENYELESAIAN
         ==================================================== -->

    <div

        class="
            graf-card
            status-resolution-card
        "

        style="
            margin-top:16px;
        "

    >


        <h3>
            Status Penyelesaian
        </h3>


        <p class="graf-subtitle">

            {{ $witelLabel[$graphWitel] }}

        </p>



        @php


            $statusTotal =

                $processCount

                +

                $completedCount;



            $processPercent =

                $statusTotal > 0

                    ? (

                        $processCount
                        /
                        $statusTotal

                    ) * 100

                    : 0;


        @endphp



        @if(

            $statusTotal <= 0

        )


            <div
                class="data-empty"
            >

                Tidak ada data
                untuk Witel ini.

            </div>


        @else


            <div
                class="
                    status-resolution-layout
                "
            >


                <!-- DONUT -->

                <div
                    class="
                        status-resolution-chart
                    "
                >


                    <div

                        class="
                            status-resolution-donut
                        "

                        style="

                            background:

                                conic-gradient(

                                    #8A0F26

                                    0

                                    {{ $processPercent }}%,

                                    #2F7058

                                    {{ $processPercent }}%

                                    100%

                                );

                        "

                    >


                        <div

                            class="
                                status-resolution-donut-hole
                            "

                        >


                            <strong>

                                {{ $statusTotal }}

                            </strong>


                            <span>

                                Total

                            </span>


                        </div>


                    </div>



                    <!-- LEGEND BAWAH -->

                    <div

                        class="
                            status-resolution-legend
                        "

                    >


                        <span

                            class="
                                status-resolution-legend-item
                            "

                        >


                            <i

                                class="
                                    status-resolution-legend-dot
                                "

                                style="
                                    background:#8A0F26;
                                "

                            ></i>


                            Process OSS


                        </span>



                        <span

                            class="
                                status-resolution-legend-item
                            "

                        >


                            <i

                                class="
                                    status-resolution-legend-dot
                                "

                                style="
                                    background:#2F7058;
                                "

                            ></i>


                            COMPLETED


                        </span>


                    </div>


                </div>



                <!-- INDIKATOR -->

                <div

                    class="
                        status-resolution-indicators
                    "

                >


                    <!-- PROCESS OSS -->

                    <div

                        class="
                            status-resolution-indicator
                        "

                    >


                        <div

                            class="
                                status-resolution-indicator-left
                            "

                        >


                            <span

                                class="
                                    status-resolution-indicator-dot
                                "

                                style="
                                    background:#8A0F26;
                                "

                            ></span>


                            <span>

                                Process OSS

                            </span>


                        </div>


                        <strong

                            class="
                                status-resolution-indicator-value
                            "

                            style="
                                color:#8A0F26;
                            "

                        >

                            {{ $processCount }}

                        </strong>


                    </div>



                    <!-- COMPLETED -->

                    <div

                        class="
                            status-resolution-indicator
                        "

                    >


                        <div

                            class="
                                status-resolution-indicator-left
                            "

                        >


                            <span

                                class="
                                    status-resolution-indicator-dot
                                "

                                style="
                                    background:#2F7058;
                                "

                            ></span>


                            <span>

                                COMPLETED

                            </span>


                        </div>


                        <strong

                            class="
                                status-resolution-indicator-value
                            "

                            style="
                                color:#2F7058;
                            "

                        >

                            {{ $completedCount }}

                        </strong>


                    </div>


                </div>


            </div>


        @endif


    </div>



</div>



</div>



<!-- ========================================================
     FOOTER
     ======================================================== -->

<footer>


    <div class="brand">

        © {{ now()->year }}

        Telkom Indonesia —

        Fallout Management System

    </div>


    <div>

        Data tersedia untuk umum.
        Login diperlukan untuk manajemen data.

    </div>


</footer>



<script>

/*
|--------------------------------------------------------------------------
| TABEL / GRAFIK
|--------------------------------------------------------------------------
|
| Tabel dan grafik membaca data yang sama dari server.
| Perpindahan tab sekarang dilakukan DI DALAM UI tanpa reload halaman,
| sehingga tabel tidak lagi kehilangan data hanya karena query ?view=grafik.
|--------------------------------------------------------------------------
*/

function rfOpenOrderId(button){
    const modal = document.getElementById('rfOrderModal');
    const text = document.getElementById('rfOrderIdText');

    if(!modal || !text || !button){
        return;
    }

    const value = button.dataset.orderId && String(button.dataset.orderId).trim() !== ''
        ? button.dataset.orderId
        : '-';

    text.textContent = value;

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('rf-order-open');

    const closeButton = modal.querySelector('.rf-order-close');
    if(closeButton){
        closeButton.focus();
    }
}

function rfCloseOrderId(){
    const modal = document.getElementById('rfOrderModal');

    if(!modal){
        return;
    }

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('rf-order-open');
}

function rfCopyOrderId(){
    const text = document.getElementById('rfOrderIdText');
    const button = document.getElementById('rfOrderCopyBtn');

    if(!text){
        return;
    }

    const value = text.textContent || '';

    const done = function(){
        if(!button){
            return;
        }

        const original = button.textContent;
        button.textContent = 'ID Tersalin';

        setTimeout(function(){
            button.textContent = original;
        }, 1200);
    };

    if(navigator.clipboard && window.isSecureContext){
        navigator.clipboard.writeText(value).then(done).catch(function(){
            rfCopyOrderIdFallback(value, done);
        });
        return;
    }

    rfCopyOrderIdFallback(value, done);
}

function rfCopyOrderIdFallback(value, done){
    const area = document.createElement('textarea');
    area.value = value;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.opacity = '0';
    area.style.pointerEvents = 'none';

    document.body.appendChild(area);
    area.focus();
    area.select();

    try{
        document.execCommand('copy');
        done();
    }catch(e){
        // Clipboard ditolak browser; tidak mengubah tampilan halaman.
    }

    document.body.removeChild(area);
}

function rfOpenDetail(button){
    const modal = document.getElementById('rfDetailModal');
    if(!modal || !button){
        return;
    }

    const setText = function(id, value){
        const element = document.getElementById(id);
        if(element){
            element.textContent = value && String(value).trim() !== ''
                ? value
                : '-';
        }
    };

    setText('rfDetailOrderId', button.dataset.orderId);
    setText('rfDetailSto', button.dataset.sto);
    setText('rfDetailDate', button.dataset.date);
    setText('rfDetailPic', button.dataset.pic);
    setText('rfDetailResolved', button.dataset.resolved);
    setText('rfDetailStatus', button.dataset.status);
    setText('rfDetailDescription', button.dataset.description);
    setText('rfDetailKet', button.dataset.ket);

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('rf-detail-open');

    const closeButton = modal.querySelector('.rf-detail-close');
    if(closeButton){
        closeButton.focus();
    }
}

function rfCloseDetail(){
    const modal = document.getElementById('rfDetailModal');
    if(!modal){
        return;
    }

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('rf-detail-open');
}

document.addEventListener('keydown', function(event){
    if(event.key !== 'Escape'){
        return;
    }

    rfCloseOrderId();
    rfCloseDetail();
});

function rfSwitchView(name){

    const tabel = document.getElementById('viewTabel');
    const grafik = document.getElementById('viewGrafik');
    const btnTabel = document.getElementById('tabBtnTabel');
    const btnGrafik = document.getElementById('tabBtnGrafik');

    if(!tabel || !grafik || !btnTabel || !btnGrafik){
        return;
    }

    const showTable = name === 'tabel';

    tabel.style.display = showTable ? 'block' : 'none';
    grafik.style.display = showTable ? 'none' : 'block';

    btnTabel.classList.toggle('active', showTable);
    btnGrafik.classList.toggle('active', !showTable);

    /*
    |--------------------------------------------------------------------------
    | Simpan state view ke URL tanpa reload.
    | Filter tabel tidak dihapus dari DOM/data server.
    |--------------------------------------------------------------------------
    */
    const url = new URL(window.location.href);
    url.searchParams.set('view', name);
    window.history.replaceState({}, '', url.toString());
}

/*
|--------------------------------------------------------------------------
| Pastikan tab aktif sesuai URL saat halaman pertama kali dibuka.
|--------------------------------------------------------------------------
*/
document.addEventListener('DOMContentLoaded', function(){
    const url = new URL(window.location.href);
    const view = url.searchParams.get('view') === 'grafik' ? 'grafik' : 'tabel';
    rfSwitchView(view);
});

/* tf-navigation.js evaluates inline scripts inside a function scope.
   Export handlers explicitly so inline buttons keep working after SPA navigation. */
window.rfOpenOrderId = rfOpenOrderId;
window.rfCloseOrderId = rfCloseOrderId;
window.rfCopyOrderId = rfCopyOrderId;
window.rfOpenDetail = rfOpenDetail;
window.rfCloseDetail = rfCloseDetail;
window.rfSwitchView = rfSwitchView;

</script>


<script>
function rfSubmitFilter(form) {
    if (!form) return;
    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
        return;
    }
    const event = new Event('submit', { bubbles: true, cancelable: true });
    if (form.dispatchEvent(event)) form.submit();
}

function rfSetStatusAndSubmit(button) {
    if (!button || !button.form) return;
    const status = button.form.querySelector('#tableStatus');
    if (!status) return;
    status.value = button.getAttribute('data-status') || 'semua';
    button.form.querySelectorAll('.status-pills .pill').forEach(function (pill) {
        pill.classList.toggle('active', pill === button);
    });
    rfSubmitFilter(button.form);
}

function rfTableWitelChanged(form, value) {
    if (!form) return;
    const tanggal = form.querySelector('[name="tanggal"]');
    const sto = form.querySelector('[name="sto"]');
    const status = form.querySelector('#tableStatus');
    if (tanggal) tanggal.value = '';
    if (sto) sto.value = 'semua';
    if (status) status.value = 'semua';
    rfSubmitFilter(form);
}

window.rfSubmitFilter = rfSubmitFilter;
window.rfSetStatusAndSubmit = rfSetStatusAndSubmit;
window.rfTableWitelChanged = rfTableWitelChanged;

</script>


<script>
function rfInitPagination() {
    const table = document.querySelector('#viewTabel .table-wrap table');
    const footer = document.getElementById('rfTablePagination');
    if (!table || !footer) return;
    const tbody = table.tBodies && table.tBodies[0];
    if (!tbody) return;

    const rows = Array.from(tbody.rows).filter(row => !row.querySelector('.data-empty'));
    const emptyRows = Array.from(tbody.rows).filter(row => Boolean(row.querySelector('.data-empty')));
    const size = document.getElementById('rfPageSize');
    const summary = document.getElementById('rfPageSummary');
    const label = document.getElementById('rfPageLabel');
    const prev = document.getElementById('rfPagePrev');
    const next = document.getElementById('rfPageNext');
    if (!size || !summary || !label || !prev || !next) return;

    let page = 1;
    function render() {
        const pageSize = size.value === 'all'
            ? Math.max(1, rows.length)
            : Math.max(1, Number(size.value) || 10);
        const pageCount = Math.max(1, Math.ceil(rows.length / pageSize));
        page = Math.min(Math.max(page, 1), pageCount);
        const first = (page - 1) * pageSize;
        const last = Math.min(first + pageSize, rows.length);

        rows.forEach((row, index) => {
            row.style.display = index >= first && index < last ? '' : 'none';
        });
        emptyRows.forEach(row => { row.style.display = rows.length ? 'none' : ''; });

        summary.textContent = rows.length
            ? (first + 1) + '–' + last + ' dari ' + rows.length + ' data'
            : 'Tidak ada data';
        label.textContent = 'Halaman ' + page + ' dari ' + pageCount;
        prev.disabled = page <= 1;
        next.disabled = page >= pageCount;
        prev.style.display = pageCount <= 1 ? 'none' : '';
        next.style.display = pageCount <= 1 ? 'none' : '';
    }

    // Replace handlers cleanly when a new table is mounted by SPA navigation.
    size.onchange = function () { page = 1; render(); };
    prev.onclick = function () { page -= 1; render(); };
    next.onclick = function () { page += 1; render(); };
    render();
}

window.rfInitPagination = rfInitPagination;
rfInitPagination();
</script>

</body>

</html>
