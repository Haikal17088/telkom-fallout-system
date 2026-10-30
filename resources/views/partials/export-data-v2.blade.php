{{--

    resources/views/partials/export-data-v2.blade.php

    DATA EXPORT TERHUBUNG KE DATABASE fallout_data.

    Filter menentukan data yang ditampilkan di preview dan data yang di-download.

--}}

@php

    $witelSlug = $witelSlug ?? 'jakpus';

    $witel     = $witel ?? 'Witel';

    $validPeriods = ['harian', 'mingguan', 'bulanan', 'tahunan', 'custom'];

    $activePeriod = request()->query('periode', 'bulanan');

    if (!in_array($activePeriod, $validPeriods, true)) {

        $activePeriod = 'bulanan';

    }

    $baseExportQuery = \App\Models\FalloutData::forWitel($witelSlug);

    $latestDateRaw = (clone $baseExportQuery)

        ->whereNotNull('tanggal')

        ->max('tanggal');

    $latestDate = $latestDateRaw

        ? \Carbon\Carbon::parse($latestDateRaw)

        : now();

    $availableYears = (clone $baseExportQuery)

        ->whereNotNull('tanggal')

        ->selectRaw('EXTRACT(YEAR FROM tanggal) as tahun')

        ->distinct()

        ->orderByDesc('tahun')

        ->pluck('tahun')

        ->map(fn ($year) => (int) $year)

        ->values();

    if ($availableYears->isEmpty()) {

        $availableYears = collect([now()->year]);

    }

    $selectedTanggal = request()->query(

        'tanggal',

        $latestDate->format('Y-m-d')

    );

    $selectedMinggu = request()->query(

        'minggu',

        $latestDate->format('o-\WW')

    );

    $selectedBulan = (int) request()->query(

        'bulan',

        $latestDate->month

    );

    $selectedTahun = (int) request()->query(

        'tahun',

        $latestDate->year

    );

    $selectedDari = request()->query('dari', '');

    $selectedSampai = request()->query('sampai', '');

    if ($selectedBulan < 1 || $selectedBulan > 12) {

        $selectedBulan = $latestDate->month;

    }

    if (!$availableYears->contains($selectedTahun)) {

        $selectedTahun = $latestDate->year;

    }

    /*

     * QUERY EXPORT AKTIF

     */

    $exportQuery = clone $baseExportQuery;

    switch ($activePeriod) {

        case 'harian':

            if (\DateTime::createFromFormat('Y-m-d', $selectedTanggal) !== false) {

                $exportQuery->whereDate('tanggal', $selectedTanggal);

            } else {

                $selectedTanggal = $latestDate->format('Y-m-d');

                $exportQuery->whereDate('tanggal', $selectedTanggal);

            }

            break;

        case 'mingguan':

            if (preg_match('/^(\d{4})-W(\d{2})$/', $selectedMinggu, $matches)) {

                $weekYear = (int) $matches[1];

                $weekNumber = (int) $matches[2];

                try {

                    $weekStart = \Carbon\Carbon::now()

                        ->setISODate($weekYear, $weekNumber)

                        ->startOfWeek(\Carbon\Carbon::MONDAY);

                    $weekEnd = $weekStart->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

                    $exportQuery
                        ->whereDate('tanggal', '>=', $weekStart->toDateString())
                        ->whereDate('tanggal', '<=', $weekEnd->toDateString());

                } catch (\Throwable $e) {

                    $selectedMinggu = $latestDate->format('o-\WW');

                    $weekStart = $latestDate->copy()->startOfWeek(\Carbon\Carbon::MONDAY);

                    $weekEnd = $latestDate->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

                    $exportQuery
                        ->whereDate('tanggal', '>=', $weekStart->toDateString())
                        ->whereDate('tanggal', '<=', $weekEnd->toDateString());

                }

            } else {

                $selectedMinggu = $latestDate->format('o-\WW');

                $weekStart = $latestDate->copy()->startOfWeek(\Carbon\Carbon::MONDAY);

                $weekEnd = $latestDate->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

                $exportQuery
                        ->whereDate('tanggal', '>=', $weekStart->toDateString())
                        ->whereDate('tanggal', '<=', $weekEnd->toDateString());

            }

            break;

        case 'tahunan':

            $exportQuery->whereYear('tanggal', $selectedTahun);

            break;

        case 'custom':

            $validDari = \DateTime::createFromFormat('Y-m-d', $selectedDari) !== false;

            $validSampai = \DateTime::createFromFormat('Y-m-d', $selectedSampai) !== false;

            if ($validDari && $validSampai) {

                $from = \Carbon\Carbon::parse($selectedDari);

                $until = \Carbon\Carbon::parse($selectedSampai);

                if ($from->gt($until)) {

                    [$selectedDari, $selectedSampai] = [

                        $selectedSampai,

                        $selectedDari,

                    ];

                    $from = \Carbon\Carbon::parse($selectedDari);

                    $until = \Carbon\Carbon::parse($selectedSampai);

                }

                $exportQuery
                    ->whereDate('tanggal', '>=', $from->toDateString())
                    ->whereDate('tanggal', '<=', $until->toDateString());

            } elseif ($validDari) {

                $exportQuery->whereDate('tanggal', '>=', $selectedDari);

            } elseif ($validSampai) {

                $exportQuery->whereDate('tanggal', '<=', $selectedSampai);

            }

            break;

        case 'bulanan':

        default:

            $exportQuery

                ->whereYear('tanggal', $selectedTahun)

                ->whereMonth('tanggal', $selectedBulan);

            break;

    }

    $exRows = $exportQuery

        ->orderByDesc('tanggal')

        ->orderByDesc('row_id')

        ->get();

    $exCount = $exRows->count();

    // Preview tabel menampilkan SEMUA data pada Witel yang sedang dibuka.

    // Filter periode tetap khusus untuk data yang akan di-download.

    $exPreview = (clone $baseExportQuery)

        ->orderByDesc('tanggal')

        ->orderByDesc('row_id')

        ->get();

    $exPreviewCount = $exPreview->count();

    $bulanLabels = [

        1 => 'Januari',

        2 => 'Februari',

        3 => 'Maret',

        4 => 'April',

        5 => 'Mei',

        6 => 'Juni',

        7 => 'Juli',

        8 => 'Agustus',

        9 => 'September',

        10 => 'Oktober',

        11 => 'November',

        12 => 'Desember',

    ];

    $bulanNow = $bulanLabels[$selectedBulan] ?? $latestDate->translatedFormat('F');

    $tahunNow = (string) $selectedTahun;

    $periodLabel = match ($activePeriod) {

        'harian' => \Carbon\Carbon::parse($selectedTanggal)->translatedFormat('d F Y'),

        'mingguan' => 'Minggu ' . $selectedMinggu,

        'tahunan' => 'Tahun ' . $selectedTahun,

        'custom' => ($selectedDari && $selectedSampai)

            ? \Carbon\Carbon::parse($selectedDari)->translatedFormat('d F Y')

                . ' — '

                . \Carbon\Carbon::parse($selectedSampai)->translatedFormat('d F Y')

            : 'Custom',

        default => $bulanNow . ' ' . $tahunNow,

    };

