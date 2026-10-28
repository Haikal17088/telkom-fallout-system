<?php
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\FalloutController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WELCOME
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

Route::match(['get', 'post'], '/login', function (Request $request) {
    if ($request->isMethod('get')) {
        return view('welcome', ['showLogin' => true]);
    }
    $credentials = $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);
    if (
        Auth::attempt(
            $credentials,
            $request->boolean('remember')
        )
    ) {
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }
    return back()
        ->withErrors([
            'email' => 'Email atau password yang kamu masukkan salah.'
        ])
        ->onlyInput('email');
})->name('login');

/*
|--------------------------------------------------------------------------
| FORGOT PASSWORD
|--------------------------------------------------------------------------
*/

Route::get('/forgot-password', function () {
    return view('forgot-password');
})->name('password.request');

Route::post('/forgot-password', function (Request $request) {
    $request->validate([
        'email' => [
            'required',
            'email',
        ],
    ]);
    $status = Password::sendResetLink([
        'email' => $request->input('email'),
    ]);
    if ($status === Password::RESET_LINK_SENT) {
        return back()->with(
            'status',
            'Link reset password sudah dikirim ke email kamu.'
        );
    }
    return back()
        ->withErrors([
            'email' =>
                'Email tidak ditemukan atau link reset gagal dibuat.',
        ])
        ->withInput();
})->name('password.email');

/*
|--------------------------------------------------------------------------
| HALAMAN RESET PASSWORD
|--------------------------------------------------------------------------
| GET = membuka form saat user klik link dari email.
| POST = memproses password baru.
*/

Route::get('/reset-password/{token}', function (string $token) {
    return view(
        'reset-password',
        [
            'token' => $token,
            'email' => request()->query('email', ''),
        ]
    );
})->name('password.reset');

Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => [
            'required',
        ],
        'email' => [
            'required',
            'email',
        ],
        'password' => [
            'required',
            'confirmed',
            'min:8',
        ],
    ]);
    $status = Password::reset(
        [
            'email' =>
                $request->input('email'),
            'password' =>
                $request->input('password'),
            'password_confirmation' =>
                $request->input('password_confirmation'),
            'token' =>
                $request->input('token'),
        ],
        function ($user, $password) {
            $user->forceFill([
                'password' =>
                    Hash::make($password),
                'remember_token' =>
                    \Illuminate\Support\Str::random(60),
            ])->save();
        }
    );
    if ($status === Password::PASSWORD_RESET) {
        return redirect()
            ->route('login')
            ->with(
                'status',
                'Password berhasil diubah. Silakan login dengan password baru.'
            );
    }
    return back()
        ->withErrors([
            'email' =>
                'Link reset password tidak valid atau sudah kedaluwarsa.',
        ])
        ->withInput(
            $request->only('email')
        );
})->name('password.update');

