@php

  /*

  |--------------------------------------------------------------------------

  | ARSIP & HAPUS DATA

  |--------------------------------------------------------------------------

  | Semua angka dan daftar tanggal diambil langsung dari fallout_data

  | berdasarkan Witel yang sedang dibuka.

  |

  | Fitur yang tersedia:

  | 1. Hapus Per Tanggal

  | 2. Hapus Per Tahun

  | 3. Ringkasan per Tahun

  | 4. 10 Tanggal Terbaru

  |--------------------------------------------------------------------------

  */

  $dbRows = \App\Models\FalloutData::forWitel($witelSlug)

      ->orderByDesc('tanggal')

      ->orderByDesc('row_id')

      ->get()

      ->toBase();

  /*

  |--------------------------------------------------------------------------

  | DAFTAR TAHUN

  |--------------------------------------------------------------------------

  */

  $years = $dbRows

      ->filter(fn ($row) => !empty($row->tanggal))

      ->map(fn ($row) => \Carbon\Carbon::parse($row->tanggal)->year)

      ->unique()

      ->sortDesc()

      ->values();

  $currentYear = (int) now()->year;

  if (!$years->contains($currentYear)) {

      $years->push($currentYear);

  }

  $years = $years

      ->unique()

      ->sortDesc()

      ->values();

  /*

  |--------------------------------------------------------------------------

  | TAHUN TERPILIH

  |--------------------------------------------------------------------------

  */

  $selectedYear = (int) request()->query(

      'tahun',

      $currentYear

  );

  /*

  |--------------------------------------------------------------------------

  | RINGKASAN PER TAHUN

  |--------------------------------------------------------------------------

  */

  $yearSummary = $dbRows

      ->filter(fn ($row) => !empty($row->tanggal))

      ->groupBy(

          fn ($row) =>

              \Carbon\Carbon::parse(

                  $row->tanggal

              )->year

      )

      ->map(

          fn ($group) =>

              $group->count()

      )

      ->sortKeysDesc();

  $selectedYearCount =

      (int) (

          $yearSummary[$selectedYear]

          ?? 0

      );

  /*

  |--------------------------------------------------------------------------

  | TOTAL DATA WITEL

  |--------------------------------------------------------------------------

  */

  $totalWitelData =

      $dbRows->count();

  /*

  |--------------------------------------------------------------------------

  | 10 TANGGAL TERBARU

  |--------------------------------------------------------------------------

  */

  $latestDates = $dbRows

      ->filter(fn ($row) => !empty($row->tanggal))

      ->groupBy(

          function ($row) {

              return \Carbon\Carbon::parse(

                  $row->tanggal

              )->format('Y-m-d');

          }

      )

      ->map(

          fn ($group) =>

              $group->count()

      )

      ->sortKeysDesc()

      ->take(10);

  /*

  |--------------------------------------------------------------------------

  | TANGGAL YANG DIPILIH UNTUK HAPUS

  |--------------------------------------------------------------------------

  */

  $selectedTanggal =

      (string) request()->query(

          'tanggal',

          ''

      );

  $selectedTanggalCount = 0;

  if ($selectedTanggal !== '') {

      $selectedTanggalCount =

          $dbRows

              ->filter(

                  function ($row) use (

                      $selectedTanggal

                  ) {

                      if (!$row->tanggal) {

                          return false;

                      }

                      return \Carbon\Carbon::parse(

                          $row->tanggal

                      )->format('Y-m-d')

                      ===

                      $selectedTanggal;

                  }

              )

              ->count();

  }

  /*

  |--------------------------------------------------------------------------

  | FORMAT TANGGAL

  |--------------------------------------------------------------------------

  */

  $formatDate =

      function ($date) {

          if (!$date) {

              return '-';

          }

          return \Carbon\Carbon::parse(

              $date

          )->translatedFormat(

              'd F Y'

          );

      };



  // RIWAYAT DATA DIHAPUS: bagian ini hanya membaca tabel audit jika migrasi sudah dijalankan.
  $adhSearch = trim((string) request()->query('riwayat_cari', ''));
  $adhHistoryReady = \Illuminate\Support\Facades\Schema::hasTable('deleted_fallout_histories');
  $adhHistory = null;
  $adhHistoryTotal = 0;

  if ($adhHistoryReady) {
      $adhWitelSlug = \Illuminate\Support\Str::lower(trim((string) $witelSlug));
      $adhHistoryTotal = \Illuminate\Support\Facades\DB::table('deleted_fallout_histories')
          ->where('witel_slug', $adhWitelSlug)
          ->count();

      $adhQuery = \Illuminate\Support\Facades\DB::table('deleted_fallout_histories')
          ->where('witel_slug', $adhWitelSlug);

      if ($adhSearch !== '') {
          $adhLike = '%' . mb_strtolower($adhSearch) . '%';
          $adhQuery->where(function ($query) use ($adhLike) {
              $query->whereRaw("LOWER(COALESCE(order_id, '')) LIKE ?", [$adhLike])
                  ->orWhereRaw("LOWER(COALESCE(sto, '')) LIKE ?", [$adhLike])
                  ->orWhereRaw("LOWER(COALESCE(pic, '')) LIKE ?", [$adhLike])
                  ->orWhereRaw("LOWER(COALESCE(deleted_by_name, '')) LIKE ?", [$adhLike]);
          });
      }

      $adhHistory = $adhQuery
          ->orderByDesc('deleted_at')
          ->orderByDesc('history_id')
          ->paginate(10, ['*'], 'riwayat_page')
          ->withQueryString();
  }

@endphp

