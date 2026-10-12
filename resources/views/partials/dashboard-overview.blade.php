{{--
    Dashboard overview: ringkasan dari FalloutData yang benar-benar tersimpan.
    Semua pilihan filter dibangun dari relasi batch.branch dan tanggal pada data.
    Tidak menggunakan data contoh / angka hardcode.
--}}
@php
    $dashboardAllRows = \App\Models\FalloutData::query()
        ->with('batch.branch')
        ->orderByDesc('tanggal')
        ->orderByDesc('row_id')
        ->get([
            'row_id',
            'batch_id',
            'tanggal',
            'resolved_eskalasi',
            'status',
            'sto',
        ]);

    $dashboardBranches = $dashboardAllRows
        ->map(fn ($row) => optional($row->batch)->branch)
        ->filter(fn ($branch) => $branch && $branch->branch_id !== null)
        ->unique('branch_id')
        ->sortBy('nama_cabang', SORT_NATURAL | SORT_FLAG_CASE)
        ->values();

    $dashboardYears = $dashboardAllRows
        ->map(function ($row) {
            if (!$row->tanggal) {
                return null;
            }
            try {
                return \Carbon\Carbon::parse($row->tanggal)->format('Y');
            } catch (\Throwable $e) {
                return null;
            }
        })
        ->filter()
        ->unique()
        ->sortDesc()
        ->values();

    $dashboardSelectedBranch = trim((string) request()->query('cabang', 'semua'));
    $dashboardSelectedYear = trim((string) request()->query('tahun', 'semua'));
    $dashboardSelectedMonth = trim((string) request()->query('bulan', 'semua'));
    $dashboardSelectedStatus = strtolower(trim((string) request()->query('status', 'semua')));

    if ($dashboardSelectedBranch !== 'semua' && !$dashboardBranches->contains(fn ($branch) => (string) $branch->branch_id === $dashboardSelectedBranch)) {
        $dashboardSelectedBranch = 'semua';
    }
    if ($dashboardSelectedYear !== 'semua' && !$dashboardYears->contains($dashboardSelectedYear)) {
        $dashboardSelectedYear = 'semua';
    }
    if (!in_array($dashboardSelectedMonth, ['semua', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'], true)) {
        $dashboardSelectedMonth = 'semua';
    }
    if (!in_array($dashboardSelectedStatus, ['semua', 'resolved', 'belum_selesai', 'eskalasi_dit'], true)) {
        $dashboardSelectedStatus = 'semua';
    }

    $dashboardHeroBranchLabel = 'Semua Cabang';
    if ($dashboardSelectedBranch !== 'semua') {
        $dashboardHeroBranch = $dashboardBranches->first(
            fn ($branch) => (string) $branch->branch_id === $dashboardSelectedBranch
        );
        $dashboardHeroBranchLabel = optional($dashboardHeroBranch)->nama_cabang
            ?: (optional($dashboardHeroBranch)->kode_cabang ?: 'Cabang Terpilih');
    }

    $dashboardGetDate = static function ($value) {
        if (!$value) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    };

    $dashboardClassifyStatus = static function ($row) {
        $resolution = strtolower(trim((string) ($row->resolved_eskalasi ?? '')));
        $rawStatus = strtolower(trim((string) ($row->status ?? '')));
        $resolutionKey = preg_replace('/[\s-]+/u', '_', $resolution) ?? $resolution;
        $rawStatusKey = preg_replace('/[\s-]+/u', '_', $rawStatus) ?? $rawStatus;

        if ($resolutionKey === 'resolved') {
            return 'resolved';
        }
        if (
            str_contains($resolutionKey, 'eskalasi') ||
            str_contains($rawStatusKey, 'eskalasi_dit')
        ) {
            return 'eskalasi_dit';
        }
        return 'belum_selesai';
    };

    $dashboardBaseRows = $dashboardAllRows->filter(function ($row) use (
        $dashboardSelectedBranch,
        $dashboardSelectedYear,
        $dashboardSelectedMonth,
        $dashboardGetDate
    ) {
        $branch = optional($row->batch)->branch;
        if ($dashboardSelectedBranch !== 'semua' && (string) ($branch->branch_id ?? '') !== $dashboardSelectedBranch) {
            return false;
        }

        if ($dashboardSelectedYear !== 'semua' || $dashboardSelectedMonth !== 'semua') {
            $date = $dashboardGetDate($row->tanggal);
            if (!$date) {
                return false;
            }
            if ($dashboardSelectedYear !== 'semua' && $date->format('Y') !== $dashboardSelectedYear) {
                return false;
            }
            if ($dashboardSelectedMonth !== 'semua' && $date->format('n') !== $dashboardSelectedMonth) {
                return false;
            }
        }

        return true;
    })->values();

    $dashboardFilteredRows = $dashboardBaseRows->filter(function ($row) use ($dashboardSelectedStatus, $dashboardClassifyStatus) {
        return $dashboardSelectedStatus === 'semua' || $dashboardClassifyStatus($row) === $dashboardSelectedStatus;
    })->values();

    $dashboardTotal = $dashboardFilteredRows->count();
    $dashboardResolved = $dashboardFilteredRows->filter(fn ($row) => $dashboardClassifyStatus($row) === 'resolved')->count();
    $dashboardEscalated = $dashboardFilteredRows->filter(fn ($row) => $dashboardClassifyStatus($row) === 'eskalasi_dit')->count();
    $dashboardUnfinished = $dashboardFilteredRows->filter(fn ($row) => $dashboardClassifyStatus($row) === 'belum_selesai')->count();
    $dashboardToday = $dashboardFilteredRows->filter(function ($row) use ($dashboardGetDate) {
        $date = $dashboardGetDate($row->tanggal);
        return $date && $date->isSameDay(\Carbon\Carbon::today());
    })->count();

    $dashboardResolvedPct = $dashboardTotal > 0 ? round(($dashboardResolved / $dashboardTotal) * 100, 1) : 0;
    $dashboardUnfinishedPct = $dashboardTotal > 0 ? round(($dashboardUnfinished / $dashboardTotal) * 100, 1) : 0;
    $dashboardEscalatedPct = $dashboardTotal > 0 ? round(($dashboardEscalated / $dashboardTotal) * 100, 1) : 0;
    $dashboardEscalatedEnd = min(100, $dashboardResolvedPct + $dashboardEscalatedPct);

    $dashboardMonthNames = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];
    $dashboardMonthlyCounts = array_fill(1, 12, 0);
    foreach ($dashboardFilteredRows as $row) {
        $date = $dashboardGetDate($row->tanggal);
        if ($date) {
            $dashboardMonthlyCounts[(int) $date->format('n')]++;
        }
    }
    $dashboardMaxMonth = max(1, max($dashboardMonthlyCounts));
    $dashboardMonthLabels = [
        'semua' => 'Semua Bulan', '1' => 'Januari', '2' => 'Februari',
        '3' => 'Maret', '4' => 'April', '5' => 'Mei', '6' => 'Juni',
        '7' => 'Juli', '8' => 'Agustus', '9' => 'September',
        '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
    ];
@endphp

<style>
    .dash-overview{display:grid;gap:18px;min-width:0;width:100%;color:#20161A}
    .dash-overview *{box-sizing:border-box}
    .dash-overview-hero{position:relative;overflow:hidden;padding:23px 25px;border-radius:19px;color:#fff;background:linear-gradient(115deg,#65091d,#3A0410);box-shadow:0 16px 35px -25px rgba(58,4,16,.55)}
    .dash-overview-hero:after{content:"";position:absolute;width:190px;height:190px;right:-55px;top:-95px;border:1px solid rgba(255,255,255,.15);border-radius:50%;box-shadow:0 0 0 24px rgba(255,255,255,.035),0 0 0 48px rgba(255,255,255,.025)}
    .dash-hero-status{position:relative;z-index:1;display:inline-flex;align-items:center;gap:7px;padding:6px 12px;margin-bottom:11px;border:1px solid rgba(255,255,255,.2);border-radius:999px;background:rgba(255,255,255,.10);color:#fff;font-size:10px;font-weight:800}
    .dash-hero-status-dot{width:7px;height:7px;border-radius:50%;background:#22d18a;box-shadow:0 0 0 3px rgba(34,209,138,.14)}
    .dash-overview-eyebrow{font-size:10px;letter-spacing:.13em;text-transform:uppercase;color:rgba(255,255,255,.7);font-weight:800}
    .dash-overview-hero h2{position:relative;z-index:1;margin-top:7px;font:700 27px/1.2 'Space Grotesk',sans-serif;color:#fff}
    .dash-hero-pills{position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:9px;margin-top:18px}
    .dash-hero-pill{display:inline-flex;align-items:center;gap:8px;min-height:34px;padding:7px 13px;border:1px solid rgba(255,255,255,.2);border-radius:999px;background:rgba(255,255,255,.10);color:#fff;font-size:11px;font-weight:700}
    .dash-hero-pill svg{width:14px;height:14px;flex:none}
    .dash-overview-hero p{position:relative;z-index:1;margin-top:7px;max-width:700px;font-size:12px;line-height:1.7;color:rgba(255,255,255,.78)}
    .dash-panel{min-width:0;background:rgba(255,255,255,.87);border:1px solid #E7DEDD;border-radius:17px;padding:19px 20px;box-shadow:0 12px 30px -28px rgba(58,4,16,.35)}
    .dash-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px}
    .dash-panel-title{font-size:14px;font-weight:800;color:#20161A;display:flex;align-items:center;gap:9px}
    .dash-panel-title:before{content:"";width:4px;height:17px;border-radius:5px;background:#C8102E}
    .dash-panel-subtitle{font-size:11px;color:#7A6B6F;margin-top:4px;line-height:1.5}
    .dash-filter-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .dash-filter-field{display:grid;gap:7px;min-width:0}
    .dash-filter-field label{font-size:10px;font-weight:800;letter-spacing:.07em;color:#7A6B6F;text-transform:uppercase}
    .dash-filter-field select{width:100%;min-width:0;height:42px;padding:0 12px;border:1px solid #E7DEDD;border-radius:10px;background:#fff;color:#30252a;font:500 12px 'Inter',sans-serif;outline:none}
    .dash-filter-field select:focus{border-color:#C8102E;box-shadow:0 0 0 3px rgba(200,16,46,.08)}
    .dash-filter-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:15px;padding-top:14px;border-top:1px solid #F0E7E6}
    .dash-filter-count{font-size:11px;color:#7A6B6F}.dash-filter-count strong{color:#C8102E}
    .dash-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .dash-btn{display:inline-flex;justify-content:center;align-items:center;gap:7px;min-height:36px;padding:0 13px;border-radius:9px;border:1px solid #E7DEDD;background:white;color:#5C0A1B;text-decoration:none;font:700 11px 'Inter',sans-serif;cursor:pointer}
    .dash-btn-primary{border-color:#69091c;background:#69091c;color:#fff}.dash-btn-primary:hover{background:#4e0615}.dash-btn:hover{border-color:#C8102E}
    .dash-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px}
    .dash-stat{position:relative;min-width:0;overflow:hidden;background:#fff;border:1px solid #E7DEDD;border-radius:15px;padding:17px 17px 15px;box-shadow:0 11px 25px -25px rgba(58,4,16,.35)}
    .dash-stat-top{display:flex;align-items:center;justify-content:space-between;gap:8px}
    .dash-stat-label{font-size:11px;font-weight:700;color:#7A6B6F}
    .dash-stat-icon{width:31px;height:31px;border-radius:10px;background:#fbebee;color:#C8102E;display:grid;place-items:center;font-size:15px;font-weight:800;flex:none}
    .dash-stat-value{font:700 28px/1.15 'Space Grotesk',sans-serif;color:#20161A;margin-top:10px;overflow-wrap:anywhere}
    .dash-stat-note{font-size:10px;color:#9a8b8f;margin-top:6px;line-height:1.5}
    .dash-stat-accent{position:absolute;inset:auto 0 0;height:3px;background:#C8102E;opacity:.85}
    .dash-stat.green .dash-stat-icon{background:#e8f7ef;color:#168354}.dash-stat.green .dash-stat-accent{background:#20ad76}
    .dash-stat.gold .dash-stat-icon{background:#fff5df;color:#ac7615}.dash-stat.gold .dash-stat-accent{background:#D9A441}
    .dash-stat.blue .dash-stat-icon{background:#eaf1ff;color:#2865c7}.dash-stat.blue .dash-stat-accent{background:#2865c7}
    .dash-analytics-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(280px,.9fr);gap:16px;align-items:stretch}
    .dash-chart-card{min-width:0;background:#fff;border:1px solid #E7DEDD;border-radius:17px;padding:19px 20px}
    .dash-chart-title{font-size:13px;font-weight:800;color:#20161A}
    .dash-chart-description{font-size:10.5px;color:#8a7c80;margin-top:5px;line-height:1.5}
    .dash-donut-layout{display:grid;grid-template-columns:190px minmax(0,1fr);align-items:center;gap:16px;margin-top:22px}
    .dash-donut{width:172px;height:172px;border-radius:50%;display:grid;place-items:center;margin:auto;position:relative;background:conic-gradient(#23b77b 0% 0%,#D9A441 0% 0%,#E7DEDD 0% 100%)}
    .dash-donut-hole{width:112px;height:112px;background:#fff;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;box-shadow:0 0 0 1px #f4ebea}
    .dash-donut-hole strong{font:700 27px/1 'Space Grotesk',sans-serif;color:#20161A}
    .dash-donut-hole span{font-size:10px;color:#8a7c80;margin-top:6px}
    .dash-legend{display:grid;gap:13px;min-width:0}
    .dash-legend-item{display:grid;grid-template-columns:10px minmax(0,1fr) auto;align-items:center;gap:8px;font-size:11px;color:#75666b}
    .dash-legend-dot{width:9px;height:9px;border-radius:50%}.dash-legend-item strong{font-size:12px;color:#20161A}
    .dash-legend-meta{font-size:10px;color:#9b8d91;display:block;margin-top:3px}
    .dash-month-bars{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));align-items:end;gap:8px;height:170px;margin-top:18px;padding-top:10px;border-bottom:1px solid #F0E7E6}
    .dash-month-col{height:100%;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;gap:7px;min-width:0}
    .dash-month-value{font-size:9px;color:#7A6B6F;line-height:1;min-height:10px}
    .dash-month-bar-wrap{height:125px;display:flex;align-items:flex-end;width:100%;justify-content:center}
    .dash-month-bar{width:min(22px,78%);min-height:3px;border-radius:5px 5px 0 0;background:linear-gradient(180deg,#da334d,#790b20);transition:height .2s ease}
    .dash-month-label{font-size:9px;color:#8a7c80;padding-top:9px}
    .dash-info-list{display:grid;gap:12px;margin-top:18px}
    .dash-info-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-bottom:12px;border-bottom:1px solid #f1e9e8;font-size:11px;color:#7A6B6F}
    .dash-info-row:last-child{padding-bottom:0;border:0}.dash-info-row strong{font-size:13px;color:#20161A;text-align:right}
    .dash-info-badge{display:inline-flex;align-items:center;gap:6px;color:#168354;background:#eaf8f0;border-radius:999px;padding:5px 9px;font-size:10px;font-weight:800}
    .dash-info-badge:before{content:"";width:6px;height:6px;background:#20ad76;border-radius:50%}
    .dash-empty{padding:25px 14px;text-align:center;background:#fcf9f8;border:1px dashed #e8d8d8;border-radius:12px;color:#7A6B6F;font-size:12px}
    @media(max-width:1200px){.dash-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.dash-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.dash-analytics-grid{grid-template-columns:1fr}}
    @media(max-width:640px){.dash-overview{gap:12px}.dash-overview-hero{padding:19px;border-radius:14px}.dash-overview-hero h2{font-size:20px}.dash-hero-pills{margin-top:14px}.dash-panel,.dash-chart-card{padding:15px;border-radius:14px}.dash-filter-grid{grid-template-columns:1fr 1fr;gap:10px}.dash-filter-field select{height:40px;padding:0 8px;font-size:11px}.dash-stats{gap:9px}.dash-stat{padding:13px}.dash-stat-value{font-size:23px}.dash-stat-label{font-size:10px}.dash-stat-note{font-size:9px}.dash-donut-layout{grid-template-columns:1fr;gap:18px}.dash-donut{width:150px;height:150px}.dash-donut-hole{width:100px;height:100px}.dash-legend{width:100%}.dash-month-bars{gap:4px}.dash-month-label{font-size:8px}.dash-month-value{font-size:8px}}
</style>

<section class="dash-overview" aria-label="Ringkasan dashboard Fallout">
    <header class="dash-overview-hero">
        <div class="dash-hero-status"><span class="dash-hero-status-dot" aria-hidden="true"></span>Sistem Aktif</div>
        <div class="dash-overview-eyebrow">Telkom Fallout System</div>
        <h2>Selamat datang, {{ auth()->user()->name ?? 'Pengguna' }} 👋</h2>
        <p>Pantau dan kelola data Fallout dari seluruh cabang melalui satu dashboard terpusat.</p>
        <div class="dash-hero-pills">
            <span class="dash-hero-pill">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3.5 19 7v5c0 4.5-3 7.2-7 8.5-4-1.3-7-4-7-8.5V7l7-3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ $dashboardHeroBranchLabel }}
            </span>
            <span class="dash-hero-pill">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><ellipse cx="12" cy="6" rx="7.5" ry="3" stroke="currentColor" stroke-width="1.7"/><path d="M4.5 6v6c0 1.7 3.4 3 7.5 3s7.5-1.3 7.5-3V6M4.5 12v6c0 1.7 3.4 3 7.5 3s7.5-1.3 7.5-3v-6" stroke="currentColor" stroke-width="1.7"/></svg>
                {{ number_format($dashboardAllRows->count(), 0, ',', '.') }} Data
            </span>
        </div>
    </header>

    <section class="dash-panel" aria-labelledby="dashFilterTitle">
        <div class="dash-panel-head">
            <div>
                <div class="dash-panel-title" id="dashFilterTitle">Filter Dashboard</div>
                <div class="dash-panel-subtitle">Semua ringkasan mengikuti filter yang dipilih.</div>
            </div>
            <span class="dash-info-badge">Terhubung ke database</span>
        </div>

        <form id="dashboardOverviewFilter" method="GET" action="{{ route('dashboard') }}">
            <div class="dash-filter-grid">
                <div class="dash-filter-field">
                    <label for="dashFilterCabang">Cabang</label>
                    <select id="dashFilterCabang" name="cabang">
                        <option value="semua" {{ $dashboardSelectedBranch === 'semua' ? 'selected' : '' }}>Semua Cabang</option>
                        @foreach ($dashboardBranches as $branch)
                            <option value="{{ $branch->branch_id }}" {{ $dashboardSelectedBranch === (string) $branch->branch_id ? 'selected' : '' }}>{{ $branch->nama_cabang ?: $branch->kode_cabang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="dash-filter-field">
                    <label for="dashFilterTahun">Tahun</label>
                    <select id="dashFilterTahun" name="tahun">
                        <option value="semua" {{ $dashboardSelectedYear === 'semua' ? 'selected' : '' }}>Semua Tahun</option>
                        @foreach ($dashboardYears as $year)
                            <option value="{{ $year }}" {{ $dashboardSelectedYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="dash-filter-field">
                    <label for="dashFilterBulan">Bulan</label>
                    <select id="dashFilterBulan" name="bulan">
                        @foreach ($dashboardMonthLabels as $monthValue => $monthLabel)
                            <option value="{{ $monthValue }}" {{ $dashboardSelectedMonth === (string) $monthValue ? 'selected' : '' }}>{{ $monthLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="dash-filter-field">
                    <label for="dashFilterStatus">Status</label>
                    <select id="dashFilterStatus" name="status">
                        <option value="semua" {{ $dashboardSelectedStatus === 'semua' ? 'selected' : '' }}>Semua Status</option>
                        <option value="resolved" {{ $dashboardSelectedStatus === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="belum_selesai" {{ $dashboardSelectedStatus === 'belum_selesai' ? 'selected' : '' }}>Belum Selesai</option>
                        <option value="eskalasi_dit" {{ $dashboardSelectedStatus === 'eskalasi_dit' ? 'selected' : '' }}>Eskalasi DIT</option>
                    </select>
                </div>
            </div>
            <div class="dash-filter-actions">
                <div class="dash-filter-count">Menampilkan <strong>{{ number_format($dashboardTotal, 0, ',', '.') }}</strong> dari <strong>{{ number_format($dashboardAllRows->count(), 0, ',', '.') }}</strong> data Fallout sesuai filter.</div>
                <div class="dash-actions">
                    <a class="dash-btn" href="{{ route('dashboard') }}" id="dashboardFilterReset">Reset Filter</a>
                    <button class="dash-btn dash-btn-primary" type="submit">Terapkan Filter</button>
                </div>
            </div>
        </form>
    </section>

    <div class="dash-panel-head" style="margin:2px 2px -5px">
        <div>
            <div class="dash-panel-title">Ringkasan Data</div>
            <div class="dash-panel-subtitle">Statistik berdasarkan filter yang dipilih.</div>
        </div>
    </div>

    <section class="dash-stats" aria-label="Statistik Fallout">
        <article class="dash-stat">
            <div class="dash-stat-top"><div class="dash-stat-label">Total Fallout</div><div class="dash-stat-icon">▤</div></div>
            <div class="dash-stat-value">{{ number_format($dashboardTotal, 0, ',', '.') }}</div>
            <div class="dash-stat-note">Jumlah data sesuai filter saat ini</div><div class="dash-stat-accent"></div>
        </article>
        <article class="dash-stat green">
            <div class="dash-stat-top"><div class="dash-stat-label">Resolved</div><div class="dash-stat-icon">✓</div></div>
            <div class="dash-stat-value">{{ number_format($dashboardResolved, 0, ',', '.') }}</div>
            <div class="dash-stat-note">{{ $dashboardResolvedPct }}% dari data yang ditampilkan</div><div class="dash-stat-accent"></div>
        </article>
        <article class="dash-stat gold">
            <div class="dash-stat-top"><div class="dash-stat-label">Eskalasi DIT</div><div class="dash-stat-icon">!</div></div>
            <div class="dash-stat-value">{{ number_format($dashboardEscalated, 0, ',', '.') }}</div>
            <div class="dash-stat-note">{{ $dashboardEscalatedPct }}% dari data yang ditampilkan</div><div class="dash-stat-accent"></div>
        </article>
        <article class="dash-stat blue">
            <div class="dash-stat-top"><div class="dash-stat-label">Data Hari Ini</div><div class="dash-stat-icon">↗</div></div>
            <div class="dash-stat-value">{{ number_format($dashboardToday, 0, ',', '.') }}</div>
            <div class="dash-stat-note">Data bertanggal hari ini sesuai filter</div><div class="dash-stat-accent"></div>
        </article>
    </section>

    <section class="dash-analytics-grid">
        <article class="dash-chart-card">
            <div class="dash-chart-title">Status Penyelesaian</div>
            <div class="dash-chart-description">Perbandingan data resolved, belum selesai, dan eskalasi pada hasil filter.</div>
            @if ($dashboardTotal > 0)
                <div class="dash-donut-layout">
                    <div class="dash-donut" style="background:conic-gradient(#22b77a 0% {{ $dashboardResolvedPct }}%, #D9A441 {{ $dashboardResolvedPct }}% {{ $dashboardEscalatedEnd }}%, #E7DEDD {{ $dashboardEscalatedEnd }}% 100%)" role="img" aria-label="{{ $dashboardResolvedPct }} persen data resolved">
                        <div class="dash-donut-hole"><strong>{{ $dashboardResolvedPct }}%</strong><span>Resolved</span></div>
                    </div>
                    <div class="dash-legend">
                        <div class="dash-legend-item"><span class="dash-legend-dot" style="background:#22b77a"></span><div>Resolved<span class="dash-legend-meta">Sudah selesai</span></div><strong>{{ number_format($dashboardResolved, 0, ',', '.') }}</strong></div>
                        <div class="dash-legend-item"><span class="dash-legend-dot" style="background:#E7DEDD"></span><div>Belum Selesai<span class="dash-legend-meta">Masih dalam penanganan</span></div><strong>{{ number_format($dashboardUnfinished, 0, ',', '.') }}</strong></div>
                        <div class="dash-legend-item"><span class="dash-legend-dot" style="background:#D9A441"></span><div>Eskalasi DIT<span class="dash-legend-meta">Perlu tindak lanjut DIT</span></div><strong>{{ number_format($dashboardEscalated, 0, ',', '.') }}</strong></div>
                    </div>
                </div>
            @else
                <div class="dash-empty" style="margin-top:18px">Belum ada data untuk kombinasi filter ini. Ubah filter atau tambahkan data Fallout.</div>
            @endif
        </article>

        <article class="dash-chart-card">
            <div class="dash-chart-title">Informasi Dashboard</div>
            <div class="dash-chart-description">Ikhtisar data yang sedang ditampilkan.</div>
            <div class="dash-info-list">
                <div class="dash-info-row"><span>Total Data</span><strong>{{ number_format($dashboardTotal, 0, ',', '.') }}</strong></div>
                <div class="dash-info-row"><span>Resolved</span><strong>{{ number_format($dashboardResolved, 0, ',', '.') }}</strong></div>
                <div class="dash-info-row"><span>Belum Selesai</span><strong>{{ number_format($dashboardUnfinished, 0, ',', '.') }}</strong></div>
                <div class="dash-info-row"><span>Eskalasi DIT</span><strong>{{ number_format($dashboardEscalated, 0, ',', '.') }}</strong></div>
                <div class="dash-info-row"><span>Cabang tersedia</span><strong>{{ number_format($dashboardBranches->count(), 0, ',', '.') }}</strong></div>
                <div class="dash-info-row"><span>Waktu dibuka</span><strong>{{ now()->format('d/m/Y H:i') }}</strong></div>
            </div>
        </article>
    </section>

    <section class="dash-chart-card">
        <div class="dash-chart-title">Distribusi Fallout per Bulan</div>
        <div class="dash-chart-description">Grafik mengikuti cabang, tahun, bulan, dan status yang dipilih.</div>
        @if ($dashboardTotal > 0)
            <div class="dash-month-bars" role="img" aria-label="Grafik jumlah Fallout per bulan">
                @foreach ($dashboardMonthNames as $monthNumber => $monthName)
                    @php
                        $monthValue = (int) ($dashboardMonthlyCounts[$monthNumber] ?? 0);
                        $barHeight = $monthValue > 0 ? max(5, round(($monthValue / $dashboardMaxMonth) * 100)) : 3;
                    @endphp
                    <div class="dash-month-col" title="{{ $monthName }}: {{ $monthValue }} data">
                        <div class="dash-month-value">{{ $monthValue ?: '' }}</div>
                        <div class="dash-month-bar-wrap"><div class="dash-month-bar" style="height:{{ $barHeight }}%"></div></div>
                    </div>
                @endforeach
            </div>
            <div style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:8px;margin-top:0">
                @foreach ($dashboardMonthNames as $monthNumber => $monthName)
                    <div class="dash-month-label" style="text-align:center">{{ $monthName }}</div>
                @endforeach
            </div>
        @else
            <div class="dash-empty" style="margin-top:18px">Tidak ada data yang cocok dengan filter saat ini.</div>
        @endif
    </section>
</section>

<script>
(function () {
    var form = document.getElementById('dashboardOverviewFilter');
    if (!form || form.dataset.spaBound === '1') return;
    form.dataset.spaBound = '1';

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var url = new URL(form.action, window.location.href);
        var params = new URLSearchParams();
        new FormData(form).forEach(function (value, key) {
            if (typeof value === 'string' && value.trim() !== '') params.set(key, value.trim());
        });
        url.search = params.toString();
        if (typeof window.tfNavigate === 'function') {
            window.tfNavigate(url.href, {push: true});
        } else {
            window.location.assign(url.href);
        }
    });
})();
</script>