/*
|--------------------------------------------------------------------------
| REKAP FALLOUT PUBLIC
|--------------------------------------------------------------------------
*/
$buildRekapFallout = function (
    Request $request,
    string $witel = 'semua'
) {
    $witelLabel = [
        'semua'  => 'Semua Witel',
        'jaktim' => 'Witel Jakarta Timur',
        'jakpus' => 'Witel Jakarta Pusat',
        'jaksel' => 'Witel Jakarta Selatan',
    ];
    $validWitels = array_keys($witelLabel);
    abort_unless(
        in_array($witel, $validWitels, true),
        404
    );
    /* VIEW */
    $activeView = strtolower(
        trim((string) $request->query('view', 'tabel'))
    );
    if (!in_array($activeView, ['tabel', 'grafik'], true)) {
        $activeView = 'tabel';
    }
    /*
    |--------------------------------------------------------------------------
    | FILTER TABEL DAN GRAFIK BENAR-BENAR TERPISAH
    |--------------------------------------------------------------------------
    | Tidak menggunakan session untuk filter halaman ini.
    | Semua state berasal dari query URL sehingga tidak ada filter Witel/STO
    | lama yang "nyangkut" ketika pindah Witel.
    */
    $tableWitel = strtolower(
        trim((string) $request->query('table_witel', $witel))
    );
    $graphWitel = strtolower(
        trim((string) $request->query('graph_witel', $witel))
    );
    if (!in_array($tableWitel, $validWitels, true)) {
        $tableWitel = $witel;
    }
    if (!in_array($graphWitel, $validWitels, true)) {
        $graphWitel = $witel;
    }
    /* FILTER TABEL */
    $filterTanggal = trim(
        (string) $request->query('tanggal', '')
    );
    $filterSearch = substr(trim((string) $request->query('q', '')), 0, 100);
    $filterPeriode = strtolower(trim((string) $request->query('periode', 'harian')));
    if (!in_array($filterPeriode, ['harian', 'mingguan', 'bulanan', 'tahunan'], true)) {
        $filterPeriode = 'harian';
    }
$filterSto = strtolower(
        trim((string) $request->query('sto', 'semua'))
    );
    if ($filterSto === '') {
        $filterSto = 'semua';
    }
    $filterStatus = strtolower(
        trim((string) $request->query('status', 'semua'))
    );
    if ($filterStatus === '') {
        $filterStatus = 'semua';
    }
    $tableStatusLabel = [
        'semua'        => 'Semua',
        'resolved'     => 'RESOLVED',
        'cancel'       => 'Cancel',
        'eskalasi_dit' => 'Eskalasi DIT',
        'close'        => 'Close',
    ];
    if (!array_key_exists($filterStatus, $tableStatusLabel)) {
        $filterStatus = 'semua';
    }
    /*
    |--------------------------------------------------------------------------
    | NORMALISASI STO
    |--------------------------------------------------------------------------
    | Hanya mengambil nama STO dari nilai database. Jika data lama tersimpan
    | sebagai JSON satu record, ambil field STO-nya. Jika JSON tidak valid,
    | ambil field 'STO' dengan regex. Hasil selalu uppercase + trim.
    */
    $normalizeSto = function ($rowOrValue) {
        $value = $rowOrValue;
        if (
            is_object($rowOrValue)
            && method_exists($rowOrValue, 'getRawOriginal')
        ) {
            $raw = $rowOrValue->getRawOriginal('sto');
            $value = $raw !== null
                ? $raw
                : ($rowOrValue->sto ?? '');
        }
        if ($value === null) {
            return '';
        }
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        /* JSON object/array. */
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
                        $candidate = $item['STO'] ?? $item['sto'] ?? null;
                        if ($candidate !== null && !is_array($candidate)) {
                            $candidate = trim((string) $candidate);
                            if ($candidate !== '') {
                                return strtoupper($candidate);
                            }
                        }
                    }
                }
            }
            /* Fallback untuk JSON/teks lama yang tidak valid sepenuhnya. */
            if (preg_match('/"STO"\s*:\s*"([^"]+)"/i', $value, $match)) {
                return strtoupper(trim($match[1]));
            }
        }
        /* Fallback setelah escape quote dibersihkan. */
        $unescaped = str_replace('\\\\"', '"', $value);
        if (preg_match('/"STO"\s*:\s*"([^"]+)"/i', $unescaped, $match)) {
            return strtoupper(trim($match[1]));
        }
        return strtoupper(trim($value));
    };
    /*
    |--------------------------------------------------------------------------
    | DATA TABEL
    |--------------------------------------------------------------------------
    | Query tabel HANYA memakai tableWitel.
    | Filter tanggal/STO/status kemudian diterapkan ke collection tabel.
    */
    $tableQuery = \App\Models\FalloutData::query()
        ->with('batch.branch');
    if ($tableWitel !== 'semua') {
        $tableQuery->whereHas(
            'batch.branch',
            function ($q) use ($tableWitel) {
                $q->whereRaw(
                    'LOWER(TRIM(kode_cabang)) = ?',
                    [strtolower(trim($tableWitel))]
                );
            }
        );
    }
    $tableRows = $tableQuery
        ->orderByDesc('tanggal')
        ->orderByDesc('row_id')
        ->get();
    $filtered = $tableRows
        ->filter(function ($row) use (
            $filterTanggal,
            $filterSto,
            $filterStatus,
            $normalizeSto,
            $filterSearch,
            $filterPeriode
        ) {
            /* TANGGAL / PERIODE */
            if ($filterTanggal !== '') {
                try {
                    $selectedDate = \Carbon\Carbon::parse($filterTanggal)->startOfDay();
                    $rowDate = $row->tanggal
                        ? \Carbon\Carbon::parse($row->tanggal)->startOfDay()
                        : null;
                } catch (\Throwable $e) {
                    return false;
                }
                if (!$rowDate) {
                    return false;
                }
                $matchesDate = match ($filterPeriode) {
                    'mingguan' => $rowDate->between(
                        $selectedDate->copy()->startOfWeek(\Carbon\Carbon::MONDAY),
                        $selectedDate->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)
                    ),
                    'bulanan' => $rowDate->format('Y-m') === $selectedDate->format('Y-m'),
                    'tahunan' => $rowDate->format('Y') === $selectedDate->format('Y'),
                    default => $rowDate->toDateString() === $selectedDate->toDateString(),
                };
                if (!$matchesDate) {
                    return false;
                }
            }
            /* STO */
            if ($filterSto !== 'semua') {
                $rowSto = strtolower(
                    trim(
                        $normalizeSto($row)
                    )
                );
                $selectedSto = strtolower(
                    trim($filterSto)
                );
                if ($rowSto !== $selectedSto) {
                    return false;
                }
            }
                        /* STATUS: gunakan kolom RESOLVED/ESKALASI yang ditampilkan pada tabel */
            if ($filterStatus !== 'semua') {
                $rowStatus = strtolower(
                    trim((string) ($row->resolved_eskalasi ?? ''))
                );
                $rowStatus = preg_replace('/[\s-]+/u', '_', $rowStatus);
                $selectedStatus = strtolower(trim((string) $filterStatus));
                $selectedStatus = preg_replace('/[\s-]+/u', '_', $selectedStatus);
                if ($rowStatus !== $selectedStatus) {
                    return false;
                }
            }
            if ($filterSearch !== '') {
                $searchHaystack = strtolower(implode(' ', [
                    (string) ($row->order_id ?? ''),
                    (string) ($row->pic ?? ''),
                    (string) ($row->deskripsi ?? ''),
                    (string) ($row->resolved_eskalasi ?? ''),
                    (string) ($row->status ?? ''),
                    (string) ($row->ket ?? ''),
                    (string) $normalizeSto($row),
                    (string) (optional(optional($row->batch)->branch)->nama_cabang ?? ''),
                ]));
                if (strpos($searchHaystack, strtolower($filterSearch)) === false) {
                    return false;
                }
            }
            return true;
        })
        ->values();
    $total = $filtered->count();
    $resolved = $filtered
        ->filter(function ($row) {
            return strtolower(
                trim(
                    (string) (
                        $row->resolved_eskalasi ?? ''
                    )
                )
            ) === 'resolved';
        })
        ->count();
    $eskalasi = $filtered
        ->filter(function ($row) {
            return strtolower(
                trim(
                    (string) (
                        $row->resolved_eskalasi ?? ''
                    )
                )
            ) === 'eskalasi';
        })
        ->count();
    $stoAktif = $filtered
        ->map(function ($row) {
            return strtoupper(
                trim(
                    (string) (
                        $row->sto ?? ''
                    )
                )
            );
        })
        ->filter()
        ->unique()
        ->count();
    $picList = $filtered
        ->map(function ($row) {
            return trim(
                (string) (
                    $row->pic ?? ''
                )
            );
        })
        ->filter()
        ->unique()
        ->values();
    $picText = $picList->implode(', ');
    if ($picText === '') {
        $picText = '-';
    }
    /*
    |--------------------------------------------------------------------------
    | STO OPTIONS
    |--------------------------------------------------------------------------
    | Ambil hanya nama STO yang benar dari Witel tabel aktif.
    */
    $stoOptions = $tableRows
        ->map(function ($row) use ($normalizeSto) {
            return $normalizeSto($row);
        })
        ->filter()
        ->unique()
        ->sort()
        ->values();
    /*
    |--------------------------------------------------------------------------
    | DATA GRAFIK
    |--------------------------------------------------------------------------
    | Grafik HANYA memakai graphWitel.
    | Tidak memakai tanggal/STO/status tabel.
    */
    /*
    |--------------------------------------------------------------------------
    | DATA GRAFIK — HINDARI QUERY DUPLIKAT
    |--------------------------------------------------------------------------
    | Jika Witel tabel dan grafik sama, gunakan hasil query tabel yang sudah
    | dimuat. Bila berbeda, ambil hanya kolom yang dibutuhkan grafik dan
    | tidak memuat relasi yang tidak digunakan oleh grafik.
    */
    if ($graphWitel === $tableWitel) {
        $chartRows = $tableRows;
    } else {
        $chartQuery = \App\Models\FalloutData::query()
            ->select([
                'row_id',
                'sto',
                'resolved_eskalasi',
                'pic',
                'status',
            ]);
        if ($graphWitel !== 'semua') {
            $chartQuery->whereHas(
                'batch.branch',
                function ($q) use ($graphWitel) {
                    $q->whereRaw(
                        'LOWER(TRIM(kode_cabang)) = ?',
                        [strtolower(trim($graphWitel))]
                    );
                }
            );
        }
        $chartRows = $chartQuery->get();
    }
    $chartTotal = $chartRows->count();
    $chartResolved = $chartRows
        ->filter(function ($row) {
            return strtolower(
                trim(
                    (string) (
                        $row->resolved_eskalasi ?? ''
                    )
                )
            ) === 'resolved';
        })
        ->count();
    $chartEskalasi = $chartRows
        ->filter(function ($row) {
            return strtolower(
                trim(
                    (string) (
                        $row->resolved_eskalasi ?? ''
                    )
                )
            ) === 'eskalasi';
        })
        ->count();
    $chartStoAktif = $chartRows
        ->map(function ($row) {
            return strtoupper(
                trim(
                    (string) (
                        $row->sto ?? ''
                    )
                )
            );
        })
        ->filter()
        ->unique()
        ->count();
    $chartPicList = $chartRows
        ->map(function ($row) {
            return trim(
                (string) (
                    $row->pic ?? ''
                )
            );
        })
        ->filter()
        ->unique()
        ->values();
    $perSto = $chartRows
        ->groupBy(function ($row) {
            $sto = strtoupper(
                trim(
                    (string) (
                        $row->sto ?? ''
                    )
                )
            );
            return $sto !== ''
                ? $sto
                : 'TANPA STO';
        })
        ->map(function ($group) {
            return $group->count();
        })
        ->sortDesc();
    $maxSto = max(
        (int) ($perSto->max() ?? 0),
        1
    );
    $processCount = $chartRows
        ->filter(function ($row) {
            return strtolower(
                trim(
                    (string) (
                        $row->status ?? ''
                    )
                )
            ) === 'process_oss';
        })
        ->count();
    $completedCount = $chartRows
        ->filter(function ($row) {
            return strtolower(
                trim(
                    (string) (
                        $row->status ?? ''
                    )
                )
            ) === 'completed';
        })
        ->count();
    /*
    |--------------------------------------------------------------------------
    | ACTION URL
    |--------------------------------------------------------------------------
    | Filter tetap mengarah ke route dasar yang sedang dibuka.
    | table_witel / graph_witel dibawa sebagai query parameter.
    */
    $rekapAction = $witel === 'semua'
        ? route('rekap-fallout')
        : route(
            'rekap-fallout.witel',
            ['witel' => $witel]
        );
    return view(
        'rekap-fallout',
        [
            'witel'          => $witel,
            'activeView'     => $activeView,
            'witelLabel'     => $witelLabel,
            'tableWitel'     => $tableWitel,
            'graphWitel'     => $graphWitel,
            'filterTanggal'  => $filterTanggal,
            'filterSearch'   => $filterSearch,
            'filterPeriode'  => $filterPeriode,
            'filterSto'      => $filterSto,
            'filterStatus'   => $filterStatus,
            'statusLabel'    => $tableStatusLabel,
            'stoOptions'     => $stoOptions,
            'tableRows'      => $tableRows,
            'filtered'       => $filtered,
            'total'          => $total,
            'resolved'       => $resolved,
            'eskalasi'       => $eskalasi,
            'stoAktif'       => $stoAktif,
            'picText'        => $picText,
            'chartRows'      => $chartRows,
            'chartTotal'     => $chartTotal,
            'chartResolved'  => $chartResolved,
            'chartEskalasi'  => $chartEskalasi,
            'chartStoAktif'  => $chartStoAktif,
            'chartPicList'   => $chartPicList,
            'perSto'         => $perSto,
            'maxSto'         => $maxSto,
            'processCount'   => $processCount,
            'completedCount' => $completedCount,
            'rekapAction'    => $rekapAction,
        ]
    );
};