<div class="ah-wrap">

  {{-- ============================================================

       LOADING KHUSUS HALAMAN ARSIP & HAPUS

       Menutup seluruh area content seperti halaman Edit Data.

  ============================================================ --}}

  <div

    class="ah-page-loading"

    id="ahPageLoading"

    aria-live="polite"

    aria-label="Memuat Arsip dan Hapus"

  >

    <div class="ah-page-loading-card">

      <div class="ah-page-loading-logo" aria-hidden="true">

        <span>TF</span>

      </div>

      <div class="ah-page-loading-spinner" aria-hidden="true"></div>

      <div class="ah-page-loading-title">

        Memuat Arsip &amp; Hapus

      </div>

      <div class="ah-page-loading-subtitle">

        Menyiapkan data {{ $witel }}

      </div>

    </div>

  </div>

  {{-- ============================================================

       HEADER

  ============================================================ --}}

  <div class="ah-head">

    <div>

      <h2>

        Arsip &amp; Hapus

      </h2>

      <p>

        Hapus data per tanggal atau per tahun secara permanen

        — {{ $witel }}

      </p>

    </div>

    <div class="ah-total-badge">

      {{ number_format($totalWitelData) }} data

    </div>

  </div>

  {{-- ============================================================

       PERINGATAN

  ============================================================ --}}

  <div class="ah-warning">

    <div class="ah-warning-icon">

      <svg

        width="17"

        height="17"

        viewBox="0 0 24 24"

        fill="none"

      >

        <path

          d="M12 3l9 17H3L12 3z"

          stroke="currentColor"

          stroke-width="1.7"

          stroke-linejoin="round"

        />

        <path

          d="M12 9v4"

          stroke="currentColor"

          stroke-width="1.7"

          stroke-linecap="round"

        />

        <circle

          cx="12"

          cy="16.5"

          r="0.8"

          fill="currentColor"

        />

      </svg>

    </div>

    <div>

      <strong>

        Peringatan:

      </strong>

      Data yang dihapus

      <strong>

        tidak bisa dikembalikan.

      </strong>

      Pastikan data sudah dibackup

      (download CSV/Excel) sebelum menghapus.

    </div>

  </div>

  {{-- ============================================================

       FLASH MESSAGE DITAMPILKAN MELALUI MODAL

  ============================================================ --}}

  {{-- ============================================================

       DUA CARD HAPUS

  ============================================================ --}}

  <div class="ah-delete-grid">

    {{-- ==========================================================

         HAPUS PER TANGGAL

    ========================================================== --}}

    <div class="ah-card ah-delete-card">

      <div class="ah-card-head">

        <div class="ah-card-icon">

          <svg

            width="18"

            height="18"

            viewBox="0 0 24 24"

            fill="none"

          >

            <rect

              x="4"

              y="5"

              width="16"

              height="15"

              rx="2"

              stroke="currentColor"

              stroke-width="1.8"

            />

            <path

              d="M8 3v4M16 3v4M4 10h16"

              stroke="currentColor"

              stroke-width="1.8"

              stroke-linecap="round"

            />

          </svg>

        </div>

        <div>

          <h3>

            Hapus Per Tanggal

          </h3>

          <p>

            Hapus semua data di tanggal tertentu

          </p>

        </div>

      </div>

      <form

        method="POST"

        action="{{ route('arsip.hapus.tanggal', ['witel' => $witelSlug]) }}"

        onsubmit="return confirmDeleteTanggal(this);"

      >

        @csrf

        @method('DELETE')

        <div class="ah-form-group">

          <label for="ahTanggal">

            Pilih Tanggal

          </label>

          <input

            type="date"

            id="ahTanggal"

            name="tanggal"

            value="{{ $selectedTanggal }}"

            onchange="ahUpdateTanggalPreview()"

          >

        </div>

        <div class="ah-danger-preview">

          <strong id="ahTanggalDeleteCount">

            @if($selectedTanggal !== '')

              {{ number_format($selectedTanggalCount) }}

              data akan dihapus

            @else

              Pilih tanggal terlebih dahulu

            @endif

          </strong>

          <span id="ahTanggalDeleteText">

            @if($selectedTanggal !== '')

              Seluruh data pada

              {{ $formatDate($selectedTanggal) }}

            @else

              Belum ada tanggal yang dipilih

            @endif

          </span>

        </div>

        <button

          type="submit"

          class="ah-delete-btn ah-delete-date-btn"

          id="ahDeleteTanggalBtn"

          {{ ($selectedTanggal === '' || $selectedTanggalCount <= 0) ? 'disabled' : '' }}

        >

          <svg

            width="15"

            height="15"

            viewBox="0 0 24 24"

            fill="none"

          >

            <path

              d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"

              stroke="currentColor"

              stroke-width="1.8"

              stroke-linecap="round"

              stroke-linejoin="round"

            />

          </svg>

          <span>

            Hapus Data Tanggal Ini

          </span>

        </button>

      </form>

    </div>

    {{-- ==========================================================

         HAPUS PER TAHUN

    ========================================================== --}}

    <div class="ah-card ah-delete-card">

      <div class="ah-card-head">

        <div class="ah-card-icon ah-card-icon-year">

          <svg

            width="18"

            height="18"

            viewBox="0 0 24 24"

            fill="none"

          >

            <circle

              cx="12"

              cy="12"

              r="8"

              stroke="currentColor"

              stroke-width="1.8"

            />

            <path

              d="M12 8v4l2.5 2"

              stroke="currentColor"

              stroke-width="1.8"

              stroke-linecap="round"

              stroke-linejoin="round"

            />

          </svg>

        </div>

        <div>

          <h3>

            Hapus Per Tahun

          </h3>

          <p>

            Hapus semua data dalam satu tahun

          </p>

        </div>

      </div>

      <form

        method="POST"

        action="{{ route('arsip.hapus.tahun', ['witel' => $witelSlug]) }}"

        onsubmit="return confirmDeleteYear(this);"

      >

        @csrf

        @method('DELETE')

        <div class="ah-form-group">

          <label for="ahTahun">

            Pilih Tahun

          </label>

          <select

            id="ahTahun"

            name="tahun"

            onchange="ahUpdateYearPreview()"

          >

            @foreach($years as $year)

              <option

                value="{{ $year }}"

                {{ (int) $year === $selectedYear ? 'selected' : '' }}

              >

                {{ $year }}

              </option>

            @endforeach

          </select>

        </div>

        <div class="ah-danger-preview">

          <strong id="ahDeleteCount">

            {{ number_format($selectedYearCount) }}

            data akan dihapus

          </strong>

          <span id="ahDeleteText">

            Seluruh data tahun

            {{ $selectedYear }}

            @if($selectedYearCount > 0)

              ({{ count(

                $dbRows

                  ->filter(function ($row) use ($selectedYear) {

                      return $row->tanggal

                        && \Carbon\Carbon::parse($row->tanggal)->year == $selectedYear;

                  })

                  ->groupBy(function ($row) {

                      return \Carbon\Carbon::parse($row->tanggal)->format('Y-m-d');

                  })

                  ->keys()

              ) }} tanggal)

            @endif

          </span>

        </div>

        <button

          type="submit"

          class="ah-delete-btn"

          id="ahDeleteYearBtn"

          {{ $selectedYearCount <= 0 ? 'disabled' : '' }}

        >

          <svg

            width="15"

            height="15"

            viewBox="0 0 24 24"

            fill="none"

          >

            <path

              d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"

              stroke="currentColor"

              stroke-width="1.8"

              stroke-linecap="round"

              stroke-linejoin="round"

            />

          </svg>

          <span>

            Hapus Semua Data Tahun

            <span id="ahDeleteYearLabel">

              {{ $selectedYear }}

            </span>

          </span>

        </button>

      </form>

    </div>

  </div>

  {{-- ============================================================

       RINGKASAN DATABASE

  ============================================================ --}}

  <div class="ah-card ah-summary-card">

    <div class="ah-summary-title">

      Ringkasan Data di Database

    </div>

    <div class="ah-summary-grid">

      {{-- ========================================================

           PER TAHUN

      ======================================================== --}}

      <div class="ah-summary-section">

        <div class="ah-summary-label">

          PER TAHUN

        </div>

        <div class="ah-year-list">

          @forelse($yearSummary as $year => $count)

            <div class="ah-year-row">

              <span>

                {{ $year }}

              </span>

              <strong>

                {{ number_format($count) }} data

              </strong>

            </div>

          @empty

            <div class="ah-empty-mini">

              Belum ada data di database.

            </div>

          @endforelse

        </div>

      </div>

      {{-- ========================================================

           10 TANGGAL TERBARU

      ======================================================== --}}

      <div class="ah-summary-section">

        <div class="ah-summary-label">

          10 TANGGAL TERBARU

        </div>

        <div class="ah-date-list">

          @forelse($latestDates as $date => $count)

            <div class="ah-date-row">

              <span>

                {{ $formatDate($date) }}

              </span>

              <strong>

                {{ number_format($count) }}

                data

              </strong>

            </div>

          @empty

            <div class="ah-empty-mini">

              Belum ada data di database.

            </div>

          @endforelse

        </div>

        @if($latestDates->count() >= 10)

          <div class="ah-more-date">

            @php

              $totalUniqueDates =

                  $dbRows

                      ->filter(

                          fn ($row) =>

                              !empty($row->tanggal)

                      )

                      ->map(

                          fn ($row) =>

                              \Carbon\Carbon::parse(

                                  $row->tanggal

                              )->format('Y-m-d')

                      )

                      ->unique()

                      ->count();

              $otherDates =

                  max(

                      $totalUniqueDates - 10,

                      0

                  );

            @endphp

            @if($otherDates > 0)

              ...dan

              {{ $otherDates }}

              tanggal lainnya

            @endif

          </div>

        @endif

      </div>

    </div>

  </div>

  {{-- RIWAYAT DATA DIHAPUS — terintegrasi langsung pada halaman Arsip & Hapus --}}
  <section class="adh-history-card" id="adhDeletedHistory">
    <div class="adh-history-head">
      <div class="adh-history-title-wrap">
        <span class="adh-history-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        <div>
          <h3>Riwayat Data Dihapus</h3>
          <p>Data yang pernah dihapus beserta pengguna dan waktu penghapusannya.</p>
        </div>
      </div>

      <div class="adh-history-tools">
        <form class="adh-history-search" method="GET" action="{{ route('dashboard.menu', ['witel' => $witelSlug, 'menu' => 'arsip-hapus']) }}">
          @foreach(request()->except(['riwayat_cari', 'riwayat_page']) as $key => $value)
            @if(is_scalar($value))
              <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
          @endforeach
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.5" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          <input type="search" name="riwayat_cari" value="{{ $adhSearch }}" placeholder="Cari ID, STO, PIC..." aria-label="Cari riwayat data dihapus">
          @if($adhSearch !== '')
            <a class="adh-history-clear" href="{{ route('dashboard.menu', ['witel' => $witelSlug, 'menu' => 'arsip-hapus']) }}" aria-label="Hapus pencarian">×</a>
          @endif
        </form>

        @if($adhHistoryReady)
          <form class="adh-history-delete-form" method="POST"
                action="{{ route('arsip-hapus.riwayat.destroy', ['witel' => $witelSlug]) }}"
                onsubmit="confirmDeleteHistory(this); return false;">
            @csrf
            @method('DELETE')
            <button type="submit" class="adh-history-delete-btn" {{ $adhHistoryTotal <= 0 ? 'disabled' : '' }}>
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" aria-hidden="true">
                <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Hapus Riwayat</span>
              <span class="adh-history-delete-count">{{ number_format($adhHistoryTotal, 0, ',', '.') }}</span>
            </button>
          </form>
        @endif
      </div>
    </div>

    @if(!$adhHistoryReady)
      <div class="adh-history-empty">Aktifkan penyimpanan riwayat terlebih dahulu dengan menjalankan migrasi tabel riwayat.</div>
    @else
      <div class="adh-history-table-wrap">
        <table class="adh-history-table">
          <thead><tr><th>No</th><th>ID Fallout</th><th>STO</th><th>PIC</th><th>Dihapus Oleh</th><th>Waktu</th><th>Aksi</th></tr></thead>
          <tbody>
            @forelse($adhHistory as $history)
              <tr>
                <td>{{ ($adhHistory->firstItem() ?? 1) + $loop->index }}</td>
                @php
                  $adhRowId = (string) ($history->order_id ?: ('#' . ($history->original_row_id ?? $history->history_id)));
                  $adhShortId = mb_strlen($adhRowId) > 34
                      ? mb_substr($adhRowId, 0, 18) . '…' . mb_substr($adhRowId, -10)
                      : $adhRowId;
                @endphp
                <td class="adh-history-id" title="{{ $adhRowId }}" aria-label="ID Fallout lengkap: {{ $adhRowId }}">{{ $adhShortId }}</td>
                <td><span class="adh-sto-badge">{{ $history->sto ?: '-' }}</span></td>
                <td>{{ $history->pic ?: '-' }}</td>
                <td><span class="adh-deleter"><span class="adh-user-mark" aria-hidden="true">♙</span>{{ $history->deleted_by_name ?: 'System' }}</span></td>
                <td class="adh-history-time">{{ \Carbon\Carbon::parse($history->deleted_at)->translatedFormat('d/m/Y, H.i') }}</td>
                <td>
                  <button type="button" class="adh-detail-btn" data-adh-detail
                    data-fallout-id="{{ $adhRowId }}"
                    data-sto="{{ $history->sto ?: '-' }}"
                    data-tanggal="{{ $history->tanggal ? \Carbon\Carbon::parse($history->tanggal)->format('d/m/Y') : '-' }}"
                    data-pic="{{ $history->pic ?: '-' }}"
                    data-resolution="{{ $history->resolved_eskalasi ?: '-' }}"
                    data-status="{{ $history->status ?: '-' }}"
                    data-deskripsi="{{ $history->deskripsi ?: '-' }}"
                    data-keterangan="{{ $history->ket ?: '-' }}"
                    data-deleted-by="{{ $history->deleted_by_name ?: 'System' }}"
                    data-deleted-at="{{ \Carbon\Carbon::parse($history->deleted_at)->translatedFormat('d/m/Y, H.i') }}">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                    Detail
                  </button>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="adh-history-empty">@if($adhSearch !== '')Tidak ada riwayat yang cocok dengan pencarian “{{ $adhSearch }}”.@else Belum ada riwayat data dihapus untuk Witel ini.@endif</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="adh-history-footer">
        <span>Menampilkan {{ $adhHistory->count() }} dari {{ number_format($adhHistory->total()) }} arsip</span>
        @if($adhHistory->hasPages())
          <nav class="adh-history-pagination" aria-label="Halaman riwayat data dihapus">
            @if($adhHistory->onFirstPage())<span class="is-disabled">Sebelumnya</span>@else<a href="{{ $adhHistory->previousPageUrl() }}">Sebelumnya</a>@endif
            <span class="adh-page-number">{{ $adhHistory->currentPage() }} / {{ $adhHistory->lastPage() }}</span>
            @if($adhHistory->hasMorePages())<a href="{{ $adhHistory->nextPageUrl() }}">Berikutnya</a>@else<span class="is-disabled">Berikutnya</span>@endif
          </nav>
        @endif
      </div>
    @endif
  </section>

  <div class="adh-detail-overlay" id="adhDetailOverlay" aria-hidden="true">
    <div class="adh-detail-modal" role="dialog" aria-modal="true" aria-labelledby="adhDetailTitle">
      <div class="adh-detail-modal-head">
        <div><h3 id="adhDetailTitle"><span class="adh-history-icon" aria-hidden="true">⌫</span>Detail Data Terhapus</h3><p>ID: <span id="adhDetailSubId">-</span></p></div>
        <button type="button" class="adh-detail-close" id="adhDetailClose" aria-label="Tutup detail">×</button>
      </div>
      <div class="adh-detail-grid">
        <div class="adh-detail-field"><span>FALLOUT ID</span><strong id="adhDetailFalloutId">-</strong></div>
        <div class="adh-detail-field"><span>STO</span><strong id="adhDetailSto">-</strong></div>
        <div class="adh-detail-field"><span>TANGGAL</span><strong id="adhDetailTanggal">-</strong></div>
        <div class="adh-detail-field"><span>PIC</span><strong id="adhDetailPic">-</strong></div>
        <div class="adh-detail-field"><span>RESOLUTION</span><strong id="adhDetailResolution">-</strong></div>
        <div class="adh-detail-field"><span>STATUS</span><strong id="adhDetailStatus">-</strong></div>
        <div class="adh-detail-field adh-detail-field-wide"><span>DESKRIPSI</span><strong id="adhDetailDeskripsi">-</strong></div>
        <div class="adh-detail-field adh-detail-field-wide"><span>KETERANGAN</span><strong id="adhDetailKeterangan">-</strong></div>
        <div class="adh-detail-field"><span>DIHAPUS OLEH</span><strong id="adhDetailDeletedBy">-</strong></div>
        <div class="adh-detail-field"><span>WAKTU PENGHAPUSAN</span><strong id="adhDetailDeletedAt">-</strong></div>
      </div>
    </div>
  </div>

  {{-- ============================================================
       INFORMASI
  ============================================================ --}}
  <div class="ah-info-card">

    <div class="ah-info-icon">

      ✓

    </div>

    <div>

      <strong>

        Data yang digunakan adalah data asli dari fallout_data

      </strong>

      <p>

        Jumlah data, ringkasan tahun, tanggal terbaru, serta

        proses penghapusan semuanya menggunakan data

        Witel {{ $witel }} yang tersimpan di database.

        Setelah penghapusan berhasil, halaman dimuat ulang

        sehingga angka langsung ikut berubah.

      </p>

    </div>

  </div>

