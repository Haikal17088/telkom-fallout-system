@php

    /*
    |--------------------------------------------------------------------------

    | AMBIL DATA DARI DATABASE
    |--------------------------------------------------------------------------

    */

    $dbRowsQuery = \Illuminate\Support\Facades\DB::table('fallout_data')
        ->join(
            'upload_batches',
            'upload_batches.batch_id',
            '=',
            'fallout_data.batch_id'
        )
        ->join(
            'branches',
            'branches.branch_id',
            '=',
            'upload_batches.branch_id'
        );

    /*
    |--------------------------------------------------------------------------
    | FILTER WITEL
    |--------------------------------------------------------------------------
    | Cocokkan kode Witel secara aman (trim + lowercase).
    */

    $dbRowsQuery->whereRaw(
        'LOWER(TRIM(branches.kode_cabang)) = ?',
        [
            strtolower(
                trim((string) $witelSlug)
            )
        ]
    );

    $dbRows = $dbRowsQuery
        ->orderByDesc('fallout_data.tanggal')
        ->orderByDesc('fallout_data.row_id')
        ->get([
            'fallout_data.row_id',
            'fallout_data.batch_id',
            'fallout_data.order_id',
            'fallout_data.deskripsi',
            'fallout_data.sto',
            'fallout_data.tanggal',
            'fallout_data.pic',
            'fallout_data.resolved_eskalasi',
            'fallout_data.status',
            'fallout_data.ket',
            'fallout_data.uploaded_at',
        ]);


    /*
    |--------------------------------------------------------------------------

    | NORMALISASI STO
    |--------------------------------------------------------------------------

    | Pastikan STO selalu bersih (contoh: KBY), termasuk bila data
    | lama tersimpan sebagai JSON.
    */

    $normalizeSto = function ($value) {

        if ($value === null) {
            return '';
        }

        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if ($value[0] === '{' || $value[0] === '[') {

            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {

                if (isset($decoded['STO']) && !is_array($decoded['STO'])) {
                    $candidate = trim((string) $decoded['STO']);

                    if ($candidate !== '') {
                        return strtoupper($candidate);
                    }
                }

                if (isset($decoded['sto']) && !is_array($decoded['sto'])) {
                    $candidate = trim((string) $decoded['sto']);

                    if ($candidate !== '') {
                        return strtoupper($candidate);
                    }
                }

                if (array_is_list($decoded)) {

                    foreach ($decoded as $item) {

                        if (!is_array($item)) {
                            continue;
                        }

                        $candidate =
                            $item['STO']
                            ?? $item['sto']
                            ?? null;

                        if (
                            $candidate !== null
                            && !is_array($candidate)
                        ) {

                            $candidate = trim((string) $candidate);

                            if ($candidate !== '') {
                                return strtoupper($candidate);
                            }
                        }
                    }
                }
            }

            if (preg_match(
                '/"STO"\s*:\s*"([^"]+)"/i',
                $value,
                $match
            )) {
                return strtoupper(trim($match[1]));
            }
        }

        $unescaped = str_replace('\\\"', '"', $value);

        if (preg_match(
            '/"STO"\s*:\s*"([^"]+)"/i',
            $unescaped,
            $match
        )) {
            return strtoupper(trim($match[1]));
        }

        return strtoupper($value);
    };


    /*
    |--------------------------------------------------------------------------

    | MAPPING DATA
    |--------------------------------------------------------------------------

    */

    $rows = $dbRows->values()->map(function ($r, $i) use ($normalizeSto) {

        /*
        |--------------------------------------------------------------------------

        | RESOLVED / ESKALASI
        |--------------------------------------------------------------------------

        */

        $statusReRaw = trim((string) ($r->resolved_eskalasi ?? ''));

        /*
         * Normalisasi kategori berdasarkan kolom RESOLVED/ESKALASI
         * yang benar-benar ditampilkan pada tabel. Spasi dan tanda
         * hubung disamakan menjadi underscore agar nilai seperti
         * "Eskalasi DIT" dan "ESKALASI_DIT" bisa difilter sama.
         */
        $statusReKey = strtolower($statusReRaw);
        $statusReKey = preg_replace('/[\\s-]+/u', '_', $statusReKey);
        $statusReKey = preg_replace('/_+/', '_', $statusReKey);
        $statusRe = strtoupper($statusReKey);

        $statusReDisplay = [
            'resolved' => 'RESOLVED',
            'eskalasi' => 'ESKALASI',
            'cancel' => 'CANCEL',
            'eskalasi_dit' => 'ESKALASI_DIT',
            'close' => 'CLOSE',
        ][$statusReKey] ?? ($statusReRaw !== '' ? strtoupper($statusReRaw) : '-');


        /*
        |--------------------------------------------------------------------------

        | TANGGAL
        |--------------------------------------------------------------------------

        */

        $tanggalObj = null;

        if ($r->tanggal) {
            try {
                $tanggalObj = \Carbon\Carbon::parse($r->tanggal);
            } catch (\Throwable $e) {
                $tanggalObj = null;
            }
        }


        /*
        |--------------------------------------------------------------------------

        | UPLOADED AT
        |--------------------------------------------------------------------------

        */

        $uploadedAtObj = null;

        if ($r->uploaded_at) {
            try {
                $uploadedAtObj =
                    \Carbon\Carbon::parse($r->uploaded_at);
            } catch (\Throwable $e) {
                $uploadedAtObj = null;
            }
        }


        /*
        |--------------------------------------------------------------------------

        | BARU
        |--------------------------------------------------------------------------

        */

        $isBaru =
            $uploadedAtObj
                ? $uploadedAtObj->isToday()
                : false;


        /*
        |--------------------------------------------------------------------------

        | FILTER STATUS
        |--------------------------------------------------------------------------

        */

        $rowStatus = strtolower(
            trim((string) ($r->status ?? ''))
        );
        $rowStatus = preg_replace('/[\\s-]+/u', '_', $rowStatus);
        $rowStatus = preg_replace('/_+/', '_', $rowStatus);

        /*
         * Filter status mengikuti kategori utama di kolom
         * resolved_eskalasi. Kolom status dipakai sebagai fallback
         * hanya bila kategori utama tidak dikenal/kosong.
         */
        $filterableStatuses = [
            'resolved',
            'eskalasi',
            'cancel',
            'eskalasi_dit',
            'close',
        ];

        if (in_array($statusReKey, $filterableStatuses, true)) {
            $statusReFilter = $statusReKey;
        } elseif (in_array($rowStatus, $filterableStatuses, true)) {
            $statusReFilter = $rowStatus;
        } else {
            $statusReFilter = 'lainnya';
        }


        /*
        |--------------------------------------------------------------------------

        | RETURN
        |--------------------------------------------------------------------------

        */

        return [

            'no' =>
                $i + 1,

            'order_id' =>
                $r->order_id,

            'deskripsi' =>
                $r->deskripsi,

            'sto' =>
                $normalizeSto($r->sto),

            'tgl' =>
                $tanggalObj
                    ? $tanggalObj->translatedFormat('d F Y')
                    : '-',

            'tgl_iso' =>
                $tanggalObj
                    ? $tanggalObj->format('Y-m-d')
                    : '',

            'pic' =>
                $r->pic,

            'status_re' =>
                $statusReDisplay,

            'status_re_filter' =>
                $statusReFilter,

            'status' =>
                $r->status,

            'ket' =>
                $r->ket,

            'baru' =>
                $isBaru,

        ];

    })->all();


    /*
    |--------------------------------------------------------------------------

    | TOTAL DATA
    |--------------------------------------------------------------------------

    */

    $totalData =
        \App\Models\FalloutData::count();


    /*
    |--------------------------------------------------------------------------

    | LIST STO
    |--------------------------------------------------------------------------

    */

    $stoList =
        collect($rows)
            ->map(function ($row) {
                return strtoupper(
                    trim((string) ($row['sto'] ?? ''))
                );
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();


    /*
    |--------------------------------------------------------------------------

    | BULAN
    |--------------------------------------------------------------------------

    */

    $bulanOptions = [

        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember',

    ];


    /*
    |--------------------------------------------------------------------------

    | LABEL STATUS
    |--------------------------------------------------------------------------

    */

    $statusReLabels = [

        'resolved'     => 'RESOLVED',
        'eskalasi'     => 'ESKALASI',
        'cancel'       => 'CANCEL',
        'eskalasi_dit' => 'ESKALASI DIT',
        'close'        => 'CLOSE',
        'lainnya'      => 'LAINNYA',

    ];

@endphp


<div class="df-wrap">

    {{-- Loading khusus halaman Detail Fallout --}}
    <div
        class="df-page-loading"
        id="dfPageLoading"
        aria-live="polite"
        aria-label="Memuat Detail Fallout"
    >
        <div class="df-page-loading-card">
            <div class="df-page-loading-logo">TF</div>
            <div
                class="df-page-loading-spinner"
                aria-hidden="true"
            ></div>
            <div class="df-page-loading-title">
                Memuat Detail Fallout
            </div>
            <div class="df-page-loading-subtitle">
                Menyiapkan data {{ $witel }}
            </div>
        </div>
    </div>

    {{-- Pasang overlay sebelum browser membangun tabel panjang (mis. 284 data). --}}
    <script>
    (function () {
        'use strict';

        var loader = document.getElementById('dfPageLoading');
        var wrap = loader ? loader.closest('.df-wrap') : null;

        if (!loader || !wrap || loader.dataset.dfBootstrapped === '1') {
            return;
        }
        loader.dataset.dfBootstrapped = '1';

        var root = document.documentElement;
        var body = document.body;
        var target =
            wrap.closest('.content-shell') ||
            document.getElementById('contentShell') ||
            wrap.closest('.content') ||
            wrap;

        if (!target) {
            return;
        }

        var hadShellClass = target.classList.contains('df-detail-loading-shell');
        var finished = false;
        var removeTimer = null;

        /*
         * Gaya darurat ditambahkan ke head sebelum parsing tabel lanjut.
         * Meniru Total Rekap: overlay satu lapis, penuh, dan center-flex.
         * Style bersyarat pada class sehingga tidak mengubah halaman lain.
         */
        var earlyStyle = document.getElementById('dfEarlyLoadingStyle');
        if (!earlyStyle) {
            earlyStyle = document.createElement('style');
            earlyStyle.id = 'dfEarlyLoadingStyle';
            earlyStyle.textContent = [
                'html.dfLoadingLock,body.dfLoadingLock{overflow:hidden!important;cursor:none!important}',
                'html.dfLoadingLock *,body.dfLoadingLock *,html.dfLoadingLock *::before,html.dfLoadingLock *::after,body.dfLoadingLock *::before,body.dfLoadingLock *::after{cursor:none!important}',
                '.df-detail-loading-shell{position:relative!important;isolation:isolate!important;overflow:hidden!important}',
                '.df-detail-loading-shell>.df-page-loading{position:absolute!important;inset:0!important;z-index:2147483647!important;display:flex!important;align-items:center!important;justify-content:center!important;box-sizing:border-box!important;padding:24px!important;overflow:hidden!important;user-select:none!important;pointer-events:auto!important;touch-action:none!important;overscroll-behavior:none!important;cursor:none!important;background:rgba(250,247,247,.995)!important;backdrop-filter:blur(8px)!important;-webkit-backdrop-filter:blur(8px)!important;border-radius:22px!important}',
                '.df-detail-loading-shell>.df-page-loading .df-page-loading-card{position:relative!important;top:auto!important;width:min(300px,100%)!important;margin:0 auto!important;box-sizing:border-box!important}',
                '.df-detail-loading-shell>.content{overflow:hidden!important;cursor:none!important}',
                '.df-page-loading,.df-page-loading *,.df-page-loading *::before,.df-page-loading *::after{cursor:none!important}'
            ].join('\n');
            document.head.appendChild(earlyStyle);
        }

        /* Portal loader ke content shell sebelum konten panjang ditampilkan. */
        if (loader.parentElement !== target) {
            target.insertBefore(loader, target.firstChild);
        }

        target.classList.add('df-detail-loading-shell');
        root.classList.add('dfLoadingLock');
        body.classList.add('dfLoadingLock');

        function stopMove(event) {
            event.preventDefault();
        }

        function stopScrollKeys(event) {
            if ([
                'ArrowUp', 'ArrowDown', 'PageUp', 'PageDown',
                'Home', 'End', ' '
            ].indexOf(event.key) !== -1) {
                event.preventDefault();
            }
        }

        loader.addEventListener('wheel', stopMove, { passive: false });
        loader.addEventListener('touchmove', stopMove, { passive: false });
        document.addEventListener('keydown', stopScrollKeys, true);

        function hideDetailLoader() {
            if (finished) {
                return;
            }
            finished = true;

            loader.classList.add('is-hidden');
            loader.removeEventListener('wheel', stopMove);
            loader.removeEventListener('touchmove', stopMove);
            document.removeEventListener('keydown', stopScrollKeys, true);
            window.removeEventListener('pageshow', onPageShow);

            root.classList.remove('dfLoadingLock');
            body.classList.remove('dfLoadingLock');

            if (!hadShellClass) {
                target.classList.remove('df-detail-loading-shell');
            }

            if (removeTimer) {
                window.clearTimeout(removeTimer);
            }
            removeTimer = window.setTimeout(function () {
                if (loader.parentNode) {
                    loader.parentNode.removeChild(loader);
                }
            }, 150);
        }

        function onPageShow(event) {
            if (event.persisted) {
                hideDetailLoader();
            }
        }

        window.addEventListener('pageshow', onPageShow);

        /* Waktu pendek seperti Total Rekap; timeout kedua hanya sebagai pengaman. */
        window.setTimeout(hideDetailLoader, 1000);
        window.setTimeout(hideDetailLoader, 5000);
    })();
    </script>


    {{-- =========================================================
         HEADER
         ========================================================= --}}

    <div class="df-head">

        <div>

            <h2>
                Detail Fallout
            </h2>

            <p>
                Menampilkan
                {{ count($rows) }}
                dari
                {{ $totalData }}
                data
            </p>

        </div>

    </div>


    {{-- =========================================================
         INFO BARU
         ========================================================= --}}

    <div class="df-badge-info">

        <span class="df-badge">
            BARU
        </span>

        <span>
            = Data yang diupload pada
            {{ now()->translatedFormat('d F Y') }}
        </span>

    </div>


    {{-- =========================================================
         FILTER PANEL
         ========================================================= --}}

    <div class="df-panel">


        {{-- TABS --}}

        <div
            class="df-tabs"
            id="dfTabs"
        >

            <button
                type="button"
                class="df-tab active"
                data-range="tanggal"
            >
                Per Tanggal
            </button>

            <button
                type="button"
                class="df-tab"
                data-range="bulan"
            >
                Per Bulan
            </button>

            <button
                type="button"
                class="df-tab"
                data-range="tahun"
            >
                Per Tahun
            </button>

        </div>


        {{-- FILTER --}}

        <div class="df-filter-row">


            {{-- TANGGAL --}}

            <div
                class="df-field"
                data-range-field="tanggal"
            >

                <label>
                    Tanggal:
                </label>

                <input
                    type="date"
                    id="dfTanggal"
                    value=""
                >

            </div>


            {{-- BULAN --}}

            <div
                class="df-field"
                data-range-field="bulan"
                style="display:none;"
            >

                <label>
                    Bulan:
                </label>

                <select id="dfBulan">

                    @foreach ($bulanOptions as $val => $label)

                        <option
                            value="{{ $val }}"
                            {{ $val === now()->format('m') ? 'selected' : '' }}
                        >
                            {{ $label }}
                        </option>

                    @endforeach

                </select>


                <select id="dfTahunBulan">

                    <option value="2025">
                        2025
                    </option>

                    <option
                        value="2026"
                        selected
                    >
                        2026
                    </option>

                </select>

            </div>


            {{-- TAHUN --}}

            <div
                class="df-field"
                data-range-field="tahun"
                style="display:none;"
            >

                <label>
                    Tahun:
                </label>

                <select id="dfTahunOnly">

                    <option value="2025">
                        2025
                    </option>

                    <option
                        value="2026"
                        selected
                    >
                        2026
                    </option>

                </select>

            </div>


            {{-- STO --}}

            <select
                class="df-sto-select"
                id="dfSto"
            >

                <option value="">
                    Semua STO
                </option>

                @foreach ($stoList as $sto)

                    <option value="{{ trim(strtoupper($sto)) }}">
                        {{ trim(strtoupper($sto)) }}
                    </option>

                @endforeach

            </select>

        </div>


        {{-- FILTER STATUS --}}

        <div
            class="df-status-row"
            id="dfStatusRow"
        >

            <button
                type="button"
                class="df-status-btn active"
                data-filter="semua"
            >
                Semua
            </button>

            <button
                type="button"
                class="df-status-btn df-status-resolved"
                data-filter="resolved"
            >
                RESOLVED
            </button>

            <button
                type="button"
                class="df-status-btn df-status-cancel"
                data-filter="cancel"
            >
                Cancel
            </button>

            <button
                type="button"
                class="df-status-btn df-status-eskalasidit"
                data-filter="eskalasi_dit"
            >
                Eskalasi DIT
            </button>

            <button
                type="button"
                class="df-status-btn df-status-close"
                data-filter="close"
            >
                Close
            </button>

        </div>


        {{-- META --}}

        <div
            class="df-meta"
            id="dfMeta"
        >
            Menampilkan data · Semua tanggal
            ·
            {{ count($rows) }}
            data ditemukan
        </div>

    </div>


    {{-- =========================================================
         TABLE
         ========================================================= --}}

    <div class="df-table-wrap">

        <table
            class="df-table"
            id="dfTable"
        >

            <thead>

                <tr>

                    <th>
                        No
                    </th>

                    <th>
                        No/Order ID
                    </th>

                    <th>
                        Deskripsi
                    </th>

                    <th>
                        STO ↕
                    </th>

                    <th>
                        Tgl Fallout ↓
                    </th>

                    <th>
                        PIC ↕
                    </th>

                    <th>
                        RESOLVED/ESK ↕
                    </th>

                    <th>
                        Status ↕
                    </th>

                    <th>
                        KET
                    </th>

                </tr>

            </thead>


            <tbody id="dfTableBody">

                @forelse ($rows as $r)

                    <tr
                        data-status="{{ $r['status_re_filter'] }}"
                        data-sto="{{ $r['sto'] }}"
                        data-date="{{ $r['tgl_iso'] }}"
                        data-order-id="{{ $r['order_id'] }}"
                        data-description="{{ $r['deskripsi'] }}"
                    >


                        {{-- =====================================
                             NO
                             ===================================== --}}

                        <td class="df-no-cell">

                            <div class="df-no-wrap">

                                <span class="df-number">
                                    {{ $r['no'] }}
                                </span>


                                @if ($r['baru'])

                                    <span class="df-badge df-badge-sm">
                                        BARU
                                    </span>

                                @endif

                            </div>

                        </td>


                        {{-- =====================================
                             NO / ORDER ID
                             ===================================== --}}

                        <td class="df-order-cell">

                            <div class="df-preview-line">


                                <span
                                    class="df-order-text"
                                    title="{{ $r['order_id'] }}"
                                >
                                    {{ $r['order_id'] ?: '-' }}
                                </span>


                                @if (!empty($r['order_id']))

                                    <button
                                        type="button"
                                        class="df-view-btn"
                                        data-preview="order"
                                        title="Lihat Order ID lengkap"
                                    >

                                        <svg
                                            width="14"
                                            height="14"
                                            viewBox="0 0 24 24"
                                            fill="none"
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


                        {{-- =====================================
                             DESKRIPSI
                             ===================================== --}}

                        <td class="df-description-cell">

                            <div class="df-preview-line">


                                <span
                                    class="df-description-text"
                                    title="{{ $r['deskripsi'] }}"
                                >
                                    {{ $r['deskripsi'] ?: '-' }}
                                </span>


                                @if (!empty($r['deskripsi']))

                                    <button
                                        type="button"
                                        class="df-view-btn"
                                        data-preview="description"
                                        title="Lihat deskripsi lengkap"
                                    >

                                        <svg
                                            width="14"
                                            height="14"
                                            viewBox="0 0 24 24"
                                            fill="none"
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


                        {{-- =====================================
                             STO
                             ===================================== --}}

                        <td class="df-nowrap">
                            {{ $r['sto'] ?: '-' }}
                        </td>


                        {{-- =====================================
                             TANGGAL
                             ===================================== --}}

                        <td class="df-nowrap">
                            {{ $r['tgl'] ?: '-' }}
                        </td>


                        {{-- =====================================
                             PIC
                             ===================================== --}}

                        <td class="df-nowrap">
                            {{ $r['pic'] ?: '-' }}
                        </td>


                        {{-- =====================================
                             RESOLVED / ESKALASI
                             ===================================== --}}

                        <td>

                            @if ($r['status_re'] === 'RESOLVED')

                                <span class="df-pill df-pill-resolved">
                                    RESOLVED
                                </span>

                            @elseif ($r['status_re'] === 'ESKALASI')

                                <span class="df-pill df-pill-eskalasi">
                                    ESKALASI
                                </span>

                            @elseif ($r['status_re'] === 'CANCEL')

                                <span class="df-pill df-pill-cancel">
                                    CANCEL
                                </span>

                            @elseif ($r['status_re'] === 'ESKALASI_DIT')

                                <span class="df-pill df-pill-eskalasi_dit">
                                    ESKALASI DIT
                                </span>

                            @elseif ($r['status_re'] === 'CLOSE')

                                <span class="df-pill df-pill-close">
                                    CLOSE
                                </span>

                            @else

                                <span class="df-pill">
                                    {{ $r['status_re'] }}
                                </span>

                            @endif

                        </td>


                        {{-- =====================================
                             STATUS
                             ===================================== --}}

                        <td>

                            @php

                                $statusValue =
                                    strtolower(
                                        trim(
                                            (string)
                                            ($r['status'] ?? '')
                                        )
                                    );

                            @endphp


                            @if ($statusValue === 'completed')

                                <span class="df-status-badge df-completed">
                                    COMPLETED
                                </span>

                            @elseif ($statusValue === 'process_oss')

                                <span class="df-status-badge df-process">
                                    Process OSS
                                </span>

                            @else

                                <span class="df-status-badge df-other">
                                    {{ $r['status'] ?: '-' }}
                                </span>

                            @endif

                        </td>


                        {{-- =====================================
                             KET
                             ===================================== --}}

                        <td class="df-ket">
                            {{ $r['ket'] ?: '-' }}
                        </td>


                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="9"
                            class="df-empty"
                        >
                            Belum ada data untuk witel ini.
                            Coba upload data dulu lewat menu
                            "Upload Data".
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>


        <div
            class="df-no-match"
            id="dfNoMatch"
            style="display:none;"
        >
            Tidak ada data yang cocok dengan filter ini.
        </div>

    </div>

</div>


{{-- =============================================================
     MODAL PREVIEW
     ============================================================= --}}

<div
    class="df-modal-overlay"
    id="dfPreviewModal"
>

    <div
        class="df-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="dfModalTitle"
    >


        <div class="df-modal-head">

            <h3
                class="df-modal-title"
                id="dfModalTitle"
            >
                Preview
            </h3>


            <button
                type="button"
                class="df-modal-close"
                id="dfModalClose"
            >
                ×
            </button>

        </div>


        <div class="df-modal-body">

            <div
                class="df-modal-content"
                id="dfModalContent"
            ></div>

        </div>


        <div class="df-modal-footer">

            <button
                type="button"
                class="df-copy-btn"
                id="dfCopyBtn"
            >
                Copy
            </button>

        </div>

    </div>

</div>


<style>

/* =========================================================
   WRAPPER
   ========================================================= */

.df-wrap{
    position:relative;
    width:100%;
    min-height:calc(100vh - 250px);
    padding:8px 6px 28px;
    box-sizing:border-box;
}



  .df-page-loading{
    position:absolute !important;
    inset:0 !important;
    width:100% !important;
    height:100% !important;
    min-height:100% !important;
    z-index:2147483647 !important;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:28px;
    box-sizing:border-box;
    overflow:hidden;
    user-select:none;
    pointer-events:auto;
    touch-action:none;
    overscroll-behavior:none;
    background:rgba(250,247,247,.985);
    border-radius:22px;
    cursor:none !important;
}

.df-page-loading.is-hidden{
    display:none !important;
    opacity:0 !important;
    visibility:hidden !important;
    pointer-events:none !important;
}

/* Saat loader tampil, kursor dan scroll dinonaktifkan seperti Upload Data. */
html.dfLoadingLock,
body.dfLoadingLock{
    overflow:hidden !important;
    cursor:none !important;
}

html.dfLoadingLock *,
body.dfLoadingLock *{
    cursor:none !important;
}

.content-shell.df-detail-loading-shell{
    position:relative !important;
    isolation:isolate !important;
}

.content-shell.df-detail-loading-shell > .df-page-loading{
    position:absolute !important;
    inset:0 !important;
    z-index:2147483647 !important;
}

.df-page-loading,
.df-page-loading *{
    cursor:none !important;
}

.df-page-loading-card{
    width:min(320px,100%);
    padding:28px 24px 24px;
    text-align:center;
    border:1px solid rgba(255,255,255,.95);
    border-radius:18px;
    background:#fff;
    box-shadow:0 18px 45px -28px rgba(58,4,16,.24);
    cursor:none !important;
}

.df-page-loading-logo{
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
    box-shadow:0 10px 22px -14px rgba(200,16,46,.65);
    cursor:none !important;
}

.df-page-loading-spinner{
    width:30px;
    height:30px;
    margin:0 auto 14px;
    border:3px solid rgba(200,16,46,.14);
    border-top-color:#C8102E;
    border-right-color:#8A0F26;
    border-radius:50%;
    animation:dfPageLoadingSpin .72s linear infinite;
}

.df-page-loading-title{
    font-family:'Space Grotesk',sans-serif;
    font-size:14px;
    font-weight:700;
    color:#20161A;
    cursor:none !important;
}

.df-page-loading-subtitle{
    margin-top:5px;
    font-family:'Inter',sans-serif;
    font-size:11px;
    line-height:1.5;
    color:#817377;
    cursor:none !important;
}

@keyframes dfPageLoadingSpin{
    to{transform:rotate(360deg);}
}

/* Saat loader aktif, cursor disembunyikan hanya dalam area Detail Fallout. */
.df-wrap.df-detail-loading,
.df-wrap.df-detail-loading *{
    cursor:none !important;
}
.df-head h2{
    margin:0;
    font-family:'Space Grotesk',sans-serif;
    font-size:22px;
    font-weight:600;
    color:#20161A;
}


.df-head p{
    margin-top:4px;
    font-size:12.5px;
    color:#7A6B6F;
}


/* =========================================================
   BARU
   ========================================================= */

.df-badge-info{
    display:flex;
    align-items:center;
    gap:10px;
    margin-top:16px;
    margin-bottom:18px;
    font-size:12.5px;
    color:#7A6B6F;
}


.df-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background:#C8102E;
    color:#fff;
    font-size:10px;
    font-weight:700;
    letter-spacing:.03em;
    padding:4px 10px;
    border-radius:999px;
    white-space:nowrap;
}


.df-badge-sm{
    font-size:8.5px;
    padding:2px 7px;
}


/* =========================================================
   FILTER PANEL
   ========================================================= */

.df-panel{
    background:rgba(255,255,255,.78);
    border:1px solid rgba(255,255,255,.85);
    border-radius:16px;
    padding:20px;
    margin-bottom:18px;
}


.df-tabs{
    display:inline-flex;
    gap:4px;
    padding:5px;
    border-radius:12px;
    background:rgba(255,255,255,.6);
    border:1px solid #E7DEDD;
    margin-bottom:18px;
}


.df-tab{
    border:none;
    background:none;
    cursor:pointer;
    padding:9px 16px;
    border-radius:9px;
    font-size:12.5px;
    font-weight:600;
    color:#7A6B6F;
    font-family:'Inter',sans-serif;
}


.df-tab.active{
    background:#3A0410;
    color:#fff;
}


.df-filter-row{
    display:flex;
    align-items:center;
    gap:14px;
    margin-bottom:16px;
    flex-wrap:wrap;
}


.df-field{
    display:flex;
    align-items:center;
    gap:8px;
}


.df-field label{
    font-size:13px;
    font-weight:600;
    color:#20161A;
}


.df-field input[type="date"],
.df-field select{
    padding:9px 12px;
    border-radius:9px;
    border:1.3px solid #E7DEDD;
    background:#fff;
    font-size:13px;
    color:#20161A;
    font-family:'Inter',sans-serif;
}


.df-sto-select{
    padding:9px 14px;
    border-radius:9px;
    border:1.3px solid #E7DEDD;
    background:#fff;
    font-size:13px;
    color:#20161A;
    font-family:'Inter',sans-serif;
    min-width:140px;
    margin-left:auto;
}


/* =========================================================
   STATUS FILTER
   ========================================================= */

.df-status-row{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin-bottom:14px;
}


.df-status-btn{
    border:1.3px solid #E7DEDD;
    background:#fff;
    cursor:pointer;
    padding:8px 16px;
    border-radius:9px;
    font-size:12px;
    font-weight:700;
    color:#7A6B6F;
    font-family:'Inter',sans-serif;
}


.df-status-btn.active{
    background:#3A0410;
    color:#fff;
    border-color:#3A0410;
}


.df-status-btn.df-status-resolved.active{
    background:#1E7A46;
    border-color:#1E7A46;
}


.df-status-btn.df-status-cancel.active{
    background:#7A6B6F;
    border-color:#7A6B6F;
}


.df-status-btn.df-status-eskalasidit.active{
    background:#B4651E;
    border-color:#B4651E;
}


.df-status-btn.df-status-close.active{
    background:#3A0410;
    border-color:#3A0410;
}


.df-meta{
    font-size:11.5px;
    color:#7A6B6F;
}


/* =========================================================
   TABLE WRAPPER
   ========================================================= */

.df-table-wrap{
    width:100%;
    background:rgba(255,255,255,.90);
    border:1px solid #E7DEDD;
    border-radius:16px;
    overflow:auto;
    max-height:520px;
    position:relative;
}


/* =========================================================
   TABLE
   ========================================================= */

.df-table{
    width:100%;
    min-width:1280px;
    border-collapse:separate;
    border-spacing:0;
    table-layout:fixed;
    font-size:12px;
}


/* =========================================================
   HEADER TABLE
   ========================================================= */

.df-table thead th{
    position:sticky;
    top:0;
    z-index:10;
    background:#760014;
    color:#fff;
    text-align:left;
    padding:12px 14px;
    font-size:11px;
    font-weight:700;
    white-space:nowrap;
    border-bottom:1px solid #620012;
    overflow:hidden;
    text-overflow:ellipsis;
}


/* =========================================================
   WIDTH KOLOM
   =========================================================
   Tanggal dan PIC diperlebar supaya tidak berdempetan.
   Semua isi kolom juga dipotong dengan ellipsis bila terlalu panjang.
   */

.df-table th:nth-child(1),
.df-table td:nth-child(1){
    width:55px;
}


.df-table th:nth-child(2),
.df-table td:nth-child(2){
    width:185px;
}


.df-table th:nth-child(3),
.df-table td:nth-child(3){
    width:315px;
}


.df-table th:nth-child(4),
.df-table td:nth-child(4){
    width:78px;
}


.df-table th:nth-child(5),
.df-table td:nth-child(5){
    width:145px;
}


.df-table th:nth-child(6),
.df-table td:nth-child(6){
    width:135px;
}


.df-table th:nth-child(7),
.df-table td:nth-child(7){
    width:160px;
}


.df-table th:nth-child(8),
.df-table td:nth-child(8){
    width:175px;
}


.df-table th:nth-child(9),
.df-table td:nth-child(9){
    width:90px;
}


/* =========================================================
   BODY
   ========================================================= */

.df-table tbody tr{
    height:60px;
}


.df-table tbody td{
    height:60px;
    padding:10px 14px;
    border-bottom:1px solid #F1EAE9;
    color:#20161A;
    vertical-align:middle;
    background:#fff;
}


.df-table tbody tr:hover td{
    background:#FFFAFB;
}


/* =========================================================
   NO
   ========================================================= */

.df-no-cell{
    white-space:nowrap;
}


.df-no-wrap{
    display:flex;
    align-items:center;
    gap:6px;
    min-width:0;
}


/* =========================================================
   ORDER ID
   ========================================================= */

.df-order-cell{
    overflow:hidden;
}


.df-preview-line{
    width:100%;
    display:flex;
    align-items:center;
    gap:7px;
    min-width:0;
}


.df-order-text{
    display:block;
    flex:1;
    min-width:0;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    color:#65595E;
    line-height:1.4;
}


/* =========================================================
   DESKRIPSI
   ========================================================= */

.df-description-cell{
    overflow:hidden;
}


.df-description-text{
    display:block;
    flex:1;
    min-width:0;
    max-width:100%;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    color:#31282C;
    line-height:1.4;
}


/* =========================================================
   BUTTON EYE
   ========================================================= */

.df-view-btn{
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


.df-view-btn:hover{
    background:#F8E8EB;
    color:#C8102E;
}


.df-view-btn:active{
    transform:scale(.95);
}


/* =========================================================
   KOLOM BIASA
   =========================================================
   INI YANG MEMASTIKAN TANGGAL TIDAK "BOCOR" KE KOLOM PIC.
   */

.df-nowrap{
    display:block;
    width:100%;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}


/* =========================================================
   PILL
   ========================================================= */

.df-pill{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:24px;
    padding:4px 10px;
    border-radius:999px;
    white-space:nowrap;
    font-size:10px;
    font-weight:700;
    max-width:100%;
    overflow:hidden;
    text-overflow:ellipsis;
}


.df-pill-resolved{
    background:#E7F6ED;
    color:#16804A;
}


.df-pill-eskalasi{
    background:#FCE8EC;
    color:#C8102E;
}


.df-pill-eskalasi_dit{
    background:#FFF1DE;
    color:#B4651E;
}


.df-pill-cancel{
    background:#F0EEEE;
    color:#75686D;
}


.df-pill-close{
    background:#ECE7E9;
    color:#4A1824;
}


/* =========================================================
   STATUS
   ========================================================= */

.df-status-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:24px;
    padding:4px 10px;
    border-radius:999px;
    white-space:nowrap;
    font-size:10px;
    font-weight:700;
    max-width:100%;
    overflow:hidden;
    text-overflow:ellipsis;
}


.df-completed{
    background:#E4F6ED;
    color:#087A42;
}


.df-process{
    background:#FFF1D9;
    color:#A55700;
}


.df-other{
    background:#F0EEEE;
    color:#74686C;
}


/* =========================================================
   KET
   ========================================================= */

.df-ket{
    max-width:90px;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}


/* =========================================================
   EMPTY
   ========================================================= */

.df-empty{
    text-align:center !important;
    padding:40px !important;
    color:#7A6B6F !important;
}


.df-no-match{
    text-align:center;
    padding:32px;
    color:#7A6B6F;
    font-size:12px;
}


/* =========================================================
   MODAL
   ========================================================= */

.df-modal-overlay{
    display:none;
    position:fixed;
    inset:0;
    z-index:999999;
    align-items:center;
    justify-content:center;
    padding:20px;
    background:rgba(25,12,16,.55);
    backdrop-filter:blur(2px);
}


.df-modal-overlay.open{
    display:flex;
}


.df-modal{
    width:min(680px,95vw);
    max-height:88vh;
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    box-shadow:
        0 35px 90px rgba(0,0,0,.30);
    animation:dfModalIn .18s ease-out;
}


@keyframes dfModalIn{

    from{
        opacity:0;
        transform:
            translateY(8px)
            scale(.98);
    }

    to{
        opacity:1;
        transform:
            translateY(0)
            scale(1);
    }

}


/* =========================================================
   MODAL HEADER
   ========================================================= */

.df-modal-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    padding:18px 20px;
    border-bottom:1px solid #ECE5E5;
    background:#fff;
}