/*
|--------------------------------------------------------------------------
| URL REKAP SEMUA WITEL
|--------------------------------------------------------------------------
*/

Route::get(
    '/rekap-fallout',
    function (
        Request $request
    ) use (
        $buildRekapFallout
    ) {
        return $buildRekapFallout(
            $request,
            'semua'
        );
    }
)->name('rekap-fallout');

/*
|--------------------------------------------------------------------------
| URL REKAP PER WITEL
|--------------------------------------------------------------------------
*/

Route::get(
    '/rekap-fallout/{witel}',
    function (
        Request $request,
        string $witel
    ) use (
        $buildRekapFallout
    ) {
        return $buildRekapFallout(
            $request,
            $witel
        );
    }
)->name('rekap-fallout.witel');

/*
|--------------------------------------------------------------------------
| DOWNLOAD DATA REKAP FALLOUT PUBLIC
|--------------------------------------------------------------------------
| Mengikuti filter Tabel Data yang sedang dipilih.
*/

Route::get(
    '/rekap-fallout-download/{witel}',
    function (
        Request $request,
        string $witel
    ) {
        $validWitels = [
            'semua',
            'jaktim',
            'jakpus',
            'jaksel',
        ];
        abort_unless(
            in_array($witel, $validWitels, true),
            404
        );
        $tableWitel = strtolower(
            trim(
                (string) $request->query(
                    'witel',
                    $witel
                )
            )
        );
        if (!in_array($tableWitel, $validWitels, true)) {
            $tableWitel = $witel;
        }
        $tanggal = trim(
            (string) $request->query(
                'tanggal',
                ''
            )
        );
        $sto = strtolower(
            trim(
                (string) $request->query(
                    'sto',
                    'semua'
                )
            )
        );
        $status = strtolower(
            trim(
                (string) $request->query(
                    'status',
                    'semua'
                )
            )
        );
        if ($sto === '') {
            $sto = 'semua';
        }
        if ($status === '') {
            $status = 'semua';
        }
        $search = substr(trim((string) $request->query('q', '')), 0, 100);
        $periode = strtolower(trim((string) $request->query('periode', 'harian')));
        if (!in_array($periode, ['harian', 'mingguan', 'bulanan', 'tahunan'], true)) {
            $periode = 'harian';
        }
        $query = \App\Models\FalloutData::query()
            ->with('batch.branch');
        if ($tableWitel !== 'semua') {
            $query->whereHas('batch.branch', function ($q) use ($tableWitel) {
                $q->whereRaw(
                    'LOWER(TRIM(kode_cabang)) = ?',
                    [strtolower(trim($tableWitel))]
                );
            });
        }
        $normalizeStoExport = static function ($rowOrValue): string {
            $value = $rowOrValue;
            if (is_object($rowOrValue) && method_exists($rowOrValue, 'getRawOriginal')) {
                $raw = $rowOrValue->getRawOriginal('sto');
                $value = $raw !== null ? $raw : ($rowOrValue->sto ?? '');
            }
            $value = trim((string) ($value ?? ''));
            if ($value === '') {
                return '';
            }
            if ($value[0] === '{' || $value[0] === '[') {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    foreach (['STO', 'sto'] as $key) {
                        if (isset($decoded[$key]) && !is_array($decoded[$key])) {
                            $candidate = trim((string) $decoded[$key]);
                            if ($candidate !== '') {
                                return strtoupper($candidate);
                            }
                        }
                    }
                    if (array_is_list($decoded)) {
                        foreach ($decoded as $item) {
                            if (is_array($item)) {
                                $candidate = $item['STO'] ?? $item['sto'] ?? null;
                                if ($candidate !== null && !is_array($candidate) && trim((string) $candidate) !== '') {
                                    return strtoupper(trim((string) $candidate));
                                }
                            }
                        }
                    }
                }
                if (preg_match('/"STO"\s*:\s*"([^"]+)"/i', $value, $match)) {
                    return strtoupper(trim($match[1]));
                }
            }
            return strtoupper(trim(str_replace('\\\"', '"', $value)));
        };
        $normalizeStatusExport = static function ($value): string {
            $normalized = strtolower(trim((string) ($value ?? '')));
            return preg_replace('/[\s-]+/u', '_', $normalized) ?? $normalized;
        };
        $rows = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('row_id')
            ->get()
            ->filter(function ($row) use (
                $tanggal,
                $periode,
                $sto,
                $status,
                $search,
                $normalizeStoExport,
                $normalizeStatusExport
            ) {
                if ($tanggal !== '') {
                    try {
                        $selectedDate = \Carbon\Carbon::parse($tanggal)->startOfDay();
                        $rowDate = $row->tanggal
                            ? \Carbon\Carbon::parse($row->tanggal)->startOfDay()
                            : null;
                    } catch (\Throwable $e) {
                        return false;
                    }
                    if (!$rowDate) {
                        return false;
                    }
                    $matchesDate = match ($periode) {
                        'mingguan' => $rowDate->between(
                            $selectedDate->copy()->startOfWeek(\Carbon\Carbon::MONDAY),
                            $selectedDate->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)
                        ),
                        'bulanan' => $rowDate->format('Y-m') === $selectedDate->format('Y-m'),
                        'tahunan' => $rowDate->format('Y') === $selectedDate->format('Y'),
                        default => $rowDate->toDateString() === $selectedDate->toDateString(),
                    };
                    if (!$matchesDate) {
                        return false;
                    }
                }
                if ($sto !== 'semua' && strtolower($normalizeStoExport($row)) !== strtolower(trim($sto))) {
                    return false;
                }
                if ($status !== 'semua' && $normalizeStatusExport($row->resolved_eskalasi ?? '') !== $normalizeStatusExport($status)) {
                    return false;
                }
                if ($search !== '') {
                    $haystack = strtolower(implode(' ', [
                        (string) ($row->order_id ?? ''),
                        (string) ($row->deskripsi ?? ''),
                        (string) (optional(optional($row->batch)->branch)->nama_cabang ?? ''),
                        (string) $normalizeStoExport($row),
                        (string) ($row->pic ?? ''),
                        (string) ($row->resolved_eskalasi ?? ''),
                        (string) ($row->status ?? ''),
                        (string) ($row->ket ?? ''),
                    ]));
                    if (strpos($haystack, strtolower($search)) === false) {
                        return false;
                    }
                }
                return true;
            })
            ->values();
