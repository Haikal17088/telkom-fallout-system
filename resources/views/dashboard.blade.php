<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width"
    >

    <title>
        Telkom Fallout System — Dashboard
    </title>


    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <style>

        :root{
            --maroon-0:#3A0410;
            --maroon-1:#5C0A1B;
            --maroon-2:#8A0F26;
            --red:#C8102E;
            --gold:#D9A441;
            --paper:#FBF9F7;
            --ink:#20161A;
            --ink-lo:#7A6B6F;
            --line:#E7DEDD;

            --glass-bg:rgba(255,255,255,0.08);
            --glass-bg-strong:rgba(255,255,255,0.14);
            --glass-border:rgba(255,255,255,0.14);
        }


        *{
            box-sizing:border-box;
            margin:0;
            padding:0;
        }


        html,
        body{
            height:100%;
            min-width:1180px;
        }


        body{
            font-family:'Inter', sans-serif;
            color:var(--ink);
            min-height:100vh;

            background:
                radial-gradient(
                    60% 50% at 8% 8%,
                    rgba(200,16,46,0.16),
                    transparent 60%
                ),
                radial-gradient(
                    55% 45% at 95% 12%,
                    rgba(217,164,65,0.14),
                    transparent 60%
                ),
                radial-gradient(
                    70% 60% at 50% 100%,
                    rgba(138,15,38,0.12),
                    transparent 60%
                ),
                #F4F0EE;

            padding:18px;

            display:flex;

            gap:18px;
        }



        /* =====================================================
           SIDEBAR
           ===================================================== */

        .sidebar{

            width:300px;

            flex:none;

            border-radius:22px;

            padding:22px 16px;

            background:
                linear-gradient(
                    165deg,
                    rgba(90,10,27,0.92) 0%,
                    rgba(58,4,16,0.95) 65%,
                    rgba(40,3,11,0.97) 100%
                );

            backdrop-filter:blur(22px);

            -webkit-backdrop-filter:blur(22px);

            border:
                1px solid
                rgba(255,255,255,0.08);

            box-shadow:
                0 30px 70px -24px rgba(58,4,16,0.55),
                inset 0 1px 0 rgba(255,255,255,0.06);

            display:flex;

            flex-direction:column;

            max-height:
                calc(100vh - 36px);

            overflow-y:auto;
            overflow-x:hidden;

            transition:
                width 0.28s
                cubic-bezier(.4,0,.2,1);

            position:relative;

            z-index:20;
        }


        /* =====================================================
           SIDEBAR COLLAPSED
           ===================================================== */

        .sidebar.collapsed{
            width:82px;
        }


        .sidebar.collapsed:hover{

            width:300px;

            box-shadow:
                0 30px 80px -20px rgba(58,4,16,0.7),
                inset 0 1px 0 rgba(255,255,255,0.06);
        }


        /*
         * SEMUA TEKS disembunyikan saat sidebar tertutup.
         * ADMINISTRASI juga masuk di sini.
         */

        .sidebar.collapsed:not(:hover) .brand-text,
        .sidebar.collapsed:not(:hover) .witel-label,
        .sidebar.collapsed:not(:hover) .chev,
        .sidebar.collapsed:not(:hover) .nav-text,
        .sidebar.collapsed:not(:hover) .nav-label,
        .sidebar.collapsed:not(:hover) .logout-text,
        .sidebar.collapsed:not(:hover) .witel-body{
            display:none;
        }


        /*
         * Brand tetap berada di tengah
         */

        .sidebar.collapsed:not(:hover) .brand-row{
            justify-content:center;

            padding-left:0;
            padding-right:0;
        }


        /*
         * Tombol Witel tetap berada di tengah
         */

        .sidebar.collapsed:not(:hover) .witel-head,
        .sidebar.collapsed:not(:hover) .logout-btn{

            justify-content:center;

            padding-left:0;
            padding-right:0;
        }



        /* =====================================================
           ADMIN MENU SAAT COLLAPSED
           ===================================================== */

        .sidebar.collapsed:not(:hover) .admin-nav-group{

            margin-top:16px;

        }


        .sidebar.collapsed:not(:hover) .admin-nav-item{

            justify-content:center;

            padding-left:0;
            padding-right:0;

            gap:0;

            min-height:40px;
        }


        .sidebar.collapsed:not(:hover) .admin-nav-item svg{

            margin:0;
        }



        /* Scrollbar */

        .sidebar::-webkit-scrollbar{
            width:6px;
        }


        .sidebar::-webkit-scrollbar-thumb{

            background:
                rgba(255,255,255,0.14);

            border-radius:10px;
        }



        /* =====================================================
           BRAND
           ===================================================== */

        .brand-row{

            display:flex;

            align-items:center;

            gap:11px;

            padding:
                6px
                8px
                20px;
        }


        .brand-mark{

            width:38px;
            height:38px;

            border-radius:10px;

            background:
                linear-gradient(
                    145deg,
                    var(--red),
                    #8A0F26
                );

            display:grid;

            place-items:center;

            color:#fff;

            font-family:
                'Space Grotesk',
                sans-serif;

            font-weight:700;

            font-size:14px;

            box-shadow:
                0 8px 18px -6px
                rgba(200,16,46,0.7);
        }


        .brand-logo-img{
            width:100%;
            height:100%;
            display:block;
            object-fit:contain;
            border-radius:10px;
        }


        .brand-text .t1{

            color:#fff;

            font-family:
                'Space Grotesk',
                sans-serif;

            font-weight:600;

            font-size:14.5px;

            line-height:1.25;
        }


        .brand-text .t2{

            color:
                rgba(251,222,224,0.65);

            font-size:11px;

            margin-top:1px;
        }



        /* =====================================================
           WITEL
           ===================================================== */

        .dashboard-home-link{
            width:100%;
            margin:0 0 4px;
        }

        .dashboard-branch-label{
            margin-top:5px;
        }

        .witel-list{

            display:flex;

            flex-direction:column;

            gap:8px;

            margin-top:4px;
        }


        .witel-head{

            width:100%;

            display:flex;

            align-items:center;

            gap:10px;

            padding:
                12px
                12px;

            border-radius:14px;

            border:
                1px solid transparent;

            background:
                var(--glass-bg);

            color:
                rgba(255,226,227,0.9);

            font-size:13.5px;

            font-weight:600;

            cursor:pointer;

            transition:
                background 0.18s ease,
                border-color 0.18s ease,
                transform 0.12s ease;
        }


        .witel-head:hover{

            background:
                var(--glass-bg-strong);
        }


        .witel-head:active{

            transform:scale(0.99);
        }


        .witel-branch.open > .witel-head{

            background:
                linear-gradient(
                    120deg,
                    rgba(200,16,46,0.35),
                    rgba(217,164,65,0.14)
                );

            border-color:
                rgba(255,255,255,0.18);

            color:#fff;
        }


        .witel-icon{

            width:26px;
            height:26px;

            flex:none;

            border-radius:8px;

            background:
                rgba(255,255,255,0.08);

            display:grid;

            place-items:center;
        }


        .witel-branch.open .witel-icon{

            background:
                rgba(255,255,255,0.18);
        }


        .witel-head .chev{

            margin-left:auto;

            transition:
                transform 0.2s ease;

            opacity:0.75;
        }


        .witel-branch.open
        .witel-head
        .chev{

            transform:
                rotate(90deg);
        }


        .witel-body{

            display:none;

            padding:
                12px
                6px
                4px
                14px;

            margin-top:2px;

            border-left:
                1.5px solid
                rgba(255,255,255,0.12);

            margin-left:16px;
        }


        .witel-branch.open
        .witel-body{

            display:block;
        }



        /* =====================================================
           NAVIGATION
           ===================================================== */

        .nav-group + .nav-group{

            margin-top:16px;
        }


        .nav-label{

            font-size:10px;

            font-weight:700;

            letter-spacing:0.08em;

            color:
                rgba(255,200,201,0.55);

            padding:
                0
                8px;

            margin-bottom:8px;
        }


        .nav-items{

            display:flex;

            flex-direction:column;

            gap:5px;
        }


        .nav-item{

            display:flex;

            align-items:center;

            gap:10px;

            padding:
                9px
                10px;

            border-radius:10px;

            font-size:12.8px;

            font-weight:600;

            color:
                rgba(255,231,232,0.78);

            text-decoration:none;

            transition:
                background 0.15s ease,
                color 0.15s ease,
                transform 0.12s ease;
        }


        .nav-item:hover{

            background:
                rgba(255,255,255,0.08);

            color:#fff;
        }


        .nav-item:active{

            transform:scale(0.985);
        }


        .nav-item.active{

            background:
                linear-gradient(
                    120deg,
                    var(--red),
                    #A00E26
                );

            color:#fff;

            box-shadow:
                0 10px 22px -10px
                rgba(200,16,46,0.7);
        }


        .nav-item svg{

            flex:none;

            opacity:0.9;
        }



        /* =====================================================
           ADMIN MENU
           ===================================================== */

        .admin-nav-group{

            margin-top:16px;

            padding-top:2px;
        }


        .admin-nav-item{

            position:relative;
        }


        .admin-nav-item svg{

            color:inherit;

            flex:none;
        }



        /* =====================================================
           SIDEBAR FOOTER
           ===================================================== */

        .sidebar-footer{

            margin-top:auto;

            padding-top:16px;
        }


        .logout-btn{

            width:100%;

            display:flex;

            align-items:center;

            gap:10px;

            padding:
                11px
                12px;

            border-radius:12px;

            background:
                rgba(255,255,255,0.05);

            border:
                1px solid
                rgba(255,255,255,0.08);

            color:
                rgba(255,226,227,0.85);

            font-size:13px;

            font-weight:600;

            cursor:pointer;

            transition:
                background 0.15s ease;
        }


        .logout-btn:hover{

            background:
                rgba(255,255,255,0.1);
        }



        /* =====================================================
           MAIN
           ===================================================== */

        .main{

            flex:1 1 auto;

            display:flex;

            flex-direction:column;

            gap:16px;

            min-width:0;

            width:100%;
        }



        /* =====================================================
           TOPBAR
           ===================================================== */

        .topbar{

            display:flex;

            align-items:center;

            justify-content:space-between;

            padding:
                16px
                22px;

            border-radius:18px;

            background:
                rgba(255,255,255,0.55);

            backdrop-filter:blur(16px);

            -webkit-backdrop-filter:blur(16px);

            border:
                1px solid
                rgba(255,255,255,0.6);

            box-shadow:
                0 14px 34px -20px
                rgba(58,4,16,0.25);
        }


        .topbar-left{

            display:flex;

            align-items:center;

            gap:14px;
        }


        .menu-btn{

            width:36px;
            height:36px;

            border-radius:10px;

            display:grid;

            place-items:center;

            background:
                rgba(200,16,46,0.08);

            color:var(--red);

            cursor:pointer;

            border:none;
        }


        .topbar h1{

            font-family:
                'Space Grotesk',
                sans-serif;

            font-size:16px;

            font-weight:600;

            color:var(--ink);
        }


        .user-chip{

            display:flex;

            align-items:center;

            gap:10px;

            padding:
                6px
                14px
                6px
                6px;

            border-radius:999px;

            background:
                rgba(255,255,255,0.6);

            border:
                1px solid
                rgba(255,255,255,0.7);
        }


        .user-avatar{

            width:34px;
            height:34px;

            border-radius:50%;

            background:
                linear-gradient(
                    145deg,
                    var(--red),
                    #8A0F26
                );

            color:#fff;

            display:grid;

            place-items:center;

            font-weight:700;

            font-size:13px;
        }


        .user-meta .name{

            font-size:12.5px;

            font-weight:700;

            color:var(--ink);

            line-height:1.25;
        }


        .user-meta .mail{

            font-size:10.5px;

            color:var(--ink-lo);
        }



        /* =====================================================
           CONTENT
           ===================================================== */

        .content{

            flex:1 1 auto;

            border-radius:22px;

            background:
                rgba(255,255,255,0.6);

            backdrop-filter:blur(18px);

            -webkit-backdrop-filter:blur(18px);

            border:
                1px dashed
                rgba(200,16,46,0.28);

            box-shadow:
                0 20px 50px -28px
                rgba(58,4,16,0.2);

            display:flex;

            flex-direction:column;

            overflow-y:auto;

            overflow-x:hidden;

            min-width:0;

            width:100%;

            padding:
                32px
                36px;
        }


        .content-center{

            margin:auto;
        }



        /* PAGE LOADING dashboard sengaja dihapus. Loading ditangani oleh partial masing-masing. */




        /* =====================================================
           SIDEBAR MOBILE — backdrop + tombol tutup
           Hanya aktif di layar <= 900px (mode drawer).
           ===================================================== */

        .sidebar-backdrop,
        .sidebar-close{
            display:none;
        }

        @media (max-width: 900px){

            .sidebar-backdrop{
                display:block;
                position:fixed;
                inset:0;
                z-index:999;
                background:rgba(20,16,18,.55);
                backdrop-filter:blur(3px);
                -webkit-backdrop-filter:blur(3px);
                opacity:0;
                visibility:hidden;
                transition:
                    opacity .25s ease,
                    visibility .25s ease;
            }

            .sidebar-backdrop{
                cursor:pointer;
                touch-action:manipulation;
                -webkit-tap-highlight-color:transparent;
            }

            .sidebar-backdrop.open{
                opacity:1;
                visibility:visible;
            }

            .sidebar-close{
                display:grid;
                place-items:center;
                position:fixed;
                top:24px;
                left:calc(10px + min(300px, calc(100vw - 20px)) - 48px);
                width:34px;
                height:34px;
                border:none;
                border-radius:10px;
                background:rgba(255,255,255,0.14);
                color:#fff;
                cursor:pointer;
                z-index:1001;
                opacity:0;
                visibility:hidden;
                pointer-events:none;
                touch-action:manipulation;
                -webkit-tap-highlight-color:transparent;
                transition:
                    opacity .2s ease,
                    visibility .2s ease;
            }

            body.sidebar-open .sidebar-close{
                opacity:1;
                visibility:visible;
                pointer-events:auto;
            }

            .sidebar-close:active{
                background:rgba(255,255,255,0.24);
            }

            /* Beri ruang supaya teks brand tidak tertimpa tombol tutup */
            .sidebar .brand-row{
                padding-right:44px;
            }

            body.sidebar-open{
                overflow:hidden;
            }

        }



        /* =====================================================
           EMPTY STATE
           ===================================================== */

        .empty-state{

            text-align:center;

            max-width:360px;
        }


        .empty-icon{

            width:52px;
            height:52px;

            margin:
                0
                auto
                18px;

            border-radius:14px;

            background:
                rgba(200,16,46,0.1);

            display:grid;

            place-items:center;
        }


        .empty-state h2{

            font-family:
                'Space Grotesk',
                sans-serif;

            font-size:17px;

            font-weight:600;

            color:var(--ink);
        }


        .empty-state p{

            margin-top:8px;

            font-size:13px;

            color:var(--ink-lo);

            line-height:1.6;
        }



        /* =====================================================
           PLACEHOLDER
           ===================================================== */

        .placeholder-card{

            text-align:center;

            max-width:420px;
        }


        .placeholder-tag{

            display:inline-block;

            font-size:11px;

            font-weight:700;

            letter-spacing:0.04em;

            color:var(--red);

            background:
                rgba(200,16,46,0.08);

            padding:
                5px
                12px;

            border-radius:999px;

            margin-bottom:14px;
        }


        .placeholder-card h2{

            font-family:
                'Space Grotesk',
                sans-serif;

            font-size:20px;

            font-weight:600;

            color:var(--ink);
        }


        .placeholder-card p{

            margin-top:10px;

            font-size:13.5px;

            color:var(--ink-lo);

            line-height:1.6;
        }



        /* =====================================================
           RESPONSIVE
           ===================================================== */

        /*
         * DESKTOP / LAPTOP:
         * Tampilan utama tetap seperti semula.
         * Hanya memastikan area content bisa mengecil dengan benar.
         */

        @media (max-width: 1200px){

            body{
                padding:12px;

                gap:12px;
            }

            .sidebar{
                width:270px;
            }

            .main{
                min-width:0;
            }

            .content{
                padding:
                    26px
                    28px;
            }

            /*
             * Elemen isi tidak boleh membuat halaman utama
             * melebar keluar viewport.
             */
            .content > *{
                max-width:100%;
            }

        }


        /*
         * TABLET:
         * Sidebar berubah menjadi drawer.
         */

        @media (max-width: 900px){

            html,
            body{
                min-width:0;
                width:100%;
                max-width:100%;
                overflow-x:hidden;
            }

            body{
                display:block;
                padding:10px;
            }

            .sidebar{
                position:fixed;

                top:10px;
                left:10px;
                bottom:10px;

                width:min(
                    300px,
                    calc(100vw - 20px)
                );

                max-height:none;

                z-index:1000;

                transform:translateX(
                    calc(-100% - 30px)
                );

                transition:
                    transform .25s
                    cubic-bezier(.4,0,.2,1);

                box-shadow:
                    0 30px 80px -20px
                    rgba(58,4,16,0.72),
                    inset 0 1px 0
                    rgba(255,255,255,0.06);
            }

            /*
             * Sidebar dibuka oleh tombol hamburger.
             */
            .sidebar.mobile-open{
                transform:translateX(0);
            }

            /*
             * Class collapsed tidak berlaku sebagai
             * sidebar kecil di mobile.
             */
            .sidebar.collapsed{
                width:min(
                    300px,
                    calc(100vw - 20px)
                );
            }

            .sidebar.collapsed:hover{
                width:min(
                    300px,
                    calc(100vw - 20px)
                );
            }

            /*
             * Saat mobile drawer terbuka,
             * seluruh teks sidebar tetap terlihat.
             */
            .sidebar.collapsed:not(:hover) .brand-text,
            .sidebar.collapsed:not(:hover) .witel-label,
            .sidebar.collapsed:not(:hover) .chev,
            .sidebar.collapsed:not(:hover) .nav-text,
            .sidebar.collapsed:not(:hover) .nav-label,
            .sidebar.collapsed:not(:hover) .logout-text,
            .sidebar.collapsed:not(:hover) .witel-body{
                display:block;
            }

            .sidebar.collapsed:not(:hover) .brand-row{
                justify-content:flex-start;

                padding-left:6px;
                padding-right:8px;
            }

            .sidebar.collapsed:not(:hover) .witel-head,
            .sidebar.collapsed:not(:hover) .logout-btn{
                justify-content:flex-start;

                padding-left:12px;
                padding-right:12px;
            }

            .sidebar.collapsed:not(:hover) .admin-nav-item{
                justify-content:flex-start;

                padding-left:10px;
                padding-right:10px;

                gap:10px;

                min-height:40px;
            }

            .sidebar.collapsed:not(:hover) .admin-nav-item svg{
                margin:0;
            }

            .main{
                width:100%;
                min-width:0;
            }

            .topbar{
                width:100%;
                min-width:0;
            }

            .content{
                width:100%;
                min-width:0;

                padding:
                    20px;
            }

            /*
             * Form di dalam content jangan memaksa lebar desktop.
             */
            .content input,
            .content select,
            .content textarea,
            .content button{
                max-width:100%;
            }

            /*
             * Card/grid yang memang dua kolom kita
             * turunkan menjadi satu kolom di tablet.
             */
            .content .ud-main-grid,
            .content .ah-delete-grid,
            .content .ah-summary-grid{
                grid-template-columns:1fr;
            }

            /*
             * Tabel tetap boleh scroll horizontal,
             * tetapi container-nya tidak boleh memperlebar halaman.
             */
            .content .ud-table-wrap,
            .content .df-table-wrap,
            .content .ed-table-wrap,
            .content .ex-table-scroll{
                width:100%;
                max-width:100%;
                overflow-x:auto;
                -webkit-overflow-scrolling:touch;
            }

        }


        /*
         * HP:
         * Fokus pada touch target, teks, form dan card.
         */

        @media (max-width: 640px){

            body{
                padding:8px;
            }

            .topbar{
                padding:
                    12px
                    14px;

                border-radius:14px;

                gap:10px;
            }

            .topbar-left{
                min-width:0;
                gap:9px;
            }

            .menu-btn{
                width:40px;
                height:40px;
                flex:none;
            }

            .topbar h1{
                font-size:14px;

                white-space:nowrap;
            }

            .user-chip{
                min-width:0;

                max-width:
                    52vw;

                padding:
                    4px
                    9px
                    4px
                    4px;

                gap:7px;
            }

            .user-avatar{
                width:30px;
                height:30px;

                flex:none;

                font-size:12px;
            }

            .user-meta{
                min-width:0;
            }

            .user-meta .name,
            .user-meta .mail{
                max-width:100%;

                overflow:hidden;

                text-overflow:ellipsis;

                white-space:nowrap;
            }

            .user-meta .name{
                font-size:11px;
            }

            .user-meta .mail{
                font-size:9px;
            }

            .content{
                padding:
                    14px;

                border-radius:16px;
            }

            /*
             * Head halaman seperti Kelola User,
             * Upload, Edit, Arsip, dll.
             */
            .content .ed-head,
            .content .ah-head,
            .content .ud-page-head{
                align-items:
                    flex-start;

                flex-direction:
                    column;
            }

            /*
             * Tombol aksi di mobile mengambil lebar yang
             * nyaman untuk disentuh.
             */
            .content .ed-add-btn,
            .content .ud-reset-btn{
                width:100%;
                justify-content:center;
            }

            /*
             * Filter jangan berdempetan.
             */
            .content .df-filter-row,
            .content .ed-filter-row{
                flex-direction:column;
                align-items:stretch;
            }

            .content .df-field,
            .content .ed-field{
                width:100%;
            }

            .content .df-sto-select,
            .content .ed-sto-select{
                width:100%;
                margin-left:0;
            }

            /*
             * Status filter tetap wrap.
             */
            .content .df-status-row,
            .content .ed-status-row{
                display:flex;
                flex-wrap:wrap;
            }

            /*
             * Form dua kolom menjadi satu.
             */
            .content .ed-form-row{
                grid-template-columns:1fr;
            }

            /*
             * Card arsip tetap satu kolom.
             */
            .content .ah-delete-grid,
            .content .ah-summary-grid{
                grid-template-columns:1fr;
            }

            /*
             * Upload grid satu kolom.
             */
            .content .ud-main-grid{
                grid-template-columns:1fr;
            }

            /*
             * Admin user table: halaman tetap tidak
             * melebar; tabel digeser horizontal di card.
             */
            .content table{
                max-width:100%;
            }

            /*
             * Tabel data besar tetap horizontal scroll,
             * bukan mengecil sampai tulisan bertabrakan.
             */
            .content .df-table-wrap,
            .content .ed-table-wrap,
            .content .ud-preview-wrap,
            .content .ex-table-scroll{
                overflow-x:auto;
                max-width:100%;
            }

            /*
             * Ukuran heading isi halaman.
             */
            .content h1{
                max-width:100%;
            }

            .content h2{
                max-width:100%;
                overflow-wrap:anywhere;
            }

            /*
             * Modal harus muat pada layar HP.
             */
            .content .ed-modal{
                width:calc(100vw - 30px);
                max-width:calc(100vw - 30px);
            }

        }


        /*
         * HP SANGAT KECIL
         */

        @media (max-width: 380px){

            body{
                padding:6px;
            }

            .content{
                padding:11px;
            }

            .topbar{
                padding:
                    10px
                    11px;
            }

            .topbar h1{
                font-size:13px;
            }

            .user-chip{
                max-width:48vw;
            }

        }



        /* =====================================================
           LOGOUT LOADING
           ===================================================== */

        html.logout-lock,
        body.logout-lock{
            overflow:hidden !important;
            cursor:none !important;
        }

        .logout-loading{
            display:none;
            position:fixed !important;
            inset:0 !important;
            width:100vw !important;
            height:100vh !important;
            z-index:2147483647 !important;
            align-items:center;
            justify-content:center;
            padding:16px;
            box-sizing:border-box;
            background:rgba(20,16,18,.72);
            backdrop-filter:blur(9px);
            -webkit-backdrop-filter:blur(9px);
            pointer-events:all !important;
            cursor:none !important;
        }

        .logout-loading.open{
            display:flex;
            animation:logoutFadeIn .16s ease-out;
        }

        .logout-loading,
        .logout-loading *{
            cursor:none !important;
        }

        .logout-loading-card{
            width:min(320px, calc(100vw - 32px));
            padding:28px 24px 24px;
            background:#fff;
            border-radius:20px;
            box-shadow:0 35px 100px rgba(0,0,0,.35);
            text-align:center;
            animation:logoutPop .18s ease-out;
        }

        .logout-loading-logo{
            width:52px;
            height:52px;
            margin:0 auto 14px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:14px;
            background:linear-gradient(135deg,#C8102E,#8A0F26);
            color:#fff;
            font-family:'Space Grotesk',sans-serif;
            font-size:17px;
            font-weight:800;
            box-shadow:0 12px 25px rgba(200,16,46,.25);
        }

        .logout-loading-spinner{
            width:32px;
            height:32px;
            margin:0 auto 16px;
            border:3px solid #F2D9DE;
            border-top-color:#C8102E;
            border-radius:50%;
            animation:logoutSpin .75s linear infinite;
        }

        .logout-loading-title{
            font-family:'Space Grotesk',sans-serif;
            font-size:16px;
            font-weight:700;
            color:#20161A;
        }

        .logout-loading-subtitle{
            margin-top:5px;
            font-family:'Inter',sans-serif;
            font-size:11.5px;
            color:#7A6B6F;
        }

        @keyframes logoutSpin{
            to{ transform:rotate(360deg); }
        }

        @keyframes logoutFadeIn{
            from{ opacity:0; }
            to{ opacity:1; }
        }

        @keyframes logoutPop{
            from{
                opacity:0;
                transform:translateY(8px) scale(.98);
            }
            to{
                opacity:1;
                transform:translateY(0) scale(1);
            }
        }

    </style>

<script src="{{ asset('js/tf-navigation.js') }}" defer></script>

</head>


<body data-tf-nav="dashboard">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <!-- Backdrop drawer (mobile). Klik di luar sidebar = tutup -->
    <div
        class="sidebar-backdrop"
        id="sidebarBackdrop"
        onclick="closeMobileSidebar()"
    ></div>


    <aside
        class="sidebar"
        id="sidebar"
    >



        <!-- BRAND -->

        <div class="brand-row">

            <div class="brand-mark">
                <img
                    src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACkAAAAqCAYAAAAu9HJYAAAHbklEQVR42s2Za0xU6RnHf+85Z24wV26K8YJmtSrCalAQkaXpijHBZtvdVYzlS5NNusQtmE2aTZp+MCYmpk0v0Jhtsk36oY0Vl11J1pp1qUgbjUVgNeAF0GK6yIirgkyRmTlnznv6gYuMgI5W1CeZnMzlnPM7//d5/8/7PiMKweIVD+1pfpxfWkrBtm1kFxSweMUKvKmpKIqS0LlSSkL37vFNTw+XW1po+fJLzjc2JnSuSETJtysr2VFVxZKVK5+rQv/p6uLT2lo+//jjZ4dcmZfH3poacouK5nQ4O86e5XfV1XS1t88MuWkWyDfLy9l3+HDCw/n/hpSSfbt3c6qubnpOzkS4pbyc/UeOvNDJoSjK5D3//gjoNCW/k5fHH8+ff2EKzqToe/n5dE8ZemWCcOK4t6bmpQFOKLq3poapXIqYkBT4YWXlnE+SRCK3qIi3KysnueIk21FVNfcElgXR6NjxMTGVZRJyQ2npc/fBqWBWJAzRCPJWkP9+8B7W0BAYBrK/Dys0PO2UJStXsqG0NB5y47Ztc6eeHiV6+M/ELnUQPd6AdecOCIF+vIEHv/gI82bfjKdNMGkTOZldUDB3I/zgAbFzZ7CG72OcOwOKwGhqJPzJIexvvYO6OAtL1xGaBlMmbXZBwVhOWuMzaNGKFXMGGZMSmZqGvD2AEALr3j1Gf/9rhNuDrWAT+rGjRGp+hRyOH/ZFK1ZgAaJo3H3+aZpzYj1DQ0M01H/G915bxsKUAMLrJXryb0T/9AlKIAWcLpAmzvd/iv3NreBwxnnmG6o6VnHEuD897zCMGH89cpSF6en4L7QycrwBW3EJrg8+xLo9QLThM5TFWST/fB/quvXgcEzzzDifnM39TdNEShn3mWUltgS9f3+Ie/cG+e7WLXjeegfh8xE98QVm779x7PwRWu5a3LV/QCvYhHgEkCn+Pet6MhQKcejQIa5du0Z2djYVFRV4PB4OHDjA8uXL2b59O0lJSei6jqIo2O12wuHw2IWFGP/OQIixW4msLFx7f8bob3+JGYthczpB14nW12Fbl4dWWIRwJT3dotfr9VJRUcGxY8dYtmwZBw8eZMGCBQSDQRwOB83NzUQiEXp7ewmHw+Tl5XHu3DmklCQnJ1NWVsbq1asBuHjxIq+/nou6dgO3qz9ixLR4LRhEmCZm678QQqDmrp0VcnLinJlhCPv6+jh69Ch3797F7/dz69YtBgcHcbvdSClZunQpW7ZsoaOjg2AwyMaNG2lvb2fdunWMjIxQVlbG119foOl0MymBAJqmcbO/H7fbw4/L3yWgCITDgfD5QZtZr81CPPTJmcLtdrNmzRpSUlLo6OggNzeX/v5+UlNTWbBgAf39/XR2dmJZFiUlJcyfP59oNMqSJUsIhUKoqsr69Xl4vV6udnXx4MEomwoLWZOTTWpmZmJbhycpKaUkHA5z48YNMjIy8Pl8WJaFoiiMjo7S1tZGTk4OHo8Hm80Wf3Eh0Kaoo+s6sVgMu92OqqoIIRKCfKKSiqIgpaStrQ2v10swGCQrK4t58+bR2NhIJBJBSsmlS5coLCwkGAxy8+ZNSkpK6O3tZXBwkOLiYpqamsjJycHlcnHlyhU0TWPHjh1xD/E4JZVEzCQWi9Hd3U15eTnNzc00NTWxc+dODMOgtbWVPXv2UF9fT1dXF8XFxRw+fJjm5mYuX77MiRMnuHv3LlJKuru7GR0d5eTJk9y+fTuxkjq1ds/6JEJgt9tJTk7G6XTi9/vRNI3Tp08TjUZxOp2cOnUKh8NBWloaLpeLzMxMTNMkKysLt9vN8PAwFy5cIBwOk5mZid/vT7h4CEBsHs/Jf8xSFify0jAMfD4fIyMjWJZFKBTC7XajqioDAwMsXLgQKSWapo3tsUMhdF0nIyODgYEBfD4fqqoyPDyMpmmkpaU9cbillJSo6kPIL779Fn96+ivVubh/5w7fz8h4aOZ9PT1xkIZhMDg4iKqquN1uhBAYhjFZKg3DwOFwYJrmpG/quo7NZsOyLEZGRnA6nQghkFJiGAaBQABVVROG7OvpiV9PXmlpIWfK/iYajdLZ2YmUEo/Hg2makzmo6zpJSUn4fD4ikQiBQIDr16/jcrmIxWKkpaURi8W4ceMGfr+fWCxGeno6gUDgqZS80tISn5MbSkv5zVdfTf7ANE10Xcc0TTRNIxaLja0Fx31yYlIpijJZs202G1JK7HY7QgjC4fBk3qmqitPpfCrID7dupbWxEVE8Zd/9l6tXWfwM+xzLshI250Tjm64uKlatmr7vrq+tfaYLPm9AgE/HWSyIVxLg0Jkzcbn5MqLz7Fn2bN48fUs7EbXV1XGL3JfRZqmtro5Tbhpkd3s7+3fvfmmQ+3fvpru9HfE4SICmujr27dr1QhWVUrJv1y6aZmj9KWK8Pk7WyfFXU10d7+fn03n27AvJwZ/k53O6ro4ZeYoTaEf/oLKSd6uqnsmenmQz9bW1NDypHf3GU/z7sH68sb+qoIBFz9jY7+vp4ep4Y78t0cZ+MVhiwo9e0WNcf/JVPSo80ul9Fd//Dz0KPDcK7iXkAAAAAElFTkSuQmCC"
                    alt="Telkom Indonesia"
                    class="brand-logo-img"
                >
            </div>


            <div class="brand-text">

                <div class="t1">
                    Telkom Fallout System
                </div>

                <div class="t2">
                    Divisi Provisioning
                </div>

            </div>

        </div>



        <!-- =================================================
             WITEL LIST
             ================================================= -->

        <div class="witel-list">

            @php

                $witels = [

                    'jaktim' =>
                        'Witel Jakarta Timur',

                    'jakpus' =>
                        'Witel Jakarta Pusat',

                    'jaksel' =>
                        'Witel Jakarta Selatan',

                ];


                $menus = [

                    'Data Rekap' => [

                        [
                            'slug' =>
                                'total-rekap',

                            'label' =>
                                'Total Rekap',
                        ],

                        [
                            'slug' =>
                                'detail-fallout',

                            'label' =>
                                'Detail Fallout',
                        ],

                    ],


                    'Manajemen Data' => [

                        [
                            'slug' =>
                                'upload-data',

                            'label' =>
                                'Upload Data',
                        ],

                        [
                            'slug' =>
                                'edit-data',

                            'label' =>
                                'Edit Data',
                        ],

                        [
                            'slug' =>
                                'arsip-hapus',

                            'label' =>
                                'Arsip & Hapus',
                        ],

                    ],


                    'Export' => [

                        [
                            'slug' =>
                                'export-data',

                            'label' =>
                                'Export Data',
                        ],

                    ],

                ];


                $activeWitel =
                    $activeWitel ?? null;


                $activeMenu =
                    $activeMenu ?? null;

            @endphp

            {{-- Tombol kembali ke dashboard utama; tetap terlihat di semua menu Witel. --}}
            <a
                href="{{ route('dashboard') }}"
                class="nav-item dashboard-home-link {{ (!$activeWitel && !$activeMenu) ? 'active' : '' }}"
                aria-current="{{ (!$activeWitel && !$activeMenu) ? 'page' : 'false' }}"
            >
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/>
                    <rect x="13.5" y="3.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/>
                    <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/>
                    <rect x="13.5" y="13.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/>
                </svg>
                <span class="nav-text">Dashboard</span>
            </a>

            <div class="nav-label dashboard-branch-label">CABANG</div>

            @foreach (
                $witels as $slug => $label
            )

                <div
                    class="
                        witel-branch
                        {{ $activeWitel === $slug
                            ? 'open'
                            : '' }}
                    "
                >


                    <button
                        type="button"
                        class="witel-head"
                        onclick="toggleWitel(this)"
                    >


                        <span class="witel-icon">

                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >

                                <path
                                    d="M4 20h16M6.5 20V9L12 5l5.5 4v11M9.5 20v-5h5v5M8 11h1M11.5 9.5h1M15 11h1"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />

                            </svg>

                        </span>


                        <span class="witel-label">
                            {{ $label }}
                        </span>


                        <span class="chev">

                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                            >

                                <path
                                    d="M9 6l6 6-6 6"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />

                            </svg>

                        </span>

                    </button>



                    <div class="witel-body">

                        @foreach (
                            $menus as $groupLabel => $items
                        )

                            <div class="nav-group">

                                <div class="nav-label">
                                    {{ strtoupper($groupLabel) }}
                                </div>


                                <div class="nav-items">

                                    @foreach (
                                        $items as $item
                                    )

                                        <a
                                            class="
                                                nav-item
                                                {{ (
                                                    $activeWitel === $slug
                                                    &&
                                                    $activeMenu === $item['slug']
                                                )
                                                    ? 'active'
                                                    : '' }}
                                            "
                                            href="{{
                                                route(
                                                    'dashboard.menu',
                                                    [
                                                        'witel' =>
                                                            $slug,

                                                        'menu' =>
                                                            $item['slug']
                                                    ]
                                                )
                                            }}"
                                        >


                                            @if ($item['slug'] === 'total-rekap')

                                                <svg
                                                    width="15"
                                                    height="15"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        d="M4 20V12M9.5 20V7M15 20V10M20 20V4"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                        stroke-linecap="round"
                                                    />
                                                </svg>

                                            @elseif ($item['slug'] === 'detail-fallout')

                                                <svg
                                                    width="15"
                                                    height="15"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    aria-hidden="true"
                                                >
                                                    <circle
                                                        cx="10.5"
                                                        cy="10.5"
                                                        r="5.5"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                    />
                                                    <path
                                                        d="M15 15l4.5 4.5"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                        stroke-linecap="round"
                                                    />
                                                </svg>

                                            @elseif ($item['slug'] === 'upload-data')

                                                <svg
                                                    width="15"
                                                    height="15"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        d="M12 16V4m0 0L8 8m4-4 4 4"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />
                                                    <path
                                                        d="M4 13v5a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />
                                                </svg>

                                            @elseif ($item['slug'] === 'edit-data')

                                                <svg
                                                    width="15"
                                                    height="15"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        d="M4 20l4.1-1 9.6-9.6a2.15 2.15 0 0 0-3.05-3.05l-9.6 9.6L4 20Z"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                        stroke-linejoin="round"
                                                    />
                                                    <path
                                                        d="M13.7 7.3l3 3"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                        stroke-linecap="round"
                                                    />
                                                </svg>

                                            @elseif ($item['slug'] === 'arsip-hapus')

                                                <svg
                                                    width="15"
                                                    height="15"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        d="M4 7h16M6 7v12h12V7M8 7V4h8v3"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />
                                                    <path
                                                        d="M9 12h6"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                        stroke-linecap="round"
                                                    />
                                                </svg>

                                            @elseif ($item['slug'] === 'export-data')

                                                <svg
                                                    width="15"
                                                    height="15"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        d="M12 4v12m0 0-4-4m4 4 4-4"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />
                                                    <path
                                                        d="M4 17v2a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 20 19v-2"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                        stroke-linecap="round"
                                                    />
                                                </svg>

                                            @else

                                                <svg
                                                    width="15"
                                                    height="15"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    aria-hidden="true"
                                                >
                                                    <circle
                                                        cx="12"
                                                        cy="12"
                                                        r="3.2"
                                                        stroke="currentColor"
                                                        stroke-width="1.6"
                                                    />
                                                </svg>

                                            @endif>


                                            <span class="nav-text">
                                                {{ $item['label'] }}
                                            </span>


                                        </a>

                                    @endforeach

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>

            @endforeach

        </div>



        <!-- =================================================
             ADMINISTRASI
             KHUSUS ADMIN
             ================================================= -->

        @if(
            auth()->check()
            &&
            auth()->user()->role === 'admin'
        )

            <div class="admin-nav-group nav-group">


                <div class="nav-label">
                    ADMINISTRASI
                </div>


                <div class="nav-items">


                    <a
                        href="{{ route('admin.users.index') }}"
                        class="
                            nav-item
                            admin-nav-item
                            {{ request()->routeIs('admin.users.*')
                                ? 'active'
                                : '' }}
                        "
                    >


                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 24 24"
                            fill="none"
                        >

                            <!-- User -->

                            <circle
                                cx="9"
                                cy="8"
                                r="3"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />


                            <path
                                d="M3.5 20c.8-3.1 2.8-4.8 5.5-4.8s4.7 1.7 5.5 4.8"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />


                            <!-- Plus -->

                            <path
                                d="M17 13v7M13.5 16.5h7"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />

                        </svg>


                        <span class="nav-text">
                            Kelola User
                        </span>


                    </a>

                </div>

            </div>

        @endif



        <!-- =================================================
             FOOTER
             ================================================= -->

        <div class="sidebar-footer">


            <form
                method="POST"
                action="{{ route('logout') }}"
                id="logoutForm"
            >

                @csrf


                <button
                    type="submit"
                    class="logout-btn"
                >

                    <svg
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                    >

                        <path
                            d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                        <path
                            d="M16 17l5-5-5-5M21 12H9"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                    </svg>


                    <span class="logout-text">
                        Logout
                    </span>

                </button>

            </form>

        </div>

    </aside>


    <!-- Tombol tutup (hanya tampil di mobile, di luar area scroll sidebar) -->
    <button
        type="button"
        class="sidebar-close"
        aria-label="Tutup menu"
        onclick="closeMobileSidebar()"
    >
        <svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
        >
            <path
                d="M6 6l12 12M18 6L6 18"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
            />
        </svg>
    </button>




    <!-- =====================================================
         MAIN
         ===================================================== -->

    <div class="main">


        <!-- =================================================
             TOPBAR
             ================================================= -->

        <div class="topbar">


            <div class="topbar-left">


                <button
                    class="menu-btn"
                    type="button"
                    aria-label="Menu"
                    onclick="toggleSidebar()"
                >

                    <svg
                        width="17"
                        height="17"
                        viewBox="0 0 24 24"
                        fill="none"
                    >

                        <path
                            d="M4 7h16M4 12h16M4 17h16"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />

                    </svg>

                </button>


                <h1>
                    Dashboard
                </h1>

            </div>



            <!-- USER CHIP -->

            <div class="user-chip">


                <div class="user-avatar">

                    {{
                        strtoupper(
                            substr(
                                auth()->user()->name ?? 'A',
                                0,
                                1
                            )
                        )
                    }}

                </div>


                <div class="user-meta">

                    <div class="name">

                        {{
                            auth()->user()->name
                            ?? 'User'
                        }}

                    </div>


                    <div class="mail">

                        {{
                            auth()->user()->email
                            ?? ''
                        }}

                    </div>

                </div>

            </div>

        </div>



        <!-- =================================================
             CONTENT
             ================================================= -->
        <div class="content">


            {{-- =================================================
                 KELOLA USER
                 Khusus Admin
                 ================================================= --}}

            @if($activeMenu === 'kelola-user')

                @if(
                    auth()->check()
                    &&
                    auth()->user()->role === 'admin'
                )

                    @include(
                        'partials.admin-users',
                        [
                            'users' =>
                                $users ?? collect()
                        ]
                    )

                @else

                    <div
                        class="empty-state content-center"
                    >

                        <div class="empty-icon">

                            <svg
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                            >

                                <path
                                    d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"
                                    stroke="#C8102E"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />

                            </svg>

                        </div>


                        <h2>
                            Akses Ditolak
                        </h2>


                        <p>
                            Halaman ini hanya dapat diakses oleh administrator.
                        </p>

                    </div>

                @endif



            {{-- =================================================
                 MENU DASHBOARD NORMAL
                 ================================================= --}}

            @elseif ($activeWitel && $activeMenu)


                @if($activeMenu === 'total-rekap')


                    @include(
                        'partials.total-rekap',
                        [
                            'witel' =>
                                $witels[$activeWitel],

                            'witelSlug' =>
                                $activeWitel
                        ]
                    )


                @elseif($activeMenu === 'detail-fallout')


                    @include(
                        'partials.detail-fallout',
                        [
                            'witel' =>
                                $witels[$activeWitel],

                            'witelSlug' =>
                                $activeWitel
                        ]
                    )


                @elseif($activeMenu === 'upload-data')


                    @include(
                        'partials.upload-data',
                        [
                            'witel' =>
                                $witels[$activeWitel],

                            'witelSlug' =>
                                $activeWitel
                        ]
                    )


                @elseif($activeMenu === 'edit-data')


                    @include(
                        'partials.edit-data',
                        [
                            'witel' =>
                                $witels[$activeWitel],

                            'witelSlug' =>
                                $activeWitel
                        ]
                    )


                @elseif($activeMenu === 'arsip-hapus')


                    @include(
                        'partials.arsip-hapus',
                        [
                            'witel' =>
                                $witels[$activeWitel],

                            'witelSlug' =>
                                $activeWitel
                        ]
                    )


                @elseif($activeMenu === 'export-data')


                    @include(
                        'partials.export-data-v2',
                        [
                            'witel' =>
                                $witels[$activeWitel],

                            'witelSlug' =>
                                $activeWitel
                        ]
                    )


                @else


                    @php

                        $menuLabel =
                            collect($menus)
                                ->flatten(1)
                                ->firstWhere(
                                    'slug',
                                    $activeMenu
                                )['label']
                                ?? $activeMenu;

                    @endphp


                    <div
                        class="
                            placeholder-card
                            content-center
                        "
                    >


                        <span class="placeholder-tag">

                            {{
                                $witels[$activeWitel]
                            }}

                        </span>


                        <h2>

                            {{
                                $menuLabel
                            }}

                        </h2>


                        <p>

                            Halaman ini akan dibuat
                            pada tahap selanjutnya.
                            Struktur route &
                            sidebar sudah siap dipakai.

                        </p>


                    </div>


                @endif



            {{-- =================================================
                 DASHBOARD OVERVIEW
                 Ditampilkan saat /dashboard dibuka tanpa memilih menu sidebar.
                 ================================================= --}}

            @else

                @include('partials.dashboard-overview')

            @endif


        </div>{{-- /.content --}}

        </div>{{-- /.content-shell --}}

    </div>{{-- /.main --}}



    <!-- =====================================================
         JAVASCRIPT
         ===================================================== -->

    <script>


        /*
        |--------------------------------------------------------------------------
        | Toggle Witel
        |--------------------------------------------------------------------------
        */

        function toggleWitel(headBtn)
        {

            const branch =
                headBtn.closest(
                    '.witel-branch'
                );


            const isOpen =
                branch.classList.contains(
                    'open'
                );


            document
                .querySelectorAll(
                    '.witel-branch'
                )
                .forEach(
                    b =>
                        b.classList.remove(
                            'open'
                        )
                );


            if (!isOpen) {

                branch.classList.add(
                    'open'
                );

            }

        }



        /*
        |--------------------------------------------------------------------------
        | Sidebar State
        |--------------------------------------------------------------------------
        */

        const sidebarEl =
            document.getElementById(
                'sidebar'
            );


        const SIDEBAR_KEY =
            'telkom_fallout_sidebar_collapsed';



        function applySidebarState(
            collapsed
        )
        {

            sidebarEl.classList.toggle(
                'collapsed',
                collapsed
            );

        }



        function toggleSidebar()
        {

            /*
             * Mobile:
             * sidebar menjadi drawer, bukan collapsed desktop.
             */

            if (
                window.innerWidth <= 900
            ) {

                sidebarEl.classList.toggle(
                    'mobile-open'
                );

                return;

            }


            /*
             * Desktop:
             * perilaku lama tetap dipertahankan.
             */

            const collapsed =
                !sidebarEl.classList.contains(
                    'collapsed'
                );


            applySidebarState(
                collapsed
            );


            localStorage.setItem(
                SIDEBAR_KEY,
                collapsed
                    ? '1'
                    : '0'
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Restore sidebar state
        |--------------------------------------------------------------------------
        */

        applySidebarState(

            localStorage.getItem(
                SIDEBAR_KEY
            ) === '1'

        );


        /*
         * Saat berpindah ke desktop,
         * drawer mobile ditutup.
         */

        window.addEventListener(
            'resize',
            function(){

                if (
                    window.innerWidth > 900
                ){

                    sidebarEl.classList.remove(
                        'mobile-open'
                    );

                }

            }
        );


        /*
         * Di mobile, setelah memilih menu,
         * drawer otomatis ditutup.
         */

        document.addEventListener(
            'click',
            function(event){

                if (
                    window.innerWidth > 900
                ){
                    return;
                }


                const link =
                    event.target.closest(
                        '.nav-item'
                    );


                if (
                    link &&
                    sidebarEl.classList.contains(
                        'mobile-open'
                    )
                ){

                    sidebarEl.classList.remove(
                        'mobile-open'
                    );

                }

            }
        );


        /*
         * Klik area main di mobile tidak mengubah
         * perilaku desktop dan tidak memengaruhi data.
         */

        document.addEventListener(
            'keydown',
            function(event){

                if (
                    event.key === 'Escape' &&
                    window.innerWidth <= 900
                ){

                    sidebarEl.classList.remove(
                        'mobile-open'
                    );

                }

            }
        );





        /* =====================================================
           SIDEBAR MOBILE — tutup via backdrop / tombol X / swipe
           ===================================================== */

        const sidebarBackdropEl =
            document.getElementById(
                'sidebarBackdrop'
            );


        function closeMobileSidebar()
        {

            sidebarEl.classList.remove(
                'mobile-open'
            );

            syncMobileSidebar();

        }


        /*
         * Backdrop & kunci scroll body selalu mengikuti class
         * "mobile-open", apa pun yang membuka/menutup sidebar
         * (hamburger, klik menu, Escape, resize, dll).
         */

        function syncMobileSidebar()
        {

            const open =
                sidebarEl.classList.contains(
                    'mobile-open'
                );

            if (sidebarBackdropEl) {

                sidebarBackdropEl.classList.toggle(
                    'open',
                    open
                );

            }

            document.body.classList.toggle(
                'sidebar-open',
                open
            );

        }


        /*
         * Pengaman: sinkron juga lewat observer, kalau ada kode lain
         * yang mengubah class "mobile-open".
         */
        new MutationObserver(
            function(){
                syncMobileSidebar();
            }
        ).observe(
            sidebarEl,
            {
                attributes:true,
                attributeFilter:['class']
            }
        );


        /*
         * Klik backdrop / tombol X dipasang juga lewat listener,
         * selain atribut onclick, supaya pasti tertangkap di HP.
         */

        if (sidebarBackdropEl) {

            sidebarBackdropEl.addEventListener(
                'click',
                closeMobileSidebar
            );

        }

        const sidebarCloseEl =
            document.querySelector(
                '.sidebar-close'
            );

        if (sidebarCloseEl) {

            sidebarCloseEl.addEventListener(
                'click',
                closeMobileSidebar
            );

        }


        /*
         * Swipe ke kiri pada sidebar = tutup.
         */

        let swipeStartX = null;
        let swipeStartY = null;

        sidebarEl.addEventListener(
            'touchstart',
            function(event){

                if (
                    window.innerWidth > 900 ||
                    !sidebarEl.classList.contains('mobile-open')
                ){
                    return;
                }

                swipeStartX = event.touches[0].clientX;
                swipeStartY = event.touches[0].clientY;

            },
            { passive:true }
        );

        sidebarEl.addEventListener(
            'touchend',
            function(event){

                if (swipeStartX === null) {
                    return;
                }

                const dx =
                    event.changedTouches[0].clientX
                    - swipeStartX;

                const dy =
                    event.changedTouches[0].clientY
                    - swipeStartY;

                swipeStartX = null;
                swipeStartY = null;

                if (
                    dx < -60 &&
                    Math.abs(dx) > Math.abs(dy) * 1.5
                ){
                    closeMobileSidebar();
                }

            },
            { passive:true }
        );





        /* =====================================================
           LOGOUT LOADING
           ===================================================== */

        document.addEventListener('DOMContentLoaded', function(){

            const logoutForm =
                document.getElementById('logoutForm');

            const logoutLoading =
                document.getElementById('logoutLoading');

            if (!logoutForm || !logoutLoading) {
                return;
            }

            let loggingOut = false;

            logoutForm.addEventListener(
                'submit',
                function(event){

                    if (loggingOut) {
                        return;
                    }

                    event.preventDefault();
                    loggingOut = true;

                    logoutLoading.classList.add('open');
                    logoutLoading.setAttribute('aria-hidden','false');

                    document.documentElement.classList.add('logout-lock');
                    document.body.classList.add('logout-lock');

                    // Pastikan browser sempat merender loading sebelum logout.
                    requestAnimationFrame(function(){
                        setTimeout(function(){
                            HTMLFormElement.prototype.submit.call(logoutForm);
                        }, 1000);
                    });

                }
            );

        });

</script>




    <!-- =====================================================
         LOGOUT LOADING
         ===================================================== -->
    <div
        id="logoutLoading"
        class="logout-loading"
        aria-hidden="true"
    >
        <div class="logout-loading-card">
            <div class="logout-loading-logo">
                <span>TF</span>
            </div>
            <div class="logout-loading-spinner"></div>
            <div class="logout-loading-title">
                Keluar dari Sistem
            </div>
            <div class="logout-loading-subtitle">
                Mengakhiri sesi Anda...
            </div>
        </div>
    </div>

</body>

</html>