.df-modal-title{
    margin:0;
    font-family:'Space Grotesk',sans-serif;
    font-size:17px;
    font-weight:700;
    color:#2C2528;
}


.df-modal-close{
    width:32px;
    height:32px;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:0;
    border:none;
    border-radius:8px;
    background:#F5F0F0;
    color:#8D7F84;
    cursor:pointer;
    font-size:20px;
    line-height:1;
}


.df-modal-close:hover{
    background:#F8E8EB;
    color:#C8102E;
}


/* =========================================================
   MODAL BODY
   ========================================================= */

.df-modal-body{
    padding:20px;
}


.df-modal-content{
    padding:17px;
    min-height:90px;
    max-height:390px;
    overflow-y:auto;
    background:#F8F7F7;
    border:1px solid #EEE7E7;
    border-radius:12px;
    color:#4D4448;
    font-size:13px;
    line-height:1.7;
    white-space:pre-wrap;
    word-break:break-word;
}


/* =========================================================
   MODAL FOOTER
   ========================================================= */

.df-modal-footer{
    display:flex;
    align-items:center;
    justify-content:flex-end;
    padding:14px 20px;
    border-top:1px solid #ECE5E5;
}


.df-copy-btn{
    border:none;
    padding:10px 24px;
    border-radius:9px;
    background:#8D0015;
    color:#fff;
    font-size:12px;
    font-weight:700;
    cursor:pointer;
}


