@php

/*

 * Query DATA dibuat identik dengan Detail Fallout yang sudah terbukti

 * menampilkan data database.

 *

 * Hanya kolom yang dibutuhkan untuk ringkasan yang diambil.

 * Setelah request ini selesai, filter Harian/Bulanan/Tahunan

 * diproses di browser tanpa request baru.

 */

$dbRows = \Illuminate\Support\Facades\DB::table('fallout_data')

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

    )

    ->whereRaw(

        'LOWER(TRIM(branches.kode_cabang)) = ?',

        [

            strtolower(

                trim((string) $witelSlug)

            )

        ]

    )

    ->orderByDesc('fallout_data.tanggal')

    ->orderByDesc('fallout_data.row_id')

    ->get([

        'fallout_data.tanggal',

        'fallout_data.sto',

        'fallout_data.resolved_eskalasi',

        'fallout_data.status',

        'fallout_data.uploaded_by',

    ]);


$makeSummary = static function () {

    return [

        'total' => 0,

        'resolved' => 0,

        'completed' => 0,

        'cancel' => 0,

        'eskalasi_dit' => 0,

        'close' => 0,

        'sto' => 0,

        'sto_breakdown' => [],

        'uploaders' => [],

    ];

};


$daily = [];

$monthly = [];

$yearly = [];

$uploaderIds = [];


foreach ($dbRows as $row) {

    if (empty($row->tanggal)) {

        continue;

    }


    $dayKey = \Carbon\Carbon::parse(

        $row->tanggal

    )->format('Y-m-d');


    $monthKey = substr($dayKey, 0, 7);

    $yearKey = substr($dayKey, 0, 4);


    foreach (

        [

            ['key' => $dayKey, 'bucket' => &$daily],

            ['key' => $monthKey, 'bucket' => &$monthly],

            ['key' => $yearKey, 'bucket' => &$yearly],

        ] as &$target

    ) {

        if (!isset($target['bucket'][$target['key']])) {

            $target['bucket'][$target['key']] =

                $makeSummary();

        }


        $target['bucket'][$target['key']]['total']++;


        $resolvedState = strtolower(

            trim(

                (string) (

                    $row->resolved_eskalasi ?? ''

                )

            )

        );


        $statusState = strtolower(

            trim(

                (string) (

                    $row->status ?? ''

                )

            )

        );


        if ($resolvedState === 'resolved') {

            $target['bucket'][$target['key']]['resolved']++;

        }


        if ($statusState === 'completed') {

            $target['bucket'][$target['key']]['completed']++;

        }


        if ($resolvedState === 'cancel') {

            $target['bucket'][$target['key']]['cancel']++;

        }


        if ($resolvedState === 'eskalasi_dit') {

            $target['bucket'][$target['key']]['eskalasi_dit']++;

        }


        if ($resolvedState === 'close') {

            $target['bucket'][$target['key']]['close']++;

        }


        $sto = strtoupper(

            trim(

                (string) (

                    $row->sto ?? ''

                )

            )

        );


        if ($sto !== '') {

            $target['bucket'][$target['key']]['sto_breakdown'][$sto] =

                (

                    $target['bucket'][$target['key']]['sto_breakdown'][$sto]

                    ?? 0

                ) + 1;

        }


        if (

            $row->uploaded_by !== null

            && $row->uploaded_by !== ''

        ) {

            $idKey = (string) $row->uploaded_by;


            $target['bucket'][$target['key']]['uploaders'][$idKey] =

                (

                    $target['bucket'][$target['key']]['uploaders'][$idKey]

                    ?? 0

                ) + 1;


            $uploaderIds[$idKey] =

                $row->uploaded_by;

        }

    }


    unset($target);

}


foreach (

    [

        &$daily,

        &$monthly,

        &$yearly,

    ] as &$collection

) {

    foreach ($collection as &$bucket) {

        arsort($bucket['sto_breakdown']);

        $bucket['sto'] =

            count($bucket['sto_breakdown']);


        arsort($bucket['uploaders']);

    }

}


unset($collection, $bucket);


$uploaderNames = collect();


if (!empty($uploaderIds)) {

    $uploaderNames = \App\Models\User::query()

        ->whereIn(

            'id',

            array_values($uploaderIds)

        )

        ->pluck('name', 'id');

}


foreach (

    [

        &$daily,

        &$monthly,

        &$yearly,

    ] as &$collection

) {

    foreach ($collection as &$bucket) {

        $namedUploaders = [];


        foreach (

            $bucket['uploaders']

            as $id => $count

        ) {

            $name = $uploaderNames->get(

                $uploaderIds[$id] ?? $id,

                'Tidak diketahui'

            );


            $namedUploaders[$name] =

                (int) $count;

        }


        $bucket['uploaders'] =

            $namedUploaders;

    }

}


unset($collection, $bucket);


krsort($daily);

krsort($monthly);

krsort($yearly);


$availableDates =

    collect(array_keys($daily))

        ->values();


$availableMonths =

    collect(array_keys($monthly))

        ->values();


$availableYears =

    collect(array_keys($yearly))

        ->values();


$range = request()->query('range');


if (!in_array(

    $range,

    [

        'harian',

        'bulanan',

        'tahunan',

    ],

    true

)) {

    $range = 'harian';

}


$latestAvailableDate =

    $availableDates->first()

    ?? now()->format('Y-m-d');


$requestDate =

    request()->query('tanggal');


$selectedDate = (

    is_string($requestDate)

    && preg_match(

        '/^\d{4}-\d{2}-\d{2}$/',

        $requestDate

    )

)

    ? $requestDate

    : $latestAvailableDate;


$latestMonthValue =

    $availableMonths->first()

    ?? now()->format('Y-m');


$requestMonth =

    request()->query('bulan');


