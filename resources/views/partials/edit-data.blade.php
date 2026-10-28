@php

  // ── DIHUBUNGKAN KE DATABASE (tabel fallout_data via model FalloutData) ──

  $dbRows = \App\Models\FalloutData::forWitel($witelSlug)

      ->orderByDesc('tanggal')

      ->orderByDesc('row_id')

      ->get();

  // Normalisasi STO agar filter mengikuti nilai bersih dari Witel aktif.
  $normalizeSto = static function ($value): string {
      if ($value === null) {
          return '';
      }

      $value = trim((string) $value);
      if ($value === '') {
          return '';
      }

      // Data lama kadang menyimpan STO sebagai JSON, misalnya {"STO":"KBY"}.
      $decoded = json_decode($value, true);
      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
          $candidate = $decoded['STO'] ?? $decoded['sto'] ?? null;
          if (is_scalar($candidate)) {
              $candidate = trim((string) $candidate);
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
                  if (is_scalar($candidate)) {
                      $candidate = trim((string) $candidate);
                      if ($candidate !== '') {
                          return strtoupper($candidate);
                      }
                  }
              }
          }
      }

      $unescaped = str_replace('\\"', '"', $value);
      if (preg_match('/"(?:STO|sto)"\s*:\s*"([^"]+)"/i', $unescaped, $match)) {
          return strtoupper(trim($match[1]));
      }

      return strtoupper($value);
  };

  // Normalisasi status agar huruf besar/kecil dan separator konsisten.
  $normalizeStatusKey = static function ($value): string {
      $value = strtolower(trim((string) ($value ?? '')));
      $value = preg_replace('/[\s\/-]+/u', '_', $value) ?? $value;
      return preg_replace('/_+/', '_', $value) ?? $value;
  };

  // Dipetakan ke bentuk array untuk tabel & modal.
  $rows = $dbRows->values()->map(function ($r, $i) use ($normalizeSto, $normalizeStatusKey) {
      $resolvedEskalasi = $normalizeStatusKey($r->resolved_eskalasi ?? '');
      $statusValue = $normalizeStatusKey($r->status ?? '');
      $knownStatuses = ['resolved', 'eskalasi', 'cancel', 'eskalasi_dit', 'close', 'pending'];

      // Utamakan kategori utama; gunakan kolom status sebagai fallback.
      if (in_array($resolvedEskalasi, $knownStatuses, true)) {
          $statusRe = $resolvedEskalasi;
      } elseif (in_array($statusValue, $knownStatuses, true)) {
          $statusRe = $statusValue;
      } elseif ($resolvedEskalasi !== '') {
          $statusRe = 'lainnya';
      } else {
          $statusRe = 'lainnya';
      }

      return [

          'no'        => $i + 1,

          'row_id'    => $r->row_id,

          'order_id'  => $r->order_id,

          'deskripsi' => $r->deskripsi,

          'sto'       => $normalizeSto($r->sto),

          'tgl'       => $r->tanggal ? $r->tanggal->format('Y-m-d') : '',

          'pic'       => $r->pic,

          'status_re' => $statusRe,

          'status'    => $r->status,

          'ket'       => $r->ket,

          'tiket_eskalasi' => $r->tiket_eskalasi ?? null,

      ];

  })->all();

  $availableStatusFilters = collect($rows)->pluck('status_re')->filter()->unique()->values();

  // Total keseluruhan sistem (semua witel).

  $totalData = \App\Models\FalloutData::count();

  // Dropdown STO filter dan modal memakai nilai unik dari data Witel ini.
  $stoOptions = collect($rows)
      ->pluck('sto')
      ->map(fn ($sto) => strtoupper(trim((string) $sto)))
      ->filter(fn ($sto) => $sto !== '')
      ->unique()
      ->sort()
      ->values();

  $statusReLabels = [

    'resolved'     => 'RESOLVED',

    'eskalasi'     => 'ESKALASI',

    'cancel'       => 'CANCEL',

    'eskalasi_dit' => 'ESKALASI DIT',

    'close'        => 'CLOSE',

    'pending'      => 'PENDING',

    'lainnya'      => 'ESKALASI',

  ];

  $statusLabels = [

    'completed'   => 'COMPLETED',

    'process_oss' => 'Process OSS',

  ];

  $edWitelLabel = ['jaktim' => 'Jakarta Timur', 'jakpus' => 'Jakarta Pusat', 'jaksel' => 'Jakarta Selatan'][$witelSlug] ?? ucfirst((string) $witelSlug);

@endphp

<div class="ed-wrap">

  {{-- LOADING KHUSUS HALAMAN EDIT DATA --}}

  <div class="ed-page-loading" id="edPageLoading" aria-live="polite" aria-label="Memuat Edit Data">

    <div class="ed-page-loading-card">

      <div class="ed-page-loading-logo">

        <span>TF</span>

      </div>

      <div class="ed-page-loading-spinner" aria-hidden="true"></div>

      <div class="ed-page-loading-title">Memuat Edit Data</div>

      <div class="ed-page-loading-subtitle">Menyiapkan data {{ $witel }}</div>

    </div>

  </div>

  <div class="ed-head">

    <div>

      <h2>Edit Data</h2>

      <p>Menampilkan {{ count($rows) }} dari {{ $totalData }} total data — {{ $witel }}</p>

    </div>

    <button type="button" class="ed-add-btn" id="edAddBtn">

      + Tambah Data

    </button>

  </div>

  <div class="ed-panel">

    <div class="ed-filter-row">

      <div class="ed-field">

        <label for="edFilterTanggal">Tanggal:</label>

        {{-- KOSONG = semua tanggal tampil saat pertama kali buka halaman --}}

        <input type="date" id="edFilterTanggal" value="">

      </div>

      <select class="ed-sto-select" id="edFilterSto" aria-label="Filter STO">

        <option value="semua">Semua STO</option>

        @foreach ($stoOptions as $sto)

          <option value="{{ $sto }}">{{ $sto }}</option>

        @endforeach

      </select>

    </div>

    <div class="ed-status-row" id="edStatusRow">

      <button type="button"

              class="ed-status-btn active"

              data-filter="semua">

        Semua

      </button>

      <button type="button"

              class="ed-status-btn ed-status-resolved"

              data-filter="resolved">

        RESOLVED

      </button>

      <button type="button"

              class="ed-status-btn ed-status-cancel"

              data-filter="cancel">

        Cancel

      </button>

      <button type="button"

              class="ed-status-btn ed-status-eskalasidit"

              data-filter="eskalasi_dit">

        Eskalasi DIT

      </button>

      <button type="button"

              class="ed-status-btn ed-status-close"

              data-filter="close">

        Close

      </button>

      @if ($availableStatusFilters->contains('eskalasi'))
        <button type="button" class="ed-status-btn ed-status-eskalasi" data-filter="eskalasi">ESKALASI</button>
      @endif

      @if ($availableStatusFilters->contains('pending'))
        <button type="button" class="ed-status-btn ed-status-pending" data-filter="pending">PENDING</button>
      @endif

    </div>

    <div class="ed-meta" id="edMeta">

      Semua tanggal · {{ count($rows) }} data ditemukan

    </div>

  </div>

  <div class="ed-table-wrap">

    <table class="ed-table" id="edTable">

      <thead>

        <tr>

          <th>No</th>

          <th>No/Order ID</th>

          <th>STO</th>

          <th>Tanggal</th>

          <th>PIC</th>

          <th>Status</th>

          <th>Tiket Eskalasi</th>

          <th>KET</th>

          <th>Aksi</th>

        </tr>

      </thead>

      <tbody id="edTableBody">

        @forelse ($rows as $r)

          <tr

            data-id="{{ $r['row_id'] }}"

            data-status="{{ $r['status_re'] }}"

            data-order_id="{{ $r['order_id'] }}"

            data-deskripsi="{{ $r['deskripsi'] }}"

            data-sto="{{ $r['sto'] }}"

            data-tgl="{{ $r['tgl'] }}"

            data-pic="{{ $r['pic'] }}"

            data-status_re="{{ $r['status_re'] }}"

            data-status_detail="{{ $r['status'] }}"

            data-ket="{{ $r['ket'] }}"

            data-ticket_eskalasi="{{ $r['tiket_eskalasi'] }}"

          >

            <td class="ed-no">

              {{ $r['no'] }}

            </td>

            <td class="ed-order-id">
              <div class="ed-order-id-content">
                <span class="ed-cell-ellipsis" title="{{ $r['order_id'] ?: '-' }}">{{ $r['order_id'] ?: '-' }}</span>
                @if (!empty($r['order_id']))
                  <button
                    type="button"
                    class="ed-icon-btn ed-order-id-preview-btn"
                    title="Lihat No/Order ID lengkap"
                    aria-label="Lihat No/Order ID lengkap"
                  >
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M2.5 12s3.25-6 9.5-6 9.5 6 9.5 6-3.25 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                      <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.7"/>
                    </svg>
                  </button>
                @endif
              </div>
            </td>
            <td class="ed-sto-cell">
              <span class="ed-cell-ellipsis" title="{{ $r['sto'] ?: '-' }}">{{ $r['sto'] ?: '-' }}</span>
            </td>

            <td>

              {{

                $r['tgl']

                  ? \Carbon\Carbon::parse($r['tgl'])->translatedFormat('d F Y')

                  : '-'

              }}

            </td>

            <td>

              {{ $r['pic'] }}

            </td>

            <td>

              <span class="ed-pill ed-pill-{{ $r['status_re'] }}">

                {{ $statusReLabels[$r['status_re']] ?? strtoupper($r['status_re']) }}

              </span>

            </td>

            <td>

              {{ $r['tiket_eskalasi'] ?: '-' }}

            </td>

            <td>

              {{ $r['ket'] }}

            </td>

            <td class="ed-aksi">

              <button

                type="button"

                class="ed-icon-btn ed-edit-btn"

                title="Edit"

              >

                ✏️

              </button>

              <button

                type="button"

                class="ed-icon-btn ed-delete-btn"

                title="Hapus"

              >

                🗑️

              </button>

            </td>

          </tr>

        @empty

          <tr>

            <td colspan="9" class="ed-empty">

              Belum ada data untuk witel ini.

              Coba upload data dulu lewat menu "Upload Data".

            </td>

          </tr>

        @endforelse

          @if (count($rows) > 0)
            <tr id="edNoMatchRow" style="display:none">
              <td colspan="9" class="ed-empty">Tidak ada data yang cocok dengan filter ini.</td>
            </tr>
          @endif

            </tbody>

    </table>

  </div>