.df-copy-btn:hover{
    background:#710010;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width:900px){

    .df-table{
        min-width:1280px;
    }

}


@media (max-width:640px){

    .df-panel{
        padding:15px;
    }


    .df-filter-row{
        align-items:flex-start;
        flex-direction:column;
    }


    .df-sto-select{
        margin-left:0;
        width:100%;
    }


    .df-modal{
        width:95vw;
    }

}
/* =========================================================
   FINAL TABLE FIX — SESUAI TABEL WEB ASLI
   ========================================================= */

.content .df-table-wrap,
.df-wrap .df-table-wrap{
    width:100% !important;
    max-width:100% !important;
    min-width:0 !important;
    overflow-x:auto !important;
    overflow-y:auto !important;
    box-sizing:border-box !important;
}

.content .df-table,
.df-wrap .df-table{
    display:table !important;
    width:100% !important;
    min-width:0 !important;
    max-width:none !important;
    table-layout:fixed !important;
    border-collapse:separate !important;
    border-spacing:0 !important;
}

.content .df-table thead,
.df-wrap .df-table thead{display:table-header-group !important;}

.content .df-table tbody,
.df-wrap .df-table tbody{display:table-row-group !important;}

.content .df-table tr,
.df-wrap .df-table tr{display:table-row !important;}