$selectedMonthValue = (

    is_string($requestMonth)

    && preg_match(

        '/^\d{4}-\d{2}$/',

        $requestMonth

    )

)

    ? $requestMonth

    : $latestMonthValue;


$requestYear =

    request()->query('tahun');


$selectedYear = (

    is_string($requestYear)

    && preg_match(

        '/^\d{4}$/',

        $requestYear

    )

)

    ? $requestYear

    : substr(

        $latestMonthValue,

        0,

        4

    );


$selectedMonth =

    substr(

        $selectedMonthValue,

        5,

        2

    );


$monthValues =

    collect(range(1, 12))

        ->mapWithKeys(

            static function ($month) {

                $value =

                    str_pad(

                        (string) $month,

                        2,

                        '0',

                        STR_PAD_LEFT

                    );


                return [

                    $value =>

                        \Carbon\Carbon::create(

                            2000,

                            (int) $month,

                            1

                        )->translatedFormat('F')

                ];

            }

        );


$currentKey = match ($range) {

    'tahunan' =>

        (string) $selectedYear,


    'bulanan' =>

        (string) $selectedMonthValue,


    default =>

        (string) $selectedDate,

};


$current = match ($range) {

    'tahunan' =>

        $yearly[$currentKey]

        ?? $makeSummary(),


    'bulanan' =>

        $monthly[$currentKey]

        ?? $makeSummary(),


    default =>

        $daily[$currentKey]

        ?? $makeSummary(),

};


$periodLabel = match ($range) {

    'harian' =>

        \Carbon\Carbon::parse(

            $selectedDate

        )->translatedFormat('d F Y'),


    'tahunan' =>

        $selectedYear,


    default =>

        \Carbon\Carbon::createFromFormat(

            'Y-m',

            $selectedMonthValue

        )->translatedFormat('F Y'),

};


$d = [

    'total' =>

        (int) $current['total'],


    'resolved' =>

        (int) $current['resolved'],


    'completed' =>

        (int) $current['completed'],


    'sto' =>

        (int) $current['sto'],


    'cancel' =>

        (int) $current['cancel'],


    'eskalasi_dit' =>

        (int) $current['eskalasi_dit'],


    'close' =>

        (int) $current['close'],


    'uploaders' =>

        $current['uploaders'],


    'sto_breakdown' =>

        $current['sto_breakdown'],


    'tipe_fallout' =>

        'Provisioning Failed',


    'sistem' =>

        'UIM / OSM / OSS',

];


$eskalasi =

    max(

        $d['total'] -

        $d['resolved'],

        0

    );


$resolvedPct =

    $d['total'] > 0

        ? round(

            $d['resolved'] /

            $d['total'] *

            100

        )

        : 0;


$completedPct =

    $d['resolved'] > 0

        ? round(

            $d['completed'] /

            $d['resolved'] *

            100

        )

        : 0;


$chartId =

    'stoChart_' . $witelSlug;


$showInitialLoader =

    request()->query('range') === null

    && request()->query('tanggal') === null

    && request()->query('bulan') === null

    && request()->query('tahun') === null;


$clientSummary = [

    'daily' => $daily,

    'monthly' => $monthly,

    'yearly' => $yearly,

];

@endphp