$filename =
            'rekap-fallout-'
            . $tableWitel
            . '-'
            . now()->format('Ymd-His')
            . '.csv';
        return response()->streamDownload(
            function () use ($rows) {
                $out = fopen(
                    'php://output',
                    'w'
                );
                /* BOM UTF-8 untuk Excel Windows. */
                fwrite(
                    $out,
                    "\xEF\xBB\xBF"
                );
                fputcsv(
                    $out,
                    [
                        'Order ID',
                        'Status Message',
                        'Cabang',
                        'STO',
                        'Tggl Fallout',
                        'PIC',
                        'RESOLVED/ESKALASI',
                        'Status',
                        'KET',
                    ]
                );
                foreach ($rows as $row) {
                    fputcsv(
                        $out,
                        [
                            $row->order_id,
                            $row->deskripsi,
                            optional(optional($row->batch)->branch)->nama_cabang ?? '',
                            $row->sto,
                            $row->tanggal
                                ? \Carbon\Carbon::parse(
                                    $row->tanggal
                                )->format('Y-m-d')
                                : '',
                            $row->pic,
                            $row->resolved_eskalasi,
                            $row->status,
                            $row->ket,
                        ]
                    );
                }
                fclose($out);
            },
            $filename,
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
            ]
        );
    }
)->name('rekap-fallout.download');

