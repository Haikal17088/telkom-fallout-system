@php
    /*
    |--------------------------------------------------------------------------
    | UPLOAD DATA FALLOUT
    |--------------------------------------------------------------------------
    | File yang dipilih akan:
    | 1. Dibaca di browser untuk preview
    | 2. Dihitung ringkasannya
    | 3. Tetap dikirim melalui form POST ke route upload-data.store
    |
    | Format Excel yang dipakai sistem:
    | Sheet = ALL
    |
    | A = Order ID
    | B = Status Message / Deskripsi
    | C = STO
    | D = Tanggal
    | E = PIC
    | F = RESOLVED / ESKALASI
    | G = Status
    | H = KET
    |--------------------------------------------------------------------------
    */
@endphp

<div class="ud-wrap">

    {{-- LOADING KHUSUS HALAMAN UPLOAD DATA FALLOUT --}}
    <div class="ud-page-loading" id="udPageLoading" aria-live="polite" aria-label="Memuat Upload Data Fallout">
    <div class="ud-page-loading-card">
      <div class="ud-page-loading-logo">
        <span>TF</span>
      </div>
      <div class="ud-page-loading-spinner" aria-hidden="true"></div>
      <div class="ud-page-loading-title">Memuat Upload Data Fallout</div>
      <div class="ud-page-loading-subtitle">Menyiapkan halaman {{ $witel }}</div>
    </div>
  </div>

    {{-- =========================================================
         HEADER
         ========================================================= --}}
    <div class="ud-page-head">

        <div>
            <h2>Upload Data Fallout</h2>

            <p class="ud-location">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                    <circle
                        cx="12"
                        cy="9"
                        r="2.5"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />
                </svg>

                {{ $witel }}
            </p>
        </div>

        <button
            type="button"
            class="ud-reset-btn"
            id="udResetBtn"
        >
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                <path
                    d="M20 11a8.1 8.1 0 0 0-14.8-4.3L3 9m0 0V4m0 5h5"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                <path
                    d="M4 13a8.1 8.1 0 0 0 14.8 4.3L21 15m0 0v5m0-5h-5"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>

            Reset
        </button>

    </div>


    {{-- =========================================================
         ALERT / CONFIRM
         Tampilan dibuat sama seperti UI alert di Edit Data.
         ========================================================= --}}
    <div
        class="ud-ui-alert-overlay"
        id="udUiAlertOverlay"
        aria-hidden="true"
    >
        <div
            class="ud-ui-alert-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="udUiAlertTitle"
        >
            <div
                class="ud-ui-alert-icon"
                id="udUiAlertIcon"
            ></div>

            <h3 id="udUiAlertTitle">Informasi</h3>

            <div
                class="ud-ui-alert-message"
                id="udUiAlertMessage"
            ></div>

            <div
                class="ud-ui-alert-actions"
                id="udUiAlertActions"
            >
                <button
                    type="button"
                    class="ud-ui-alert-btn ud-ui-alert-cancel"
                    id="udUiAlertCancel"
                >
                    Batal
                </button>

                <button
                    type="button"
                    class="ud-ui-alert-btn ud-ui-alert-confirm"
                    id="udUiAlertConfirm"
                >
                    OK
                </button>
            </div>
        </div>
    </div>


    {{-- =========================================================
         FORM
         ========================================================= --}}
    <form
        id="udForm"
        class="ud-form"
        method="POST"
        action="{{ route('upload-data.store', ['witel' => $witelSlug]) }}"
        enctype="multipart/form-data"
    >

        @csrf


        <div class="ud-main-grid">

            {{-- =================================================
                 KIRI
                 ================================================= --}}
            <div class="ud-left-col">

                {{-- PILIH FILE --}}
                <div class="ud-card">

                    <div class="ud-card-title">
                        Pilih File Fallout
                    </div>

                    <div class="ud-card-subtitle">
                        Upload file Excel atau CSV yang berisi data fallout.
                    </div>


                    {{-- DROPZONE --}}
                    <div
                        class="ud-dropzone"
                        id="udDropzone"
                    >

                        <input
                            type="file"
                            id="udFileInput"
                            name="file"
                            accept=".xlsx,.xls,.csv"
                            hidden
                        >


                        {{-- DEFAULT --}}
                        <div
                            class="ud-dz-default"
                            id="udDzDefault"
                        >

                            <div class="ud-upload-icon">

                                <svg
                                    width="26"
                                    height="26"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                >
                                    <path
                                        d="M12 16V4M12 4l-5 5M12 4l5-5"
                                        stroke="#C8102E"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"
                                        stroke="#C8102E"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>

                            </div>

                            <div class="ud-dz-title">
                                Drag &amp; drop file di sini
                            </div>

                            <div class="ud-dz-sub">
                                atau klik area ini untuk memilih file
                            </div>


                            <div class="ud-file-types">

                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M6 3h8l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"
                                            stroke="currentColor"
                                            stroke-width="1.6"
                                        />
                                        <path
                                            d="M14 3v5h5"
                                            stroke="currentColor"
                                            stroke-width="1.6"
                                        />
                                    </svg>
                                    XLSX
                                </span>

                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M6 3h8l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"
                                            stroke="currentColor"
                                            stroke-width="1.6"
                                        />
                                        <path
                                            d="M14 3v5h5"
                                            stroke="currentColor"
                                            stroke-width="1.6"
                                        />
                                    </svg>
                                    XLS
                                </span>

                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M6 3h8l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"
                                            stroke="currentColor"
                                            stroke-width="1.6"
                                        />
                                        <path
                                            d="M14 3v5h5"
                                            stroke="currentColor"
                                            stroke-width="1.6"
                                        />
                                    </svg>
                                    CSV
                                </span>

                            </div>

                        </div>


                        {{-- SELECTED --}}
                        <div
                            class="ud-dz-selected"
                            id="udDzSelected"
                            style="display:none;"
                        >

                            <div class="ud-selected-icon">

                                <svg
                                    width="28"
                                    height="28"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                >
                                    <path
                                        d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"
                                        stroke="#1E7A46"
                                        stroke-width="1.7"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M14 2v6h6"
                                        stroke="#1E7A46"
                                        stroke-width="1.7"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="m8 14 2.5 2.5L16 11"
                                        stroke="#1E7A46"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>

                            </div>

                            <div class="ud-selected-name" id="udFileName">
                                nama_file.xlsx
                            </div>

                            <div class="ud-selected-size" id="udFileSize">
                                0 KB
                            </div>

                            <button
                                type="button"
                                class="ud-remove-btn"
                                id="udRemoveBtn"
                            >
                                Hapus file
                            </button>

                        </div>

                    </div>


                    {{-- FORMAT INFO --}}
                    <div class="ud-format-info">

                        <div class="ud-format-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M4 5a2 2 0 0 1 2-2h7l5 5v13H6a2 2 0 0 1-2-2V5Z"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M13 3v5h5M8 13h8M8 17h6"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </div>

                        <div>

                            <div class="ud-format-title">
                                Format data
                            </div>

                            <div class="ud-format-text">
                                Gunakan sheet <strong>ALL</strong> dengan urutan kolom:
                                <strong>Order ID, Status Message, STO, Tggl Fallout, PIC, RESOLVED/ESKALASI, Status, KET.</strong>
                            </div>

                        </div>

                    </div>

                </div>


                {{-- BUTTON UPLOAD --}}
                <div class="ud-upload-box">

                    <button
                        type="submit"
                        class="ud-submit-btn"
                        id="udSubmitBtn"
                        disabled
                    >

                        <svg
                            width="17"
                            height="17"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path
                                d="M12 16V4m0 0-5 5m5-5 5 5"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>

                        <span id="udSubmitLabel">
                            Upload Data
                        </span>

                    </button>

                    <div class="ud-upload-note" id="udUploadNote">
                        Data akan disimpan ke batch cabang {{ $witelSlug }}.
                    </div>

                </div>

            </div>


            {{-- =================================================
                 KANAN — RINGKASAN
                 ================================================= --}}
            <div class="ud-right-col">

                <div class="ud-summary-title">
                    RINGKASAN DATA
                </div>


                {{-- STAT GRID --}}
                <div class="ud-stat-grid">

                    {{-- TOTAL --}}
                    <div class="ud-stat-card">

                        <div class="ud-stat-left">

                            <div class="ud-stat-label">
                                Data Siap Upload
                            </div>

                            <div class="ud-stat-value" id="udTotalData">
                                0
                            </div>

                        </div>

                        <div class="ud-stat-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <ellipse
                                    cx="12"
                                    cy="5"
                                    rx="7"
                                    ry="3"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                />
                                <path
                                    d="M5 5v7c0 1.66 3.13 3 7 3s7-1.34 7-3V5"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                />
                                <path
                                    d="M5 12v7c0 1.66 3.13 3 7 3s7-1.34 7-3v-7"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                />
                            </svg>
                        </div>

                    </div>


                    {{-- RESOLVED --}}
                    <div class="ud-stat-card">

                        <div class="ud-stat-left">

                            <div class="ud-stat-label">
                                Resolved
                            </div>

                            <div
                                class="ud-stat-value"
                                id="udResolved"
                            >
                                0
                            </div>

                        </div>

                        <div class="ud-stat-icon ud-stat-green">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path
                                    d="m6 12 4 4 8-8"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </div>

                    </div>


                    {{-- ESKALASI --}}
                    <div class="ud-stat-card">

                        <div class="ud-stat-left">

                            <div class="ud-stat-label">
                                Eskalasi
                            </div>

                            <div
                                class="ud-stat-value"
                                id="udEskalasi"
                            >
                                0
                            </div>

                        </div>

                        <div class="ud-stat-icon ud-stat-orange">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="8"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />
                                <path
                                    d="M12 8v5"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                                <circle
                                    cx="12"
                                    cy="16"
                                    r=".8"
                                    fill="currentColor"
                                />
                            </svg>
                        </div>

                    </div>


                    {{-- STO --}}
                    <div class="ud-stat-card">

                        <div class="ud-stat-left">

                            <div class="ud-stat-label">
                                STO
                            </div>

                            <div
                                class="ud-stat-value"
                                id="udStoCount"
                            >
                                0
                            </div>

                        </div>

                        <div class="ud-stat-icon ud-stat-blue">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"
                                />
                                <circle
                                    cx="12"
                                    cy="9"
                                    r="2.5"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />
                            </svg>
                        </div>

                    </div>

                </div>


                {{-- PIC --}}
                <div class="ud-summary-card">

                    <div class="ud-summary-card-head">

                        <span>PIC</span>

                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path
                                d="M6 3h8l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"
                                stroke="currentColor"
                                stroke-width="1.6"
                            />
                            <path
                                d="M14 3v5h5"
                                stroke="currentColor"
                                stroke-width="1.6"
                            />
                        </svg>

                    </div>


                    <div
                        class="ud-pic-list"
                        id="udPicList"
                    >
                        <span class="ud-empty-summary">
                            Belum ada data
                        </span>
                    </div>


                    <div
                        class="ud-summary-foot"
                        id="udPicFoot"
                    >
                        0 PIC ditemukan
                    </div>

                </div>


                {{-- STATUS LAINNYA --}}
                <div class="ud-summary-section-title">

                    STATUS LAINNYA

                    <span id="udOtherStatusCount">
                        0 status
                    </span>

                </div>


                <div
                    class="ud-other-status-grid"
                    id="udOtherStatusGrid"
                >

                    <div class="ud-other-empty">
                        Belum ada status lainnya
                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
             PREVIEW
             ===================================================== --}}
        <div
            class="ud-preview-card"
            id="udPreviewCard"
            style="display:none;"
        >

            <div class="ud-preview-head">

                <div>

                    <div class="ud-preview-title">
                        Preview Data
                    </div>

                    <div class="ud-preview-subtitle">
                        Preview menampilkan maksimal 100 baris pertama. Seluruh data tetap akan diupload.
                    </div>

                </div>

                <div
                    class="ud-preview-total"
                    id="udPreviewTotal"
                >
                    0 Total Data
                </div>

            </div>


            <div class="ud-table-wrap">

                <table class="ud-table">

                    <thead>

                        <tr>
                            <th>NO</th>
                            <th>ID</th>
                            <th>DESKRIPSI</th>
                            <th>STO</th>
                            <th>TANGGAL</th>
                            <th>PIC</th>
                            <th>RESOLVED / ESKALASI</th>
                            <th>STATUS</th>
                            <th>KET</th>
                        </tr>

                    </thead>

                    <tbody id="udPreviewBody">

                    </tbody>

                </table>

            </div>

        </div>

    </form>