<div class="tr-wrap">


    @if ($showInitialLoader)
        <div
            class="tr-page-loading"
            id="trPageLoading"
            aria-live="polite"
            aria-label="Memuat Total Rekap Fallout"
        >
            <div class="tr-page-loading-card">
                <div class="tr-page-loading-logo">TF</div>
                <div class="tr-page-loading-spinner" aria-hidden="true"></div>
                <div class="tr-page-loading-title">Memuat Total Rekap Fallout</div>
                <div class="tr-page-loading-subtitle">
                    Menyiapkan data {{ $witel }}
                </div>
            </div>
        </div>

        <script>
        (function () {
            'use strict';

            var loader = document.getElementById('trPageLoading');
            if (!loader) return;

            var root = document.documentElement;
            var body = document.body;
            var wrap = loader.closest('.tr-wrap');
            var shell = loader.closest('.content-shell');
            var content = loader.closest('.content');
            var target = shell || content || wrap;

            /*
             * Portalkan overlay ke container konten utama, bukan .tr-wrap.
             * Dengan begitu loading menutup seluruh area konten seperti Upload Data,
             * tanpa menutupi sidebar/header dashboard.
             */
            if (target && loader.parentElement !== target) {
                target.insertBefore(loader, target.firstChild);
            }

            if (target) target.classList.add('tr-loading-shell');
            root.classList.add('trLoadingLock');
            body.classList.add('trLoadingLock');

            var finished = false;

            function stopMove(event) {
                event.preventDefault();
            }

            loader.addEventListener('wheel', stopMove, { passive: false });
            loader.addEventListener('touchmove', stopMove, { passive: false });

            function hideLoader() {
                if (finished) return;
                finished = true;

                loader.classList.add('is-hidden');
                loader.removeEventListener('wheel', stopMove);
                loader.removeEventListener('touchmove', stopMove);

                root.classList.remove('trLoadingLock');
                body.classList.remove('trLoadingLock');
                if (target) target.classList.remove('tr-loading-shell');

                window.setTimeout(function () {
                    if (loader.parentNode) loader.parentNode.removeChild(loader);
                }, 180);
            }

            /* Tutup setelah konten sempat tertutup penuh, dengan pengaman timeout. */
            window.setTimeout(hideLoader, 1000);
            window.setTimeout(hideLoader, 6000);

            window.addEventListener('pageshow', function (event) {
                if (event.persisted) hideLoader();
            });
        })();
        </script>
    @endif

    <div class="tr-head">


        <span class="tr-tag">

            {{ $witel }}

        </span>


        <h2>

            Total Rekap Fallout

        </h2>


        <p>

            Periode: {{ $periodLabel }}

        </p>


    </div>


    <div class="tr-tabs">


        <button

            type="button"

            class="tr-tab active"

            data-range="harian"

                    >

            Rekap Harian

        </button>


        <button

            type="button"

            class="tr-tab"

            data-range="bulanan"

                    >

            Rekap Bulanan

        </button>


        <button

            type="button"

            class="tr-tab"

            data-range="tahunan"

                    >

            Rekap Tahunan

        </button>


    </div>


    <div

        class="tr-filters"

        data-range-group="harian"

    >


        <div class="tr-field">


            <label>

                Tanggal

            </label>


            <input

                type="date"

                data-auto-filter="tanggal"

                value="{{ $selectedDate }}"

                            >


        </div>


    </div>


    <div

        class="tr-filters"

        data-range-group="bulanan"

        style="display:none;"

    >


        <div class="tr-field">


            <label>

                Bulan

            </label>


            <select

                data-auto-filter="bulan"

                            >


                @foreach ($monthValues as $monthValue => $monthLabel)


                    <option

                        value="{{ $monthValue }}"

                        {{ (string) $selectedMonth === (string) $monthValue ? 'selected' : '' }}

                    >

                        {{ $monthLabel }}

                    </option>


                @endforeach


            </select>


        </div>


        <div class="tr-field">


            <label>

                Tahun

            </label>


            <select

                data-auto-filter="tahun"

                            >


                @forelse ($availableYears as $year)


                    <option

                        value="{{ $year }}"

                        {{ (string) $selectedYear === (string) $year ? 'selected' : '' }}

                    >

                        {{ $year }}

                    </option>


                @empty


                    <option

                        value="{{ now()->format('Y') }}"

                    >

                        {{ now()->format('Y') }}

                    </option>


                @endforelse


            </select>


        </div>


    </div>


    <div

        class="tr-filters"

        data-range-group="tahunan"

        style="display:none;"

    >


        <div class="tr-field">


            <label>

                Tahun

            </label>


            <select

                data-auto-filter="tahun"

                            >


                @forelse ($availableYears as $year)


                    <option

                        value="{{ $year }}"

                        {{ (string) $selectedYear === (string) $year ? 'selected' : '' }}

                    >

                        {{ $year }}

                    </option>


                @empty


                    <option value="">

                        Tidak ada tahun

                    </option>


                @endforelse


            </select>


        </div>


    </div>


    <div class="tr-stats">


        <div class="tr-stat-card">


            <div class="tr-stat-top">


                <span class="tr-stat-label">

                    Total Fallout

                </span>


                <span class="tr-stat-icon">


                    <svg

                        width="16"

                        height="16"

                        viewBox="0 0 24 24"

                        fill="none"

                    >


                        <path

                            d="M9 2 1 16h16L9 2Z"

                            stroke="#C8102E"

                            stroke-width="1.6"

                            stroke-linejoin="round"

                        />


                        <path

                            d="M9 8v3.5M9 13.2v.1"

                            stroke="#C8102E"

                            stroke-width="1.6"

                            stroke-linecap="round"

                        />


                    </svg>


                </span>


            </div>


            <div class="tr-stat-value" data-tr-stat="total">{{ $d['total'] }}</div>


            <div class="tr-stat-sub" data-tr-period>{{ $periodLabel }}</div>


        </div>


        <div class="tr-stat-card">


            <div class="tr-stat-top">


                <span class="tr-stat-label">

                    RESOLVED

                </span>


                <span class="tr-stat-icon">


                    <svg

                        width="16"

                        height="16"

                        viewBox="0 0 24 24"

                        fill="none"

                    >


                        <circle

                            cx="9"

                            cy="9"

                            r="8"

                            stroke="#C8102E"

                            stroke-width="1.6"

                        />


                        <path

                            d="M5.5 9.3l2.3 2.3L13 6.8"

                            stroke="#C8102E"

                            stroke-width="1.6"

                            stroke-linecap="round"

                            stroke-linejoin="round"

                        />


                    </svg>


                </span>


            </div>


            <div class="tr-stat-value" data-tr-stat="resolved">{{ $d['resolved'] }}</div>


            <div class="tr-stat-sub" data-tr-escalation>{{ $eskalasi }} eskalasi</div>


        </div>


        <div class="tr-stat-card">


            <div class="tr-stat-top">


                <span class="tr-stat-label">

                    COMPLETED

                </span>


                <span class="tr-stat-icon">


                    <svg

                        width="16"

                        height="16"

                        viewBox="0 0 24 24"

                        fill="none"

                    >


                        <circle

                            cx="9"

                            cy="9"

                            r="8"

                            stroke="#C8102E"

                            stroke-width="1.6"

                        />


                        <path

                            d="M9 5v4l3 2"

                            stroke="#C8102E"

                            stroke-width="1.6"

                            stroke-linecap="round"

                        />


                    </svg>


                </span>


            </div>


            <div class="tr-stat-value" data-tr-stat="completed">{{ $d['completed'] }}</div>


            <div class="tr-stat-sub">

                proses selesai

            </div>


        </div>


        <div class="tr-stat-card">


            <div class="tr-stat-top">


                <span class="tr-stat-label">

                    STO Aktif

                </span>


                <span class="tr-stat-icon">


                    <svg

                        width="16"

                        height="16"

                        viewBox="0 0 24 24"

                        fill="none"

                    >


                        <path

                            d="M2 15l4-5 3 3 6-8"

                            stroke="#C8102E"

                            stroke-width="1.7"

                            stroke-linecap="round"

                            stroke-linejoin="round"

                        />


                        <path

                            d="M12 5h4v4"

                            stroke="#C8102E"

                            stroke-width="1.7"

                            stroke-linecap="round"

                            stroke-linejoin="round"

                        />


                    </svg>


                </span>


            </div>


            <div class="tr-stat-value" data-tr-stat="sto">{{ $d['sto'] }}</div>


            <div class="tr-stat-sub">

                titik STO tercatat

            </div>


        </div>


        <div class="tr-stat-card">


            <div class="tr-stat-top">


                <span class="tr-stat-label">

                    CANCEL

                </span>


                <span class="tr-stat-icon">


                    <svg

                        width="16"

                        height="16"

                        viewBox="0 0 24 24"

                        fill="none"

                    >


                        <circle

                            cx="9"

                            cy="9"

                            r="8"

                            stroke="#C8102E"

                            stroke-width="1.6"

                        />


                        <path

                            d="M6 6l6 6M12 6l-6 6"

                            stroke="#C8102E"

                            stroke-width="1.6"

                            stroke-linecap="round"

                        />


                    </svg>


                </span>


            </div>


            <div class="tr-stat-value" data-tr-stat="cancel">{{ $d['cancel'] }}</div>


            <div class="tr-stat-sub">

                dibatalkan

            </div>


        </div>


        <div class="tr-stat-card">


            <div class="tr-stat-top">


                <span class="tr-stat-label">

                    ESKALASI DIT

                </span>


                <span class="tr-stat-icon">


                    <svg

                        width="16"

                        height="16"

                        viewBox="0 0 24 24"

                        fill="none"

                    >


                        <path

                            d="M4 14l3-6 3 4 3-8 3 10"

                            stroke="#C8102E"

                            stroke-width="1.6"

                            stroke-linecap="round"

                            stroke-linejoin="round"

                        />


                    </svg>


                </span>


            </div>


            <div class="tr-stat-value" data-tr-stat="eskalasi_dit">{{ $d['eskalasi_dit'] }}</div>


            <div class="tr-stat-sub">

                naik ke DIT

            </div>


        </div>


        <div class="tr-stat-card">


            <div class="tr-stat-top">


                <span class="tr-stat-label">

                    CLOSE

                </span>


                <span class="tr-stat-icon">


                    <svg

                        width="16"

                        height="16"

                        viewBox="0 0 24 24"

                        fill="none"

                    >


                        <rect

                            x="3"

                            y="3"

                            width="12"

                            height="12"

                            rx="3"

                            stroke="#C8102E"

                            stroke-width="1.6"

                        />


                        <path

                            d="M6.5 9l2 2 3-4"

                            stroke="#C8102E"

                            stroke-width="1.6"

                            stroke-linecap="round"

                            stroke-linejoin="round"

                        />


                    </svg>


                </span>


            </div>


            <div class="tr-stat-value" data-tr-stat="close">{{ $d['close'] }}</div>


            <div class="tr-stat-sub">

                tiket ditutup

            </div>


        </div>


    </div>


    <div class="tr-panels">


        <div class="tr-panel">


            <h3>

                RESOLVED / ESKALASI

            </h3>


            <div class="tr-donut-wrap">


                <svg

                    width="150"

                    height="150"

                    viewBox="0 0 150 150"

                >


                    <circle

                        cx="75"

                        cy="75"

                        r="60"

                        stroke="#E7DEDD"

                        stroke-width="16"

                        fill="none"

                    />


                    <circle

                        cx="75"

                        cy="75"

                        r="60"

                        stroke="#C8102E"

                        stroke-width="16"

                        fill="none"

                        data-tr-donut="resolved" stroke-dasharray="{{ round(2 * 3.14159 * 60 * $resolvedPct / 100) }} 999"

                        stroke-linecap="round"

                        transform="rotate(-90 75 75)"

                    />


                    <text

                        x="75"

                        y="80"

                        text-anchor="middle"

                        font-family="Space Grotesk, sans-serif"

                        font-weight="700"

                        font-size="22"

                        fill="#20161A"

                    >

                        {{ $resolvedPct }}%

                    </text>


                </svg>


                <div class="tr-legend">


                    <div>

                        <span

                            class="dot"

                            style="background:#C8102E"

                        ></span>


                        Resolved (<span data-tr-legend="resolved">{{ $d['resolved'] }}</span>)

                    </div>


                    <div>

                        <span

                            class="dot"

                            style="background:#E7DEDD"

                        ></span>


                        Eskalasi (<span data-tr-legend="escalation">{{ $eskalasi }}</span>)

                    </div>


                </div>


            </div>


        </div>


        <div class="tr-panel">


            <h3>

                Status Penyelesaian

            </h3>


            <div class="tr-donut-wrap">


                <svg

                    width="150"

                    height="150"

                    viewBox="0 0 150 150"

                >


                    <circle

                        cx="75"

                        cy="75"

                        r="60"

                        stroke="#E7DEDD"

                        stroke-width="16"

                        fill="none"

                    />


                    <circle

                        cx="75"

                        cy="75"

                        r="60"

                        stroke="#D9A441"

                        stroke-width="16"

                        fill="none"

                        data-tr-donut="completed" stroke-dasharray="{{ round(2 * 3.14159 * 60 * $completedPct / 100) }} 999"

                        stroke-linecap="round"

                        transform="rotate(-90 75 75)"

                    />


                    <text

                        x="75"

                        y="80"

                        text-anchor="middle"

                        font-family="Space Grotesk, sans-serif"

                        font-weight="700"

                        font-size="22"

                        fill="#20161A"

                    >

                        {{ $completedPct }}%

                    </text>


                </svg>


                <div class="tr-legend">


                    <div>

                        <span

                            class="dot"

                            style="background:#D9A441"

                        ></span>


                        Completed (<span data-tr-legend="completed">{{ $d['completed'] }}</span>)

                    </div>


                    <div>


                        <span

                            class="dot"

                            style="background:#E7DEDD"

                        ></span>


                        Proses

                        (<span data-tr-legend="process">{{ $d['resolved'] - $d['completed'] }}</span>)


                    </div>


                </div>


            </div>


        </div>


        <div class="tr-panel tr-info">


            <h3>

                Info Rekap

            </h3>


            <div class="tr-info-row">


                <span>

                    Witel

                </span>


                <b>

                    {{ $witel }}

                </b>


            </div>


            <div class="tr-info-row">


                <span>

                    Periode Aktif

                </span>


                <b data-tr-info="period">

                    {{ $periodLabel }}

                </b>


            </div>


            <div class="tr-info-row">


                <span>

                    Total Record

                </span>


                <b data-tr-info="total">

                    {{ $d['total'] }} data

                </b>


            </div>


            <div class="tr-info-sub" data-tr-uploader-label>

                Upload ({{ $periodLabel }})

            </div>


            <div

                class="tr-uploader-chips"

                id="trUploaderChips"

            >

                @foreach ($d['uploaders'] as $name => $count)

                    <span class="tr-chip">

                        {{ $name }}

                        <b>{{ $count }}</b>

                    </span>

                @endforeach

            </div>


            <div class="tr-info-row">


                <span>

                    Tipe Fallout

                </span>


                <b>

                    {{ $d['tipe_fallout'] }}

                </b>


            </div>


            <div class="tr-info-row">


                <span>

                    Sistem

                </span>


                <b>

                    {{ $d['sistem'] }}

                </b>


            </div>


        </div>


    </div>


    @if (!empty($d['sto_breakdown']))

        <div class="tr-panel tr-bar-panel">


            <h3>

                Fallout per STO

            </h3>


            <div

                class="tr-chart-box"

                id="trStoChart"

                aria-label="Fallout per STO"

            >

                @php

                    $maxSto = max($d['sto_breakdown']);

                @endphp


                @foreach ($d['sto_breakdown'] as $stoName => $stoTotal)

                    <div class="tr-bar-row">

                        <div class="tr-bar-label">

                            <span>{{ $stoName }}</span>

                            <b>{{ $stoTotal }}</b>

                        </div>


                        <div class="tr-bar-track">

                            <div

                                class="tr-bar-fill"

                                style="width: {{ $maxSto > 0 ? round($stoTotal / $maxSto * 100, 2) : 0 }}%;"

                            ></div>

                        </div>

                    </div>

                @endforeach

            </div>


        </div>

    @endif