</div>

  {{-- ============================================================

       UI ALERT / CONFIRM

  ============================================================ --}}

  <div class="ah-ui-alert-overlay" id="ahUiAlertOverlay" aria-hidden="true">

    <div class="ah-ui-alert-card" role="dialog" aria-modal="true" aria-labelledby="ahUiAlertTitle">

      <div class="ah-ui-alert-icon" id="ahUiAlertIcon"></div>

      <h3 id="ahUiAlertTitle">Informasi</h3>

      <div class="ah-ui-alert-message" id="ahUiAlertMessage"></div>

      <div class="ah-ui-alert-actions" id="ahUiAlertActions">

        <button

          type="button"

          class="ah-ui-alert-btn ah-ui-alert-cancel"

          id="ahUiAlertCancel"

        >

          Batal

        </button>

        <button

          type="button"

          class="ah-ui-alert-btn ah-ui-alert-confirm"

          id="ahUiAlertConfirm"

        >

          OK

        </button>

      </div>

    </div>

  </div>

<style>

  /* ============================================================

     LOADING FULL AREA ARSIP & HAPUS

     ============================================================ */

  .content.ah-page-loading-lock{

    overflow:hidden !important;

    cursor:none !important;

  }

  .content.ah-page-loading-lock,

  .content.ah-page-loading-lock *{

    cursor:none !important;

  }

  .ah-page-loading{

    position:fixed;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:28px;

    background:rgba(250,247,247,.94);

    backdrop-filter:blur(8px);

    -webkit-backdrop-filter:blur(8px);

    border-radius:0;

    z-index:2147482000;

    transition:opacity .28s ease, visibility .28s ease;

    pointer-events:auto;

    cursor:none !important;

    box-sizing:border-box;

  }

  .ah-page-loading,

  .ah-page-loading *{

    cursor:none !important;

  }

  .ah-page-loading.is-hidden{

    opacity:0;

    visibility:hidden;

    pointer-events:none;

  }

  .ah-page-loading-card{

    width:min(320px,100%);

    padding:28px 24px 24px;

    text-align:center;

    border:1px solid rgba(255,255,255,.9);

    border-radius:18px;

    background:rgba(255,255,255,.90);

    box-shadow:0 24px 60px -30px rgba(58,4,16,.30);

  }

  .ah-page-loading-logo{

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

  .ah-page-loading-spinner{

    width:30px;

    height:30px;

    margin:0 auto 14px;

    border:3px solid #F1D8DD;

    border-top-color:#C8102E;

    border-right-color:#C8102E;

    border-radius:50%;

    animation:ahPageLoadingSpin .8s linear infinite;

  }

  .ah-page-loading-title{

    font-family:'Space Grotesk',sans-serif;

    font-size:15px;

    font-weight:700;

    color:#2C2528;

  }

  .ah-page-loading-subtitle{

    margin-top:5px;

    font-family:'Inter',sans-serif;

    font-size:11.5px;

    color:#7A6B6F;

  }

  @keyframes ahPageLoadingSpin{

    to{transform:rotate(360deg)}

  }

  .ah-wrap{

    width:100%;

    padding:8px 6px 28px;

  }

  /* ==========================================================

     HEADER

  ========================================================== */

  .ah-head{

    display:flex;

    align-items:flex-start;

    justify-content:space-between;

    gap:14px;

    flex-wrap:wrap;

    margin-bottom:16px;

  }

  .ah-head h2{

    font-family:'Space Grotesk',sans-serif;

    font-size:22px;

    font-weight:700;

    color:#C8102E;

  }

  .ah-head p{

    margin-top:4px;

    font-size:12.5px;

    color:#7A6B6F;

  }

  .ah-total-badge{

    padding:9px 14px;

    border-radius:999px;

    background:rgba(200,16,46,.08);

    color:#C8102E;

    font-size:12px;

    font-weight:800;

  }

  /* ==========================================================

     WARNING

  ========================================================== */

  .ah-warning{

    display:flex;

    align-items:flex-start;

    gap:10px;

    padding:13px 15px;

    margin-bottom:16px;

    border-radius:12px;

    border:1px solid #F2D17B;

    background:#FFF9E9;

    color:#9A5B00;

    font-size:12px;

    line-height:1.5;

  }

  .ah-warning-icon{

    flex:none;

    color:#D58A00;

    margin-top:1px;

  }

  .ah-warning strong{

    font-weight:800;

  }

  /* ==========================================================

     ALERT

  ========================================================== */

  .ah-alert{

    display:flex;

    align-items:flex-start;

    gap:10px;

    padding:12px 14px;

    border-radius:12px;

    margin-bottom:14px;

    font-size:12.5px;

  }

  .ah-alert-success{

    background:rgba(30,122,70,.09);

    border:1px solid rgba(30,122,70,.16);

    color:#1E7A46;

  }

  .ah-alert-error{

    background:rgba(200,16,46,.08);

    border:1px solid rgba(200,16,46,.16);

    color:#9B1028;

  }

  /* ==========================================================

     DELETE GRID

  ========================================================== */

  .ah-delete-grid{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:18px;

    margin-bottom:16px;

  }

  .ah-card{

    background:rgba(255,255,255,.72);

    border:1px solid rgba(255,255,255,.85);

    border-radius:16px;

    padding:20px;

    box-shadow:0 10px 30px -24px rgba(58,4,16,.28);

  }

  .ah-delete-card{

    min-height:280px;

  }

  .ah-card-head{

    display:flex;

    align-items:center;

    gap:12px;

    margin-bottom:18px;

  }

  .ah-card-icon{

    width:38px;

    height:38px;

    border-radius:10px;

    background:#F9ECEE;

    color:#C8102E;

    display:grid;

    place-items:center;

    flex:none;

  }

  .ah-card-icon-year{

    color:#7A6B6F;

  }

  .ah-card-head h3{

    font-size:15px;

    font-weight:800;

    color:#20161A;

  }

  .ah-card-head p{

    margin-top:3px;

    font-size:11.5px;

    color:#7A6B6F;

  }

  /* ==========================================================

     FORM

  ========================================================== */

  .ah-form-group{

    margin-bottom:16px;

  }

  .ah-form-group label{

    display:block;

    margin-bottom:7px;

    font-size:12px;

    font-weight:700;

    color:#7A6B6F;

  }

  .ah-form-group input,

  .ah-form-group select{

    width:100%;

    min-height:42px;

    padding:10px 12px;

    border:1.2px solid #E7DEDD;

    border-radius:10px;

    background:#fff;

    color:#20161A;

    font-family:'Inter',sans-serif;

    font-size:13px;

  }

  .ah-form-group input:focus,

  .ah-form-group select:focus{

    outline:none;

    border-color:#C8102E;

  }

  /* ==========================================================

     DELETE PREVIEW

  ========================================================== */

  .ah-danger-preview{

    display:flex;

    flex-direction:column;

    gap:3px;

    min-height:59px;

    justify-content:center;

    background:rgba(200,16,46,.06);

    border:1px solid rgba(200,16,46,.16);

    border-radius:12px;

    padding:10px 14px;

    margin-bottom:14px;

  }

  .ah-danger-preview strong{

    font-size:12.5px;

    font-weight:800;

    color:#A30E28;

  }

  .ah-danger-preview span{

    font-size:11px;

    color:#8A6C71;

    line-height:1.4;

  }

  /* ==========================================================

     DELETE BUTTON

  ========================================================== */

  .ah-delete-btn{

    width:100%;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    min-height:41px;

    border:none;

    border-radius:10px;

    padding:10px 14px;

    background:#5C0A1B;

    color:#fff;

    font-family:'Inter',sans-serif;

    font-size:12.5px;

    font-weight:800;

    cursor:pointer;

    transition:

      transform .12s ease,

      opacity .15s ease,

      background .15s ease;

  }

  .ah-delete-btn:hover:not(:disabled){

    background:#430713;

    transform:translateY(-1px);

  }

  .ah-delete-btn:disabled{

    opacity:.45;

    cursor:not-allowed;

    transform:none;

  }

  /* ==========================================================

     SUMMARY

  ========================================================== */

  .ah-summary-card{

    margin-bottom:16px;

  }

  .ah-summary-title{

    font-size:15px;

    font-weight:800;

    color:#C8102E;

    margin-bottom:16px;

  }

  .ah-summary-grid{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:24px;

  }

  .ah-summary-label{

    font-size:10px;

    font-weight:800;

    letter-spacing:.08em;

    color:#9A777D;

    margin-bottom:8px;

  }

  .ah-year-list,

  .ah-date-list{

    display:flex;

    flex-direction:column;

    gap:5px;

  }

  .ah-year-row,

  .ah-date-row{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:12px;

    padding:10px 12px;

    border-radius:10px;

    background:#FBF0F2;

    font-size:12px;

    color:#20161A;

  }

  .ah-year-row strong,

  .ah-date-row strong{

    font-weight:800;

    color:#20161A;

  }

  .ah-date-row{

    background:#FBF9F7;

    border:1px solid #F1EAE9;

  }

  .ah-more-date{

    text-align:center;

    margin-top:7px;

    font-size:10.5px;

    color:#A18489;

  }

  .ah-empty-mini{

    padding:16px 12px;

    border:1px dashed #E7DEDD;

    border-radius:10px;

    color:#7A6B6F;

    font-size:12px;

    text-align:center;

  }

  /* ==========================================================

     INFO

  ========================================================== */

  .ah-info-card{

    display:flex;

    align-items:flex-start;

    gap:11px;

    background:rgba(255,255,255,.72);

    border:1px solid rgba(255,255,255,.85);

    border-radius:16px;

    padding:15px 18px;

    box-shadow:0 10px 30px -24px rgba(58,4,16,.22);

  }

  .ah-info-icon{

    width:26px;

    height:26px;

    border-radius:50%;

    background:#EEF7F1;

    color:#1E7A46;

    display:grid;

    place-items:center;

    font-size:13px;

    font-weight:800;

    flex:none;

  }

  .ah-info-card strong{

    display:block;

    font-size:12.5px;

    color:#20161A;

  }

  .ah-info-card p{

    margin-top:4px;

    font-size:11.5px;

    line-height:1.55;

    color:#7A6B6F;

  }

  /* ==========================================================

     RESPONSIVE

  ========================================================== */

  @media(max-width:850px){

    .ah-delete-grid,

    .ah-summary-grid{

      grid-template-columns:1fr;

    }

  }

  /* ==========================================================

     UI ALERT / CONFIRM

  ========================================================== */

  html.ah-ui-lock,

  body.ah-ui-lock{

    overflow:hidden !important;

  }

  .ah-ui-alert-overlay{

    display:none;

    position:fixed !important;

    inset:0 !important;

    width:100vw !important;

    height:100vh !important;

    z-index:2147483000 !important;

    background:rgba(20,16,18,.56);

    backdrop-filter:blur(7px);

    -webkit-backdrop-filter:blur(7px);

    align-items:center;

    justify-content:center;

    padding:16px;

    overflow:hidden !important;

    box-sizing:border-box;

  }

  .ah-ui-alert-overlay.open{

    display:flex;

    animation:ahUiAlertFadeIn .18s ease;

  }

  .ah-ui-alert-card{

    width:min(440px, calc(100vw - 32px));

    max-width:440px;

    max-height:calc(100vh - 32px);

    overflow:hidden;

    background:#fff;

    border-radius:22px;

    padding:24px 22px 20px;

    box-shadow:0 34px 90px rgba(0,0,0,.30);

    text-align:center;

    animation:ahUiAlertPop .18s ease;

    box-sizing:border-box;

  }

  .ah-ui-alert-icon{

    width:54px;

    height:54px;

    margin:0 auto 14px;

    border-radius:50%;

    display:flex;

    align-items:center;

    justify-content:center;

    background:rgba(200,16,46,.08);

    color:#C8102E;

  }

  .ah-ui-alert-icon.success{

    background:rgba(30,122,70,.08);

    color:#16A34A;

  }

  .ah-ui-alert-icon.error{

    background:rgba(200,16,46,.08);

    color:#C8102E;

  }

  .ah-ui-alert-icon svg{

    width:27px;

    height:27px;

    display:block;

    stroke:currentColor;

  }

  .ah-ui-alert-card h3{

    margin:0 0 8px;

    font-family:'Space Grotesk',sans-serif;

    font-size:18px;

    font-weight:700;

    color:#252331;

  }

  .ah-ui-alert-message{

    min-height:42px;

    margin:0 auto 20px;

    max-width:380px;

    font-family:'Inter',sans-serif;

    font-size:12.5px;

    line-height:1.65;

    color:#7A808B;

    overflow-wrap:anywhere;

    word-break:break-word;

  }

  .ah-ui-alert-message strong{

    color:#2C3442;

    font-weight:800;

  }

  .ah-ui-alert-actions{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:10px;

  }

  .ah-ui-alert-actions.single{

    grid-template-columns:1fr;

  }

  .ah-ui-alert-btn{

    height:42px;

    border-radius:11px;

    font-family:'Inter',sans-serif;

    font-size:13px;

    font-weight:700;

    cursor:pointer;

    transition:.18s ease;

  }

  .ah-ui-alert-cancel{

    border:1px solid #E5E0E1;

    background:#fff;

    color:#6F7177;

  }

  .ah-ui-alert-cancel:hover{

    background:#F8F6F6;

  }

  .ah-ui-alert-confirm{

    border:1px solid #B40000;

    background:#B40000;

    color:#fff;

    box-shadow:0 10px 22px -14px rgba(180,0,0,.8);

  }

  .ah-ui-alert-confirm:hover{

    background:#980000;

    border-color:#980000;

  }

  .ah-ui-alert-confirm.success-btn{

    background:#650014;

    border-color:#650014;

  }

  @keyframes ahUiAlertFadeIn{

    from{opacity:0}

    to{opacity:1}

  }

  @keyframes ahUiAlertPop{

    from{opacity:0;transform:translateY(10px) scale(.98)}

    to{opacity:1;transform:translateY(0) scale(1)}

  }

  @media(max-width:520px){

    .ah-ui-alert-card{

      max-width:calc(100vw - 32px);

      padding:20px 18px 18px;

    }

  }



/* RIWAYAT DATA DIHAPUS — styling tambahan saja */
.adh-history-card{margin:18px 0 16px;padding:20px 18px 14px;background:rgba(255,255,255,.88);border:1px solid #E9DEDE;border-radius:18px;box-shadow:0 12px 30px -26px rgba(63,4,18,.35);color:#282126}
.adh-history-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px}
.adh-history-title-wrap{display:flex;align-items:flex-start;gap:11px;min-width:220px}
.adh-history-icon{display:inline-flex;align-items:center;justify-content:center;flex:none;width:34px;height:34px;border-radius:10px;color:#920B20;background:#F9ECEF}
.adh-history-title-wrap h3{margin:0;font-size:15px;font-weight:800;color:#3A1821}
.adh-history-title-wrap p{margin:4px 0 0;font-size:11.5px;line-height:1.5;color:#88777C}
.adh-history-tools{display:flex;align-items:center;justify-content:flex-end;gap:9px;flex-wrap:wrap;min-width:0}
.adh-history-delete-form{margin:0;flex:none}
.adh-history-delete-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:40px;padding:0 12px;border:1px solid #F0C8CF;border-radius:10px;background:#FFF3F5;color:#8B1027;cursor:pointer;font:700 11px Inter,sans-serif;white-space:nowrap;transition:background .15s ease,border-color .15s ease}
.adh-history-delete-btn:hover:not(:disabled){background:#FBE4E9;border-color:#DCAAB4}
.adh-history-delete-btn:disabled{opacity:.45;cursor:not-allowed}
.adh-history-delete-count{display:inline-flex;align-items:center;justify-content:center;min-width:21px;height:21px;padding:0 5px;border-radius:999px;background:#F2D0D7;color:#781126;font-size:10px}
.adh-history-search{display:flex;align-items:center;gap:8px;width:min(280px,100%);min-height:40px;padding:0 11px;border:1px solid #E8DDDE;background:#fff;border-radius:10px;color:#998B8F}
.adh-history-search input{min-width:0;flex:1;border:0;outline:0;background:transparent;color:#35252A;font:500 12px Inter,sans-serif}
.adh-history-search input::placeholder{color:#A99A9E}
.adh-history-clear{color:#8D0B20;text-decoration:none;font-size:20px;line-height:1}
.adh-history-table-wrap{width:100%;overflow-x:auto;border:1px solid #F0E8E9;border-radius:12px}
.adh-history-table{width:100%;min-width:760px;border-collapse:separate;border-spacing:0;font-size:11.5px}
.adh-history-table thead th{padding:12px 10px;background:#FBF8F8;color:#8C7B80;font-size:9.5px;font-weight:800;letter-spacing:.04em;text-align:left;border-bottom:1px solid #EDE3E4;white-space:nowrap}
.adh-history-table tbody td{padding:12px 10px;border-bottom:1px solid #F1E9EA;color:#5D5054;vertical-align:middle}
.adh-history-table tbody tr:last-child td{border-bottom:0}.adh-history-table tbody tr:hover{background:#FFFBFC}
.adh-history-table .adh-history-id{font-weight:700;color:#9C0B24;max-width:210px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:help;font-variant-numeric:tabular-nums}
.adh-sto-badge{display:inline-block;padding:4px 8px;border-radius:999px;background:#F9E9ED;color:#970A22;font-size:10px;font-weight:800}
.adh-deleter{display:inline-flex;align-items:center;gap:6px;white-space:nowrap}.adh-user-mark{display:inline-flex;align-items:center;justify-content:center;width:23px;height:23px;border-radius:50%;color:#8F0B20;background:#F8EAED;font-size:16px}
.adh-history-time{white-space:nowrap}
.adh-detail-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid #EADFE1;border-radius:8px;padding:7px 10px;background:#fff;color:#6D1727;cursor:pointer;font:700 11px Inter,sans-serif;transition:background .15s ease,border-color .15s ease}
.adh-detail-btn:hover{background:#FFF3F5;border-color:#DAB8C0}
.adh-history-empty{padding:28px 16px!important;text-align:center;color:#97868A!important}
.adh-history-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 2px 0;color:#95868A;font-size:10.5px}
.adh-history-pagination{display:flex;align-items:center;gap:9px}
.adh-history-pagination a,.adh-history-pagination .is-disabled{padding:6px 9px;border:1px solid #EAE0E1;border-radius:7px;color:#751125;text-decoration:none;background:#fff}
.adh-history-pagination a:hover{background:#FFF3F5}.adh-history-pagination .is-disabled{color:#B7A9AC;background:#FAF8F8}.adh-page-number{font-weight:800;color:#79676C;white-space:nowrap}
.adh-detail-overlay{display:none;position:fixed;inset:0;z-index:2147483000;align-items:center;justify-content:center;padding:18px;background:rgba(35,20,24,.42);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);box-sizing:border-box}
.adh-detail-overlay.is-open{display:flex}.adh-detail-modal{width:min(720px,100%);max-height:min(82vh,760px);overflow:auto;padding:22px;border:1px solid rgba(255,255,255,.8);border-radius:20px;background:#fff;box-shadow:0 28px 90px rgba(40,0,10,.28)}
.adh-detail-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:18px}.adh-detail-modal-head h3{display:flex;align-items:center;gap:9px;margin:0;color:#35232A;font-size:16px;font-weight:800}.adh-detail-modal-head p{margin:6px 0 0;color:#908187;font-size:11px}
.adh-detail-close{width:32px;height:32px;flex:none;border:0;border-radius:9px;color:#806B71;background:#F8F3F4;font-size:23px;line-height:1;cursor:pointer}
.adh-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.adh-detail-field{min-width:0;padding:13px 14px;border:1px solid #EFE6E7;border-radius:11px;background:#FCFAFA}.adh-detail-field span{display:block;margin-bottom:7px;color:#A08F94;font-size:9px;font-weight:800;letter-spacing:.06em}.adh-detail-field strong{display:block;color:#433238;font-size:12px;font-weight:600;line-height:1.55;overflow-wrap:anywhere;white-space:pre-wrap}.adh-detail-field-wide{grid-column:1/-1}
html.adh-history-modal-lock,body.adh-history-modal-lock{overflow:hidden!important}
@media(max-width:640px){.adh-history-card{padding:15px 12px 12px}.adh-history-tools{width:100%;justify-content:stretch}.adh-history-search{width:100%;flex:1 1 100%}.adh-history-delete-form{width:100%}.adh-history-delete-btn{width:100%}.adh-detail-modal{padding:16px;border-radius:15px}.adh-detail-grid{grid-template-columns:1fr}.adh-detail-field-wide{grid-column:auto}}

</style>

<script>

  (function(){

    /* ===========================================================

       LOADING FULL AREA ARSIP & HAPUS

       Mengunci scroll + cursor pada area .content saja.

       =========================================================== */

    const ahPageLoading = document.getElementById('ahPageLoading');

    const ahWrap = document.querySelector('.ah-wrap');

    const ahContent = ahWrap

      ? (ahWrap.closest('.content') || ahWrap.parentElement)

      : null;

    let ahLoadingResizeHandler = null;

    function ahPositionPageLoading(){

      if (!ahPageLoading || !ahContent) return;

      const rect = ahContent.getBoundingClientRect();

      ahPageLoading.style.left = rect.left + 'px';

      ahPageLoading.style.top = rect.top + 'px';

      ahPageLoading.style.width = rect.width + 'px';

      ahPageLoading.style.height = rect.height + 'px';

    }

    if (ahPageLoading && ahContent){

      ahContent.classList.add('ah-page-loading-lock');

      ahPositionPageLoading();

      ahLoadingResizeHandler = ahPositionPageLoading;

      window.addEventListener('resize', ahLoadingResizeHandler);

      /* Pastikan overlay langsung berada di atas seluruh content. */

      if (ahPageLoading.parentElement !== document.body){

        document.body.appendChild(ahPageLoading);

      }

      ahPositionPageLoading();

      setTimeout(function(){

        ahPageLoading.classList.add('is-hidden');

        ahContent.classList.remove('ah-page-loading-lock');

        if (ahLoadingResizeHandler){

          window.removeEventListener('resize', ahLoadingResizeHandler);

          ahLoadingResizeHandler = null;

        }

        setTimeout(function(){

          if (ahPageLoading && ahPageLoading.parentElement === document.body){

            ahPageLoading.remove();

          }

        }, 320);

      }, 1000);

    }

    /*

    |--------------------------------------------------------------------------

    | DATA JUMLAH PER TAHUN

    |--------------------------------------------------------------------------

    */

    const yearCounts = @json(

      $yearSummary->mapWithKeys(

        fn($count, $year) => [

          (string) $year => (int) $count

        ]

      )

    );

    /*

    |--------------------------------------------------------------------------

    | DATA JUMLAH PER TANGGAL

    |--------------------------------------------------------------------------

    */

    const dateCounts = @json(

      $latestDates->mapWithKeys(

        fn($count, $date) => [

          (string) $date => (int) $count

        ]

      )

    );

    /*

    |--------------------------------------------------------------------------

    | UPDATE PREVIEW TAHUN

    |--------------------------------------------------------------------------

    */

    window.ahUpdateYearPreview =

      function(){

        const select =

          document.getElementById(

            'ahTahun'

          );

        const countEl =

          document.getElementById(

            'ahDeleteCount'

          );

        const textEl =

          document.getElementById(

            'ahDeleteText'

          );

        const button =

          document.getElementById(

            'ahDeleteYearBtn'

          );

        const yearLabel =

          document.getElementById(

            'ahDeleteYearLabel'

          );

        if (!select){

          return;

        }

        const year =

          select.value;

        const count =

          Number(

            yearCounts[year] || 0

          );

        countEl.textContent =

          count.toLocaleString(

            'id-ID'

          )

          +

          ' data akan dihapus';

        textEl.textContent =

          'Seluruh data tahun '

          +

          year;

        yearLabel.textContent =

          year;

        button.disabled =

          count <= 0;

      };

    /*

    |--------------------------------------------------------------------------

    | UPDATE PREVIEW TANGGAL

    |--------------------------------------------------------------------------

    */

    window.ahUpdateTanggalPreview =

      function(){

        const input =

          document.getElementById(

            'ahTanggal'

          );

        const countEl =

          document.getElementById(

            'ahTanggalDeleteCount'

          );

        const textEl =

          document.getElementById(

            'ahTanggalDeleteText'

          );

        const button =

          document.getElementById(

            'ahDeleteTanggalBtn'

          );

        if (!input){

          return;

        }

        const date =

          input.value;

        if (!date){

          countEl.textContent =

            'Pilih tanggal terlebih dahulu';

          textEl.textContent =

            'Belum ada tanggal yang dipilih';

          button.disabled =

            true;

          return;

        }

        /*

        |--------------------------------------------------------------------------

        | Untuk tanggal yang tidak masuk 10 tanggal terbaru,

        | nilai count belum tersedia di JS. Agar tombol tetap benar,

        | arahkan browser ke query tanggal dan gunakan nilai dari server.

        |--------------------------------------------------------------------------

        */

        const params =

          new URLSearchParams(

            window.location.search

          );

        params.set(

          'tanggal',

          date

        );

        window.location.search =

          params.toString();

      };

    /*

    |--------------------------------------------------------------------------

    | KONFIRMASI HAPUS TANGGAL

    |--------------------------------------------------------------------------

    */

    const ahUiAlertOverlay = document.getElementById('ahUiAlertOverlay');

    const ahUiAlertIcon = document.getElementById('ahUiAlertIcon');

    const ahUiAlertTitle = document.getElementById('ahUiAlertTitle');

    const ahUiAlertMessage = document.getElementById('ahUiAlertMessage');

    const ahUiAlertActions = document.getElementById('ahUiAlertActions');

    const ahUiAlertCancel = document.getElementById('ahUiAlertCancel');

    const ahUiAlertConfirm = document.getElementById('ahUiAlertConfirm');

    let ahUiAlertResolver = null;

    if (ahUiAlertOverlay && ahUiAlertOverlay.parentElement !== document.body) {

      document.body.appendChild(ahUiAlertOverlay);

    }

    function ahEscapeUiHtml(value){

      return String(value ?? '')

        .replace(/&/g,'&amp;')

        .replace(/</g,'&lt;')

        .replace(/>/g,'&gt;')

        .replace(/"/g,'&quot;')

        .replace(/'/g,'&#039;');

    }

    function ahUiIcon(type){

      if(type === 'success'){

        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5 9.2 17 19 7"/></svg>';

      }

      if(type === 'error'){

        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16.5h.01"/></svg>';

      }

      return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8.5V6a3 3 0 0 1 6 0v2.5"/><path d="M5 8.5h14l-1 11H6l-1-11Z"/><path d="M9 11.5v4"/><path d="M15 11.5v4"/></svg>';

    }

    function ahOpenUiAlert({

      type='error',

      title='Informasi',

      message='',

      confirmText='OK',

      cancelText='Batal',

      showCancel=false

    }){

      return new Promise(resolve => {

        ahUiAlertResolver = resolve;

        ahUiAlertIcon.className = `ah-ui-alert-icon ${type}`;

        ahUiAlertIcon.innerHTML = ahUiIcon(type);

        ahUiAlertTitle.textContent = title;

        ahUiAlertMessage.innerHTML = message;

        ahUiAlertCancel.textContent = cancelText;

        ahUiAlertConfirm.textContent = confirmText;

        ahUiAlertCancel.style.display = showCancel ? '' : 'none';

        ahUiAlertActions.classList.toggle('single', !showCancel);

        ahUiAlertConfirm.classList.toggle('success-btn', type === 'success');

        document.documentElement.classList.add('ah-ui-lock');

        document.body.classList.add('ah-ui-lock');

        ahUiAlertOverlay.classList.add('open');

        ahUiAlertOverlay.setAttribute('aria-hidden','false');

        setTimeout(() => ahUiAlertConfirm.focus(), 20);

      });

    }

    function ahCloseUiAlert(value){

      ahUiAlertOverlay.classList.remove('open');

      ahUiAlertOverlay.setAttribute('aria-hidden','true');

      document.documentElement.classList.remove('ah-ui-lock');

      document.body.classList.remove('ah-ui-lock');

      if(ahUiAlertResolver){

        const resolve = ahUiAlertResolver;

        ahUiAlertResolver = null;

        resolve(value);

      }

    }

    function ahShowUiAlert(options){

      return ahOpenUiAlert({...options, showCancel:false});

    }

    function ahShowUiConfirm(options){

      return ahOpenUiAlert({...options, showCancel:true, type:'error'});

    }

    ahUiAlertConfirm.addEventListener('click', () => ahCloseUiAlert(true));

    ahUiAlertCancel.addEventListener('click', () => ahCloseUiAlert(false));

    ahUiAlertOverlay.addEventListener('click', event => {

      if(event.target === ahUiAlertOverlay){

        ahCloseUiAlert(false);

      }

    });

    document.addEventListener('keydown', event => {

      if(event.key === 'Escape' && ahUiAlertOverlay.classList.contains('open')){

        ahCloseUiAlert(false);

      }

    });

    /*

    |--------------------------------------------------------------------------

    | KONFIRMASI HAPUS TANGGAL

    |--------------------------------------------------------------------------

    */

    window.confirmDeleteTanggal =

      async function(form){

        const input =

          document.getElementById(

            'ahTanggal'

          );

        if (!input || !input.value){

          await ahShowUiAlert({

            type:'error',

            title:'Tanggal Belum Dipilih',

            message:'Pilih tanggal terlebih dahulu.',

            confirmText:'OK'

          });

          return false;

        }

        const countText =

          document.getElementById(

            'ahTanggalDeleteCount'

          )?.textContent

          || '';

        if (

          countText.includes('0 data')

          ||

          countText.includes('Pilih tanggal terlebih dahulu')

        ){

          await ahShowUiAlert({

            type:'error',

            title:'Data Tidak Ditemukan',

            message:`Tidak ada data pada tanggal <strong>${ahEscapeUiHtml(input.value)}</strong>.`,

            confirmText:'OK'

          });

          return false;

        }

        const confirmed =

          await ahShowUiConfirm({

            title:'Hapus Data?',

            message:`Apakah kamu yakin ingin menghapus seluruh data pada tanggal <strong>${ahEscapeUiHtml(input.value)}</strong>? Data yang sudah dihapus tidak dapat dikembalikan.`,

            confirmText:'Hapus',

            cancelText:'Batal'

          });

        if (!confirmed){

          return false;

        }

        HTMLFormElement.prototype.submit.call(form);

        return false;

      };

    /*

    |--------------------------------------------------------------------------

    | KONFIRMASI HAPUS TAHUN

    |--------------------------------------------------------------------------

    */

    /*

    |--------------------------------------------------------------------------

    | KONFIRMASI HAPUS TAHUN

    |--------------------------------------------------------------------------

    */

    window.confirmDeleteYear =

      async function(form){

        const select =

          document.getElementById(

            'ahTahun'

          );

        const year =

          select?.value

          || '';

        const count =

          Number(

            yearCounts[year]

            || 0

          );

        if (count <= 0){

          await ahShowUiAlert({

            type:'error',

            title:'Data Tidak Ditemukan',

            message:`Tidak ada data pada tahun <strong>${ahEscapeUiHtml(year)}</strong>.`,

            confirmText:'OK'

          });

          return false;

        }

        const confirmed =

          await ahShowUiConfirm({

            title:'Hapus Data?',

            message:`Apakah kamu yakin ingin menghapus <strong>${count.toLocaleString('id-ID')} data</strong> pada tahun <strong>${ahEscapeUiHtml(year)}</strong> untuk <strong>{{ addslashes($witel) }}</strong>? Data yang sudah dihapus tidak dapat dikembalikan.`,

            confirmText:'Hapus',

            cancelText:'Batal'

          });

        if (!confirmed){

          return false;

        }

        HTMLFormElement.prototype.submit.call(form);

        return false;

      };

    /*

    |--------------------------------------------------------------------------

    | KONFIRMASI HAPUS RIWAYAT

    |--------------------------------------------------------------------------

    | Menggunakan modal konfirmasi yang sama dengan Hapus Per Tahun/Tanggal.
    | Hanya submit form setelah pengguna memilih Hapus.

    */

    window.confirmDeleteHistory =

      async function(form){

        const count = Number(@json($adhHistoryTotal) || 0);

        const witelName = @json($witel);

        if (count <= 0){

          await ahShowUiAlert({

            type:'error',

            title:'Riwayat Kosong',

            message:'Tidak ada riwayat penghapusan yang bisa dihapus untuk Witel ini.',

            confirmText:'OK'

          });

          return;

        }

        const confirmed =

          await ahShowUiConfirm({

            title:'Hapus Riwayat?',

            message:`Apakah kamu yakin ingin menghapus seluruh <strong>${count.toLocaleString('id-ID')} riwayat penghapusan</strong> untuk <strong>${ahEscapeUiHtml(witelName)}</strong>? Data Fallout asli tidak akan dihapus. Tindakan ini tidak dapat dibatalkan.`,

            confirmText:'Hapus Riwayat',

            cancelText:'Batal'

          });

        if (!confirmed){

          return;

        }

        HTMLFormElement.prototype.submit.call(form);

      };

    /*

    |--------------------------------------------------------------------------

    | INIT

    |--------------------------------------------------------------------------

    */

    ahUpdateYearPreview();

    /*

    |--------------------------------------------------------------------------

    | HASIL HAPUS DARI SERVER

    |--------------------------------------------------------------------------

    */

    const ahServerSuccess =

      @json(session('status'));

    const ahServerErrors =

      @json($errors->all());

    if (ahServerSuccess){

      ahShowUiAlert({

        type:'success',

        title:'Berhasil',

        message:ahEscapeUiHtml(ahServerSuccess),

        confirmText:'OK'

      });

    } else if (

      Array.isArray(ahServerErrors)

      &&

      ahServerErrors.length

    ){

      ahShowUiAlert({

        type:'error',

        title:'Gagal',

        message:ahServerErrors

          .map(error => ahEscapeUiHtml(error))

          .join('<br>'),

        confirmText:'OK'

      });

    }

  })();

</script>


<script>
(function(){
    'use strict';
    var root = document.getElementById('adhDeletedHistory');
    if (!root || root.dataset.adhInitialized === '1') return;
    root.dataset.adhInitialized = '1';
    var overlay = document.getElementById('adhDetailOverlay');
    var closeButton = document.getElementById('adhDetailClose');
    function setText(id, value){
        var element = document.getElementById(id);
        if (element) element.textContent = value && String(value).trim() ? value : '-';
    }
    function openDetail(button){
        if (!overlay) return;
        setText('adhDetailSubId', button.dataset.falloutId);
        setText('adhDetailFalloutId', button.dataset.falloutId);
        setText('adhDetailSto', button.dataset.sto);
        setText('adhDetailTanggal', button.dataset.tanggal);
        setText('adhDetailPic', button.dataset.pic);
        setText('adhDetailResolution', button.dataset.resolution);
        setText('adhDetailStatus', button.dataset.status);
        setText('adhDetailDeskripsi', button.dataset.deskripsi);
        setText('adhDetailKeterangan', button.dataset.keterangan);
        setText('adhDetailDeletedBy', button.dataset.deletedBy);
        setText('adhDetailDeletedAt', button.dataset.deletedAt);
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden','false');
        document.documentElement.classList.add('adh-history-modal-lock');
        document.body.classList.add('adh-history-modal-lock');
        if (closeButton) closeButton.focus();
    }
    function closeDetail(){
        if (!overlay) return;
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden','true');
        document.documentElement.classList.remove('adh-history-modal-lock');
        document.body.classList.remove('adh-history-modal-lock');
    }
    root.addEventListener('click', function(event){
        var button = event.target.closest('[data-adh-detail]');
        if (button) openDetail(button);
    });
    if (closeButton) closeButton.addEventListener('click', closeDetail);
    if (overlay) overlay.addEventListener('click', function(event){ if(event.target === overlay) closeDetail(); });
    document.addEventListener('keydown', function(event){
        if (event.key === 'Escape' && overlay && overlay.classList.contains('is-open')) closeDetail();
    });
})();
</script>