</div>

{{-- ============================================================

     MODAL PREVIEW NO/ORDER ID LENGKAP

============================================================ --}}
<div class="ed-order-id-preview-overlay" id="edOrderIdPreviewOverlay" aria-hidden="true">
  <div class="ed-order-id-preview-modal" role="dialog" aria-modal="true" aria-labelledby="edOrderIdPreviewTitle">
    <div class="ed-order-id-preview-head">
      <h3 id="edOrderIdPreviewTitle">No/Order ID Lengkap</h3>
      <button type="button" class="ed-order-id-preview-close" id="edOrderIdPreviewClose" aria-label="Tutup preview">&times;</button>
    </div>
    <div class="ed-order-id-preview-body">
      <code id="edOrderIdPreviewValue">-</code>
    </div>
    <div class="ed-order-id-preview-footer">
      <button type="button" class="ed-order-id-copy-btn" id="edOrderIdCopyButton">Salin ID</button>
      <button type="button" class="ed-order-id-preview-done" id="edOrderIdPreviewDone">Tutup</button>
    </div>
  </div>
</div>

{{-- ============================================================

     MODAL TAMBAH / EDIT

============================================================ --}}

<div class="ed-modal-overlay" id="edModalOverlay">

  <div class="ed-modal">

    <div class="ed-modal-head">
      <div class="ed-modal-heading">
        <h3 id="edModalTitle">Tambah Data Fallout</h3>
        <p class="ed-modal-subtitle">Cabang {{ $edWitelLabel }}</p>
      </div>
      <button

        type="button"

        class="ed-modal-close"

        id="edModalClose"

      >

        ✕

      </button>

    </div>

    <form id="edForm" class="ed-modal-body">

      {{-- ORDER ID --}}

      <div class="ed-form-group">

        <label for="edOrderId">No/Order ID <span class="ed-required">*</span></label>

        <input

          type="text"

          id="edOrderId"

          placeholder="Masukkan ID"

         required>

      </div>

      {{-- DESKRIPSI --}}

      <div class="ed-form-group">

        <label for="edDeskripsi">

          Deskripsi

        </label>

        <textarea

          id="edDeskripsi"

          rows="3"

          placeholder="Masukkan deskripsi"

        ></textarea>

      </div>

      {{-- STO + TANGGAL --}}

      <div class="ed-form-row">

        <div class="ed-form-group">

          <label for="edSto">

            STO

          </label>

          <select id="edSto">

            <option value="">

              Pilih STO...

            </option>

            @foreach ($stoOptions as $sto)

              <option value="{{ $sto }}">

                {{ $sto }}

              </option>

            @endforeach

            <option value="lainnya">

              Lainnya...

            </option>

          </select>

        </div>

        <div class="ed-form-group">

          <label for="edTanggal">Tanggal Fallout <span class="ed-required">*</span></label>

          <input

            type="date"

            id="edTanggal"

           required>

        </div>

      </div>

      {{-- PIC + RESOLVED/ESKALASI --}}

      <div class="ed-form-row">

        <div class="ed-form-group">

          <label for="edPic">PIC <span class="ed-required">*</span></label>

          <input

            type="text"

            id="edPic"

            placeholder="Nama PIC"

           required>

        </div>

        <div class="ed-form-group">

          <label for="edStatusRe">RESOLVED / ESKALASI <span class="ed-required">*</span></label>

          <select id="edStatusRe" required>

            <option value="">

              Pilih...

            </option>

            <option value="resolved">

              RESOLVED

            </option>

            <option value="eskalasi">ESKALASI</option>

            <option value="pending">PENDING</option>

            <option value="cancel">

              CANCEL

            </option>

            <option value="eskalasi_dit">

              ESKALASI DIT

            </option>

            <option value="close">

              CLOSE

            </option>

            <option value="lainnya">

              ESKALASI / LAINNYA

            </option>

          </select>

        </div>

      </div>

      {{-- STATUS + KET --}}

      <div class="ed-form-row">

        <div class="ed-form-group">

          <label for="edStatusDetail">Status <span class="ed-required">*</span></label>

          <select id="edStatusDetail" required>

            <option value="">

              Pilih...

            </option>

            <option value="completed">

              COMPLETED

            </option>

            <option value="process_oss">

              Process OSS

            </option>

          </select>

        </div>

        <div class="ed-form-group">

          <label for="edKet">

            KET

          </label>

          <input

            type="text"

            id="edKet"

            placeholder="Keterangan"

          >

        </div>

      </div>

      {{-- TIKET ESKALASI --}}
      <div class="ed-form-group">
        <label for="edTiketEskalasi">Tiket Eskalasi</label>
        <input type="text" id="edTiketEskalasi" placeholder="Nomor tiket eskalasi (opsional)" maxlength="255">
      </div>

    </form>

    <div class="ed-modal-footer">

      <button

        type="button"

        class="ed-btn-cancel"

        id="edBtnCancel"

      >

        Batal

      </button>

      <button

        type="button"

        class="ed-btn-save"

        id="edBtnSave"

      >

        💾 Simpan

      </button>

    </div>

  </div>