</div>


<style>


.tr-wrap {


    position:

        relative;


    width:

        100%;


    min-height:

        calc(100vh - 250px);


    padding:

        8px 6px 28px;


    box-sizing:

        border-box;

}


.tr-page-loading-card {


    width:

        min(

            320px,

            100%

        );


    padding:

        28px 24px 24px;


    text-align:

        center;


    border:

        1px solid

        rgba(

            255,

            255,

            255,

            .9

        );


    border-radius:

        18px;


    background:

        rgba(

            255,

            255,

            255,

            .88

        );


    box-shadow:

        0 24px

        60px

        -30px

        rgba(

            58,

            4,

            16,

            .30

        );


}


.tr-page-loading-logo {


    width:

        52px;


    height:

        52px;


    margin:

        0 auto 14px;


    display:

        grid;


    place-items:

        center;


    border-radius:

        14px;


    background:

        linear-gradient(

            145deg,

            #C8102E,

            #8A0F26

        );


    color:

        #fff;


    font-family:

        'Space Grotesk',

        sans-serif;


    font-size:

        17px;


    font-weight:

        700;


    letter-spacing:

        .03em;


    box-shadow:

        0 12px

        24px

        -12px

        rgba(

            200,

            16,

            46,

            .75

        );


}


.tr-page-loading-spinner {


    width:

        30px;


    height:

        30px;


    margin:

        0 auto 14px;


    border:

        3px solid

        rgba(

            200,

            16,

            46,

            .14

        );


    border-top-color:

        #C8102E;


    border-right-color:

        #8A0F26;


    border-radius:

        50%;


    animation:

        trPageLoadingSpin

        .8s

        linear

        infinite;


}