.content .df-table th,
.content .df-table td,
.df-wrap .df-table th,
.df-wrap .df-table td{
    display:table-cell !important;
    float:none !important;
    box-sizing:border-box !important;
    vertical-align:middle !important;
}

/* Proporsi tepat 100%. */
.df-wrap .df-table th:nth-child(1),
.df-wrap .df-table td:nth-child(1){width:6% !important;}
.df-wrap .df-table th:nth-child(2),
.df-wrap .df-table td:nth-child(2){width:16% !important;}
.df-wrap .df-table th:nth-child(3),
.df-wrap .df-table td:nth-child(3){width:23% !important;}
.df-wrap .df-table th:nth-child(4),
.df-wrap .df-table td:nth-child(4){width:7% !important;}
.df-wrap .df-table th:nth-child(5),
.df-wrap .df-table td:nth-child(5){width:11% !important;}
.df-wrap .df-table th:nth-child(6),
.df-wrap .df-table td:nth-child(6){width:8% !important;}
.df-wrap .df-table th:nth-child(7),
.df-wrap .df-table td:nth-child(7){width:14% !important;}
.df-wrap .df-table th:nth-child(8),
.df-wrap .df-table td:nth-child(8){width:10% !important;}
.df-wrap .df-table th:nth-child(9),
.df-wrap .df-table td:nth-child(9){width:5% !important;}