</div>


<style>

    /* =========================================================
       LOADING KHUSUS HALAMAN
       ========================================================= */

    .ud-wrap{
    position:relative;
    width:100%;
    min-height:calc(100vh - 250px);
    padding:8px 6px 28px;
    box-sizing:border-box;
}


    .ud-page-loading{
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

    .ud-page-loading.is-hidden{
        opacity:0;
        visibility:hidden;
        pointer-events:none;
    }

    .ud-page-loading-card{
        width:min(320px, 100%);
        padding:28px 24px 24px;
        text-align:center;
        border:1px solid rgba(255,255,255,.9);
        border-radius:18px;
        background:rgba(255,255,255,.88);
        box-shadow:0 24px 60px -30px rgba(58,4,16,.30);
    }

    .ud-page-loading-logo{
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

    .ud-page-loading-spinner{
        width:30px;
        height:30px;
        margin:0 auto 14px;
        border:3px solid rgba(200,16,46,.14);
        border-top-color:#C8102E;
        border-right-color:#8A0F26;
        border-radius:50%;
        animation:udPageLoadingSpin .8s linear infinite;
    }

    .ud-page-loading-title{
        font-family:'Space Grotesk',sans-serif;
        font-size:14px;
        font-weight:700;
        color:#20161A;
    }

    .ud-page-loading-subtitle{
        margin-top:5px;
        font-family:'Inter',sans-serif;
        font-size:11px;
        line-height:1.5;
        color:#817377;
    }

    @keyframes udPageLoadingSpin{
        to{ transform:rotate(360deg); }
    }


    /* =========================================================
       RESET / BASE
       ========================================================= */

    .ud-wrap{
        width:100%;
        padding:4px 6px 32px;
        color:#20161A;
    }

    .ud-wrap *,
    .ud-wrap *::before,
    .ud-wrap *::after{
        box-sizing:border-box;
    }


    /* =========================================================
       HEADER
       ========================================================= */

    .ud-page-head{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:20px;
    }

    .ud-page-head h2{
        margin:0;
        font-family:'Space Grotesk',sans-serif;
        font-size:22px;
        font-weight:700;
        color:#20161A;
    }

    .ud-location{
        margin:7px 0 0;
        display:flex;
        align-items:center;
        gap:6px;
        color:#C8102E;
        font-size:12.5px;
        font-weight:600;
    }


    /* =========================================================
       RESET
       ========================================================= */

    .ud-reset-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        padding:10px 15px;
        border:1px solid #E7DEDD;
        border-radius:10px;
        background:#fff;
        color:#51454A;
        font-family:'Inter',sans-serif;
        font-size:12.5px;
        font-weight:700;
        cursor:pointer;
        transition:.15s ease;
        box-shadow:0 5px 16px -12px rgba(0,0,0,.25);
    }

    .ud-reset-btn:hover{
        border-color:#C8102E;
        color:#C8102E;
        background:#FFF9FA;
    }


    /* =========================================================
       ALERT
       ========================================================= */

    .ud-alert{
        margin-top:18px;
        display:flex;
        align-items:flex-start;
        gap:10px;
        padding:13px 16px;
        border-radius:12px;
        font-size:12.5px;
        font-weight:600;
        line-height:1.5;
    }

    .ud-alert-success{
        background:rgba(30,122,70,.09);
        border:1px solid rgba(30,122,70,.22);
        color:#1E7A46;
    }

    .ud-alert-error{
        background:rgba(200,16,46,.07);
        border:1px solid rgba(200,16,46,.2);
        color:#C8102E;
    }

    .ud-alert-icon{
        width:19px;
        height:19px;
        flex:none;
        display:grid;
        place-items:center;
        border-radius:50%;
        font-size:11px;
        font-weight:800;
    }

    .ud-alert-success .ud-alert-icon{
        background:rgba(30,122,70,.15);
    }

    .ud-alert-error .ud-alert-icon{
        background:rgba(200,16,46,.12);
    }


    /* =========================================================
       UI ALERT / CONFIRM
       Sama seperti alert di Edit Data
       ========================================================= */

    html.ud-ui-lock,
    body.ud-ui-lock{
        overflow:hidden !important;
    }

    .ud-ui-alert-overlay{
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

    .ud-ui-alert-overlay.open{
        display:flex;
        animation:udUiAlertFadeIn .18s ease;
    }

    .ud-ui-alert-card{
        width:min(440px, calc(100vw - 32px));
        max-width:440px;
        max-height:calc(100vh - 32px);
        overflow:hidden;
        background:#fff;
        border-radius:22px;
        padding:24px 22px 20px;
        box-shadow:0 34px 90px rgba(0,0,0,.30);
        text-align:center;
        animation:udUiAlertPop .18s ease;
        box-sizing:border-box;
    }

    .ud-ui-alert-icon{
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

    .ud-ui-alert-icon.success{
        background:rgba(30,122,70,.08);
        color:#16A34A;
    }

    .ud-ui-alert-icon.error{
        background:rgba(200,16,46,.08);
        color:#C8102E;
    }

    .ud-ui-alert-icon svg{
        width:27px;
        height:27px;
        display:block;
        stroke:currentColor;
    }

    .ud-ui-alert-card h3{
        margin:0 0 8px;
        font-family:'Space Grotesk',sans-serif;
        font-size:18px;
        font-weight:700;
        color:#252331;
    }

    .ud-ui-alert-message{
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

    .ud-ui-alert-message strong{
        color:#2C3442;
        font-weight:800;
    }

    .ud-ui-alert-actions{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:10px;
    }

    .ud-ui-alert-actions.single{
        grid-template-columns:1fr;
    }

    .ud-ui-alert-btn{
        height:42px;
        border-radius:11px;
        font-family:'Inter',sans-serif;
        font-size:13px;
        font-weight:700;
        cursor:pointer;
        transition:.18s ease;
    }

    .ud-ui-alert-cancel{
        border:1px solid #E5E0E1;
        background:#fff;
        color:#6F7177;
    }

    .ud-ui-alert-cancel:hover{
        background:#F8F6F6;
    }

    .ud-ui-alert-confirm{
        border:1px solid #B40000;
        background:#B40000;
        color:#fff;
        box-shadow:0 10px 22px -14px rgba(180,0,0,.8);
    }

    .ud-ui-alert-confirm:hover{
        background:#980000;
        border-color:#980000;
    }

    .ud-ui-alert-confirm.success-btn{
        background:#650014;
        border-color:#650014;
    }

    @keyframes udUiAlertFadeIn{
        from{opacity:0}
        to{opacity:1}
    }

    @keyframes udUiAlertPop{
        from{opacity:0;transform:translateY(10px) scale(.98)}
        to{opacity:1;transform:translateY(0) scale(1)}
    }

    @media(max-width:520px){
        .ud-ui-alert-card{
            max-width:calc(100vw - 32px);
            padding:20px 18px 18px;
        }
    }


    /* =========================================================
       FORM
       ========================================================= */

    .ud-form{
        margin-top:24px;
    }

    .ud-main-grid{
        display:grid;
        grid-template-columns:minmax(0,1fr) 300px;
        gap:20px;
        align-items:start;
    }

    .ud-left-col,
    .ud-right-col{
        min-width:0;
    }


    /* =========================================================
       CARD
       ========================================================= */

    .ud-card{
        background:#fff;
        border:1px solid #E8E0DF;
        border-radius:18px;
        padding:20px;
        box-shadow:0 12px 28px -24px rgba(58,4,16,.25);
    }

    .ud-card-title{
        font-size:16px;
        font-weight:800;
        color:#20161A;
    }

    .ud-card-subtitle{
        margin-top:4px;
        font-size:11.8px;
        color:#817377;
    }


    /* =========================================================
       DROPZONE
       ========================================================= */

    .ud-dropzone{
        position:relative;
        margin-top:18px;
        min-height:238px;
        padding:22px;
        border:1.5px dashed rgba(200,16,46,.28);
        border-radius:17px;
        background:#FBFCFD;
        display:flex;
        align-items:center;
        justify-content:center;
        text-align:center;
        cursor:pointer;
        transition:
            background .15s ease,
            border-color .15s ease,
            transform .15s ease;
    }

    .ud-dropzone:hover{
        background:#FFF9FA;
        border-color:rgba(200,16,46,.5);
    }

    .ud-dropzone.ud-dragover{
        background:rgba(200,16,46,.05);
        border-color:#C8102E;
        transform:translateY(-1px);
    }

    .ud-dropzone.ud-has-file{
        border-style:solid;
        border-color:rgba(30,122,70,.25);
        background:rgba(30,122,70,.025);
    }

    .ud-dz-default{
        width:100%;
    }

    .ud-upload-icon,
    .ud-selected-icon{
        width:54px;
        height:54px;
        margin:0 auto 14px;
        border-radius:15px;
        display:grid;
        place-items:center;
    }

    .ud-upload-icon{
        background:rgba(200,16,46,.08);
    }

    .ud-selected-icon{
        background:rgba(30,122,70,.1);
    }

    .ud-dz-title{
        font-size:14px;
        font-weight:800;
        color:#20161A;
    }

    .ud-dz-sub{
        margin-top:5px;
        font-size:12px;
        color:#817377;
    }

    .ud-file-types{
        margin-top:18px;
        display:flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        flex-wrap:wrap;
    }

    .ud-file-types span{
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:7px 10px;
        border:1px solid #E8E0DF;
        border-radius:8px;
        background:#fff;
        color:#686067;
        font-size:10.5px;
        font-weight:700;
    }

    .ud-dz-selected{
        width:100%;
    }

    .ud-selected-name{
        font-size:14px;
        font-weight:800;
        color:#20161A;
        word-break:break-word;
    }

    .ud-selected-size{
        margin-top:5px;
        color:#817377;
        font-size:12px;
    }

    .ud-remove-btn{
        margin-top:14px;
        padding:7px 13px;
        border:1px solid #E7DEDD;
        border-radius:8px;
        background:#fff;
        color:#C8102E;
        font-family:'Inter',sans-serif;
        font-size:11px;
        font-weight:700;
        cursor:pointer;
    }

    .ud-remove-btn:hover{
        background:#FFF6F8;
        border-color:#C8102E;
    }


    /* =========================================================
       FORMAT INFO
       ========================================================= */

    .ud-format-info{
        margin-top:14px;
        display:flex;
        align-items:flex-start;
        gap:10px;
        padding:13px 14px;
        border-radius:12px;
        background:#F2F7FD;
        border:1px solid #DCEAF8;
        color:#40566F;
    }

    .ud-format-icon{
        flex:none;
        width:28px;
        height:28px;
        display:grid;
        place-items:center;
        color:#3E78C8;
        background:#E6F0FC;
        border-radius:8px;
    }

    .ud-format-title{
        font-size:11.5px;
        font-weight:800;
        color:#385A7A;
    }

    .ud-format-text{
        margin-top:3px;
        font-size:10.8px;
        line-height:1.55;
        color:#6E8297;
    }


    /* =========================================================
       UPLOAD BUTTON
       ========================================================= */

    .ud-upload-box{
        margin-top:18px;
        background:#fff;
        border:1px solid #E8E0DF;
        border-radius:16px;
        padding:16px;
        text-align:center;
        box-shadow:0 12px 28px -24px rgba(58,4,16,.2);
    }

    .ud-submit-btn{
        width:100%;
        min-height:46px;
        border:none;
        border-radius:11px;
        display:flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        background:linear-gradient(120deg,#C8102E,#8A0F26);
        color:#fff;
        font-family:'Inter',sans-serif;
        font-size:13px;
        font-weight:800;
        cursor:pointer;
        box-shadow:0 12px 24px -14px rgba(200,16,46,.7);
        transition:
            opacity .15s ease,
            transform .15s ease;
    }

    .ud-submit-btn:hover:not(:disabled){
        transform:translateY(-1px);
    }

    .ud-submit-btn:disabled{
        opacity:.42;
        cursor:not-allowed;
        box-shadow:none;
    }

    .ud-upload-note{
        margin-top:8px;
        color:#9A8D91;
        font-size:10.5px;
    }


    /* =========================================================
       RIGHT SUMMARY
       ========================================================= */

    .ud-summary-title{
        margin-bottom:10px;
        font-size:12px;
        font-weight:800;
        color:#6F6468;
        letter-spacing:.02em;
    }

    .ud-stat-grid{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:10px;
    }

    .ud-stat-card{
        min-height:82px;
        padding:13px;
        background:#fff;
        border:1px solid #E8E0DF;
        border-radius:14px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:8px;
        box-shadow:0 10px 24px -22px rgba(58,4,16,.2);
    }

    .ud-stat-label{
        max-width:95px;
        color:#8A7D81;
        font-size:10px;
        font-weight:600;
        line-height:1.35;
    }

    .ud-stat-value{
        margin-top:4px;
        font-family:'Space Grotesk',sans-serif;
        font-size:22px;
        line-height:1;
        font-weight:700;
        color:#20161A;
    }

    .ud-stat-icon{
        width:38px;
        height:38px;
        flex:none;
        display:grid;
        place-items:center;
        border-radius:11px;
        background:rgba(200,16,46,.07);
        color:#C8102E;
    }

    .ud-stat-green{
        color:#1E7A46;
        background:rgba(30,122,70,.08);
    }

    .ud-stat-orange{
        color:#B86E00;
        background:rgba(216,150,35,.1);
    }

    .ud-stat-blue{
        color:#3E78C8;
        background:rgba(62,120,200,.08);
    }


    /* =========================================================
       SUMMARY CARD
       ========================================================= */

    .ud-summary-card{
        margin-top:10px;
        padding:14px;
        background:#fff;
        border:1px solid #E8E0DF;
        border-radius:14px;
        box-shadow:0 10px 24px -22px rgba(58,4,16,.2);
    }

    .ud-summary-card-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        color:#696064;
        font-size:11px;
        font-weight:800;
    }

    .ud-summary-card-head svg{
        color:#C8102E;
    }

    .ud-pic-list{
        margin-top:11px;
        display:flex;
        gap:6px;
        flex-wrap:wrap;
    }

    .ud-pic-chip{
        padding:5px 8px;
        border-radius:7px;
        background:#FFF0F2;
        color:#B12A42;
        font-size:9.5px;
        font-weight:800;
    }

    .ud-empty-summary{
        color:#9A8D91;
        font-size:10.5px;
    }

    .ud-summary-foot{
        margin-top:8px;
        color:#A29699;
        font-size:9.8px;
    }


    /* =========================================================
       OTHER STATUS
       ========================================================= */

    .ud-summary-section-title{
        margin-top:18px;
        margin-bottom:9px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        color:#6F6468;
        font-size:12px;
        font-weight:800;
    }

    .ud-summary-section-title span{
        color:#A29699;
        font-size:9.8px;
        font-weight:600;
    }

    .ud-other-status-grid{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:10px;
    }

    .ud-other-card{
        min-height:78px;
        padding:13px;
        background:#fff;
        border:1px solid #E8E0DF;
        border-radius:14px;
        box-shadow:0 10px 24px -22px rgba(58,4,16,.2);
    }

    .ud-other-label{
        color:#807478;
        font-size:9.8px;
        font-weight:700;
        text-transform:uppercase;
    }

    .ud-other-value{
        margin-top:5px;
        font-family:'Space Grotesk',sans-serif;
        font-size:21px;
        font-weight:700;
        line-height:1;
        color:#20161A;
    }

    .ud-other-icon{
        margin-top:7px;
        color:#A67D18;
    }

    .ud-other-empty{
        grid-column:1 / -1;
        padding:16px 12px;
        background:#fff;
        border:1px dashed #E8E0DF;
        border-radius:12px;
        text-align:center;
        color:#9A8D91;
        font-size:10.5px;
    }


    /* =========================================================
       PREVIEW
       ========================================================= */

    .ud-preview-card{
        margin-top:20px;
        background:#fff;
        border:1px solid #E8E0DF;
        border-radius:17px;
        overflow:hidden;
        box-shadow:0 12px 28px -24px rgba(58,4,16,.25);
    }

    .ud-preview-head{
        padding:17px 18px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:14px;
    }

    .ud-preview-title{
        font-size:15px;
        font-weight:800;
        color:#20161A;
    }

    .ud-preview-subtitle{
        margin-top:4px;
        color:#8A7D81;
        font-size:10.8px;
    }

    .ud-preview-total{
        flex:none;
        padding:7px 10px;
        border-radius:9px;
        background:#FFF3F5;
        color:#A42A40;
        font-size:10px;
        font-weight:800;
    }

    .ud-table-wrap{
        width:100%;
        max-height:560px;
        overflow-x:auto;
        overflow-y:auto;
        overscroll-behavior:contain;
        border-top:1px solid #EEE7E6;
    }

    .ud-table{
        width:100%;
        min-width:1060px;
        border-collapse:collapse;
        table-layout:fixed;
    }

    .ud-table thead tr{
        background:#FAFBFC;
    }

    .ud-table th{
        position:sticky;
        top:0;
        z-index:2;
        padding:11px 12px;
        text-align:left;
        color:#707075;
        font-size:9.3px;
        font-weight:800;
        white-space:nowrap;
        border-bottom:1px solid #EEE7E6;
    }

    .ud-table td{
        padding:12px;
        color:#463B40;
        font-size:10.5px;
        border-bottom:1px solid #F0EBEA;
        vertical-align:middle;
    }

    .ud-table tbody tr:last-child td{
        border-bottom:none;
    }

    .ud-table tbody tr:hover{
        background:#FCFAFA;
    }

    .ud-table th:nth-child(1),
    .ud-table td:nth-child(1){
        width:46px;
    }

    .ud-table th:nth-child(2),
    .ud-table td:nth-child(2){
        width:145px;
    }

    .ud-table th:nth-child(3),
    .ud-table td:nth-child(3){
        width:255px;
    }

    .ud-table th:nth-child(4),
    .ud-table td:nth-child(4){
        width:65px;
    }

    .ud-table th:nth-child(5),
    .ud-table td:nth-child(5){
        width:92px;
    }

    .ud-table th:nth-child(6),
    .ud-table td:nth-child(6){
        width:90px;
    }

    .ud-table th:nth-child(7),
    .ud-table td:nth-child(7){
        width:145px;
    }

    .ud-table th:nth-child(8),
    .ud-table td:nth-child(8){
        width:165px;
    }

    .ud-table th:nth-child(9),
    .ud-table td:nth-child(9){
        width:95px;
    }

    .ud-cell-clip{
        max-width:100%;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }

    .ud-status-pill{
        display:inline-flex;
        align-items:center;
        max-width:145px;
        padding:7px 9px;
        border-radius:999px;
        background:#F4F5F6;
        color:#4D4E52;
        font-size:9px;
        font-weight:700;
        line-height:1.25;
    }

    .ud-resolved-pill{
        display:inline-flex;
        align-items:center;
        padding:6px 9px;
        border-radius:999px;
        background:#EBFAF1;
        color:#27935A;
        font-size:9px;
        font-weight:800;
    }

    .ud-eskalasi-pill{
        display:inline-flex;
        align-items:center;
        padding:6px 9px;
        border-radius:999px;
        background:#FFF3E4;
        color:#BD6A15;
        font-size:9px;
        font-weight:800;
    }

    .ud-sto-pill{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:38px;
        padding:5px 7px;
        border-radius:7px;
        background:#F7F8F9;
        color:#52565B;
        font-size:9px;
        font-weight:800;
    }

    .ud-no-data{
        padding:28px 16px !important;
        text-align:center !important;
        color:#9A8D91 !important;
    }


    /* =========================================================
       RESPONSIVE
       ========================================================= */

    @media(max-width:1100px){

        .ud-main-grid{
            grid-template-columns:1fr;
        }

        .ud-right-col{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:10px;
            align-items:start;
        }

        .ud-summary-title,
        .ud-summary-section-title{
            grid-column:1 / -1;
        }

        .ud-summary-card{
            margin-top:0;
        }

    }


    @media(max-width:700px){

        .ud-wrap{
            padding-left:0;
            padding-right:0;
        }

        .ud-page-head{
            flex-direction:column;
        }

        .ud-reset-btn{
            align-self:flex-end;
        }

        .ud-right-col{
            display:block;
        }

        .ud-summary-card{
            margin-top:10px;
        }

        .ud-stat-grid{
            grid-template-columns:1fr 1fr;
        }

        .ud-preview-head{
            align-items:flex-start;
            flex-direction:column;
        }

    }



  /* FULL PAGE LOADING LOCK — hanya halaman ini, tanpa menyentuh dashboard/sidebar */
  html.udLoadingLock, body.udLoadingLock{
    overflow:hidden !important;
    cursor:none !important;
  }

  body.udLoadingLock *,
  html.udLoadingLock *{
    cursor:none !important;
  }

  .ud-wrap.udLoadingLock{
    overflow:hidden !important;
    cursor:none !important;
  }

  .ud-page-loading.is-hidden{
    cursor:none !important;
  }

</style>


{{-- =========================================================
     SHEETJS
     Dipakai hanya untuk membaca file di browser sebelum submit.
     ========================================================= --}}
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>


<script>

(function () {

    'use strict';

    // ============================================================
    // LOADING KHUSUS UPLOAD DATA
    // Sama pola dengan Edit Data: loading dijalankan paling awal.
    // ============================================================
    // FULL LOADING LOCK — seperti Edit Data: seluruh area halaman
    // tertutup, scrollbar dikunci, dan cursor disembunyikan 1 detik.
    // ============================================================
    (function () {
        const pageLoading = document.getElementById('udPageLoading');
        const pageWrap = document.querySelector('.ud-wrap');
        const lockClass = 'udLoadingLock';
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
        }, 180);
    })();

    /* =========================================================
       ELEMENTS
       ========================================================= */

    const form = document.getElementById('udForm');

    const dropzone = document.getElementById('udDropzone');

    const fileInput = document.getElementById('udFileInput');

    const dzDefault = document.getElementById('udDzDefault');

    const dzSelected = document.getElementById('udDzSelected');

    const fileNameEl = document.getElementById('udFileName');

    const fileSizeEl = document.getElementById('udFileSize');

    const removeBtn = document.getElementById('udRemoveBtn');

    const resetBtn = document.getElementById('udResetBtn');

    const submitBtn = document.getElementById('udSubmitBtn');

    const submitLabel = document.getElementById('udSubmitLabel');

    const uploadNote = document.getElementById('udUploadNote');

    const previewCard = document.getElementById('udPreviewCard');

    const previewBody = document.getElementById('udPreviewBody');

    const previewTotal = document.getElementById('udPreviewTotal');

    const previewSubtitle = document.querySelector('.ud-preview-subtitle');

    const totalDataEl = document.getElementById('udTotalData');

    const resolvedEl = document.getElementById('udResolved');

    const eskalasiEl = document.getElementById('udEskalasi');

    const stoCountEl = document.getElementById('udStoCount');

    const picListEl = document.getElementById('udPicList');

    const picFootEl = document.getElementById('udPicFoot');

    const otherStatusGridEl = document.getElementById('udOtherStatusGrid');

    const otherStatusCountEl = document.getElementById('udOtherStatusCount');

    const uiAlertOverlay = document.getElementById('udUiAlertOverlay');
    const uiAlertIcon = document.getElementById('udUiAlertIcon');
    const uiAlertTitle = document.getElementById('udUiAlertTitle');
    const uiAlertMessage = document.getElementById('udUiAlertMessage');
    const uiAlertActions = document.getElementById('udUiAlertActions');
    const uiAlertCancel = document.getElementById('udUiAlertCancel');
    const uiAlertConfirm = document.getElementById('udUiAlertConfirm');

    let uiAlertResolver = null;


    let selectedFile = null;


    /* =========================================================
       UI ALERT / CONFIRM
       ========================================================= */

    if (
        uiAlertOverlay &&
        uiAlertOverlay.parentElement !== document.body
    ) {
        document.body.appendChild(uiAlertOverlay);
    }


    function uiIcon(type) {

        if (type === 'success') {

            return `
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.4"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M5 12.5 9.2 17 19 7"/>
                </svg>
            `;

        }


        if (type === 'error') {

            return `
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                >
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8v5"/>
                    <path d="M12 16.5h.01"/>
                </svg>
            `;

        }


        return `
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M6 8.5V6a3 3 0 0 1 6 0v2.5"/>
                <path d="M5 8.5h14l-1 11H6l-1-11Z"/>
                <path d="M9 11.5v4"/>
                <path d="M15 11.5v4"/>
            </svg>
        `;

    }


    function openUiAlert({
        type = 'error',
        title = 'Informasi',
        message = '',
        confirmText = 'OK',
        cancelText = 'Batal',
        showCancel = false
    }) {

        return new Promise(function (resolve) {

            uiAlertResolver = resolve;

            uiAlertIcon.className =
                `ud-ui-alert-icon ${type}`;

            uiAlertIcon.innerHTML =
                uiIcon(type);

            uiAlertTitle.textContent =
                title;

            uiAlertMessage.innerHTML =
                message;

            uiAlertCancel.textContent =
                cancelText;

            uiAlertConfirm.textContent =
                confirmText;

            uiAlertCancel.style.display =
                showCancel ? '' : 'none';

            uiAlertActions.classList.toggle(
                'single',
                !showCancel
            );

            uiAlertConfirm.classList.toggle(
                'success-btn',
                type === 'success'
            );

            document.documentElement.classList.add(
                'ud-ui-lock'
            );

            document.body.classList.add(
                'ud-ui-lock'
            );

            uiAlertOverlay.classList.add(
                'open'
            );

            uiAlertOverlay.setAttribute(
                'aria-hidden',
                'false'
            );

            setTimeout(function () {
                uiAlertConfirm.focus();
            }, 20);

        });

    }


    function closeUiAlert(value) {

        uiAlertOverlay.classList.remove('open');

        uiAlertOverlay.setAttribute(
            'aria-hidden',
            'true'
        );

        document.documentElement.classList.remove(
            'ud-ui-lock'
        );

        document.body.classList.remove(
            'ud-ui-lock'
        );


        if (uiAlertResolver) {

            const resolve =
                uiAlertResolver;

            uiAlertResolver =
                null;

            resolve(value);

        }

    }


    function showUiAlert(options) {

        return openUiAlert({
            ...options,
            showCancel:false
        });

    }


    function showUiConfirm(options) {

        return openUiAlert({
            ...options,
            showCancel:true,
            type:'error'
        });

    }


    uiAlertConfirm.addEventListener(
        'click',
        function () {
            closeUiAlert(true);
        }
    );


    uiAlertCancel.addEventListener(
        'click',
        function () {
            closeUiAlert(false);
        }
    );


    uiAlertOverlay.addEventListener(
        'click',
        function (event) {

            if (
                event.target ===
                uiAlertOverlay
            ) {
                closeUiAlert(false);
            }

        }
    );


    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape' &&
                uiAlertOverlay.classList.contains('open')
            ) {
                closeUiAlert(false);
            }

        }
    );


    /* =========================================================
       HELPERS
       ========================================================= */

    function escapeHtml(value) {

        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

    }


    function cleanValue(value) {

        if (value === null || value === undefined) {
            return '';
        }

        if (typeof value === 'number') {
            return String(value);
        }

        return String(value).trim();

    }


    function normalizeText(value) {

        return cleanValue(value)
            .toLowerCase()
            .trim();

    }


    function formatSize(bytes) {

        if (!bytes) {
            return '0 B';
        }

        if (bytes < 1024) {
            return bytes + ' B';
        }

        if (bytes < 1024 * 1024) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }

        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';

    }


    function excelSerialToDate(serial) {

        const excelEpoch = new Date(Date.UTC(1899, 11, 30));

        const milliseconds = Number(serial) * 86400000;

        return new Date(excelEpoch.getTime() + milliseconds);

    }


    function formatDate(value) {

        if (
            value === null ||
            value === undefined ||
            value === ''
        ) {
            return '-';
        }


        let date = null;


        if (value instanceof Date) {

            date = value;

        } else if (
            typeof value === 'number' &&
            value > 20000 &&
            value < 70000
        ) {

            date = excelSerialToDate(value);

        } else {

            const text = String(value).trim();

            if (/^\d{4}-\d{1,2}-\d{1,2}$/.test(text)) {

                const parts = text.split('-');

                date = new Date(
                    Number(parts[0]),
                    Number(parts[1]) - 1,
                    Number(parts[2])
                );

            } else if (
                /^\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}$/.test(text)
            ) {

                const parts = text.split(/[\/-]/);

                let day = Number(parts[0]);

                let month = Number(parts[1]);

                let year = Number(parts[2]);

                if (year < 100) {
                    year += 2000;
                }

                date = new Date(
                    year,
                    month - 1,
                    day
                );

            } else {

                const parsed = new Date(text);

                if (!isNaN(parsed.getTime())) {
                    date = parsed;
                }

            }

        }


        if (!date || isNaN(date.getTime())) {

            return cleanValue(value) || '-';

        }


        const day = String(date.getDate()).padStart(2, '0');

        const month = String(date.getMonth() + 1).padStart(2, '0');

        const year = date.getFullYear();


        return `${year}-${month}-${day}`;

    }


    function resetSummary() {

        totalDataEl.textContent = '0';

        resolvedEl.textContent = '0';

        eskalasiEl.textContent = '0';

        stoCountEl.textContent = '0';


        picListEl.innerHTML = `
            <span class="ud-empty-summary">
                Belum ada data
            </span>
        `;


        picFootEl.textContent = '0 PIC ditemukan';


        otherStatusGridEl.innerHTML = `
            <div class="ud-other-empty">
                Belum ada status lainnya
            </div>
        `;


        otherStatusCountEl.textContent = '0 status';


        previewBody.innerHTML = '';

        previewTotal.textContent = '0 Total Data';

        previewCard.style.display = 'none';

    }


    /* =========================================================
       STATUS
       ========================================================= */

    function isResolved(value) {

        return normalizeText(value) === 'resolved';

    }


    function isEskalasi(value) {

        const text = normalizeText(value);

        return text === 'eskalasi' ||
               text.includes('eskalasi');

    }


    /* =========================================================
       BUILD SUMMARY
       ========================================================= */

    function buildSummary(rows) {

        const total = rows.length;

        let resolved = 0;

        let eskalasi = 0;


        const stoSet = new Set();

        const picSet = new Set();

        const otherStatuses = new Map();


        rows.forEach(function (row) {

            const orderId = cleanValue(row[0]);

            const sto = cleanValue(row[2]);

            const pic = cleanValue(row[4]);

            const resolvedStatus = cleanValue(row[5]);

            const detailStatus = cleanValue(row[6]);


            if (!orderId) {
                return;
            }


            if (isResolved(resolvedStatus)) {
                resolved++;
            }


            if (isEskalasi(resolvedStatus)) {
                eskalasi++;
            }


            if (sto) {
                stoSet.add(sto.toUpperCase());
            }


            if (pic) {
                picSet.add(pic);
            }


            if (detailStatus) {

                const normalized = normalizeText(detailStatus);

                const standardCompleted =
                    normalized === 'completed';

                const standardProcess =
                    normalized.includes('process oss');


                if (
                    !standardCompleted &&
                    !standardProcess
                ) {

                    const existing = otherStatuses.get(detailStatus) || 0;

                    otherStatuses.set(
                        detailStatus,
                        existing + 1
                    );

                }

            }

        });


        totalDataEl.textContent = total.toLocaleString('id-ID');

        resolvedEl.textContent = resolved.toLocaleString('id-ID');

        eskalasiEl.textContent = eskalasi.toLocaleString('id-ID');

        stoCountEl.textContent = stoSet.size.toLocaleString('id-ID');


        /* =====================================================
           PIC
           ===================================================== */

        const picArray = Array.from(picSet);

        picArray.sort(function (a, b) {

            return a.localeCompare(
                b,
                'id',
                {
                    sensitivity:'base'
                }
            );

        });


        if (picArray.length === 0) {

            picListEl.innerHTML = `
                <span class="ud-empty-summary">
                    Belum ada data
                </span>
            `;

        } else {

            picListEl.innerHTML = picArray
                .slice(0, 20)
                .map(function (pic) {

                    return `
                        <span class="ud-pic-chip">
                            ${escapeHtml(pic)}
                        </span>
                    `;

                })
                .join('');

        }


        picFootEl.textContent =
            picArray.length.toLocaleString('id-ID') +
            ' PIC ditemukan';


        /* =====================================================
           STATUS LAINNYA
           ===================================================== */

        const statusArray =
            Array.from(otherStatuses.entries());


        statusArray.sort(function (a, b) {

            return b[1] - a[1];

        });


        const totalOtherStatuses =
            statusArray.length;


        otherStatusCountEl.textContent =
            totalOtherStatuses.toLocaleString('id-ID') +
            ' status';


        if (statusArray.length === 0) {

            otherStatusGridEl.innerHTML = `
                <div class="ud-other-empty">
                    Tidak ada status lainnya
                </div>
            `;

        } else {

            otherStatusGridEl.innerHTML =
                statusArray
                    .map(function (item) {

                        const label = item[0];

                        const count = item[1];

                        return `
                            <div class="ud-other-card">

                                <div class="ud-other-label">
                                    ${escapeHtml(label)}
                                </div>

                                <div class="ud-other-value">
                                    ${count.toLocaleString('id-ID')}
                                </div>

                                <div class="ud-other-icon">
                                    <svg
                                        width="16"
                                        height="16"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                    >
                                        <path
                                            d="M12 8v5"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                        />
                                        <circle
                                            cx="12"
                                            cy="16"
                                            r=".9"
                                            fill="currentColor"
                                        />
                                    </svg>
                                </div>

                            </div>
                        `;

                    })
                    .join('');

        }

    }


    /* =========================================================
       PREVIEW
       ========================================================= */

    function buildPreview(rows) {

        const validRows = rows.filter(function (row) {

            return cleanValue(row[0]) !== '';

        });


        const PREVIEW_LIMIT = 100;

        // Jangan render ribuan baris sekaligus ke DOM.
        // File asli tetap dikirim utuh ke server saat tombol Upload ditekan.
        const previewRows = validRows.slice(0, PREVIEW_LIMIT);


        previewTotal.textContent =
            validRows.length.toLocaleString('id-ID') +
            ' Total Data';

        if (previewSubtitle) {
            previewSubtitle.textContent = validRows.length > PREVIEW_LIMIT
                ? `Menampilkan ${PREVIEW_LIMIT.toLocaleString('id-ID')} baris pertama dari ${validRows.length.toLocaleString('id-ID')} data. Seluruh data tetap akan diupload.`
                : `Menampilkan seluruh ${validRows.length.toLocaleString('id-ID')} data.`;
        }


        if (previewRows.length === 0) {

            previewBody.innerHTML = `
                <tr>
                    <td
                        colspan="9"
                        class="ud-no-data"
                    >
                        Tidak ada data valid untuk ditampilkan.
                    </td>
                </tr>
            `;

            previewCard.style.display = 'block';

            return;

        }


        previewBody.innerHTML =
            previewRows.map(function (row, index) {

                const orderId =
                    cleanValue(row[0]) || '-';

                const deskripsi =
                    cleanValue(row[1]) || '-';

                const sto =
                    cleanValue(row[2]) || '-';

                const tanggal =
                    formatDate(row[3]);

                const pic =
                    cleanValue(row[4]) || '-';

                const resolved =
                    cleanValue(row[5]) || '-';

                const status =
                    cleanValue(row[6]) || '-';

                const ket =
                    cleanValue(row[7]) || '-';


                const resolvedNormalized =
                    normalizeText(resolved);


                let resolvedHtml = `
                    <span class="ud-resolved-pill">
                        ${escapeHtml(resolved)}
                    </span>
                `;


                if (
                    resolvedNormalized.includes('eskalasi')
                ) {

                    resolvedHtml = `
                        <span class="ud-eskalasi-pill">
                            ${escapeHtml(resolved)}
                        </span>
                    `;

                }


                return `
                    <tr>

                        <td>
                            ${index + 1}
                        </td>

                        <td>
                            <div
                                class="ud-cell-clip"
                                title="${escapeHtml(orderId)}"
                            >
                                ${escapeHtml(orderId)}
                            </div>
                        </td>

                        <td>
                            <div
                                class="ud-cell-clip"
                                title="${escapeHtml(deskripsi)}"
                            >
                                ${escapeHtml(deskripsi)}
                            </div>
                        </td>

                        <td>
                            <span class="ud-sto-pill">
                                ${escapeHtml(sto)}
                            </span>
                        </td>

                        <td>
                            ${escapeHtml(tanggal)}
                        </td>

                        <td>
                            <div
                                class="ud-cell-clip"
                                title="${escapeHtml(pic)}"
                            >
                                ${escapeHtml(pic)}
                            </div>
                        </td>

                        <td>
                            ${resolvedHtml}
                        </td>

                        <td>
                            <span
                                class="ud-status-pill"
                                title="${escapeHtml(status)}"
                            >
                                ${escapeHtml(status)}
                            </span>
                        </td>

                        <td>
                            <div
                                class="ud-cell-clip"
                                title="${escapeHtml(ket)}"
                            >
                                ${escapeHtml(ket)}
                            </div>
                        </td>

                    </tr>
                `;

            }).join('');


        previewCard.style.display = 'block';

    }


    /* =========================================================
       READ EXCEL
       ========================================================= */

    function readExcel(file) {

        if (
            typeof XLSX === 'undefined'
        ) {

            showUiAlert({
                type:'error',
                title:'Library Excel Tidak Dimuat',
                message:'Library Excel belum berhasil dimuat. Periksa koneksi internet lalu refresh halaman.',
                confirmText:'OK'
            });

            return;

        }


        const reader = new FileReader();


        reader.onload = function (event) {

            try {

                const data = new Uint8Array(
                    event.target.result
                );


                const workbook =
                    XLSX.read(
                        data,
                        {
                            type:'array',
                            cellDates:true,
                            dense:true
                        }
                    );


                /* =================================================
                   SHEET ALL WAJIB
                   ================================================= */

                let sheetName = 'ALL';


                if (
                    !workbook.SheetNames.includes('ALL')
                ) {

                    showUiAlert({
                        type:'error',
                        title:'Sheet ALL Tidak Ditemukan',
                        message:'Sheet "ALL" tidak ditemukan. File harus memiliki sheet ALL.',
                        confirmText:'OK'
                    });

                    resetDropzone();

                    return;

                }


                const worksheet =
                    workbook.Sheets[sheetName];


                const rows =
                    XLSX.utils.sheet_to_json(
                        worksheet,
                        {
                            header:1,
                            defval:'',
                            raw:true,
                            blankrows:false
                        }
                    );


                if (
                    !rows ||
                    rows.length < 2
                ) {

                    showUiAlert({
                        type:'error',
                        title:'Data Tidak Ditemukan',
                        message:'Sheet ALL belum memiliki data. Minimal harus ada header dan 1 baris data.',
                        confirmText:'OK'
                    });

                    resetDropzone();

                    return;

                }


                /* =================================================
                   DATA DIMULAI DARI BARIS 2
                   ================================================= */

                const dataRows =
                    rows
                        .slice(1)
                        .filter(function (row) {

                            return row.some(function (cell) {

                                return cleanValue(cell) !== '';

                            });

                        });


                buildSummary(dataRows);

                buildPreview(dataRows);


                submitBtn.disabled =
                    dataRows.length === 0;


                if (dataRows.length > 0) {

                    submitLabel.textContent =
                        'Upload ' +
                        dataRows.length.toLocaleString('id-ID') +
                        ' Data';

                    uploadNote.textContent =
                        'Data akan disimpan ke batch cabang ' +
                        '{{ $witelSlug }}.';

                }


            } catch (error) {

                console.error(error);

                showUiAlert({
                    type:'error',
                    title:'Gagal Membaca File',
                    message:'Gagal membaca file Excel. Pastikan file tidak rusak.',
                    confirmText:'OK'
                });

                resetDropzone();

            }

        };


        reader.onerror = function () {

            showUiAlert({
                type:'error',
                title:'File Tidak Dapat Dibaca',
                message:'File tidak dapat dibaca oleh browser.',
                confirmText:'OK'
            });

            resetDropzone();

        };


        reader.readAsArrayBuffer(file);

    }


    /* =========================================================
       SELECTED FILE
       ========================================================= */

    function showSelectedFile(file) {

        selectedFile = file;


        fileNameEl.textContent =
            file.name;

        fileSizeEl.textContent =
            formatSize(file.size);


        dzDefault.style.display =
            'none';

        dzSelected.style.display =
            'block';


        dropzone.classList.add(
            'ud-has-file'
        );


        submitBtn.disabled = true;

        submitLabel.textContent =
            'Membaca Data...';


        uploadNote.textContent =
            'Sedang membaca isi file...';


        readExcel(file);

    }


    /* =========================================================
       RESET
       ========================================================= */

    function resetDropzone() {

        selectedFile = null;

        fileInput.value = '';


        dzDefault.style.display =
            'block';

        dzSelected.style.display =
            'none';


        dropzone.classList.remove(
            'ud-has-file'
        );


        submitBtn.disabled = true;

        submitLabel.textContent =
            'Upload Data';


        uploadNote.textContent =
            'Data akan disimpan ke batch cabang {{ $witelSlug }}.';


        resetSummary();

    }


    /* =========================================================
       CLICK DROPZONE
       ========================================================= */

    dropzone.addEventListener(
        'click',
        function (event) {

            if (
                event.target.closest(
                    '#udRemoveBtn'
                )
            ) {
                return;
            }


            fileInput.click();

        }
    );


    /* =========================================================
       INPUT FILE
       ========================================================= */

    fileInput.addEventListener(
        'change',
        function () {

            if (
                fileInput.files &&
                fileInput.files.length > 0
            ) {

                showSelectedFile(
                    fileInput.files[0]
                );

            }

        }
    );


    /* =========================================================
       DRAG
       ========================================================= */

    ['dragenter', 'dragover'].forEach(
        function (eventName) {

            dropzone.addEventListener(
                eventName,
                function (event) {

                    event.preventDefault();

                    event.stopPropagation();


                    dropzone.classList.add(
                        'ud-dragover'
                    );

                }
            );

        }
    );


    ['dragleave', 'drop'].forEach(
        function (eventName) {

            dropzone.addEventListener(
                eventName,
                function (event) {

                    event.preventDefault();

                    event.stopPropagation();


                    dropzone.classList.remove(
                        'ud-dragover'
                    );

                }
            );

        }
    );


    /* =========================================================
       DROP
       ========================================================= */

    dropzone.addEventListener(
        'drop',
        function (event) {

            const files =
                event.dataTransfer.files;


            if (
                !files ||
                files.length === 0
            ) {
                return;
            }


            const file = files[0];


            const allowedExtensions =
                ['xlsx', 'xls', 'csv'];


            const extension =
                file.name
                    .split('.')
                    .pop()
                    .toLowerCase();


            if (
                !allowedExtensions.includes(
                    extension
                )
            ) {

                showUiAlert({
                    type:'error',
                    title:'Format File Tidak Sesuai',
                    message:'Format file harus XLSX, XLS, atau CSV.',
                    confirmText:'OK'
                });

                return;

            }


            /*
             * Masukkan file hasil drop
             * ke input form memakai DataTransfer.
             */

            try {

                const dataTransfer =
                    new DataTransfer();

                dataTransfer.items.add(
                    file
                );

                fileInput.files =
                    dataTransfer.files;

            } catch (error) {

                console.warn(
                    'DataTransfer tidak tersedia.',
                    error
                );

            }


            showSelectedFile(file);

        }
    );


    /* =========================================================
       REMOVE FILE
       ========================================================= */

    removeBtn.addEventListener(
        'click',
        function (event) {

            event.preventDefault();

            event.stopPropagation();

            resetDropzone();

        }
    );


    /* =========================================================
       RESET BUTTON
       ========================================================= */

    resetBtn.addEventListener(
        'click',
        function () {

            resetDropzone();

        }
    );


    /* =========================================================
       BEFORE SUBMIT
       ========================================================= */

    form.addEventListener(
        'submit',
        async function (event) {

            if (
                !selectedFile ||
                !fileInput.files ||
                fileInput.files.length === 0
            ) {

                event.preventDefault();

                showUiAlert({
                    type:'error',
                    title:'File Belum Dipilih',
                    message:'Pilih file Excel terlebih dahulu.',
                    confirmText:'OK'
                });

                return;

            }


            /*
             * Konfirmasi upload menggunakan modal seperti
             * konfirmasi Hapus Data di Edit Data.
             */
            event.preventDefault();


            const confirmed =
                await showUiConfirm({
                    title:'Upload Data?',
                    message:
                        `File <strong>${escapeHtml(selectedFile.name)}</strong> akan diproses dan seluruh data yang tampil di Preview Data akan disimpan ke batch cabang <strong>{{ $witelSlug }}</strong>.`,
                    confirmText:'Upload',
                    cancelText:'Batal'
                });


            if (!confirmed) {
                return;
            }


            /*
             * Setelah dikonfirmasi, baru kirim form
             * ke route upload-data.store yang sudah ada.
             */
            submitBtn.disabled = true;

            submitLabel.textContent =
                'Mengupload...';

            uploadNote.textContent =
                'Mohon tunggu, data sedang diproses.';


            HTMLFormElement.prototype.submit.call(form);

        }
    );


    /* =========================================================
       HASIL UPLOAD DARI SERVER
       Jika redirect kembali membawa session status/error,
       tampilkan sebagai modal, bukan banner.
       ========================================================= */

    const serverSuccessMessage =
        @json(session('status'));


    const serverErrors =
        @json($errors->all());


    if (
        serverSuccessMessage ||
        (
            Array.isArray(serverErrors) &&
            serverErrors.length > 0
        )
    ) {

        setTimeout(function () {

            if (serverSuccessMessage) {

                showUiAlert({
                    type:'success',
                    title:'Upload Berhasil',
                    message:
                        escapeHtml(serverSuccessMessage),
                    confirmText:'OK'
                });

            } else {

                showUiAlert({
                    type:'error',
                    title:'Upload Gagal',
                    message:
                        serverErrors
                            .map(function (error) {
                                return `<div>${escapeHtml(error)}</div>`;
                            })
                            .join(''),
                    confirmText:'OK'
                });

            }

        }, 120);

    }


    /* =========================================================
       INIT
       ========================================================= */

    resetSummary();

})();

</script>