.tr-page-loading-title {


    font-family:

        'Space Grotesk',

        sans-serif;


    font-size:

        14px;


    font-weight:

        700;


    color:

        #20161A;


}


.tr-page-loading-subtitle {


    margin-top:

        5px;


    font-family:

        'Inter',

        sans-serif;


    font-size:

        11px;


    line-height:

        1.5;


    color:

        #817377;


}


@keyframes trPageLoadingSpin {


    to {


        transform:

            rotate(

                360deg

            );


    }


}


.tr-head {


    margin-bottom:

        22px;


}


.tr-tag {


    display:

        inline-block;


    font-size:

        11px;


    font-weight:

        700;


    letter-spacing:

        0.04em;


    color:

        #C8102E;


    background:

        rgba(

            200,

            16,

            46,

            0.08

        );


    padding:

        5px 12px;


    border-radius:

        999px;


    margin-bottom:

        10px;


}


.tr-head h2 {


    font-family:

        'Space Grotesk',

        sans-serif;


    font-size:

        22px;


    font-weight:

        600;


    color:

        #20161A;


}


.tr-head p {


    margin-top:

        4px;


    font-size:

        12.5px;


    color:

        #7A6B6F;


}


.tr-tabs {


    display:

        inline-flex;


    gap:

        4px;


    padding:

        5px;


    border-radius:

        12px;


    background:

        rgba(

            255,

            255,

            255,

            0.6

        );


    border:

        1px solid

        rgba(

            255,

            255,

            255,

            0.7

        );


    margin-bottom:

        20px;


}


.tr-tab {


    border:

        none;


    background:

        none;


    cursor:

        pointer;


    padding:

        9px 16px;


    border-radius:

        9px;


    font-size:

        12.5px;


    font-weight:

        600;


    color:

        #7A6B6F;


    font-family:

        'Inter',

        sans-serif;


}


.tr-tab.active {


    background:

        #3A0410;


    color:

        #fff;


}


.tr-filters {


    display:

        flex;


    gap:

        14px;


    margin-bottom:

        22px;


}