/*
|--------------------------------------------------------------------------
| ROUTE LOGIN USER
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard',
        function () {
            return view(
                'dashboard',
                [
                    'activeWitel' => null,
                    'activeMenu'  => null,
                ]
            );
        }
    )->name('dashboard');
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD MENU
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/{witel}/{menu}',
        function (
            string $witel,
            string $menu
        ) {
            $validWitel = [
                'jaktim',
                'jakpus',
                'jaksel',
            ];
            $validMenu = [
                'total-rekap',
                'detail-fallout',
                'upload-data',
                'edit-data',
                'arsip-hapus',
                'export-data',
            ];
            abort_unless(
                in_array(
                    $witel,
                    $validWitel,
                    true
                )
                &&
                in_array(
                    $menu,
                    $validMenu,
                    true
                ),
                404
            );
            return view(
                'dashboard',
                [
                    'activeWitel' =>
                        $witel,
                    'activeMenu' =>
                        $menu,
                ]
            );
        }
    )->name('dashboard.menu');
    /*
    |--------------------------------------------------------------------------
    | FALLOUT - TAMBAH DATA
    |--------------------------------------------------------------------------
    */
    Route::post(
        '/dashboard/{witel}/edit-data',
        [
            FalloutController::class,
            'store',
        ]
    )->name('fallout.store');
    /*
    |--------------------------------------------------------------------------
    | FALLOUT - UPDATE DATA
    |--------------------------------------------------------------------------
    */
    Route::put(
        '/dashboard/{witel}/edit-data/{id}',
        [
            FalloutController::class,
            'update',
        ]
    )->name('fallout.update');
    /*
    |--------------------------------------------------------------------------
    | FALLOUT - HAPUS SATU DATA
    |--------------------------------------------------------------------------
    |
    | INI SATU-SATUNYA ROUTE BARU UNTUK DELETE.
    |
    */
    Route::delete(
        '/dashboard/{witel}/edit-data/{id}',
        [
            FalloutController::class,
            'destroy',
        ]
    )->name('fallout.destroy');
    /*
    |--------------------------------------------------------------------------
    | UPLOAD EXCEL
    |--------------------------------------------------------------------------
    |
    | File Excel:
    |
    | Sheet = ALL
    |
    | A = Order ID
    | B = Status Message / Deskripsi
    | C = STO
    | D = Tggl Fallout
    | E = PIC
    | F = RESOLVED/ESKALASI
    | G = Status
    | H = KET
    |
    */
    Route::post(
        '/dashboard/{witel}/upload-data',
        function (
            Request $request,
            string $witel
        ) {
            $validWitel = [
                'jaktim',
                'jakpus',
                'jaksel',
            ];
            abort_unless(
                in_array($witel, $validWitel, true),
                404
            );
            $request->validate([
                'file' => [
                    'required',
                    'file',
                    'mimes:xlsx,xls,csv',
                    'max:10240',
                ],
            ]);
            /*
            |--------------------------------------------------------------------------
            | CEK DUPLIKAT FILE
            |--------------------------------------------------------------------------
            | Fingerprint dibuat dari file asli, sebelum PhpSpreadsheet memprosesnya.
            | File yang byte-for-byte sama akan memiliki SHA-256 yang sama.
            */
            $uploadedFile = $request->file('file');
            $filePath = $uploadedFile
                ? $uploadedFile->getRealPath()
                : false;
            $fileHash = $filePath
                ? hash_file('sha256', $filePath)
                : false;
            if (!$fileHash) {
                return back()->withErrors([
                    'file' =>
                        'Fingerprint file gagal dibuat. Silakan coba upload kembali.',
                ]);
            }
            $branch = \App\Models\Branch::bySlug($witel);
            abort_unless(
                $branch,
                404,
                'Witel tidak ditemukan di database.'
            );
            
            /*
            |--------------------------------------------------------------------------
            | BACA FILE EXCEL
            |--------------------------------------------------------------------------
            */
            try {
                $spreadsheet =
                    \PhpOffice\PhpSpreadsheet\IOFactory::load(
                        $request
                            ->file('file')
                            ->getRealPath()
                    );
            } catch (\Throwable $e) {
                return back()->withErrors([
                    'file' =>
                        'Gagal membaca file Excel: '
                        . $e->getMessage()
                ]);
            }
            $sheet =
                $spreadsheet->getSheetByName('ALL');
            if (!$sheet) {
                return back()->withErrors([
                    'file' =>
                        'Sheet "ALL" tidak ditemukan di file Excel.'
                ]);
            }
            $highestRow =
                $sheet->getHighestRow();
            if ($highestRow < 2) {
                return back()->withErrors([
                    'file' =>
                        'Tidak ada data pada sheet ALL.'
                ]);
            }
            /*
            |--------------------------------------------------------------------------
            | HELPER
            |--------------------------------------------------------------------------
            */
            $clean = function ($value) {
                if ($value === null) {
                    return null;
                }
                if ($value instanceof \DateTimeInterface) {
                    return $value;
                }
                $value =
                    trim((string) $value);
                if ($value === '') {
                    return null;
                }
                if (
                    in_array(
                        $value,
                        [
                            '#REF!',
                            '#VALUE!',
                            '#N/A',
                            '#NAME?',
                            '#DIV/0!',
                            '#NULL!',
                            '#NUM!',
                        ],
                        true
                    )
                ) {
                    return null;
                }
                return $value;
            };
            $normalize =
                function ($value) {
                    if (
                        $value === null ||
                        $value === ''
                    ) {
                        return null;
                    }
                    return strtolower(
                        str_replace(
                            [
                                ' ',
                                '/',
                                '-',
                            ],
                            '_',
                            trim((string) $value)
                        )
                    );
                };
            /*
            |--------------------------------------------------------------------------
            | CEK uploaded_at
            |--------------------------------------------------------------------------
            */
            $hasUploadedAt =
                \Illuminate\Support\Facades\Schema::hasColumn(
                    'fallout_data',
                    'uploaded_at'
                );
            /*
            |--------------------------------------------------------------------------
            | SIAPKAN DATA
            |--------------------------------------------------------------------------
            */
            $rowsToInsert = [];
            $skipped = 0;

            // Ambil Order ID pada Witel aktif; tanggal ikut menjadi bagian kunci duplikat.
            $existingOrderKeys = \Illuminate\Support\Facades\DB::table('fallout_data')
                ->join('upload_batches', 'upload_batches.batch_id', '=', 'fallout_data.batch_id')
                ->where('upload_batches.branch_id', $branch->branch_id)
                ->get(['fallout_data.order_id', 'fallout_data.tanggal'])
                ->mapWithKeys(static function ($row) {
                    $orderKey = strtolower(trim((string) $row->order_id));
                    $dateKey = substr((string) $row->tanggal, 0, 10);
                    return [$orderKey . '|' . $dateKey => true];
                })
                ->all();

            $seenOrderKeys = [];
            $skippedDuplicates = 0;
            for (
                $rowNumber = 2;
                $rowNumber <= $highestRow;
                $rowNumber++
            ) {
                /*
                |--------------------------------------------------------------------------
                | A = ORDER ID
                |--------------------------------------------------------------------------
                */
                $orderId =
                    $sheet
                        ->getCell("A{$rowNumber}")
                        ->getCalculatedValue();
                $orderId =
                    trim((string) $orderId);
                if ($orderId === '') {
                    $skipped++;
                    continue;
                }
                /*
                |--------------------------------------------------------------------------
                | B-H
                |--------------------------------------------------------------------------
                */
                $deskripsi =
                    $sheet
                        ->getCell("B{$rowNumber}")
                        ->getCalculatedValue();
                $sto =
                    $sheet
                        ->getCell("C{$rowNumber}")
                        ->getCalculatedValue();
                $tanggalRaw =
                    $sheet
                        ->getCell("D{$rowNumber}")
                        ->getCalculatedValue();
                $pic =
                    $sheet
                        ->getCell("E{$rowNumber}")
                        ->getCalculatedValue();
                $resolved =
                    $sheet
                        ->getCell("F{$rowNumber}")
                        ->getCalculatedValue();
                $status =
                    $sheet
                        ->getCell("G{$rowNumber}")
                        ->getCalculatedValue();
                $ket =
                    $sheet
                        ->getCell("H{$rowNumber}")
                        ->getCalculatedValue();

                $tiketEskalasi = $sheet->getCell("I{$rowNumber}")->getCalculatedValue();
                /*
                |--------------------------------------------------------------------------
                | CLEAN DATA
                |--------------------------------------------------------------------------
                */
                $deskripsi = $clean($deskripsi);
                $sto        = $clean($sto);
                $pic        = $clean($pic);
                $resolved   = $clean($resolved);
                $status     = $clean($status);
                $ket = $clean($ket);
                $tiketEskalasi = $clean($tiketEskalasi);
                /*
                |--------------------------------------------------------------------------
                | TANGGAL
                |--------------------------------------------------------------------------
                */
                $tanggal =
                    now()->toDateString();
                if (
                    $tanggalRaw instanceof
                    \DateTimeInterface
                ) {
                    $tanggal =
                        $tanggalRaw
                            ->format('Y-m-d');
                } elseif (
                    is_numeric($tanggalRaw)
                ) {
                    try {
                        $tanggal =
                            \PhpOffice\PhpSpreadsheet\Shared\Date
                                ::excelToDateTimeObject(
                                    $tanggalRaw
                                )
                                ->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $tanggal =
                            now()->toDateString();
                    }
                } elseif (
                    !empty($tanggalRaw)
                ) {
                    try {
                        $tanggal =
                            \Carbon\Carbon::parse(
                                $tanggalRaw
                            )->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $tanggal =
                            now()->toDateString();
                    }
                }
                /*
                |--------------------------------------------------------------------------
                | STATUS
                |--------------------------------------------------------------------------
                */
                $orderKey = strtolower(trim($orderId)) . '|' . $tanggal;
                if (isset($existingOrderKeys[$orderKey]) || isset($seenOrderKeys[$orderKey])) {
                    $skippedDuplicates++;
                    continue;
                }
                $seenOrderKeys[$orderKey] = true;

                $statusNormalized = null;
                if ($status) {
                    $statusNormalized =
                        strtolower(
                            trim((string) $status)
                        );
                    if (
                        $statusNormalized === 'completed'
                    ) {
                        $statusNormalized =
                            'completed';
                    } elseif (
                        str_starts_with(
                            $statusNormalized,
                            'process oss'
                        )
                    ) {
                        $statusNormalized =
                            'process_oss';
                    } else {
                        $statusNormalized =
                            str_replace(
                                [
                                    ' ',
                                    '/',
                                    '-',
                                ],
                                '_',
                                $statusNormalized
                            );
                    }
                }
                /*
                |--------------------------------------------------------------------------
                | FALLBACK STATUS DARI KET
                |--------------------------------------------------------------------------
                */
                if (
                    !$statusNormalized &&
                    $ket
                ) {
                    $ketLower =
                        strtolower(
                            trim((string) $ket)
                        );
                    $completedCodes = [
                        '1054',
                        '1053',
                        '1030',
                        '1011',
                        '1052',
                        '2200',
                        '2400',
                        '5003',
                        'term',
                    ];
                    if (
                        in_array(
                            $ketLower,
                            $completedCodes,
                            true
                        )
                    ) {
                        $statusNormalized =
                            'completed';
                    } else {
                        $statusNormalized =
                            'process_oss';
                    }
                }
                /*
                |--------------------------------------------------------------------------
                | DATA YANG AKAN DIINSERT
                |--------------------------------------------------------------------------
                */
                $rowData = [
                    /*
                    | batch_id akan diisi setelah batch dibuat.
                    */
                    'batch_id' =>
                        null,
                    'order_id' =>
                        $orderId,
                    'deskripsi' =>
                        $deskripsi,
                    'sto' =>
                        $sto
                            ? strtoupper(
                                trim((string) $sto)
                            )
                            : null,
                    'tanggal' =>
                        $tanggal,
                    'pic' =>
                        $pic,
                    'resolved_eskalasi' => $normalize($resolved) === null
                        ? null
                        : strtoupper((string) $normalize($resolved)),
                    'status' =>
                        $statusNormalized,
                    'ket' => $ket,
                    'tiket_eskalasi' => $tiketEskalasi !== '' ? $tiketEskalasi : null,
                    'uploaded_by' =>
                        auth()->id(),
                ];
                if ($hasUploadedAt) {
                    $rowData['uploaded_at'] =
                        now();
                }
                $rowsToInsert[] =
                    $rowData;
            }
            /*
            |--------------------------------------------------------------------------
            | TIDAK ADA DATA
            |--------------------------------------------------------------------------
            */
            if (count($rowsToInsert) === 0) {
                if ($skippedDuplicates > 0) {
                    $message = "Tidak ada data baru untuk {$branch->nama_cabang}. {$skippedDuplicates} baris duplikat dilewati.";
                    if ($skipped > 0) {
                        $message .= " {$skipped} baris kosong juga dilewati.";
                    }
                    return back()->with('status', $message);
                }

                return back()->withErrors([
                    'file' => 'Tidak ada data valid yang ditemukan pada sheet ALL.'
                ]);
            }
            /*
            |--------------------------------------------------------------------------
            | SIMPAN DALAM SATU TRANSACTION
            |--------------------------------------------------------------------------
            */
            try {
                $result =
                    \Illuminate\Support\Facades\DB::transaction(
                        function () use (
                            $branch,
                            $rowsToInsert,
                            $fileHash
                        ) {
                            /*
                            |------------------------------------------------------
                            | BUAT BATCH
                            |------------------------------------------------------
                            */
                            $batch =
                                \App\Models\UploadBatch::create([
                                    'branch_id' =>
                                        $branch->branch_id,
                                    'tanggal' =>
                                        now()->toDateString(),
                                    'pic' =>
                                        auth()->user()->name
                                        ?? null,
                                    'total_data' =>
                                        0,
                                    'uploaded_by' =>
                                        auth()->id(),
                                    'file_hash' =>
                                        $fileHash,
                                ]);
                            /*
                            |------------------------------------------------------
                            | PASANG batch_id
                            |------------------------------------------------------
                            */
                            $payload = [];
                            foreach (
                                $rowsToInsert as $row
                            ) {
                                $row['batch_id'] =
                                    $batch->batch_id;
                                $payload[] =
                                    $row;
                            }
                            /*
                            |------------------------------------------------------
                            | INSERT KE fallout_data
                            |------------------------------------------------------
                            */
                            \Illuminate\Support\Facades\DB::table(
                                'fallout_data'
                            )->insert(
                                $payload
                            );
                            /*
                            |------------------------------------------------------
                            | UPDATE TOTAL BATCH
                            |------------------------------------------------------
                            */
                            $batch->update([
                                'total_data' =>
                                    count($payload),
                            ]);
                            return $batch;
                        }
                    );
                /*
                |--------------------------------------------------------------------------
                | PESAN SUKSES
                |--------------------------------------------------------------------------
                */
                $message =
                    count($rowsToInsert)
                    . ' data berhasil diupload ke '
                    . $branch->nama_cabang
                    . '.';
                if ($skipped > 0) {
                    $message .=
                        ' '
                        . $skipped
                        . ' baris kosong dilewati.';
                }
                if ($skippedDuplicates > 0) {
                    $message .= ' ' . $skippedDuplicates . ' baris duplikat dilewati.';
                }

                return back()->with(
                    'status',
                    $message
                );
            } catch (\Illuminate\Database\QueryException $e) {
                /*
                |------------------------------------------------------------------
                | UNIQUE FILE HASH
                |------------------------------------------------------------------
                | Pengaman tambahan jika dua request identik masuk hampir
                | bersamaan. Unique index database menjadi lapisan terakhir.
                */
                
                \Log::error(
                    'UPLOAD FALLOUT GAGAL',
                    [
                        'witel' =>
                            $witel,
                        'file' =>
                            $request
                                ->file('file')
                                ->getClientOriginalName(),
                        'error' =>
                            $e->getMessage(),
                        'line' =>
                            $e->getLine(),
                        'error_file' =>
                            $e->getFile(),
                    ]
                );
                return back()->withErrors([
                    'file' =>
                        'Data gagal disimpan ke fallout_data: '
                        . $e->getMessage()
                ]);
            } catch (\Throwable $e) {
                \Log::error(
                    'UPLOAD FALLOUT GAGAL',
                    [
                        'witel' =>
                            $witel,
                        'file' =>
                            $request
                                ->file('file')
                                ->getClientOriginalName(),
                        'error' =>
                            $e->getMessage(),
                        'line' =>
                            $e->getLine(),
                        'error_file' =>
                            $e->getFile(),
                    ]
                );
                return back()->withErrors([
                    'file' =>
                        'Data gagal disimpan ke fallout_data: '
                        . $e->getMessage()
                ]);
            }
        }
    )->name('upload-data.store');
    /*
    |--------------------------------------------------------------------------
    | ARSIP & HAPUS - TANGGAL
    |--------------------------------------------------------------------------
    */