.df-wrap .df-table thead th{
    height:38px !important;
    padding:10px 9px !important;
    font-size:10px !important;
    line-height:1.15 !important;
    white-space:nowrap !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
}

.df-wrap .df-table tbody tr{
    height:56px !important;
}

.df-wrap .df-table tbody td{
    height:56px !important;
    padding:8px 9px !important;
    font-size:10.5px !important;
    line-height:1.3 !important;
    vertical-align:middle !important;
    overflow:hidden !important;
}

.df-wrap .df-table td:nth-child(1),
.df-wrap .df-table td:nth-child(4),
.df-wrap .df-table td:nth-child(5),
.df-wrap .df-table td:nth-child(6),
.df-wrap .df-table td:nth-child(7),
.df-wrap .df-table td:nth-child(8),
.df-wrap .df-table td:nth-child(9){
    white-space:nowrap !important;
}

.df-wrap .df-order-cell,
.df-wrap .df-description-cell{
    min-width:0 !important;
    overflow:hidden !important;
}

.df-wrap .df-preview-line{
    width:100% !important;
    min-width:0 !important;
    display:flex !important;
    align-items:center !important;
    gap:5px !important;
    overflow:hidden !important;
}

.df-wrap .df-order-text{
    width:1px !important;
    min-width:0 !important;
    flex:1 1 auto !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
    white-space:nowrap !important;
    font-size:10px !important;
}