.tr-field label {


    display:

        block;


    font-size:

        11.5px;


    font-weight:

        700;


    color:

        #20161A;


    margin-bottom:

        6px;


}


.tr-field select,

.tr-field input[type="date"] {


    padding:

        10px 14px;


    border-radius:

        9px;


    border:

        1.3px solid

        #E7DEDD;


    background:

        #fff;


    font-size:

        13px;


    color:

        #20161A;


    font-family:

        'Inter',

        sans-serif;


    min-width:

        140px;


}


.tr-stats {


    display:

        grid;


    grid-template-columns:

        repeat(

            auto-fill,

            minmax(

                160px,

                1fr

            )

        );


    gap:

        14px;


    margin-bottom:

        22px;


}


.tr-stat-card {


    background:

        rgba(

            255,

            255,

            255,

            0.7

        );


    border:

        1px solid

        rgba(

            255,

            255,

            255,

            0.8

        );


    border-radius:

        16px;


    padding:

        18px;


}


.tr-stat-top {


    display:

        flex;


    align-items:

        center;


    justify-content:

        space-between;


}


.tr-stat-label {


    font-size:

        11.5px;


    color:

        #7A6B6F;


    font-weight:

        600;


}


.tr-stat-icon {


    width:

        30px;


    height:

        30px;


    border-radius:

        9px;


    background:

        rgba(

            200,

            16,

            46,

            0.08

        );


    display:

        grid;


    place-items:

        center;


}


.tr-stat-value {


    font-family:

        'Space Grotesk',

        sans-serif;


    font-weight:

        700;


    font-size:

        28px;


    color:

        #20161A;


    margin-top:

        12px;


}


.tr-stat-sub {


    font-size:

        11px;


    color:

        #7A6B6F;


    margin-top:

        4px;


}


.tr-panels {


    display:

        grid;


    grid-template-columns:

        1fr 1fr 1fr;


    gap:

        14px;


    margin-bottom:

        14px;


    align-items:

        start;


}


.tr-panel {


    background:

        rgba(

            255,

            255,

            255,

            0.7

        );


    border:

        1px solid

        rgba(

            255,

            255,

            255,

            0.8

        );


    border-radius:

        16px;


    padding:

        18px;


}


.tr-panel h3 {


    font-family:

        'Space Grotesk',

        sans-serif;


    font-size:

        13px;


    font-weight:

        600;


    color:

        #C8102E;


    margin-bottom:

        14px;


}


.tr-donut-wrap {


    display:

        flex;


    flex-direction:

        column;


    align-items:

        center;


    gap:

        12px;


}


.tr-legend {


    font-size:

        11.5px;


    color:

        #20161A;


    width:

        100%;


}


.tr-legend div {


    display:

        flex;


    align-items:

        center;


    gap:

        7px;


    padding:

        4px 0;


}


.tr-legend .dot {


    width:

        9px;


    height:

        9px;


    border-radius:

        50%;


    flex:

        none;


    display:

        inline-block;


}


.tr-info-row {


    display:

        flex;


    justify-content:

        space-between;


    align-items:

        center;


    padding:

        10px 0;


    font-size:

        12px;


    color:

        #7A6B6F;


    border-bottom:

        1px solid

        #E7DEDD;


}


.tr-info-row:last-child {


    border-bottom:

        none;


}


.tr-info-row b {


    color:

        #20161A;


    font-size:

        12.5px;


}


.tr-info-sub {


    font-size:

        10.5px;


    font-weight:

        700;


    letter-spacing:

        0.03em;


    color:

        #7A6B6F;


    text-transform:

        uppercase;


    padding-top:

        12px;


    margin-bottom:

        4px;


}


.tr-uploader-chips {


    display:

        flex;


    flex-wrap:

        wrap;


    gap:

        6px;


    padding-bottom:

        10px;


    border-bottom:

        1px solid

        #E7DEDD;


    margin-bottom:

        2px;


}


.tr-chip {


    display:

        inline-flex;


    align-items:

        center;


    gap:

        5px;


    padding:

        5px 10px;


    border-radius:

        999px;


    background:

        rgba(

            200,

            16,

            46,

            0.07

        );


    font-size:

        11px;


    color:

        #20161A;


    font-weight:

        500;


}


.tr-chip b {


    color:

        #C8102E;


    font-weight:

        700;


}


/* =========================================================

   CHART

   ========================================================= */


.tr-bar-panel {


    width:

        100%;


    padding-bottom:

        12px;


}


.tr-chart-box {


    position:

        relative;


    height:

        220px;


    width:

        100%;


    margin-top:

        4px;


}


@media (max-width: 980px) {


    .tr-panels {


        grid-template-columns:

            1fr;


    }


}


/* =========================================================
   TOTAL REKAP — FULL CONTENT LOADING OVERLAY
   ========================================================= */
html.trLoadingLock,
body.trLoadingLock {
    overflow: hidden !important;
    cursor: none !important;
}

html.trLoadingLock *,
body.trLoadingLock *,
.tr-loading-shell,
.tr-loading-shell * {
    cursor: none !important;
}

.tr-loading-shell {
    position: relative !important;
    isolation: isolate !important;
    overflow: hidden !important;
}

.tr-page-loading {
    position: absolute !important;
    inset: 0 !important;
    z-index: 2147483647 !important;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    box-sizing: border-box;
    overflow: hidden;
    user-select: none;
    pointer-events: auto;
    touch-action: none;
    overscroll-behavior: none;
    cursor: none !important;
    background: rgba(250, 247, 247, 0.985) !important;
    border-radius: 22px;
}

.tr-page-loading.is-hidden {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
}

.tr-page-loading-card {
    width: min(300px, 100%);
    padding: 24px 22px;
    text-align: center;
    border: 1px solid rgba(255,255,255,.9);
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 18px 45px -28px rgba(58,4,16,.24);
    cursor: none !important;
}