</div>

{{-- UI ALERT / CONFIRM --}}

<div class="ed-ui-alert-overlay" id="edUiAlertOverlay" aria-hidden="true">

  <div class="ed-ui-alert-card" role="dialog" aria-modal="true" aria-labelledby="edUiAlertTitle">

    <div class="ed-ui-alert-icon" id="edUiAlertIcon"></div>

    <h3 id="edUiAlertTitle">Informasi</h3>

    <div class="ed-ui-alert-message" id="edUiAlertMessage"></div>

    <div class="ed-ui-alert-actions" id="edUiAlertActions">

      <button type="button" class="ed-ui-alert-btn ed-ui-alert-cancel" id="edUiAlertCancel">Batal</button>

      <button type="button" class="ed-ui-alert-btn ed-ui-alert-confirm" id="edUiAlertConfirm">OK</button>

    </div>

  </div>

</div>

<style>

  /* ============================================================

     LOADING KHUSUS HALAMAN EDIT DATA

     Tidak menutupi Dashboard / sidebar.

     ============================================================ */

  .ed-wrap{

    position:relative;

    min-height:100%;

  }

  .ed-page-loading{

    position:absolute;

    inset:0;

    z-index:1200;

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

  .ed-page-loading.is-hidden{

    opacity:0;

    visibility:hidden;

    pointer-events:none;

  }

  .ed-page-loading-card{

    width:min(320px, 100%);

    padding:28px 24px 24px;

    text-align:center;

    border:1px solid rgba(255,255,255,.9);

    border-radius:18px;

    background:rgba(255,255,255,.88);

    box-shadow:0 24px 60px -30px rgba(58,4,16,.30);

  }

  .ed-page-loading-logo{

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

  .ed-page-loading-spinner{

    width:30px;

    height:30px;

    margin:0 auto 14px;

    border:3px solid rgba(200,16,46,.14);

    border-top-color:#C8102E;

    border-right-color:#8A0F26;

    border-radius:50%;

    animation:edPageLoadingSpin .8s linear infinite;

  }

  .ed-page-loading-title{

    font-family:'Space Grotesk',sans-serif;

    font-size:15px;

    font-weight:700;

    color:#20161A;

  }

  .ed-page-loading-subtitle{

    margin-top:6px;

    font-family:'Inter',sans-serif;

    font-size:11.5px;

    color:#7A6B6F;

  }

  @keyframes edPageLoadingSpin{

    to{ transform:rotate(360deg); }

  }

  .ed-wrap{

    width:100%;

    padding:6px 4px 16px;

  }

  .ed-head{

    display:flex;

    align-items:flex-start;

    justify-content:space-between;

    gap:14px;

    flex-wrap:wrap;

  }

  .ed-head h2{

    font-family:'Space Grotesk', sans-serif;

    font-size:22px;

    font-weight:700;

    color:#C8102E;

  }

  .ed-head p{

    margin-top:4px;

    font-size:12.5px;

    color:#7A6B6F;

  }

  .ed-add-btn{

    border:none;

    cursor:pointer;

    padding:11px 20px;

    border-radius:11px;

    font-size:13px;

    font-weight:700;

    color:#fff;

    font-family:'Inter', sans-serif;

    background:linear-gradient(120deg, #C8102E, #8A0F26);

    box-shadow:0 12px 26px -12px rgba(200,16,46,0.7);

    white-space:nowrap;

  }

  .ed-panel{

    background:rgba(255,255,255,0.7);

    border:1px solid rgba(255,255,255,0.8);

    border-radius:16px;

    padding:14px 16px 12px;

    margin-top:12px;

    margin-bottom:8px;

  }

  .ed-filter-row{

    display:flex;

    align-items:center;

    gap:10px;

    margin-bottom:10px;

    flex-wrap:wrap;

  }

  .ed-field{

    display:flex;

    align-items:center;

    gap:8px;

  }

  .ed-field label{

    font-size:13px;

    font-weight:600;

    color:#20161A;

  }

  .ed-field input[type="date"]{

    padding:9px 12px;

    border-radius:9px;

    border:1.3px solid #E7DEDD;

    background:#fff;

    font-size:13px;

    color:#20161A;

    font-family:'Inter', sans-serif;

  }

  .ed-sto-select{

    padding:9px 14px;

    border-radius:9px;

    border:1.3px solid #E7DEDD;

    background:#fff;

    font-size:13px;

    color:#20161A;

    font-family:'Inter', sans-serif;

    min-width:140px;

  }

  .ed-status-row{

    display:flex;

    gap:6px;

    flex-wrap:wrap;

    margin-bottom:8px;

  }

  .ed-status-btn{

    border:1.3px solid #E7DEDD;

    background:#fff;

    cursor:pointer;

    padding:8px 16px;

    border-radius:9px;

    font-size:12px;

    font-weight:700;

    letter-spacing:0.02em;

    color:#7A6B6F;

    font-family:'Inter', sans-serif;

  }

  .ed-status-btn.active{

    background:#3A0410;

    color:#fff;

    border-color:#3A0410;

  }

  .ed-status-btn.ed-status-resolved.active{

    background:#1E7A46;

    border-color:#1E7A46;

  }

  .ed-status-btn.ed-status-cancel.active{

    background:#7A6B6F;

    border-color:#7A6B6F;

  }

  .ed-status-btn.ed-status-eskalasidit.active{

    background:#B4651E;

    border-color:#B4651E;

  }

  .ed-status-btn.ed-status-close.active{

    background:#3A0410;

    border-color:#3A0410;

  }

  .ed-meta{

    font-size:11.5px;

    color:#7A6B6F;

  }

  .ed-table-wrap{

    background:rgba(255,255,255,0.7);

    border:1px solid rgba(255,255,255,0.8);

    border-radius:16px;

    overflow-x:auto;

    max-height:460px;

    overflow-y:auto;

  }

  .ed-table{
    width:100%;
    min-width:780px;
    table-layout:fixed;
    border-collapse:collapse;
    font-size:12px;
  }

  /* 9 kolom; lebar total 100% agar Tiket Eskalasi dan Aksi sama-sama terlihat. */
  .ed-table thead th:nth-child(1){width:4%}   /* No */
  .ed-table thead th:nth-child(2){width:18%}  /* No/Order ID + tombol preview */
  .ed-table thead th:nth-child(3){width:7%}   /* STO */
  .ed-table thead th:nth-child(4){width:12%}  /* Tanggal */
  .ed-table thead th:nth-child(5){width:9%}   /* PIC */
  .ed-table thead th:nth-child(6){width:12%}  /* Status */
  .ed-table thead th:nth-child(7){width:14%}  /* Tiket Eskalasi */
  .ed-table thead th:nth-child(8){width:10%}  /* KET */
  .ed-table thead th:nth-child(9){width:14%}  /* Aksi */

  .ed-table thead th{

    position:sticky;

    top:0;

    z-index:2;

    background:#5C0A1B;

    text-align:left;

    padding:10px 12px;

    font-size:11px;

    font-weight:700;

    color:#fff;

    white-space:nowrap;

  }

  .ed-table tbody td{
    padding:9px 10px;
    border-bottom:1px solid #F1EAE9;
    color:#20161A;
    vertical-align:middle;
    overflow:hidden;
  }

  .ed-cell-ellipsis{
    display:block;
    max-width:100%;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
  }

  .ed-order-id,
  .ed-sto-cell{
    min-width:0;
    overflow:hidden;
  }

  .ed-order-id .ed-cell-ellipsis{font-weight:700;color:#7A6B6F}
  .ed-sto-cell .ed-cell-ellipsis{font-weight:700;color:#65565C}

  .ed-order-id-content{
    display:flex;
    align-items:center;
    gap:5px;
    width:100%;
    min-width:0;
    overflow:hidden;
  }

  .ed-order-id-content .ed-cell-ellipsis{
    flex:1 1 auto;
    width:1px;
    min-width:0;
  }

  .ed-order-id-preview-btn{
    flex:0 0 25px;
    width:25px;
    min-width:25px;
    height:25px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:3px;
    color:#A30B22;
    border-radius:6px;
  }

  .ed-order-id-preview-btn svg{
    width:15px;
    height:15px;
    display:block;
  }

  .ed-table tbody tr:hover{

    background:rgba(200,16,46,0.04);

  }

  .ed-no{

    color:#C8102E;

    font-weight:700;

  }

  .ed-order-id{

    color:#7A6B6F;

    white-space:nowrap;

  }

  .ed-pill{

    display:inline-block;

    padding:4px 10px;

    border-radius:999px;

    font-size:10.5px;

    font-weight:700;

    letter-spacing:0.02em;

    background:#F1EAE9;

    color:#7A6B6F;

  }

  .ed-pill-resolved{

    background:rgba(30,122,70,0.12);

    color:#1E7A46;

  }

  .ed-pill-cancel{

    background:rgba(122,107,111,0.14);

    color:#7A6B6F;

  }

  .ed-pill-eskalasi_dit{

    background:rgba(180,101,30,0.14);

    color:#B4651E;

  }

  .ed-pill-close{

    background:rgba(58,4,16,0.1);

    color:#3A0410;

  }

  .ed-pill-lainnya{

    background:rgba(180,101,30,0.14);

    color:#B4651E;

  }

  .ed-aksi{

    white-space:nowrap;

  }

  .ed-icon-btn{

    border:none;

    background:none;

    cursor:pointer;

    font-size:14px;

    padding:4px 6px;

    border-radius:6px;

  }

  .ed-icon-btn:hover{

    background:rgba(200,16,46,0.08);

  }

  .ed-empty{

    text-align:center;

    padding:30px;

    color:#7A6B6F;

  }

  /* ============================================================

     MODAL

  ============================================================ */

  .ed-modal-overlay{

    display:none;

    position:fixed;

    inset:0;

    z-index:9999;

    background:rgba(32,22,26,0.55);

    align-items:center;

    justify-content:center;

    padding:20px;

  }

  .ed-modal-overlay.open{

    display:flex;

  }

  .ed-modal{

    width:100%;

    max-width:768px;

    background:#fff;

    border-radius:22px;

    overflow:hidden;

    max-height:90vh;

    display:flex;

    flex-direction:column;

    box-shadow:0 40px 90px -30px rgba(0,0,0,0.4);

  }

  .ed-modal-head{

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:18px 24px;

    background:#fff;
    border-bottom:1px solid #eee7e7;

  }

  .ed-modal-head h3{
    margin:0;
    font-family:'Space Grotesk', sans-serif;
    font-size:19px;
    font-weight:700;
    color:#20161A;
  }

  .ed-modal-subtitle{margin:4px 0 0;color:#64748b;font-size:12px;line-height:1.4;}

  .ed-modal-close{

    border:none;

    background:transparent;

    color:#94a3b8;

    width:28px;

    height:28px;

    border-radius:8px;

    cursor:pointer;

    font-size:13px;

  }

  .ed-modal-body{

    padding:22px 24px;

    overflow-y:auto;

  }

  .ed-form-group{

    margin-bottom:16px;

  }

  .ed-form-row{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:14px;

  }

  .ed-form-group label{

    display:block;

    font-size:12.5px;

    font-weight:700;

    color:#20161A;

    margin-bottom:6px;

  }

  .ed-form-group input,

  .ed-form-group select,

  .ed-form-group textarea{

    width:100%;

    padding:10px 13px;

    border-radius:9px;

    border:1.3px solid #E7DEDD;

    background:#FBF9F7;

    font-size:13px;

    color:#20161A;

    font-family:'Inter', sans-serif;

    resize:vertical;

    box-sizing:border-box;

  }

  .ed-form-group input:focus,

  .ed-form-group select:focus,

  .ed-form-group textarea:focus{

    outline:none;

    border-color:#C8102E;

  }

  .ed-modal-footer{

    display:flex;

    justify-content:flex-end;

    gap:10px;

    padding:16px 24px;

    border-top:1px solid #E7DEDD;

  }

  .ed-btn-cancel{

    border:1.3px solid #E7DEDD;

    background:#fff;

    cursor:pointer;

    padding:10px 18px;

    border-radius:10px;

    font-size:13px;

    font-weight:700;

    color:#7A6B6F;

    font-family:'Inter', sans-serif;

  }

  .ed-btn-save{

    border:none;

    cursor:pointer;

    padding:10px 20px;

    border-radius:10px;

    font-size:13px;

    font-weight:700;

    color:#fff;

    font-family:'Inter', sans-serif;

    background:linear-gradient(120deg, #C8102E, #8A0F26);

  }

  .ed-btn-save:disabled{

    opacity:.65;

    cursor:not-allowed;

  }

  @media (max-width: 640px){

    .ed-form-row{

      grid-template-columns:1fr;

    }

  }

  /* UI ALERT / CONFIRM */

  html.ed-ui-lock,

  body.ed-ui-lock{

    overflow:hidden !important;

  }

  .ed-order-id-preview-overlay{
    display:none;
    position:fixed;
    inset:0;
    z-index:2147482000;
    align-items:center;
    justify-content:center;
    padding:18px;
    box-sizing:border-box;
    background:rgba(25,12,16,.55);
    backdrop-filter:blur(3px);
  }

  .ed-order-id-preview-overlay.open{display:flex}

  .ed-order-id-preview-modal{
    width:min(620px, calc(100vw - 36px));
    max-height:calc(100vh - 36px);
    overflow:hidden;
    border:1px solid #eee5e7;
    border-radius:16px;
    background:#fff;
    box-shadow:0 28px 80px rgba(20,0,5,.25);
  }

  .ed-order-id-preview-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:17px 20px;
    border-bottom:1px solid #f0e7e9;
  }

  .ed-order-id-preview-head h3{
    margin:0;
    color:#3A0410;
    font-size:15px;
    font-weight:700;
  }

  .ed-order-id-preview-close{
    width:30px;
    height:30px;
    border:0;
    border-radius:8px;
    background:#f8eff1;
    color:#8A0F26;
    font-size:22px;
    line-height:1;
    cursor:pointer;
  }

  .ed-order-id-preview-body{
    padding:20px;
    max-height:45vh;
    overflow:auto;
  }

  .ed-order-id-preview-body code{
    display:block;
    width:100%;
    box-sizing:border-box;
    padding:14px;
    border:1px solid #eee3e6;
    border-radius:10px;
    background:#fbf8f8;
    color:#31252a;
    font:600 13px/1.7 Consolas,Monaco,monospace;
    white-space:pre-wrap;
    overflow-wrap:anywhere;
    user-select:text;
  }

  .ed-order-id-preview-footer{
    display:flex;
    justify-content:flex-end;
    gap:9px;
    padding:14px 20px;
    border-top:1px solid #f0e7e9;
  }

  .ed-order-id-copy-btn,
  .ed-order-id-preview-done{
    border-radius:9px;
    padding:9px 15px;
    font-size:12px;
    font-weight:700;
    cursor:pointer;
  }

  .ed-order-id-copy-btn{
    border:1px solid #e8dadd;
    background:#fff;
    color:#7c1025;
  }

  .ed-order-id-preview-done{
    border:1px solid #650014;
    background:#650014;
    color:#fff;
  }

  .ed-ui-alert-overlay{

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

  .ed-ui-alert-overlay.open{

    display:flex;

    animation:edUiAlertFadeIn .18s ease;

  }

  .ed-ui-alert-card{

    width:min(440px, calc(100vw - 32px));

    max-width:440px;

    max-height:calc(100vh - 32px);

    overflow:hidden;

    background:#fff;

    border-radius:22px;

    padding:24px 22px 20px;

    box-shadow:0 34px 90px rgba(0,0,0,.30);

    text-align:center;

    animation:edUiAlertPop .18s ease;

    box-sizing:border-box;

  }

  .ed-ui-alert-icon{

    width:54px;height:54px;margin:0 auto 14px;border-radius:50%;display:flex;align-items:center;justify-content:center;

    background:rgba(200,16,46,.08);color:#C8102E;

  }

  .ed-ui-alert-icon.success{background:rgba(30,122,70,.08);color:#16A34A;}

  .ed-ui-alert-icon.error{background:rgba(200,16,46,.08);color:#C8102E;}

  .ed-ui-alert-icon svg{width:27px;height:27px;display:block;stroke:currentColor;}

  .ed-ui-alert-card h3{margin:0 0 8px;font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:#252331;}

  .ed-ui-alert-message{

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

  .ed-ui-alert-message strong{color:#2C3442;font-weight:800;}

  .ed-ui-alert-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;}

  .ed-ui-alert-actions.single{grid-template-columns:1fr;}

  .ed-ui-alert-btn{height:42px;border-radius:11px;font-family:'Inter',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:.18s ease;}

  .ed-ui-alert-cancel{border:1px solid #E5E0E1;background:#fff;color:#6F7177;}

  .ed-ui-alert-cancel:hover{background:#F8F6F6;}

  .ed-ui-alert-confirm{border:1px solid #B40000;background:#B40000;color:#fff;box-shadow:0 10px 22px -14px rgba(180,0,0,.8);}

  .ed-ui-alert-confirm:hover{background:#980000;border-color:#980000;}

  .ed-ui-alert-confirm.success-btn{background:#650014;border-color:#650014;}

  @keyframes edUiAlertFadeIn{from{opacity:0}to{opacity:1}}

  @keyframes edUiAlertPop{from{opacity:0;transform:translateY(10px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}

  @media(max-width:520px){.ed-ui-alert-card{max-width:calc(100vw - 32px);padding:20px 18px 18px;}}

  /* ============================================================
     EDIT DATA LOADING — overlay penuh seperti Upload Data
     Aktif hanya ketika loader Edit Data sedang ditampilkan.
     ============================================================ */
  html.ed-page-loading-lock,
  body.ed-page-loading-lock{
    overflow:hidden !important;
    cursor:none !important;
  }

  html.ed-page-loading-lock *,
  body.ed-page-loading-lock *{
    cursor:none !important;
  }

  .content-shell.ed-page-loading-shell,
  .content.ed-page-loading-shell,
  .ed-wrap.ed-page-loading-shell{
    position:relative !important;
    isolation:isolate !important;
    overflow:hidden !important;
    overscroll-behavior:none !important;
  }

  .content-shell.ed-page-loading-shell > .content{
    overflow:hidden !important;
  }

  .content-shell.ed-page-loading-shell > .ed-page-loading,
  .content.ed-page-loading-shell > .ed-page-loading,
  .ed-wrap.ed-page-loading-shell > .ed-page-loading{
    position:absolute !important;
    inset:0 !important;
    width:100% !important;
    height:100% !important;
    min-height:100% !important;
    z-index:2147483647 !important;
    display:flex !important;
    align-items:center;
    justify-content:center;
    box-sizing:border-box;
    overflow:hidden;
    pointer-events:auto;
    touch-action:none;
    overscroll-behavior:none;
    background:rgba(250,247,247,.96) !important;
    backdrop-filter:blur(8px);
    -webkit-backdrop-filter:blur(8px);
    cursor:none !important;
  }

  .ed-page-loading,
  .ed-page-loading *{
    cursor:none !important;
  }

  .ed-page-loading.is-hidden{
    opacity:0 !important;
    visibility:hidden !important;
    pointer-events:none !important;
  }

  .ed-page-loading-card{
    position:sticky;
    top:calc(50vh - 130px);
    margin:0 auto;
    box-sizing:border-box;
    background:rgba(255,255,255,.9);
    cursor:none !important;
  }

  .ed-page-loading-logo,
  .ed-page-loading-title,
  .ed-page-loading-subtitle{
    cursor:none !important;
  }

</style>

<script>

  (function () {

    // ============================================================

    // LOADING KHUSUS EDIT DATA — FULL CONTENT, MENIRU UPLOAD DATA
    const edPageLoading = document.getElementById('edPageLoading');

    if (edPageLoading) {
      const pageWrap = edPageLoading.closest('.ed-wrap');
      const contentShell =
        (pageWrap && pageWrap.closest('.content-shell')) ||
        document.getElementById('contentShell');

      const target =
        contentShell ||
        (pageWrap && pageWrap.closest('.content')) ||
        pageWrap;

      if (target && edPageLoading.parentElement !== target) {
        target.insertBefore(edPageLoading, target.firstChild);
      }

      if (target) {
        target.classList.add('ed-page-loading-shell');
      }

      const root = document.documentElement;
      const body = document.body;
      root.classList.add('ed-page-loading-lock');
      body.classList.add('ed-page-loading-lock');

      let loaderFinished = false;
      let cleanupTimer = null;

      function stopPageMove(event) {
        event.preventDefault();
      }

      function stopScrollKeys(event) {
        const scrollKeys = [
          'ArrowUp', 'ArrowDown', 'PageUp', 'PageDown',
          'Home', 'End', ' '
        ];
        if (scrollKeys.includes(event.key)) {
          event.preventDefault();
        }
      }

      edPageLoading.addEventListener('wheel', stopPageMove, { passive: false });
      edPageLoading.addEventListener('touchmove', stopPageMove, { passive: false });
      document.addEventListener('keydown', stopScrollKeys, true);

      function hideEditDataLoader() {
        if (loaderFinished) return;
        loaderFinished = true;

        edPageLoading.classList.add('is-hidden');

        if (cleanupTimer) {
          window.clearTimeout(cleanupTimer);
        }

        cleanupTimer = window.setTimeout(function () {
          edPageLoading.removeEventListener('wheel', stopPageMove);
          edPageLoading.removeEventListener('touchmove', stopPageMove);
          document.removeEventListener('keydown', stopScrollKeys, true);

          root.classList.remove('ed-page-loading-lock');
          body.classList.remove('ed-page-loading-lock');

          if (target) {
            target.classList.remove('ed-page-loading-shell');
          }

          if (edPageLoading.parentNode) {
            edPageLoading.parentNode.removeChild(edPageLoading);
          }
        }, 300);
      }

      /* Sama seperti Upload Data: tunggu load bila dokumen belum selesai,
         tampilkan overlay singkat, dan tetap punya timeout pengaman. */
      function startEditDataLoaderTimer() {
        window.setTimeout(hideEditDataLoader, 1000);
      }

      if (document.readyState === 'complete') {
        startEditDataLoaderTimer();
      } else {
        window.addEventListener('load', startEditDataLoaderTimer, { once: true });
      }

      window.setTimeout(hideEditDataLoader, 6000);

      window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
          hideEditDataLoader();
        }
      });
    }

// ELEMENT

    // ============================================================

    const statusButtons =

      document.querySelectorAll(

        '#edStatusRow .ed-status-btn'

      );

    const meta =

      document.getElementById('edMeta');

    const filterTanggal =

      document.getElementById('edFilterTanggal');

    const filterSto =

      document.getElementById('edFilterSto');

    const overlay =

      document.getElementById('edModalOverlay');

    const modalTitle =

      document.getElementById('edModalTitle');

    const addBtn =

      document.getElementById('edAddBtn');

    const closeBtn =

      document.getElementById('edModalClose');

    const cancelBtn =

      document.getElementById('edBtnCancel');

    const saveBtn =

      document.getElementById('edBtnSave');

    const tableBody =

      document.getElementById('edTableBody');

    const form =

      document.getElementById('edForm');

    form.addEventListener('submit', event => {
      event.preventDefault();
      saveBtn.click();
    });

    const fOrderId =

      document.getElementById('edOrderId');

    const fDeskripsi =

      document.getElementById('edDeskripsi');

    const fSto =

      document.getElementById('edSto');

    const fTanggal =

      document.getElementById('edTanggal');

    const fPic =

      document.getElementById('edPic');

    const fStatusRe =

      document.getElementById('edStatusRe');

    const fStatusDetail =

      document.getElementById('edStatusDetail');

    const fKet =

      document.getElementById('edKet');

    const fTiketEskalasi =

      document.getElementById('edTiketEskalasi');

    const statusReLabel = {

      resolved: 'RESOLVED',

      eskalasi: 'ESKALASI',

      pending: 'PENDING',

      cancel: 'CANCEL',

      eskalasi_dit: 'ESKALASI DIT',

      close: 'CLOSE',

      lainnya: 'ESKALASI'

    };

    let editingRow = null;

    // ============================================================

    // MODAL DIPINDAH KE BODY

    // ============================================================

    document.body.appendChild(overlay);

    // UI ALERT / CONFIRM HELPERS

    const uiAlertOverlay = document.getElementById('edUiAlertOverlay');

    const uiAlertIcon = document.getElementById('edUiAlertIcon');

    const uiAlertTitle = document.getElementById('edUiAlertTitle');

    const uiAlertMessage = document.getElementById('edUiAlertMessage');

    const uiAlertActions = document.getElementById('edUiAlertActions');

    const uiAlertCancel = document.getElementById('edUiAlertCancel');

    const uiAlertConfirm = document.getElementById('edUiAlertConfirm');

    // Preview No/Order ID lengkap (aman: isi ditampilkan menggunakan textContent).
    const orderIdPreviewOverlay = document.getElementById('edOrderIdPreviewOverlay');
    const orderIdPreviewValue = document.getElementById('edOrderIdPreviewValue');
    const orderIdPreviewClose = document.getElementById('edOrderIdPreviewClose');
    const orderIdPreviewDone = document.getElementById('edOrderIdPreviewDone');
    const orderIdCopyButton = document.getElementById('edOrderIdCopyButton');

    if (orderIdPreviewOverlay && orderIdPreviewOverlay.parentElement !== document.body) {
      document.body.appendChild(orderIdPreviewOverlay);
    }

    function openOrderIdPreview(value) {
      if (!orderIdPreviewOverlay || !orderIdPreviewValue) return;
      orderIdPreviewValue.textContent = value || '-';
      orderIdPreviewOverlay.classList.add('open');
      orderIdPreviewOverlay.setAttribute('aria-hidden', 'false');
      if (orderIdPreviewClose) orderIdPreviewClose.focus();
    }

    function closeOrderIdPreview() {
      if (!orderIdPreviewOverlay) return;
      orderIdPreviewOverlay.classList.remove('open');
      orderIdPreviewOverlay.setAttribute('aria-hidden', 'true');
    }

    if (orderIdPreviewClose) orderIdPreviewClose.addEventListener('click', closeOrderIdPreview);
    if (orderIdPreviewDone) orderIdPreviewDone.addEventListener('click', closeOrderIdPreview);

    if (orderIdPreviewOverlay) {
      orderIdPreviewOverlay.addEventListener('click', function (event) {
        if (event.target === orderIdPreviewOverlay) closeOrderIdPreview();
      });
    }

    if (orderIdCopyButton) {
      orderIdCopyButton.addEventListener('click', async function () {
        const value = orderIdPreviewValue ? orderIdPreviewValue.textContent : '';
        if (!value || value === '-') return;
        try {
          await navigator.clipboard.writeText(value);
          orderIdCopyButton.textContent = 'Tersalin ✓';
        } catch (error) {
          // Fallback for environments where Clipboard API is not available.
          const selection = window.getSelection();
          const range = document.createRange();
          range.selectNodeContents(orderIdPreviewValue);
          selection.removeAllRanges();
          selection.addRange(range);
          orderIdCopyButton.textContent = 'Pilih lalu salin';
        }
      });
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && orderIdPreviewOverlay && orderIdPreviewOverlay.classList.contains('open')) {
        closeOrderIdPreview();
      }
    });

    let uiAlertResolver = null;

    // Modal notifikasi/konfirmasi harus berada langsung di <body>

    // agar menutup seluruh halaman, bukan hanya area dashboard.

    if (uiAlertOverlay && uiAlertOverlay.parentElement !== document.body) {

      document.body.appendChild(uiAlertOverlay);

    }

    function escapeUiHtml(value){

      return String(value ?? '')

        .replace(/&/g,'&amp;')

        .replace(/</g,'&lt;')

        .replace(/>/g,'&gt;')

        .replace(/"/g,'&quot;')

        .replace(/'/g,'&#039;');

    }

    function uiIcon(type){

      if(type === 'success'){

        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5 9.2 17 19 7"/></svg>';

      }

      if(type === 'error'){

        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16.5h.01"/></svg>';

      }

      return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8.5V6a3 3 0 0 1 6 0v2.5"/><path d="M5 8.5h14l-1 11H6l-1-11Z"/><path d="M9 11.5v4"/><path d="M15 11.5v4"/></svg>';

    }

    function openUiAlert({type='error',title='Informasi',message='',confirmText='OK',cancelText='Batal',showCancel=false}){

      return new Promise(resolve => {

        uiAlertResolver = resolve;

        uiAlertIcon.className = `ed-ui-alert-icon ${type}`;

        uiAlertIcon.innerHTML = uiIcon(type);

        uiAlertTitle.textContent = title;

        uiAlertMessage.innerHTML = message;

        uiAlertCancel.textContent = cancelText;

        uiAlertConfirm.textContent = confirmText;

        uiAlertCancel.style.display = showCancel ? '' : 'none';

        uiAlertActions.classList.toggle('single', !showCancel);

        uiAlertConfirm.classList.toggle('success-btn', type === 'success');

        document.documentElement.classList.add('ed-ui-lock');

        document.body.classList.add('ed-ui-lock');

        uiAlertOverlay.classList.add('open');

        uiAlertOverlay.setAttribute('aria-hidden','false');

        setTimeout(() => uiAlertConfirm.focus(), 20);

      });

    }

    function closeUiAlert(value){

      uiAlertOverlay.classList.remove('open');

      uiAlertOverlay.setAttribute('aria-hidden','true');

      document.documentElement.classList.remove('ed-ui-lock');

      document.body.classList.remove('ed-ui-lock');

      if(uiAlertResolver){

        const resolve = uiAlertResolver;

        uiAlertResolver = null;

        resolve(value);

      }

    }

    function showUiAlert(options){

      return openUiAlert({...options, showCancel:false});

    }

    function showUiConfirm(options){

      return openUiAlert({...options, showCancel:true, type:'error'});

    }

    uiAlertConfirm.addEventListener('click', () => closeUiAlert(true));

    uiAlertCancel.addEventListener('click', () => closeUiAlert(false));

    uiAlertOverlay.addEventListener('click', event => {

      if(event.target === uiAlertOverlay) closeUiAlert(false);

    });

    document.addEventListener('keydown', event => {

      if(event.key === 'Escape' && uiAlertOverlay.classList.contains('open')) closeUiAlert(false);

    });

    // ============================================================

    // TANGGAL HARI INI

    // ============================================================

    function todayIso(){

      const now = new Date();

      const local =

        new Date(

          now.getTime()

          -

          (

            now.getTimezoneOffset()

            *

            60000

          )

        );

      return local

        .toISOString()

        .slice(0,10);

    }

    // ============================================================

    // FORMAT TANGGAL INDONESIA

    // ============================================================

    function formatTanggal(value){

      if (!value){

        return '-';

      }

      const parts =

        value

          .split('-')

          .map(Number);

      if (

        parts.length !== 3 ||

        parts.some(Number.isNaN)

      ){

        return value;

      }

      const date =

        new Date(

          parts[0],

          parts[1] - 1,

          parts[2]

        );

      return date.toLocaleDateString(

        'id-ID',

        {

          day:'numeric',

          month:'long',

          year:'numeric'

        }

      );

    }

    // ============================================================

    // STATUS FILTER AKTIF

    // ============================================================

    function getActiveStatusFilter(){

      const active =

        document.querySelector(

          '#edStatusRow .ed-status-btn.active'

        );

      return active

        ? active.dataset.filter

        : 'semua';

    }

    // ============================================================

    // APPLY SEMUA FILTER

    // ============================================================

    function applyFilter(){

      const statusFilter =

        getActiveStatusFilter();

      const tanggalFilter =

        filterTanggal.value;

      const stoFilter =

        filterSto.value;

      const rows =

        document.querySelectorAll(

          '#edTableBody tr[data-status]'

        );

      let shown = 0;
      const noMatchRow = document.getElementById('edNoMatchRow');

      rows.forEach(row => {

        const statusMatch =

          statusFilter === 'semua' ||

          row.dataset.status === statusFilter;

        const dateMatch =

          !tanggalFilter ||

          row.dataset.tgl === tanggalFilter;

        const rowSto =

          (row.dataset.sto || '')

            .trim()

            .toUpperCase();

        const selectedSto =

          (stoFilter || 'semua')

            .trim()

            .toUpperCase();

        const stoMatch =

          selectedSto === 'SEMUA' ||

          rowSto === selectedSto;

        const match =

          statusMatch &&

          dateMatch &&

          stoMatch;

        row.style.display =

          match

            ? ''

            : 'none';

        if (match){

          shown++;

        }

      });

      if (noMatchRow) {
        noMatchRow.style.display = shown === 0 ? 'table-row' : 'none';
      }

      let dateText =

        'Semua tanggal';

      if (tanggalFilter){

        dateText =

          formatTanggal(

            tanggalFilter

          );

      }

      let statusText =

        statusFilter === 'semua'

          ? 'Semua status'

          : (

              statusReLabel[statusFilter]

              ||

              statusFilter

            );

      let stoText =

        stoFilter === 'semua'

          ? 'Semua STO'

          : stoFilter;

      meta.textContent =

        `${dateText} · ${statusText} · ${stoText} · ${shown} data ditemukan`;

    }

    // ============================================================

    // EVENT FILTER STATUS

    // ============================================================

    statusButtons.forEach(btn => {

      btn.addEventListener(

        'click',

        function(){

          statusButtons.forEach(

            b =>

              b.classList.remove(

                'active'

              )

          );

          this.classList.add(

            'active'

          );

          applyFilter();

        }

      );

    });

    filterTanggal.addEventListener(

      'change',

      applyFilter

    );

    filterSto.addEventListener(

      'change',

      applyFilter

    );

    // ============================================================

    // BUKA MODAL

    // ============================================================

    function openModal(mode, row){

      editingRow =

        row || null;

      modalTitle.textContent =

        mode === 'edit'

          ? 'Edit Data Fallout'

          : 'Tambah Data Fallout';

      const legacyStatusOption = fStatusRe.querySelector('option[value="lainnya"]');
      if (legacyStatusOption) legacyStatusOption.hidden = mode !== 'edit';

      if (row){

        // EDIT

        fOrderId.value =

          row.dataset.order_id || '';

        fDeskripsi.value =

          row.dataset.deskripsi || '';

        fSto.value =

          row.dataset.sto || '';

        fTanggal.value =

          row.dataset.tgl || '';

        fPic.value =

          row.dataset.pic || '';

        fStatusRe.value =

          row.dataset.status_re || '';

        fStatusDetail.value =

          row.dataset.status_detail || '';

        fKet.value =

          row.dataset.ket || '';

        fTiketEskalasi.value =

          row.dataset.ticket_eskalasi || '';

      } else {

        // TAMBAH

        form.reset();

        // Otomatis hari ini.

        fTanggal.value =

          todayIso();

      }

      overlay.classList.add(

        'open'

      );

      setTimeout(

        () =>

          fOrderId.focus(),

        50

      );

    }

    // ============================================================

    // TUTUP MODAL

    // ============================================================

    function closeModal(){

      overlay.classList.remove(

        'open'

      );

      editingRow =

        null;

    }

    addBtn.addEventListener(

      'click',

      () =>

        openModal('add')

    );

    closeBtn.addEventListener(

      'click',

      closeModal

    );

    cancelBtn.addEventListener(

      'click',

      closeModal

    );

    overlay.addEventListener(

      'click',

      function(e){

        if (

          e.target === overlay

        ){

          closeModal();

        }

      }

    );

    document.addEventListener(

      'keydown',

      function(e){

        if (

          e.key === 'Escape' &&

          overlay.classList.contains('open')

        ){

          closeModal();

        }

      }

    );

    // ============================================================

    // EDIT / DELETE

    // ============================================================

    tableBody.addEventListener(

      'click',

      async function(e){

        const editButton =

          e.target.closest(

            '.ed-edit-btn'

          );

        const deleteButton =

          e.target.closest(

            '.ed-delete-btn'

          );

        const orderIdPreviewButton =

          e.target.closest(

            '.ed-order-id-preview-btn'

          );

        const row =

          e.target.closest(

            'tr[data-id]'

          );

        if (!row){

          return;

        }

        // Preview ID lengkap melalui tombol mata.
        if (orderIdPreviewButton){

          openOrderIdPreview(row.dataset.order_id || '');

          return;

        }

        // EDIT

        if (editButton){

          openModal(

            'edit',

            row

          );

          return;

        }

        // DELETE

        if (deleteButton){

          const rowId =

            row.dataset.id;

          if (!rowId){

            await showUiAlert({

              type:'error',

              title:'Data Tidak Ditemukan',

              message:'ID data tidak ditemukan. Silakan refresh halaman.',

              confirmText:'OK'

            });

            return;

          }

          const orderIdForDelete =

            row.dataset.order_id || 'ini';

          const confirmed =

            await showUiConfirm({

              title:'Hapus Data?',

              message:`Apakah kamu yakin ingin menghapus data <strong>${escapeUiHtml(orderIdForDelete)}</strong>? Data yang sudah dihapus tidak dapat dikembalikan.`,

              confirmText:'Hapus',

              cancelText:'Batal'

            });

          if (!confirmed){

            return;

          }

          try {

            const response =

              await fetch(

                `{{ url('/dashboard/' . $witelSlug . '/edit-data') }}/${rowId}`,

                {

                  method:'DELETE',

                  headers:{

                    'X-CSRF-TOKEN':

                      '{{ csrf_token() }}',

                    'Accept':

                      'application/json',

                    'X-Requested-With':

                      'XMLHttpRequest'

                  },

                  credentials:

                    'same-origin'

                }

              );

            const result =

              await response

                .json()

                .catch(

                  () => ({})

                );

            if (

              !response.ok ||

              !result.success

            ){

              throw new Error(

                result.message ||

                'Gagal menghapus data.'

              );

            }

            row.remove();

            renumberRows();

            applyFilter();

            await showUiAlert({

              type:'success',

              title:'Data Berhasil Dihapus',

              message:`Data <strong>${escapeUiHtml(orderIdForDelete)}</strong> berhasil dihapus dan riwayat penghapusannya telah dicatat.`,

              confirmText:'OK'

            });

          } catch(error){

            console.error(

              error

            );

            await showUiAlert({

              type:'error',

              title:'Gagal Menghapus Data',

              message:escapeUiHtml(error.message || 'Terjadi kesalahan saat menghapus data.'),

              confirmText:'OK'

            });

          }

        }

      }

    );

    // ============================================================

    // NOMOR URUT

    // ============================================================

    function renumberRows(){

      const rows =

        document.querySelectorAll(

          '#edTableBody tr[data-status]'

        );

      rows.forEach(

        (row,index) => {

          const noCell =

            row.querySelector(

              '.ed-no'

            );

          if (noCell){

            noCell.textContent =

              index + 1;

          }

        }

      );

    }

    // ============================================================

    // BUILD PAYLOAD

    // ============================================================

    function buildPayload(){

      return {

        order_id:

          fOrderId.value.trim(),

        deskripsi:

          fDeskripsi.value.trim(),

        sto:

          fSto.value.trim(),

        tanggal:

          fTanggal.value,

        pic:

          fPic.value.trim(),

        resolved_eskalasi:

          fStatusRe.value,

        status:

          fStatusDetail.value,

        ket:

          fKet.value.trim(),

        tiket_eskalasi:

          fTiketEskalasi.value.trim()

      };

    }

    // ============================================================

    // PARSE RESPONSE SERVER

    // ============================================================

    async function parseResponse(

      response

    ){

      const result =

        await response

          .json()

          .catch(

            () => ({})

          );

      if (

        response.ok &&

        result.success

      ){

        return result;

      }

      const validationErrors =

        result.errors

          ? Object.values(

              result.errors

            )

              .flat()

              .join('\n')

          : '';

      let message =

        validationErrors

        ||

        result.message

        ||

        `Permintaan gagal (${response.status}).`;

      // Jangan tampilkan SQL mentah ke user.

      // Khusus duplicate key, tampilkan pesan yang mudah dipahami.

      if (

        /Integrity constraint violation|Duplicate entry|1062/i.test(message)

      ){

        const duplicateMatch =

          message.match(/Duplicate entry ['"](?:[^-'"]+-)?([^'"]+)['"]/i);

        const duplicateId =

          duplicateMatch && duplicateMatch[1]

            ? duplicateMatch[1]

            : fOrderId.value.trim();

        message =

          `Order ID ${duplicateId || 'tersebut'} sudah ada pada batch data yang dipilih. Gunakan Order ID lain atau edit data yang sudah ada.`;

      }

      throw new Error(message);

    }

    // ============================================================

    // SIMPAN DATA

    //

    // TAMBAH  -> POST

    // EDIT    -> PUT

    // ============================================================

    saveBtn.addEventListener(

      'click',

      async function(){

        const payload =

          buildPayload();

        // VALIDASI FRONTEND

        if (!payload.order_id){

          showUiAlert({

            type:'error',

            title:'Data Belum Lengkap',

            message:'No/Order ID wajib diisi.',

            confirmText:'OK'

          });

          fOrderId.focus();

          return;

        }

        if (!payload.tanggal){

          showUiAlert({

            type:'error',

            title:'Data Belum Lengkap',

            message:'Tanggal wajib diisi.',

            confirmText:'OK'

          });

          fTanggal.focus();

          return;

        }

        if (!payload.pic){
          showUiAlert({type:'error',title:'Data Belum Lengkap',message:'PIC wajib diisi.',confirmText:'OK'});
          fPic.focus();
          return;
        }

        if (

          !payload.resolved_eskalasi

        ){

          showUiAlert({

            type:'error',

            title:'Data Belum Lengkap',

            message:'RESOLVED/ESKALASI wajib diisi.',

            confirmText:'OK'

          });

          fStatusRe.focus();

          return;

        }

        if (!payload.status){
          showUiAlert({type:'error',title:'Data Belum Lengkap',message:'Status wajib dipilih.',confirmText:'OK'});
          fStatusDetail.focus();
          return;
        }

        // Saat tambah, cegah duplikasi yang sudah terlihat di tabel.

        // Backend tetap melakukan pengecekan final.

        if (!editingRow){

          const wantedOrderId = payload.order_id.toLowerCase();

          const duplicateRow = Array.from(

            document.querySelectorAll('#edTableBody tr[data-status]')

          ).find(row =>

            String(row.dataset.order_id || '').trim().toLowerCase() === wantedOrderId

          );

          if (duplicateRow){

            showUiAlert({

              type:'error',

              title:'Data Sudah Ada',

              message:`Order ID <strong>${escapeUiHtml(payload.order_id)}</strong> sudah ada di tabel Witel ini. Gunakan data lain atau klik Edit pada data tersebut.`,

              confirmText:'OK'

            });

            fOrderId.focus();

            return;

          }

        }

        const isEdit =

          Boolean(

            editingRow

          );

        const rowId =

          isEdit

            ? editingRow.dataset.id

            : null;

        const url =

          isEdit

            ? `{{ url('/dashboard/' . $witelSlug . '/edit-data') }}/${rowId}`

            : `{{ url('/dashboard/' . $witelSlug . '/edit-data') }}`;

        const method =

          isEdit

            ? 'PUT'

            : 'POST';

        saveBtn.disabled =

          true;

        saveBtn.textContent =

          isEdit

            ? '⏳ Menyimpan...'

            : '⏳ Menambahkan...';

        try {

          const response =

            await fetch(

              url,

              {

                method,

                headers:{

                  'Content-Type':

                    'application/json',

                  'X-CSRF-TOKEN':

                    '{{ csrf_token() }}',

                  'Accept':

                    'application/json',

                  'X-Requested-With':

                    'XMLHttpRequest'

                },

                credentials:

                  'same-origin',

                body:

                  JSON.stringify(

                    payload

                  )

              }

            );

          const result =

            await parseResponse(

              response

            );

          closeModal();

          await showUiAlert({

            type:'success',

            title:isEdit

              ? 'Data Berhasil Diperbarui'

              : 'Data Berhasil Ditambahkan',

            message:escapeUiHtml(

              result.message

              ||

              (

                isEdit

                  ? 'Data berhasil diperbarui.'

                  : 'Data berhasil ditambahkan.'

              )

            ),

            confirmText:'OK'

          });

          // PENTING:

          // ambil ulang data dari DATABASE.

          // Jadi data tidak hilang ketika browser refresh.

          window.location.reload();

        } catch(error){

          console.error(

            error

          );

          await showUiAlert({

            type:'error',

            title:'Gagal Menyimpan Data',

            message:escapeUiHtml(

              error.message

              ||

              'Terjadi kesalahan saat menyimpan data.'

            ),

            confirmText:'OK'

          });

        } finally {

          saveBtn.disabled =

            false;

          saveBtn.textContent =

            '💾 Simpan';

        }

      }

    );

    // ============================================================

    // DEFAULT HALAMAN

    //

    // Tanggal sengaja kosong:

    // SEMUA DATA akan langsung tampil.

    // ============================================================

    filterTanggal.value =

      '';

    filterSto.value =

      'semua';

    applyFilter();

  })();

</script>