.df-wrap .df-description-text{
    width:1px !important;
    min-width:0 !important;
    flex:1 1 auto !important;
    display:-webkit-box !important;
    -webkit-box-orient:vertical !important;
    -webkit-line-clamp:2 !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
    white-space:normal !important;
    max-height:2.6em !important;
    font-size:10px !important;
}

.df-wrap .df-view-btn{
    width:22px !important;
    min-width:22px !important;
    height:22px !important;
    flex:0 0 22px !important;
    padding:0 !important;
}

.df-wrap .df-no-wrap{
    display:flex !important;
    flex-direction:column !important;
    align-items:flex-start !important;
    justify-content:center !important;
    gap:3px !important;
    min-width:0 !important;
    width:100% !important;
    padding-right:0 !important;
}

.df-wrap .df-badge-sm{
    display:inline-flex !important;
    width:auto !important;
    max-width:100% !important;
    padding:2px 6px !important;
    font-size:8px !important;
    line-height:1 !important;
}

.df-wrap .df-view-btn svg{
    width:12px !important;
    height:12px !important;
}

.df-wrap .df-nowrap,
.df-wrap .df-ket{
    width:100% !important;
    min-width:0 !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
    white-space:nowrap !important;
}

.df-wrap .df-pill,
.df-wrap .df-status-badge{
    display:inline-flex !important;
    max-width:100% !important;
    min-width:0 !important;
    padding:4px 8px !important;
    font-size:9px !important;
    line-height:1.1 !important;
    white-space:nowrap !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
}