.tr-page-loading-logo {
    width: 48px;
    height: 48px;
    margin: 0 auto 12px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: linear-gradient(145deg, #C8102E, #8A0F26);
    color: #fff;
    font: 700 16px/1 "Space Grotesk", sans-serif;
    cursor: none !important;
}

.tr-page-loading-spinner {
    width: 28px;
    height: 28px;
    margin: 0 auto 12px;
    border: 3px solid rgba(200,16,46,.14);
    border-top-color: #C8102E;
    border-right-color: #8A0F26;
    border-radius: 50%;
    animation: trPageLoadingSpin .72s linear infinite;
}

.tr-page-loading-title {
    font: 700 14px/1.25 "Space Grotesk", sans-serif;
    color: #20161A;
    cursor: none !important;
}

.tr-page-loading-subtitle {
    margin-top: 5px;
    font: 11px/1.5 "Inter", sans-serif;
    color: #817377;
    cursor: none !important;
}

@keyframes trPageLoadingSpin {
    to { transform: rotate(360deg); }
}
.tr-bar-row {

    display: grid;

    gap: 6px;

    margin-top: 10px;

}


.tr-bar-label {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    font-size: 11.5px;

    color: #514347;

}


.tr-bar-label b {

    color: #20161A;

}


.tr-bar-track {

    height: 10px;

    border-radius: 999px;

    overflow: hidden;

    background: #EEE7E6;

}


.tr-bar-fill {

    height: 100%;

    border-radius: inherit;

    background: #8A0F26;

    transition: width .14s ease;

}


</style>


<script>

(function () {

    var wrap = document.querySelector('.tr-wrap');


    if (!wrap) {

        return;

    }


    var payload = @json($clientSummary);

    var range = @json($range);


    var selectedDate = @json($selectedDate);

    var selectedMonth = @json($selectedMonth);

    var selectedYear = @json($selectedYear);


    var statMap = {};

    wrap.querySelectorAll('[data-tr-stat]').forEach(function (el) {

        statMap[el.dataset.trStat] = el;

    });


    var legendMap = {};

    wrap.querySelectorAll('[data-tr-legend]').forEach(function (el) {

        legendMap[el.dataset.trLegend] = el;

    });


    var donutMap = {};

    wrap.querySelectorAll('[data-tr-donut]').forEach(function (el) {

        donutMap[el.dataset.trDonut] = el;

    });


    var infoPeriod =

        wrap.querySelector('[data-tr-info="period"]');


    var infoTotal =

        wrap.querySelector('[data-tr-info="total"]');


    var periodSub =

        wrap.querySelector('[data-tr-period]');


    var escalationSub =

        wrap.querySelector('[data-tr-escalation]');


    var uploaderLabel =

        wrap.querySelector('[data-tr-uploader-label]');


    var uploaderBox =

        wrap.querySelector('#trUploaderChips');


    var chartBox =

        wrap.querySelector('#trStoChart');


    var dateField =

        wrap.querySelector('[data-auto-filter="tanggal"]');


    var monthField =

        wrap.querySelector('[data-auto-filter="bulan"]');


    var yearFields =

        wrap.querySelectorAll('[data-auto-filter="tahun"]');


    var tabs =

        wrap.querySelectorAll('.tr-tab');


    var groups =

        wrap.querySelectorAll('[data-range-group]');


    function emptyData() {

        return {

            total: 0,

            resolved: 0,

            completed: 0,

            cancel: 0,

            eskalasi_dit: 0,

            close: 0,

            sto: 0,

            sto_breakdown: {},

            uploaders: {}

        };

    }


    function getData() {

        if (range === 'tahunan') {

            return payload.yearly[selectedYear]

                || emptyData();

        }


        if (range === 'bulanan') {

            var monthKey =

                selectedYear + '-' +

                String(selectedMonth).padStart(2, '0');


            return payload.monthly[monthKey]

                || emptyData();

        }


        return payload.daily[selectedDate]

            || emptyData();

    }


    function getLabel() {

        var value;

        var options;


        if (range === 'tahunan') {

            value = selectedYear + '-01-01';


            options = {

                year: 'numeric'

            };

        } else if (range === 'bulanan') {

            value =

                selectedYear + '-' +

                String(selectedMonth).padStart(2, '0') +

                '-01';


            options = {

                month: 'long',

                year: 'numeric'

            };

        } else {

            value = selectedDate;


            options = {

                day: '2-digit',

                month: 'long',

                year: 'numeric'

            };

        }


        return new Date(

            value + 'T00:00:00'

        ).toLocaleDateString(

            'id-ID',

            options

        );

    }


    function updateUrl() {

        var url =

            new URL(window.location.href);


        url.searchParams.set(

            'range',

            range

        );


        url.searchParams.delete('tanggal');

        url.searchParams.delete('bulan');

        url.searchParams.delete('tahun');


        if (range === 'harian') {

            url.searchParams.set(

                'tanggal',

                selectedDate

            );

        }


        if (range === 'bulanan') {

            url.searchParams.set(

                'bulan',

                String(selectedMonth).padStart(2, '0')

            );


            url.searchParams.set(

                'tahun',

                selectedYear

            );

        }


        if (range === 'tahunan') {

            url.searchParams.set(

                'tahun',

                selectedYear

            );

        }


        /*

         * Penting:

         * replaceState hanya mengubah URL.

         * Tidak ada fetch/reload/request baru.

         */

        window.history.replaceState(

            {},

            '',

            url.toString()

        );

    }


    function updateDonut(

        circle,

        percentage

    ) {

        if (!circle) {

            return;

        }


        var circumference =

            2 * Math.PI * 60;


        circle.setAttribute(

            'stroke-dasharray',

            (

                circumference *

                percentage /

                100

            ).toFixed(2) + ' 999'

        );

    }


    function renderUploaders(data) {

        if (!uploaderBox) {

            return;

        }


        uploaderBox.innerHTML = '';


        var entries =

            Object.entries(

                data.uploaders || {}

            );


        if (uploaderLabel) {

            uploaderLabel.style.display =

                entries.length ? '' : 'none';

        }


        uploaderBox.style.display =

            entries.length ? '' : 'none';


        entries.forEach(function (entry) {

            var chip =

                document.createElement('span');


            chip.className = 'tr-chip';


            chip.appendChild(

                document.createTextNode(

                    entry[0] + ' '

                )

            );


            var count =

                document.createElement('b');


            count.textContent =

                entry[1];


            chip.appendChild(count);

            uploaderBox.appendChild(chip);

        });

    }


    function renderBars(data) {

        if (!chartBox) {

            return;

        }


        chartBox.innerHTML = '';


        var entries =

            Object.entries(

                data.sto_breakdown || {}

            );


        if (!entries.length) {

            return;

        }


        var maxValue =

            Math.max.apply(

                null,

                entries.map(function (entry) {

                    return Number(entry[1]) || 0;

                })

            );


        entries.forEach(function (entry) {

            var row =

                document.createElement('div');


            row.className =

                'tr-bar-row';


            var label =

                document.createElement('div');


            label.className =

                'tr-bar-label';


            var name =

                document.createElement('span');


            name.textContent =

                entry[0];


            var count =

                document.createElement('b');


            count.textContent =

                entry[1];


            label.appendChild(name);

            label.appendChild(count);


            var track =

                document.createElement('div');


            track.className =

                'tr-bar-track';


            var fill =

                document.createElement('div');


            fill.className =

                'tr-bar-fill';


            fill.style.width =

                (

                    maxValue > 0

                        ? Number(entry[1]) /

                            maxValue * 100

                        : 0

                ).toFixed(2) + '%';


            track.appendChild(fill);

            row.appendChild(label);

            row.appendChild(track);

            chartBox.appendChild(row);

        });

    }


    function render() {

        var data = getData();


        var escalation =

            Math.max(

                Number(data.total || 0) -

                Number(data.resolved || 0),

                0

            );


        var resolvedPct =

            data.total > 0

                ? Math.round(

                    data.resolved /

                    data.total *

                    100

                )

                : 0;


        var completedPct =

            data.resolved > 0

                ? Math.round(

                    data.completed /

                    data.resolved *

                    100

                )

                : 0;


        Object.keys(statMap).forEach(

            function (key) {

                statMap[key].textContent =

                    data[key] || 0;

            }

        );


        var activeLabel =

            getLabel();


        if (periodSub) {

            periodSub.textContent =

                activeLabel;

        }


        if (infoPeriod) {

            infoPeriod.textContent =

                activeLabel;

        }


        if (infoTotal) {

            infoTotal.textContent =

                (data.total || 0) +

                ' data';

        }


        if (escalationSub) {

            escalationSub.textContent =

                escalation +

                ' eskalasi';

        }


        if (legendMap.resolved) {

            legendMap.resolved.textContent =

                data.resolved || 0;

        }


        if (legendMap.escalation) {

            legendMap.escalation.textContent =

                escalation;

        }


        if (legendMap.completed) {

            legendMap.completed.textContent =

                data.completed || 0;

        }


        if (legendMap.process) {

            legendMap.process.textContent =

                Math.max(

                    (data.resolved || 0) -

                    (data.completed || 0),

                    0

                );

        }


        updateDonut(

            donutMap.resolved,

            resolvedPct

        );


        updateDonut(

            donutMap.completed,

            completedPct

        );


        renderUploaders(data);

        renderBars(data);


        tabs.forEach(function (tab) {

            tab.classList.toggle(

                'active',

                tab.dataset.range === range

            );

        });


        groups.forEach(function (group) {

            group.style.display =

                group.dataset.rangeGroup === range

                    ? 'flex'

                    : 'none';

        });

    }


    tabs.forEach(function (tab) {

        tab.addEventListener(

            'click',

            function () {

                var nextRange =

                    this.dataset.range;


                if (

                    !nextRange ||

                    nextRange === range

                ) {

                    return;

                }


                range = nextRange;


                if (range === 'harian') {

                    var days =

                        Object.keys(

                            payload.daily

                        );


                    if (days.length) {

                        selectedDate = days[0];


                        if (dateField) {

                            dateField.value =

                                selectedDate;

                        }

                    }

                }


                if (range === 'bulanan') {

                    var months =

                        Object.keys(

                            payload.monthly

                        );


                    if (months.length) {

                        var monthKey =

                            months[0];


                        selectedYear =

                            monthKey.slice(0, 4);


                        selectedMonth =

                            monthKey.slice(5, 7);


                        if (monthField) {

                            monthField.value =

                                selectedMonth;

                        }

                    }

                }


                if (range === 'tahunan') {

                    var years =

                        Object.keys(

                            payload.yearly

                        );


                    if (years.length) {

                        selectedYear =

                            years[0];

                    }

                }


                updateUrl();

                render();

            }

        );

    });


    if (dateField) {

        dateField.addEventListener(

            'change',

            function () {

                if (!this.value) {

                    return;

                }


                selectedDate =

                    this.value;


                range = 'harian';


                updateUrl();

                render();

            }

        );

    }


    if (monthField) {

        monthField.addEventListener(

            'change',

            function () {

                if (!this.value) {

                    return;

                }


                selectedMonth =

                    this.value;


                range = 'bulanan';


                updateUrl();

                render();

            }

        );

    }


    yearFields.forEach(

        function (field) {

            field.addEventListener(

                'change',

                function () {

                    if (!this.value) {

                        return;

                    }


                    selectedYear =

                        this.value;


                    updateUrl();

                    render();

                }

            );

        }

    );


    render();

})();

</script>