@endphp

<div class="ex-wrap">

    {{-- LOADING KHUSUS HALAMAN EXPORT DATA --}}

    <div class="ex-page-loading" id="exPageLoading" aria-live="polite" aria-label="Memuat Export Data">

        <div class="ex-page-loading-card">

            <div class="ex-page-loading-logo"><span>TF</span></div>

            <div class="ex-page-loading-spinner" aria-hidden="true"></div>

            <div class="ex-page-loading-title">Memuat Export Data</div>

            <div class="ex-page-loading-subtitle">Menyiapkan data {{ $witel }}</div>

        </div>

    </div>

    <div class="ex-head">

        <h2>Export Data</h2>

        <p>Gunakan filter untuk menentukan cakupan data yang akan di-download &middot; {{ $witel }}</p>

    </div>

    <div class="ex-card">

        <div class="ex-tabs">

            <button

                type="button"

                class="ex-tab-btn {{ $activePeriod === 'harian' ? 'active' : '' }}"

                onclick="exSwitchTab('harian', this)"

            >Harian</button>

            <button

                type="button"

                class="ex-tab-btn {{ $activePeriod === 'mingguan' ? 'active' : '' }}"

                onclick="exSwitchTab('mingguan', this)"

            >Mingguan</button>

            <button

                type="button"

                class="ex-tab-btn {{ $activePeriod === 'bulanan' ? 'active' : '' }}"

                onclick="exSwitchTab('bulanan', this)"

            >Bulanan</button>

            <button

                type="button"

                class="ex-tab-btn {{ $activePeriod === 'tahunan' ? 'active' : '' }}"

                onclick="exSwitchTab('tahunan', this)"

            >Tahunan</button>

            <button

                type="button"

                class="ex-tab-btn {{ $activePeriod === 'custom' ? 'active' : '' }}"

                onclick="exSwitchTab('custom', this)"

            >Custom</button>

        </div>

        <div class="ex-tabs-line"></div>

        <div

            class="ex-filter-box"

            id="ex-filter-harian"

            style="{{ $activePeriod === 'harian' ? 'display:flex;' : 'display:none;' }}"

        >

            <div class="ex-field">

                <label>Tanggal</label>

                <input

                    type="date"

                    id="exTanggal"

                    value="{{ $selectedTanggal }}"

                >

            </div>

        </div>

        <div

            class="ex-filter-box"

            id="ex-filter-mingguan"

            style="{{ $activePeriod === 'mingguan' ? 'display:flex;' : 'display:none;' }}"

        >

            <div class="ex-field">

                <label>Minggu</label>

                <input

                    type="week"

                    id="exMinggu"

                    value="{{ $selectedMinggu }}"

                >

            </div>

        </div>

        <div

            class="ex-filter-box"

            id="ex-filter-bulanan"

            style="{{ $activePeriod === 'bulanan' ? 'display:flex;' : 'display:none;' }}"

        >

            <div class="ex-field">

                <label>Bulan</label>

                <select id="exBulan">

                    @foreach ($bulanLabels as $monthNumber => $monthLabel)

                        <option

                            value="{{ $monthNumber }}"

                            {{ $monthNumber === $selectedBulan ? 'selected' : '' }}

                        >

                            {{ $monthLabel }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="ex-field">

                <label>Tahun</label>

                <select id="exTahunBulanan">

                    @foreach ($availableYears as $year)

                        <option

                            value="{{ $year }}"

                            {{ (int) $year === $selectedTahun ? 'selected' : '' }}

                        >

                            {{ $year }}

                        </option>

                    @endforeach

                </select>

            </div>

        </div>

        <div

            class="ex-filter-box"

            id="ex-filter-tahunan"

            style="{{ $activePeriod === 'tahunan' ? 'display:flex;' : 'display:none;' }}"

        >

            <div class="ex-field">

                <label>Tahun</label>

                <select id="exTahunTahunan">

                    @foreach ($availableYears as $year)

                        <option

                            value="{{ $year }}"

                            {{ (int) $year === $selectedTahun ? 'selected' : '' }}

                        >

                            {{ $year }}

                        </option>

                    @endforeach

                </select>

            </div>

        </div>

        <div

            class="ex-filter-box"

            id="ex-filter-custom"

            style="{{ $activePeriod === 'custom' ? 'display:flex;' : 'display:none;' }}"

        >

            <div class="ex-field">

                <label>Dari Tanggal</label>

                <input

                    type="date"

                    id="exDari"

                    value="{{ $selectedDari }}"

                >

            </div>

            <div class="ex-field">

                <label>Sampai Tanggal</label>

                <input

                    type="date"

                    id="exSampai"

                    value="{{ $selectedSampai }}"

                >

            </div>

        </div>

        <div class="ex-info-bar">

            <span>

                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="vertical-align:-2px; margin-right:6px;">

                    <rect x="3" y="5" width="18" height="16" rx="2" stroke="#C8102E" stroke-width="1.6"/>

                    <path d="M3 9h18M8 3v4M16 3v4" stroke="#C8102E" stroke-width="1.6" stroke-linecap="round"/>

                </svg>

                Siap Export: <strong id="exPeriodLabel">{{ $periodLabel }}</strong>

            </span>

            <span>

                <strong id="exVisibleCount">{{ $exCount }}</strong> baris data ditemukan

            </span>

        </div>

        <form

            id="exDownloadForm"

            method="GET"

            action="{{ route('export.download', ['witel' => $witelSlug]) }}"

        >

            <input type="hidden" name="periode" id="exDownloadPeriode" value="{{ $activePeriod }}">

            <input type="hidden" name="tanggal" id="exDownloadTanggal" value="{{ $selectedTanggal }}">

            <input type="hidden" name="minggu" id="exDownloadMinggu" value="{{ $selectedMinggu }}">

            <input type="hidden" name="bulan" id="exDownloadBulan" value="{{ $selectedBulan }}">

            <input type="hidden" name="tahun" id="exDownloadTahun" value="{{ $selectedTahun }}">

            <input type="hidden" name="dari" id="exDownloadDari" value="{{ $selectedDari }}">

            <input type="hidden" name="sampai" id="exDownloadSampai" value="{{ $selectedSampai }}">

            <button

                type="submit"

                class="ex-download-btn"

                @if($exCount === 0) disabled @endif

            >

                &#8681; Download Data

            </button>

        </form>

    </div>

    <div class="ex-preview-card" id="exPreviewCard">

        <div class="ex-preview-head">

            <div class="ex-preview-head-left">

                <h3 id="exPreviewTitle">Preview Data Export</h3>

                <span>

                    Data sesuai filter:

                    <strong id="exPreviewCount">{{ $exCount }}</strong>

                </span>

            </div>

            <div class="ex-preview-head-actions">

                <button type="button" class="ex-preview-full-btn" id="exOpenFullPreview">

                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">

                        <path d="M8 4H4v4M16 4h4v4M8 20H4v-4M20 20h-4v-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>

                    </svg>

                    Lihat Semua Data

                </button>

                <button type="button" class="ex-preview-full-close" id="exCloseFullPreview" aria-label="Tutup Preview Full">

                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">

                        <path d="M7 7l10 10M17 7 7 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>

                    </svg>

                </button>

            </div>

        </div>

        @if ($exPreviewCount === 0)

            <div class="ex-empty">Belum ada data pada Witel ini</div>

        @else

            <div class="ex-table-scroll">

                <table class="ex-table">

                    <colgroup>

                        <col class="ex-col-no">

                        <col class="ex-col-order">

                        <col class="ex-col-message">

                        <col class="ex-col-sto">

                        <col class="ex-col-date">

                        <col class="ex-col-pic">

                        <col class="ex-col-resolved">

                        <col class="ex-col-status">

                        <col class="ex-col-ket">

                    </colgroup>

                    <thead>

                        <tr>

                            <th>No</th>

                            <th>Order ID</th>

                            <th>Status Message</th>

                            <th>STO</th>

                            <th>Tggl Fallout</th>

                            <th>PIC</th>

                            <th>RESOLVED/ESKALASI</th>

                            <th>Status</th>

                            <th>KET</th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($exPreview as $r)

                            <tr data-export-date="{{ $r->tanggal ? \Carbon\Carbon::parse($r->tanggal)->format('Y-m-d') : '' }}">

                                <td class="ex-no-cell">

                                    {{ $loop->iteration }}

                                </td>

                                <td class="ex-order-cell">

                                    <div class="ex-detail-preview-line">

                                        <span

                                            class="ex-detail-order-text"

                                            title="{{ $r->order_id }}"

                                        >

                                            {{ $r->order_id ?: '-' }}

                                        </span>

                                        @if (!empty($r->order_id))

                                            <button

                                                type="button"

                                                class="ex-detail-view-btn"

                                                data-preview-type="order"

                                                data-preview-value="{{ $r->order_id }}"

                                                title="Lihat Order ID lengkap"

                                                aria-label="Lihat Order ID lengkap"

                                            >

                                                <svg

                                                    width="14"

                                                    height="14"

                                                    viewBox="0 0 24 24"

                                                    fill="none"

                                                    aria-hidden="true"

                                                >

                                                    <path

                                                        d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"

                                                        stroke="currentColor"

                                                        stroke-width="1.8"

                                                        stroke-linecap="round"

                                                        stroke-linejoin="round"

                                                    />

                                                    <circle

                                                        cx="12"

                                                        cy="12"

                                                        r="2.5"

                                                        stroke="currentColor"

                                                        stroke-width="1.8"

                                                    />

                                                </svg>

                                            </button>

                                        @endif

                                    </div>

                                </td>

                                <td class="ex-message-cell">

                                    <div class="ex-detail-preview-line">

                                        <span

                                            class="ex-detail-message-text"

                                            title="{{ $r->deskripsi }}"

                                        >

                                            {{ $r->deskripsi ?: '-' }}

                                        </span>

                                        @if (!empty($r->deskripsi))

                                            <button

                                                type="button"

                                                class="ex-detail-view-btn"

                                                data-preview-type="message"

                                                data-preview-value="{{ $r->deskripsi }}"

                                                title="Lihat Status Message lengkap"

                                                aria-label="Lihat Status Message lengkap"

                                            >

                                                <svg

                                                    width="14"

                                                    height="14"

                                                    viewBox="0 0 24 24"

                                                    fill="none"

                                                    aria-hidden="true"

                                                >

                                                    <path

                                                        d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"

                                                        stroke="currentColor"

                                                        stroke-width="1.8"

                                                        stroke-linecap="round"

                                                        stroke-linejoin="round"

                                                    />

                                                    <circle

                                                        cx="12"

                                                        cy="12"

                                                        r="2.5"

                                                        stroke="currentColor"

                                                        stroke-width="1.8"

                                                    />

                                                </svg>

                                            </button>

                                        @endif

                                    </div>

                                </td>

                                <td>

                                    <span class="ex-sto-tag">

                                        {{ $r->sto }}

                                    </span>

                                </td>

                                <td class="ex-date-cell">

                                    {{

                                        $r->tanggal

                                            ? \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d F Y')

                                            : '-'

                                    }}

                                </td>

                                <td class="ex-single-line">

                                    {{ $r->pic }}

                                </td>

                                <td class="ex-resolved ex-single-line">

                                    {{ $r->resolved_eskalasi }}

                                </td>

                                <td class="ex-single-line">

                                    {{ $r->status }}

                                </td>

                                <td class="ex-single-line">

                                    {{ $r->ket }}

                                </td>

                            </tr>

                        @endforeach

                        <tr id="exNoMatchRow" style="display:none;">
                            <td colspan="9" class="ex-empty">Tidak ada data yang cocok dengan filter ini.</td>
                        </tr>

                    </tbody>

                </table>

            </div>

        @endif

    </div>

</div>

{{-- PREVIEW SEMUA DATA — OVERLAY DI LUAR AREA EXPORT DATA, SEPERTI DETAIL FALLOUT --}}

<div class="ex-full-overlay" id="exFullOverlay" aria-hidden="true">

    <div class="ex-full-shell" id="exFullShell"></div>

</div>

{{-- MODAL PREVIEW DETAIL --}}

<div class="ex-preview-modal-overlay" id="exPreviewModal" aria-hidden="true">

    <div class="ex-preview-modal" role="dialog" aria-modal="true" aria-labelledby="exPreviewModalTitle">

        <div class="ex-preview-modal-head">

            <h3 id="exPreviewModalTitle">Order ID Lengkap</h3>

            <button type="button" class="ex-preview-modal-close" id="exPreviewModalClose" aria-label="Tutup">

                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">

                    <path d="M7 7l10 10M17 7 7 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>

                </svg>

            </button>

        </div>

        <div class="ex-preview-modal-body">

            <div class="ex-preview-modal-value" id="exPreviewModalValue"></div>

        </div>

        <div class="ex-preview-modal-foot">

            <button type="button" class="ex-preview-copy-btn" id="exPreviewCopyBtn">Copy</button>

        </div>

    </div>

</div>

<style>

    .ex-wrap{ width:100%; padding:8px 6px 28px; }

    /* ==========================================================

       LOADING FULL — mengikuti pola Edit Data / halaman lain

       ========================================================== */

    .ex-wrap{

        position:relative;

        min-height:calc(100vh - 250px);

        box-sizing:border-box;

    }

    .ex-page-loading{

        pointer-events:auto;

        cursor:none;

        user-select:none;

        overflow:hidden;

        position:absolute;

        inset:0;

        z-index:99999;

        display:flex;

        align-items:center;

        justify-content:center;

        padding:28px;

        background:rgba(250,247,247,.94);

        backdrop-filter:blur(8px);

        -webkit-backdrop-filter:blur(8px);

        border-radius:22px;

        transition:opacity .28s ease, visibility .28s ease;

    }

    .ex-page-loading.is-hidden{

        opacity:0;

        visibility:hidden;

        pointer-events:none;

    }

    .ex-page-loading-card{

        width:min(320px, 100%);

        padding:28px 24px 24px;

        text-align:center;

        border:1px solid rgba(255,255,255,.9);

        border-radius:18px;

        background:rgba(255,255,255,.88);

        box-shadow:0 24px 60px -30px rgba(58,4,16,.30);

    }

    .ex-page-loading-logo{

        width:52px;

        height:52px;

        margin:0 auto 14px;

        display:grid;

        place-items:center;

        border-radius:14px;

        background:linear-gradient(145deg,#C8102E,#8A0F26);

        color:#fff;

        font-family:'Space Grotesk',sans-serif;

        font-size:17px;

        font-weight:700;

        letter-spacing:.03em;

        box-shadow:0 12px 24px -12px rgba(200,16,46,.75);

    }

    .ex-page-loading-spinner{

        width:30px;

        height:30px;

        margin:0 auto 14px;

        border:3px solid rgba(200,16,46,.14);

        border-top-color:#C8102E;

        border-right-color:#8A0F26;

        border-radius:50%;

        animation:exPageLoadingSpin .8s linear infinite;

    }

    .ex-page-loading-title{

        font-family:'Space Grotesk',sans-serif;

        font-size:14px;

        font-weight:700;

        color:#20161A;

    }

    .ex-page-loading-subtitle{

        margin-top:5px;

        font-family:'Inter',sans-serif;

        font-size:11px;

        line-height:1.5;

        color:#817377;

    }

    @keyframes exPageLoadingSpin{

        to{ transform:rotate(360deg); }

    }

    /* Kunci scroll + cursor saat loading aktif */

    html.exPageLoadingLock,

    body.exPageLoadingLock{

        overflow:hidden !important;

        cursor:none !important;

    }

    body.exPageLoadingLock *,

    html.exPageLoadingLock *{

        cursor:none !important;

    }

    .ex-wrap.exPageLoadingLock{

        overflow:hidden !important;

        cursor:none !important;

    }

    .ex-head h2{ font-family:'Space Grotesk', sans-serif; font-size:22px; font-weight:700; color:var(--red); }

    .ex-head p{ margin-top:4px; font-size:12.5px; color:var(--ink-lo); }

    .ex-card{

        margin-top:18px; background:#fff; border-radius:18px; padding:22px;

        box-shadow:0 14px 30px -22px rgba(58,4,16,0.25); border:1px solid var(--line);

    }

    .ex-tabs{ display:flex; gap:8px; flex-wrap:wrap; }

    .ex-tab-btn{

        padding:9px 18px; border-radius:9px; border:1.4px solid var(--line); background:#fff;

        font-size:12.5px; font-weight:700; color:var(--ink-lo); cursor:pointer; transition:all .15s ease;

    }

    .ex-tab-btn.active{ background:var(--maroon-1); border-color:var(--maroon-1); color:#fff; }

    .ex-tabs-line{ border-bottom:1px solid var(--line); margin:16px 0 18px; }

    .ex-filter-box{ display:flex; gap:16px; flex-wrap:wrap; background:#FAF8F7; border-radius:12px; padding:16px; }

    .ex-field label{ display:block; font-size:11.5px; font-weight:700; color:var(--ink-lo); margin-bottom:6px; }

    .ex-field select, .ex-field input{

        padding:10px 14px; border-radius:9px; border:1.3px solid var(--line); background:#fff;

        font-size:13px; color:var(--ink); font-family:'Inter', sans-serif; min-width:160px;

    }

    .ex-info-bar{

        display:flex; align-items:center; justify-content:space-between; margin-top:16px;

        background:rgba(200,16,46,0.06); border:1px solid rgba(200,16,46,0.18);

        border-radius:11px; padding:13px 18px; font-size:12.5px; color:var(--ink);

    }

    .ex-info-bar strong{ color:var(--red); }

    .ex-download-btn{

        width:100%; margin-top:14px; padding:14px; border:none; border-radius:12px;

        background:linear-gradient(120deg, var(--maroon-1), var(--maroon-0));

        color:#fff; font-size:13.5px; font-weight:700; cursor:pointer; font-family:'Inter', sans-serif;

        transition:opacity .15s ease;

    }

    .ex-download-btn:hover{ opacity:.92; }

    .ex-download-btn:disabled{ background:rgba(200,16,46,0.35); cursor:not-allowed; }

    .ex-preview-card{

        margin-top:16px; background:#fff; border-radius:18px; padding:22px;

        box-shadow:0 14px 30px -22px rgba(58,4,16,0.25); border:1px solid var(--line);

    }

    .ex-preview-head{ display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:16px; }

    .ex-preview-head-left{ min-width:0; }

    .ex-preview-head h3{ font-family:'Space Grotesk', sans-serif; font-size:14.5px; font-weight:700; color:var(--red); }

    .ex-preview-head span{ display:block; margin-top:3px; font-size:12px; color:var(--ink-lo); }

    .ex-preview-head strong{ color:var(--ink); }

    .ex-preview-head-actions{ display:flex; align-items:center; gap:8px; flex:none; }

    .ex-preview-full-btn,

    .ex-preview-full-close{

        border:1px solid var(--line); background:#fff; color:var(--maroon-1);

        border-radius:10px; cursor:pointer; font-family:'Inter',sans-serif; font-size:12px;

        font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:7px; transition:.15s ease;

    }

    .ex-preview-full-btn{ padding:9px 12px; }

    .ex-preview-full-btn:hover{ background:#FBF3F3; border-color:#E2C9CC; }

    .ex-preview-full-close{ width:36px; height:36px; display:none; }

    .ex-preview-full-close:hover{ background:#F7F1F2; }

    html.ex-full-preview-lock,

    body.ex-full-preview-lock{ overflow:hidden !important; }

    /* Overlay full-screen benar-benar berada di atas seluruh dashboard. */

    .ex-full-overlay{

        display:none;

        position:fixed !important;

        inset:0 !important;

        width:100vw !important;

        height:100vh !important;

        z-index:2147482500 !important;

        padding:14px;

        box-sizing:border-box;

        background:rgba(20,16,18,.62);

        backdrop-filter:blur(7px);

        -webkit-backdrop-filter:blur(7px);

        align-items:center;

        justify-content:center;

        overflow:hidden !important;

    }

    .ex-full-overlay.open{

        display:flex;

        animation:exFullFadeIn .18s ease;

    }

    .ex-full-shell{

        width:100%;

        height:100%;

        min-width:0;

        min-height:0;

    }

    .ex-full-overlay .ex-preview-card{

        width:100%;

        height:100%;

        max-height:none;

        margin:0;

        padding:22px;

        display:flex;

        flex-direction:column;

        border-radius:18px;

        box-shadow:0 34px 100px rgba(0,0,0,.38);

        overflow:hidden;

    }

    .ex-full-overlay .ex-preview-head{

        flex:none;

        margin-bottom:14px;

    }

    .ex-full-overlay .ex-preview-full-btn{ display:none; }

    .ex-full-overlay .ex-preview-full-close{ display:inline-flex; }

    .ex-full-overlay .ex-table-scroll{

        flex:1 1 auto;

        min-height:0;

        max-height:none;

        overflow:auto;

        border:1px solid #EEE5E6;

        border-radius:12px;

    }

    .ex-full-overlay table.ex-table{ min-width:1180px; }

    .ex-full-overlay table.ex-table thead th{ position:sticky; top:0; z-index:3; }

    @keyframes exFullFadeIn{ from{opacity:0} to{opacity:1} }

    .ex-empty{ text-align:center; padding:44px 20px; font-size:13px; color:var(--ink-lo); }

    .ex-table-scroll{ overflow-x:auto; border-radius:10px; }

    table.ex-table{ width:100%; border-collapse:separate; border-spacing:0; min-width:1120px; table-layout:fixed; }

    table.ex-table .ex-col-no{ width:54px; }

    table.ex-table .ex-col-order{ width:185px; }

    table.ex-table .ex-col-message{ width:390px; }

    table.ex-table .ex-col-sto{ width:82px; }

    table.ex-table .ex-col-date{ width:135px; }

    table.ex-table .ex-col-pic{ width:90px; }

    table.ex-table .ex-col-resolved{ width:165px; }

    table.ex-table .ex-col-status{ width:105px; }

    table.ex-table .ex-col-ket{ width:100px; }

    table.ex-table thead tr{ background:var(--maroon-1); }

    table.ex-table thead th{

        padding:11px 12px; font-size:10.5px; font-weight:700; color:#fff; text-align:left;

        letter-spacing:.03em; white-space:nowrap; height:38px;

    }

    table.ex-table thead tr th:first-child{ border-top-left-radius:10px; }

    table.ex-table thead tr th:last-child{ border-top-right-radius:10px; }

    table.ex-table tbody tr{ height:54px; }

    table.ex-table tbody tr:nth-child(even){ background:#FBF3F3; }

    table.ex-table tbody tr:hover{ background:rgba(200,16,46,.035); }

    table.ex-table tbody td{

        padding:10px 12px; font-size:12px; border-bottom:1px solid #F1EDEC;

        vertical-align:middle; color:var(--ink);

    }

    .ex-no-cell{ color:var(--ink-lo); font-weight:600; text-align:center; }

    /* ==========================================================

       ORDER ID + STATUS MESSAGE

       Dibuat sama seperti preview Detail Fallout:

       satu baris, ellipsis, lalu icon mata di kanan.

       ========================================================== */

    table.ex-table tbody td.ex-order-cell,

    table.ex-table tbody td.ex-message-cell{

        overflow:hidden;

        height:54px;

        vertical-align:middle;

    }

    .ex-detail-preview-line{

        width:100%;

        height:27px;

        display:flex;

        align-items:center;

        gap:7px;

        min-width:0;

        overflow:hidden;

    }

    .ex-detail-order-text,

    .ex-detail-message-text{

        display:block;

        flex:1 1 auto;

        width:0;

        min-width:0;

        max-width:100%;

        overflow:hidden !important;

        text-overflow:ellipsis !important;

        white-space:nowrap !important;

        line-height:1.4;

    }

    .ex-detail-order-text{

        color:var(--red);

        font-weight:700;

    }

    .ex-detail-message-text{

        color:#4F4A4D;

    }

    .ex-detail-view-btn{

        width:27px;

        height:27px;

        min-width:27px;

        padding:0;

        display:inline-flex;

        align-items:center;

        justify-content:center;

        border:none;

        background:transparent;

        color:#9A8D92;

        border-radius:7px;

        cursor:pointer;

        flex-shrink:0;

        transition:

            background .15s ease,

            color .15s ease,

            transform .15s ease;

    }

    .ex-detail-view-btn:hover{

        background:#F8E8EB;

        color:#C8102E;

    }

    .ex-detail-view-btn:active{

        transform:scale(.95);

    }

    .ex-single-line,

    .ex-date-cell{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

    .ex-sto-tag{

        display:inline-block; padding:4px 10px; border-radius:999px;

        background:#FBE2E2; color:var(--red); font-weight:700; font-size:10.5px;

        white-space:nowrap; max-width:100%; overflow:hidden; text-overflow:ellipsis;

    }

    .ex-resolved{ color:#1F8A4C; font-weight:700; }

    /* ==========================================================

       PREVIEW MODAL — gaya mengikuti preview Detail Fallout

       ========================================================== */

    html.ex-preview-lock,

    body.ex-preview-lock{ overflow:hidden !important; }

    .ex-preview-modal-overlay{

        display:none; position:fixed; inset:0; z-index:2147483000;

        background:rgba(20,16,18,.56); backdrop-filter:blur(7px);

        -webkit-backdrop-filter:blur(7px); align-items:center; justify-content:center;

        padding:16px; box-sizing:border-box;

    }

    .ex-preview-modal-overlay.open{ display:flex; animation:exPreviewFadeIn .18s ease; }

    .ex-preview-modal{

        width:min(700px, calc(100vw - 32px)); max-height:calc(100vh - 32px);

        background:#fff; border-radius:18px; overflow:hidden;

        box-shadow:0 34px 90px rgba(0,0,0,.30);

        animation:exPreviewPop .18s ease;

    }

    .ex-preview-modal-head{

        display:flex; align-items:center; justify-content:space-between; gap:12px;

        padding:18px 22px; border-bottom:1px solid #EEE6E7;

    }

    .ex-preview-modal-head h3{

        margin:0; font-family:'Space Grotesk',sans-serif; font-size:16px;

        font-weight:700; color:#3A3437;

    }

    .ex-preview-modal-close{

        width:32px; height:32px; border:none; border-radius:9px;

        background:#F7F3F2; color:#8C8185; cursor:pointer;

        display:grid; place-items:center;

    }

    .ex-preview-modal-close:hover{ background:#F0E9EA; color:#5C0A1B; }

    .ex-preview-modal-body{ padding:20px 22px; }

    .ex-preview-modal-value{

        min-height:72px; max-height:46vh; overflow-y:auto;

        padding:18px 16px; border:1px solid #E8DEDF; border-radius:12px;

        background:#FBF9F9; color:#5D5558; font-size:13px; line-height:1.65;

        white-space:pre-wrap; overflow-wrap:anywhere; word-break:break-word;

    }

    .ex-preview-modal-foot{

        display:flex; justify-content:flex-end; padding:14px 22px;

        border-top:1px solid #EEE6E7;

    }

    .ex-preview-copy-btn{

        min-width:110px; height:40px; border:none; border-radius:10px;

        background:#B00018; color:#fff; font-family:'Inter',sans-serif;

        font-size:12.5px; font-weight:700; cursor:pointer;

    }

    .ex-preview-copy-btn:hover{ background:#8F0014; }

    @keyframes exPreviewFadeIn{ from{opacity:0} to{opacity:1} }

    @keyframes exPreviewPop{ from{opacity:0; transform:translateY(10px) scale(.98)} to{opacity:1; transform:translateY(0) scale(1)} }

</style>

<script>

    // ============================================================

    // FULL LOADING LOCK — seperti Edit Data / halaman lainnya.

    // Seluruh area Export Data tertutup, scrollbar dikunci, dan

    // cursor disembunyikan selama 1 detik.

    // ============================================================

    (function () {

        const pageLoading = document.getElementById('exPageLoading');

        const pageWrap = document.querySelector('.ex-wrap');

        const lockClass = 'exPageLoadingLock';

        const lockedNodes = [];

        if (!pageLoading || !pageWrap) return;

        function lockScrollableParents() {

            let node = pageWrap;

            while (node) {

                const style = window.getComputedStyle(node);

                const canScrollY =

                    ['auto', 'scroll', 'overlay'].includes(style.overflowY) ||

                    node.scrollHeight > node.clientHeight + 1;

                const canScrollX =

                    ['auto', 'scroll', 'overlay'].includes(style.overflowX) ||

                    node.scrollWidth > node.clientWidth + 1;

                if (canScrollY || canScrollX) {

                    lockedNodes.push({

                        node,

                        overflow: node.style.overflow,

                        overflowY: node.style.overflowY,

                        overflowX: node.style.overflowX,

                    });

                    node.style.setProperty('overflow', 'hidden', 'important');

                    node.style.setProperty('overflow-y', 'hidden', 'important');

                    node.style.setProperty('overflow-x', 'hidden', 'important');

                }

                if (node === document.body) break;

                node = node.parentElement;

            }

        }

        function unlockScrollableParents() {

            lockedNodes.reverse().forEach(function (item) {

                item.node.style.overflow = item.overflow;

                item.node.style.overflowY = item.overflowY;

                item.node.style.overflowX = item.overflowX;

            });

        }

        document.documentElement.classList.add(lockClass);

        document.body.classList.add(lockClass);

        pageWrap.classList.add(lockClass);

        pageLoading.style.cursor = 'none';

        lockScrollableParents();

        setTimeout(function () {

            unlockScrollableParents();

            pageWrap.classList.remove(lockClass);

            document.documentElement.classList.remove(lockClass);

            document.body.classList.remove(lockClass);

            pageLoading.classList.add('is-hidden');

        }, 1000);

    })();

    (function () {
        'use strict';

        let exCurrentPeriod = @json($activePeriod);
        const exAllowedPeriods = ['harian', 'mingguan', 'bulanan', 'tahunan', 'custom'];

        function exFieldValue(id) {
            return document.getElementById(id)?.value || '';
        }

        function exSyncDownloadFields() {
            const values = {
                exDownloadPeriode: exCurrentPeriod,
                exDownloadTanggal: exFieldValue('exTanggal'),
                exDownloadMinggu: exFieldValue('exMinggu'),
                exDownloadBulan: exFieldValue('exBulan'),
                exDownloadTahun: exCurrentPeriod === 'tahunan'
                    ? exFieldValue('exTahunTahunan')
                    : exFieldValue('exTahunBulanan'),
                exDownloadDari: exFieldValue('exDari'),
                exDownloadSampai: exFieldValue('exSampai')
            };

            Object.entries(values).forEach(([id, value]) => {
                const input = document.getElementById(id);
                if (input) input.value = value;
            });
        }

        function exGetIsoWeek(dateText) {
            if (!/^\d{4}-\d{2}-\d{2}$/.test(dateText)) return '';
            const [year, month, day] = dateText.split('-').map(Number);
            const date = new Date(Date.UTC(year, month - 1, day));
            if (date.getUTCFullYear() !== year || date.getUTCMonth() !== month - 1 || date.getUTCDate() !== day) return '';
            const weekday = date.getUTCDay() || 7;
            date.setUTCDate(date.getUTCDate() + 4 - weekday);
            const isoYear = date.getUTCFullYear();
            const yearStart = new Date(Date.UTC(isoYear, 0, 1));
            const week = Math.ceil((((date - yearStart) / 86400000) + 1) / 7);
            return `${isoYear}-W${String(week).padStart(2, '0')}`;
        }

        function exFormatDate(dateText) {
            if (!/^\d{4}-\d{2}-\d{2}$/.test(dateText)) return dateText || '-';
            const [year, month, day] = dateText.split('-').map(Number);
            return new Intl.DateTimeFormat('id-ID', {
                day: '2-digit', month: 'long', year: 'numeric', timeZone: 'UTC'
            }).format(new Date(Date.UTC(year, month - 1, day)));
        }

        function exCurrentLabel() {
            if (exCurrentPeriod === 'harian') return exFormatDate(exFieldValue('exTanggal'));
            if (exCurrentPeriod === 'mingguan') return `Minggu ${exFieldValue('exMinggu') || '-'}`;
            if (exCurrentPeriod === 'bulanan') {
                const monthSelect = document.getElementById('exBulan');
                const month = monthSelect?.selectedOptions?.[0]?.textContent?.trim() || '-';
                return `${month} ${exFieldValue('exTahunBulanan') || ''}`.trim();
            }
            if (exCurrentPeriod === 'tahunan') return `Tahun ${exFieldValue('exTahunTahunan') || '-'}`;

            let from = exFieldValue('exDari');
            let until = exFieldValue('exSampai');
            if (from && until && from > until) [from, until] = [until, from];
            if (!from && !until) return 'Pilih rentang tanggal';
            return `${from ? exFormatDate(from) : 'Awal'} — ${until ? exFormatDate(until) : 'Terakhir'}`;
        }

        function exRowMatches(row) {
            const date = row.dataset.exportDate || '';
            if (!date) return false;

            if (exCurrentPeriod === 'harian') {
                const selected = exFieldValue('exTanggal');
                return selected !== '' && date === selected;
            }
            if (exCurrentPeriod === 'mingguan') {
                const selected = exFieldValue('exMinggu');
                return selected !== '' && exGetIsoWeek(date) === selected;
            }
            if (exCurrentPeriod === 'bulanan') {
                const month = exFieldValue('exBulan').padStart(2, '0');
                const year = exFieldValue('exTahunBulanan');
                return month !== '' && year !== '' && date.startsWith(`${year}-${month}-`);
            }
            if (exCurrentPeriod === 'tahunan') {
                const year = exFieldValue('exTahunTahunan');
                return year !== '' && date.startsWith(`${year}-`);
            }
            if (exCurrentPeriod === 'custom') {
                let from = exFieldValue('exDari');
                let until = exFieldValue('exSampai');
                if (!from && !until) return false;
                if (from && until && from > until) [from, until] = [until, from];
                return (!from || date >= from) && (!until || date <= until);
            }
            return false;
        }

        function exApplyCurrentFilter() {
            const tableBody = document.querySelector('#exPreviewCard .ex-table tbody');
            const countEl = document.getElementById('exVisibleCount');
            const previewCountEl = document.getElementById('exPreviewCount');
            const periodLabelEl = document.getElementById('exPeriodLabel');
            const downloadButton = document.querySelector('#exDownloadForm .ex-download-btn');
            const noMatchRow = document.getElementById('exNoMatchRow');
            const rows = tableBody ? Array.from(tableBody.querySelectorAll('tr[data-export-date]')) : [];
            let visible = 0;

            rows.forEach(row => {
                const matches = exRowMatches(row);
                row.hidden = !matches;
                if (matches) {
                    visible += 1;
                    const numberCell = row.querySelector('.ex-no-cell');
                    if (numberCell) numberCell.textContent = String(visible);
                }
            });

            if (noMatchRow) noMatchRow.style.display = visible === 0 ? '' : 'none';
            if (countEl) countEl.textContent = visible.toLocaleString('id-ID');
            if (previewCountEl) previewCountEl.textContent = visible.toLocaleString('id-ID');
            if (periodLabelEl) periodLabelEl.textContent = exCurrentLabel();
            if (downloadButton) downloadButton.disabled = visible === 0;
            exSyncDownloadFields();
        }

        // Inline onclick perlu berada di window karena halaman dirender melalui navigasi SPA.
        window.exSwitchTab = function (name, button) {
            if (!exAllowedPeriods.includes(name)) return;
            exCurrentPeriod = name;
            document.querySelectorAll('.ex-tab-btn').forEach(tab => tab.classList.remove('active'));
            if (button) button.classList.add('active');
            exAllowedPeriods.forEach(key => {
                const panel = document.getElementById('ex-filter-' + key);
                if (panel) panel.style.display = key === name ? 'flex' : 'none';
            });
            exApplyCurrentFilter();
        };

        document.addEventListener('DOMContentLoaded', function () {
            exAllowedPeriods.forEach(period => {
                const tab = document.querySelector(`.ex-tab-btn[onclick*="${period}"]`);
                const panel = document.getElementById('ex-filter-' + period);
                if (panel) panel.style.display = period === exCurrentPeriod ? 'flex' : 'none';
                if (tab) tab.classList.toggle('active', period === exCurrentPeriod);
            });

            [
                'exTanggal', 'exMinggu', 'exBulan', 'exTahunBulanan',
                'exTahunTahunan', 'exDari', 'exSampai'
            ].forEach(id => {
                const field = document.getElementById(id);
                if (field) field.addEventListener('change', exApplyCurrentFilter);
            });

            const downloadForm = document.getElementById('exDownloadForm');
            if (downloadForm) {
                downloadForm.addEventListener('submit', function (event) {
                    exSyncDownloadFields();
                    if (exCurrentPeriod === 'custom' && !exFieldValue('exDari') && !exFieldValue('exSampai')) {
                        event.preventDefault();
                        window.alert('Pilih minimal satu tanggal pada filter Custom.');
                    }
                });
            }

            exApplyCurrentFilter();
        });
    })();

    document.addEventListener('DOMContentLoaded', function () {

        const fullPreviewCard = document.getElementById('exPreviewCard');

        const openFullPreview = document.getElementById('exOpenFullPreview');

        const closeFullPreview = document.getElementById('exCloseFullPreview');

        const fullOverlay = document.getElementById('exFullOverlay');

        const fullShell = document.getElementById('exFullShell');

        const previewModal = document.getElementById('exPreviewModal');

        /*

         * PENTING:

         * Dashboard memiliki container dengan overflow/backdrop-filter.

         * Element fixed di dalam container tersebut bisa ikut terbatasi oleh

         * dashboard. Karena itu overlay dipindahkan langsung ke <body>,

         * seperti modal pada Detail Fallout.

         */

        if (fullOverlay && fullOverlay.parentElement !== document.body) {

            document.body.appendChild(fullOverlay);

        }

        if (previewModal && previewModal.parentElement !== document.body) {

            document.body.appendChild(previewModal);

        }

        let previewOriginalParent = null;

        let previewOriginalNextSibling = null;

        function exOpenFullPreview(){

            if (!fullPreviewCard || !fullOverlay || !fullShell) return;

            previewOriginalParent = fullPreviewCard.parentElement;

            previewOriginalNextSibling = fullPreviewCard.nextSibling;

            // Pindahkan kartu preview keluar dari area dashboard/export.

            fullShell.appendChild(fullPreviewCard);

            fullOverlay.classList.add('open');

            fullOverlay.setAttribute('aria-hidden', 'false');

            document.documentElement.classList.add('ex-full-preview-lock');

            document.body.classList.add('ex-full-preview-lock');

            setTimeout(() => {

                if (closeFullPreview) closeFullPreview.focus();

            }, 20);

        }

        function exCloseFullPreview(){

            if (!fullPreviewCard || !fullOverlay) return;

            // Kembalikan kartu ke posisi awal setelah preview ditutup.

            if (previewOriginalParent) {

                if (previewOriginalNextSibling && previewOriginalNextSibling.parentNode === previewOriginalParent) {

                    previewOriginalParent.insertBefore(fullPreviewCard, previewOriginalNextSibling);

                } else {

                    previewOriginalParent.appendChild(fullPreviewCard);

                }

            }

            fullOverlay.classList.remove('open');

            fullOverlay.setAttribute('aria-hidden', 'true');

            document.documentElement.classList.remove('ex-full-preview-lock');

            document.body.classList.remove('ex-full-preview-lock');

        }

        if (openFullPreview) {

            openFullPreview.addEventListener('click', exOpenFullPreview);

        }

        if (closeFullPreview) {

            closeFullPreview.addEventListener('click', exCloseFullPreview);

        }

        if (fullOverlay) {

            fullOverlay.addEventListener('click', function(event){

                if (event.target === fullOverlay) {

                    exCloseFullPreview();

                }

            });

        }

        document.addEventListener('keydown', function(event){

            if (event.key === 'Escape' && fullOverlay?.classList.contains('open')) {

                exCloseFullPreview();

            }

        });

        /* ==========================================================

           PREVIEW ORDER ID / STATUS MESSAGE

           ========================================================== */

        const previewTitle = document.getElementById('exPreviewModalTitle');

        const previewValue = document.getElementById('exPreviewModalValue');

        const previewClose = document.getElementById('exPreviewModalClose');

        const previewCopy = document.getElementById('exPreviewCopyBtn');

        function exOpenPreview(type, value){

            if (!previewModal || !previewTitle || !previewValue) return;

            previewTitle.textContent = type === 'message'

                ? 'Status Message Lengkap'

                : 'Order ID Lengkap';

            previewValue.textContent = value || '-';

            previewModal.classList.add('open');

            previewModal.setAttribute('aria-hidden', 'false');

            document.documentElement.classList.add('ex-preview-lock');

            document.body.classList.add('ex-preview-lock');

            setTimeout(() => {

                if (previewClose) previewClose.focus();

            }, 20);

        }

        function exClosePreview(){

            if (!previewModal) return;

            previewModal.classList.remove('open');

            previewModal.setAttribute('aria-hidden', 'true');

            document.documentElement.classList.remove('ex-preview-lock');

            document.body.classList.remove('ex-preview-lock');

        }

        document.querySelectorAll('.ex-detail-view-btn').forEach(btn => {

            btn.addEventListener('click', function(){

                exOpenPreview(

                    this.dataset.previewType || 'order',

                    this.dataset.previewValue || ''

                );

            });

        });

        if (previewClose) {

            previewClose.addEventListener('click', exClosePreview);

        }

        if (previewModal) {

            previewModal.addEventListener('click', function(event){

                if (event.target === previewModal) {

                    exClosePreview();

                }

            });

        }

        document.addEventListener('keydown', function(event){

            if (event.key === 'Escape' && previewModal?.classList.contains('open')) {

                exClosePreview();

            }

        });

        if (previewCopy) {

            previewCopy.addEventListener('click', async function(){

                const text = previewValue?.textContent || '';

                try {

                    await navigator.clipboard.writeText(text);

                    const original = this.textContent;

                    this.textContent = 'Tersalin';

                    setTimeout(() => { this.textContent = original; }, 1200);

                } catch (error) {

                    const textarea = document.createElement('textarea');

                    textarea.value = text;

                    textarea.style.position = 'fixed';

                    textarea.style.opacity = '0';

                    document.body.appendChild(textarea);

                    textarea.select();

                    document.execCommand('copy');

                    textarea.remove();

                    const original = this.textContent;

                    this.textContent = 'Tersalin';

                    setTimeout(() => { this.textContent = original; }, 1200);

                }

            });

        }

    });

</script>