@media (max-width:1100px){
    .content .df-table-wrap,
    .df-wrap .df-table-wrap{
        width:100% !important;
        max-width:100% !important;
        overflow-x:auto !important;
    }

    .content .df-table,
    .df-wrap .df-table{
        width:100% !important;
        min-width:0 !important;
    }
}


/* =========================================================
   DETAIL FALLOUT — LOADING SAMA POLANYA DENGAN TOTAL REKAP
   Overlay dipasang pada content-shell sebelum tabel panjang.
   ========================================================= */
html.dfLoadingLock,
body.dfLoadingLock {
    overflow: hidden !important;
    cursor: none !important;
}

html.dfLoadingLock *,
body.dfLoadingLock *,
html.dfLoadingLock *::before,
html.dfLoadingLock *::after,
body.dfLoadingLock *::before,
body.dfLoadingLock *::after,
.df-detail-loading-shell,
.df-detail-loading-shell *,
.df-detail-loading-shell *::before,
.df-detail-loading-shell *::after {
    cursor: none !important;
}

.df-detail-loading-shell {
    position: relative !important;
    isolation: isolate !important;
    overflow: hidden !important;
}

.df-detail-loading-shell > .content {
    overflow: hidden !important;
    cursor: none !important;
}

.df-detail-loading-shell > .df-page-loading {
    position: absolute !important;
    inset: 0 !important;
    z-index: 2147483647 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-sizing: border-box !important;
    padding: 24px !important;
    overflow: hidden !important;
    user-select: none !important;
    pointer-events: auto !important;
    touch-action: none !important;
    overscroll-behavior: none !important;
    cursor: none !important;
    background: rgba(250, 247, 247, .995) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    border-radius: 22px !important;
    transition: opacity .18s ease, visibility .18s ease !important;
}

.df-detail-loading-shell > .df-page-loading .df-page-loading-card {
    position: relative !important;
    top: auto !important;
    width: min(300px, 100%) !important;
    margin: 0 auto !important;
    padding: 24px 22px !important;
    box-sizing: border-box !important;
}

.df-page-loading,
.df-page-loading *,
.df-page-loading *::before,
.df-page-loading *::after {
    cursor: none !important;
}

.df-page-loading.is-hidden {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
}
</style>


<script>