Route::delete(
    '/dashboard/{witel}/arsip-hapus/tanggal',
    function (
        Request $request,
        string $witel
    ) {
        $validWitel = [
            'jaktim',
            'jakpus',
            'jaksel',
        ];
        abort_unless(
            in_array(
                $witel,
                $validWitel,
                true
            ),
            404
        );
        $request->validate([
            'tanggal' => 'required|date',
        ]);
        $branch = \App\Models\Branch::bySlug($witel);
        abort_unless(
            $branch,
            404,
            'Witel tidak ditemukan di database.'
        );
        /*
        |--------------------------------------------------------------------------
        | SIMPAN ID BATCH YANG TERKENA
        |--------------------------------------------------------------------------
        | Setelah fallout_data dihapus, batch yang sudah kosong ikut dibereskan
        | supaya ringkasan upload_batches tidak menyisakan jumlah lama.
        */
        [$deleted, $batchIds] = \Illuminate\Support\Facades\DB::transaction(
            function () use ($witel, $request) {
                $rowsToDelete = \App\Models\FalloutData::forWitel($witel)
                    ->whereDate('tanggal', $request->tanggal)
                    ->lockForUpdate()
                    ->get();
                $batchIds = $rowsToDelete
                    ->pluck('batch_id')
                    ->filter()
                    ->unique()
                    ->values();
                \App\Support\FalloutDeletionHistoryRecorder::record(
                    $rowsToDelete,
                    $witel,
                    'arsip_tanggal'
                );
                $ids = $rowsToDelete->pluck('row_id')->all();
                $deleted = empty($ids)
                    ? 0
                    : \App\Models\FalloutData::forWitel($witel)
                        ->whereIn('row_id', $ids)
                        ->delete();
                if ($deleted !== count($ids)) {
                    throw new \RuntimeException(
                        'Jumlah data yang dihapus tidak sesuai dengan data riwayat.'
                    );
                }
                return [$deleted, $batchIds];
            }
        );
        /*
        |--------------------------------------------------------------------------
        | SINKRONISASI BATCH
        |--------------------------------------------------------------------------
        */
        foreach ($batchIds as $batchId) {
            $remaining = \App\Models\FalloutData::where(
                'batch_id',
                $batchId
            )->count();
            $batch = \App\Models\UploadBatch::where(
                'batch_id',
                $batchId
            )->first();
            if (!$batch) {
                continue;
            }
            if ($remaining <= 0) {
                $batch->delete();
            } else {
                $batch->update([
                    'total_data' => $remaining,
                ]);
            }
        }
        return back()->with(
            'status',
            "{$deleted} data pada tanggal "
            . "{$request->tanggal} berhasil dihapus."
        );
    }
)->name('arsip.hapus.tanggal');
    /*
    |--------------------------------------------------------------------------
    | ARSIP & HAPUS - TAHUN
    |--------------------------------------------------------------------------
    */