(function () {

    /*
    |--------------------------------------------------------------------------

    | FILTER ELEMENT

    |--------------------------------------------------------------------------

    */

    const tabs =
        document.querySelectorAll(
            '#dfTabs .df-tab'
        );


    const fields =
        document.querySelectorAll(
            '.df-field[data-range-field]'
        );


    const statusButtons =
        document.querySelectorAll(
            '#dfStatusRow .df-status-btn'
        );


    const rows =
        document.querySelectorAll(
            '#dfTableBody tr[data-status]'
        );


    const noMatchEl =
        document.getElementById(
            'dfNoMatch'
        );


    const meta =
        document.getElementById(
            'dfMeta'
        );


    const dfTanggal =
        document.getElementById(
            'dfTanggal'
        );


    const dfBulan =
        document.getElementById(
            'dfBulan'
        );


    const dfTahunBulan =
        document.getElementById(
            'dfTahunBulan'
        );


    const dfTahunOnly =
        document.getElementById(
            'dfTahunOnly'
        );


    const dfSto =
        document.getElementById(
            'dfSto'
        );


    /*
    |--------------------------------------------------------------------------
    | MODAL
    |--------------------------------------------------------------------------
    */

    const previewModal =
        document.getElementById(
            'dfPreviewModal'
        );


    const modalTitle =
        document.getElementById(
            'dfModalTitle'
        );


    const modalContent =
        document.getElementById(
            'dfModalContent'
        );


    const modalClose =
        document.getElementById(
            'dfModalClose'
        );


    const copyBtn =
        document.getElementById(
            'dfCopyBtn'
        );


    /*
    |--------------------------------------------------------------------------
    | PINDAHKAN MODAL KE BODY
    |--------------------------------------------------------------------------
    |
    | Ini penting supaya modal tidak terikat ukuran .content dashboard.
    |
    */

    if (
        previewModal &&
        previewModal.parentElement !== document.body
    ) {

        document.body.appendChild(
            previewModal
        );

    }


    /*
    |--------------------------------------------------------------------------
    | RANGE AKTIF
    |--------------------------------------------------------------------------
    */

    let activeRange =
        'tanggal';


    /*
    |--------------------------------------------------------------------------
    | SWITCH RANGE
    |--------------------------------------------------------------------------
    */

    function switchRange(tab) {

        activeRange =
            tab.dataset.range;


        tabs.forEach(
            function (t) {

                t.classList.remove(
                    'active'
                );

            }
        );


        tab.classList.add(
            'active'
        );


        fields.forEach(
            function (field) {

                field.style.display =
                    field.dataset.rangeField
                    === activeRange
                        ? 'flex'
                        : 'none';

            }
        );


        applyFilters();

    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT TANGGAL
    |--------------------------------------------------------------------------
    */

    function formatTanggalId(
        isoDate
    ) {

        if (!isoDate) {
            return '';
        }


        const bulanNama = [

            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',

        ];


        const parts =
            isoDate.split('-');


        if (parts.length !== 3) {
            return isoDate;
        }


        const year =
            parts[0];


        const month =
            parseInt(
                parts[1],
                10
            );


        const day =
            parseInt(
                parts[2],
                10
            );


        return (
            day
            + ' '
            + bulanNama[month - 1]
            + ' '
            + year
        );

    }


    /*
    |--------------------------------------------------------------------------
    | APPLY FILTER
    |--------------------------------------------------------------------------
    */

    function applyFilters() {

        const activeStatusButton =
            document.querySelector(
                '#dfStatusRow .df-status-btn.active'
            );


        const statusFilter =
            activeStatusButton
                ? activeStatusButton.dataset.filter
                : 'semua';


        const stoFilter =
            dfSto
                ? String(dfSto.value || '').trim().toUpperCase()
                : '';


        let shown = 0;

        let periodLabel = '';


        /*
        |--------------------------------------------------------------------------
        | LABEL PERIODE
        |--------------------------------------------------------------------------
        */

        if (
            activeRange ===
            'tanggal'
        ) {

            periodLabel =
                dfTanggal.value
                    ? formatTanggalId(
                        dfTanggal.value
                    )
                    : '';

        }

        else if (
            activeRange ===
            'bulan'
        ) {

            const selected =
                dfBulan.options[
                    dfBulan.selectedIndex
                ];


            periodLabel =
                (
                    selected
                        ? selected.text
                        : ''
                )
                + ' '
                + dfTahunBulan.value;

        }

        else if (
            activeRange ===
            'tahun'
        ) {

            periodLabel =
                'Tahun '
                + dfTahunOnly.value;

        }


        /*
        |--------------------------------------------------------------------------
        | LOOP DATA
        |--------------------------------------------------------------------------
        */

        rows.forEach(
            function (row) {

                const rowDate =
                    row.dataset.date
                    || '';


                const rowSto =
                    String(
                        row.dataset.sto
                        || ''
                    )
                    .trim()
                    .toUpperCase();


                const rowStatus =
                    row.dataset.status
                    || '';


                let rangeMatch =
                    true;


                if (
                    activeRange ===
                    'tanggal'
                ) {

                    rangeMatch =
                        !dfTanggal.value
                        ||
                        rowDate ===
                            dfTanggal.value;

                }


                else if (
                    activeRange ===
                    'bulan'
                ) {

                    rangeMatch =
                        rowDate.slice(0, 4)
                            ===
                        dfTahunBulan.value
                        &&
                        rowDate.slice(5, 7)
                            ===
                        dfBulan.value;

                }


                else if (
                    activeRange ===
                    'tahun'
                ) {

                    rangeMatch =
                        rowDate.slice(0, 4)
                            ===
                        dfTahunOnly.value;

                }


                const statusMatch =
                    statusFilter ===
                    'semua'
                    ||
                    rowStatus ===
                    statusFilter;


                const stoMatch =
                    !stoFilter
                    ||
                    rowSto ===
                    stoFilter;


                const visible =
                    rangeMatch
                    &&
                    statusMatch
                    &&
                    stoMatch;


                /*
                 * CSS tabel memakai display:table-row !important.
                 * Karena itu display biasa dari JS kalah oleh CSS.
                 * Pakai setProperty(..., 'important') agar baris yang
                 * tidak cocok benar-benar tersembunyi saat filter STO.
                 */
                row.style.setProperty(
                    'display',
                    visible
                        ? 'table-row'
                        : 'none',
                    'important'
                );


                if (visible) {
                    shown++;
                }

            }
        );


        if (noMatchEl) {

            noMatchEl.style.display =
                shown === 0
                    ? 'block'
                    : 'none';

        }


        if (meta) {

            meta.textContent =
                'Menampilkan data · '
                + periodLabel
                + ' · '
                + shown
                + ' data ditemukan';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | TAB CLICK
    |--------------------------------------------------------------------------
    */

    tabs.forEach(
        function (tab) {

            tab.addEventListener(
                'click',
                function () {

                    switchRange(
                        this
                    );

                }
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | STATUS CLICK
    |--------------------------------------------------------------------------
    */

    statusButtons.forEach(
        function (button) {

            button.addEventListener(
                'click',
                function () {

                    statusButtons.forEach(
                        function (b) {

                            b.classList.remove(
                                'active'
                            );

                        }
                    );


                    this.classList.add(
                        'active'
                    );


                    applyFilters();

                }
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | FILTER CHANGE
    |--------------------------------------------------------------------------
    */

    [
        dfTanggal,
        dfBulan,
        dfTahunBulan,
        dfTahunOnly,
        dfSto

    ].forEach(
        function (element) {

            if (!element) {
                return;
            }


            element.addEventListener(
                'change',
                applyFilters
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | BUTTON EYE
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '.df-view-btn'
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        const row =
                            this.closest('tr');


                        if (!row) {
                            return;
                        }


                        const previewType =
                            this.dataset.preview;


                        if (
                            previewType ===
                            'order'
                        ) {

                            openPreview(
                                'No/Order ID Lengkap',
                                row.dataset.orderId
                            );

                        }

                        else {

                            openPreview(
                                'Deskripsi Lengkap',
                                row.dataset.description
                            );

                        }

                    }
                );

            }
        );


    /*
    |--------------------------------------------------------------------------
    | OPEN PREVIEW
    |--------------------------------------------------------------------------
    */

    function openPreview(
        title,
        content
    ) {

        if (!previewModal) {
            return;
        }


        modalTitle.textContent =
            title;


        modalContent.textContent =
            content || '-';


        copyBtn.dataset.copyText =
            content || '';


        previewModal.classList.add(
            'open'
        );


        document.body.style.overflow =
            'hidden';

    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE PREVIEW
    |--------------------------------------------------------------------------
    */

    function closePreview() {

        if (!previewModal) {
            return;
        }


        previewModal.classList.remove(
            'open'
        );


        document.body.style.overflow =
            '';

    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE BUTTON
    |--------------------------------------------------------------------------
    */

    if (modalClose) {

        modalClose.addEventListener(
            'click',
            closePreview
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CLICK BACKDROP
    |--------------------------------------------------------------------------
    */

    if (previewModal) {

        previewModal.addEventListener(
            'click',
            function (event) {

                if (
                    event.target ===
                    previewModal
                ) {

                    closePreview();

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | ESCAPE
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key ===
                'Escape'
            ) {

                closePreview();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | COPY BUTTON
    |--------------------------------------------------------------------------
    */

    if (copyBtn) {

        copyBtn.addEventListener(
            'click',
            function () {

                const text =
                    this.dataset.copyText
                    || '';


                if (
                    navigator.clipboard &&
                    navigator.clipboard.writeText
                ) {

                    navigator.clipboard
                        .writeText(text)
                        .then(
                            function () {

                                const oldText =
                                    copyBtn.textContent;


                                copyBtn.textContent =
                                    'Tersalin ✓';


                                setTimeout(
                                    function () {

                                        copyBtn.textContent =
                                            oldText;

                                    },
                                    1200
                                );

                            }
                        );

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | FILTER PERTAMA
    |--------------------------------------------------------------------------
    */

    applyFilters();

})();

</script>