Route::delete(
    '/dashboard/{witel}/arsip-hapus/tahun',
    function (
        Request $request,
        string $witel
    ) {
        $validWitel = [
            'jaktim',
            'jakpus',
            'jaksel',
        ];
        abort_unless(
            in_array(
                $witel,
                $validWitel,
                true
            ),
            404
        );
        $request->validate([
            'tahun' => 'required|digits:4',
        ]);
        $branch = \App\Models\Branch::bySlug($witel);
        abort_unless(
            $branch,
            404,
            'Witel tidak ditemukan di database.'
        );
        /*
        |--------------------------------------------------------------------------
        | SIMPAN ID BATCH YANG TERKENA
        |--------------------------------------------------------------------------
        */
        [$deleted, $batchIds] = \Illuminate\Support\Facades\DB::transaction(
            function () use ($witel, $request) {
                $rowsToDelete = \App\Models\FalloutData::forWitel($witel)
                    ->whereYear('tanggal', $request->tahun)
                    ->lockForUpdate()
                    ->get();
                $batchIds = $rowsToDelete
                    ->pluck('batch_id')
                    ->filter()
                    ->unique()
                    ->values();
                \App\Support\FalloutDeletionHistoryRecorder::record(
                    $rowsToDelete,
                    $witel,
                    'arsip_tahun'
                );
                $ids = $rowsToDelete->pluck('row_id')->all();
                $deleted = empty($ids)
                    ? 0
                    : \App\Models\FalloutData::forWitel($witel)
                        ->whereIn('row_id', $ids)
                        ->delete();
                if ($deleted !== count($ids)) {
                    throw new \RuntimeException(
                        'Jumlah data yang dihapus tidak sesuai dengan data riwayat.'
                    );
                }
                return [$deleted, $batchIds];
            }
        );
        /*
        |--------------------------------------------------------------------------
        | SINKRONISASI BATCH
        |--------------------------------------------------------------------------
        */
        foreach ($batchIds as $batchId) {
            $remaining = \App\Models\FalloutData::where(
                'batch_id',
                $batchId
            )->count();
            $batch = \App\Models\UploadBatch::where(
                'batch_id',
                $batchId
            )->first();
            if (!$batch) {
                continue;
            }
            if ($remaining <= 0) {
                $batch->delete();
            } else {
                $batch->update([
                    'total_data' => $remaining,
                ]);
            }
        }
        return back()->with(
            'status',
            "{$deleted} data pada tahun "
            . "{$request->tahun} berhasil dihapus."
        );
    }
)->name('arsip.hapus.tahun');

/*
|--------------------------------------------------------------------------
| ARSIP & HAPUS - RIWAYAT PENGHAPUSAN
|--------------------------------------------------------------------------
*/

Route::delete('/dashboard/{witel}/arsip-hapus/riwayat', function (string $witel) {
    $witel = \Illuminate\Support\Str::lower(trim($witel));

    abort_unless(
        in_array($witel, ['jaktim', 'jakpus', 'jaksel'], true),
        404
    );

    abort_unless(
        \Illuminate\Support\Facades\Schema::hasTable('deleted_fallout_histories'),
        500,
        'Tabel riwayat penghapusan belum tersedia.'
    );

    $deletedCount = \Illuminate\Support\Facades\DB::table('deleted_fallout_histories')
        ->where('witel_slug', $witel)
        ->delete();

    return redirect()
        ->route('dashboard.menu', [
            'witel' => $witel,
            'menu' => 'arsip-hapus',
        ])
        ->with(
            'status',
            number_format($deletedCount, 0, ',', '.')
                . ' riwayat penghapusan untuk Witel '
                . strtoupper($witel)
                . ' berhasil dihapus. Data Fallout asli tidak dihapus.'
        );
})->name('arsip-hapus.riwayat.destroy');
    /*
    |--------------------------------------------------------------------------
    | EXPORT DATA
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/{witel}/export-data/download',
        function (
            Request $request,
            string $witel
        ) {
            $validWitel = [
                'jaktim',
                'jakpus',
                'jaksel',
            ];
            abort_unless(
                in_array(
                    $witel,
                    $validWitel,
                    true
                ),
                404
            );
            $branch =
                \App\Models\Branch::bySlug(
                    $witel
                );
            abort_unless(
                $branch,
                404,
                'Witel tidak ditemukan di database.'
            );
            $rows =
                \App\Models\FalloutData::forWitel(
                    $witel
                )->get();
            $filename =
                'export-fallout-'
                . $witel
                . '-'
                . now()->format(
                    'Ymd-His'
                )
                . '.csv';
            return response()->streamDownload(
                function () use ($rows) {
                    $out =
                        fopen(
                            'php://output',
                            'w'
                        );
                    fputcsv(
                        $out,
                        [
                            'Order ID',
                            'Deskripsi',
                            'STO',
                            'Tanggal',
                            'PIC',
                            'Resolved/Eskalasi',
                            'Status',
                            'KET',
                        ]
                    );
                    foreach (
                        $rows as $r
                    ) {
                        fputcsv(
                            $out,
                            [
                                $r->order_id,
                                $r->deskripsi,
                                $r->sto,
                                $r->tanggal,
                                $r->pic,
                                $r->resolved_eskalasi,
                                $r->status,
                                $r->ket,
                            ]
                        );
                    }
                    fclose($out);
                },
                $filename,
                [
                    'Content-Type' =>
                        'text/csv',
                ]
            );
        }
    )->name('export.download');
    /*
    |--------------------------------------------------------------------------
    | ADMIN - KELOLA USER
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->group(function () {
        Route::get(
            '/admin/users',
            [
                AdminUserController::class,
                'index',
            ]
        )->name('admin.users.index');
        Route::get(
            '/admin/users/create',
            [
                AdminUserController::class,
                'create',
            ]
        )->name('admin.users.create');
        Route::post(
            '/admin/users',
            [
                AdminUserController::class,
                'store',
            ]
        )->name('admin.users.store');
        Route::get(
            '/admin/users/{id}/edit',
            [
                AdminUserController::class,
                'edit',
            ]
        )->name('admin.users.edit');
        Route::put(
            '/admin/users/{id}',
            [
                AdminUserController::class,
                'update',
            ]
        )->name('admin.users.update');
        Route::delete(
            '/admin/users/{id}',
            [
                AdminUserController::class,
                'destroy',
            ]
        )->name('admin.users.destroy');
    });
    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */
    Route::post(
        '/logout',
        function (Request $request) {
            Auth::guard('web')->logout();
            $request
                ->session()
                ->invalidate();
            $request
                ->session()
                ->regenerateToken();
            return redirect()
                ->route('welcome');
        }
    )->name('logout');
});
