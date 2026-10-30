<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Telkom Fallout System</title>

    <link rel="icon" type="image/png" href="{{ asset('images/image.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>

        :root {
            --red: #C8102E;
            --red-dark: #A80E27;

            --maroon: #5C0A1B;
            --maroon-dark: #3A0410;

            --white: #FFFFFF;
            --paper: #FBF9F7;

            --text: #20161A;
            --text-soft: #7A6B6F;

            --line: #E7DEDD;
        }


        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        html,
        body {
            width: 100%;
            height: 100%;
            min-width: 0;
        }


        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            overflow: hidden;
            background: var(--paper);
        }


        /* =====================================================
           MAIN
           ===================================================== */

        .welcome-page {

            width: 100%;
            height: 100vh;

            display: grid;

            grid-template-columns: 55% 45%;

            overflow: hidden;
        }


        /* =====================================================
           LEFT PANEL
           ===================================================== */

        .left-panel {

            position: relative;

            height: 100vh;

            overflow: hidden;

            color: white;

            display: flex;

            flex-direction: column;

            justify-content: space-between;

            padding:
                46px
                64px
                30px
                64px;
        }


        /* Background foto tanpa blur */

        .left-background {

            position: absolute;

            inset: 0;

            background-image:
                url('{{ asset("images/gedung-telkom.jpg") }}');

            background-size: cover;

            background-position: center center;

            filter: none;

            transform: scale(1.01);

            z-index: 0;
        }


        /* Overlay */

        .left-overlay {

            position: absolute;

            inset: 0;

            background:
                linear-gradient(
                    90deg,
                    rgba(58, 4, 16, 0.62) 0%,
                    rgba(58, 4, 16, 0.30) 55%,
                    rgba(58, 4, 16, 0.45) 100%
                ),

                linear-gradient(
                    180deg,
                    rgba(58, 4, 16, 0.10) 0%,
                    rgba(58, 4, 16, 0.18) 45%,
                    rgba(58, 4, 16, 0.78) 100%
                );

            z-index: 1;
        }


        /* =====================================================
           LOGO
           ===================================================== */

        .left-logo {

            position: relative;

            z-index: 2;

            display: flex;

            align-items: flex-start;
        }


        .left-logo img {

            width: auto;

            height: 68px;

            object-fit: contain;

            filter:
                drop-shadow(
                    0 2px 4px rgba(0,0,0,0.20)
                );
        }


        /* =====================================================
           LEFT CONTENT
           ===================================================== */

        .left-content {

            position: relative;

            z-index: 2;

            max-width: 650px;

            margin-top: auto;

            margin-bottom: auto;

            padding-top: 20px;
        }


        /* Small heading */

        .left-eyebrow {

            font-size: 11px;

            font-weight: 600;

            letter-spacing: 0.30em;

            color:
                rgba(255,255,255,0.78);

            text-transform: uppercase;

            margin-bottom: 18px;
        }


        /* TITLE */

        .left-title {

            font-family:
                'Space Grotesk',
                sans-serif;

            /*
             * DIPERBESAR KEMBALI
             */

            font-size: clamp(
                52px,
                5vw,
                72px
            );

            font-weight: 700;

            line-height: 1.02;

            letter-spacing: -0.045em;

            color: #fff;

            max-width: 650px;
        }


        /* DESCRIPTION */

        .left-description {

            max-width: 560px;

            margin-top: 22px;

            font-size: 14px;

            line-height: 1.75;

            color:
                rgba(255,255,255,0.82);
        }


        /* =====================================================
           LEFT BUTTON
           ===================================================== */

        .left-button {

            display: inline-flex;

            align-items: center;

            gap: 9px;

            margin-top: 28px;

            padding:
                14px
                23px;

            border-radius: 999px;

            background: var(--red);

            color: white;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            transition:
                background 0.18s ease,
                transform 0.18s ease,
                box-shadow 0.18s ease;
        }


        .left-button:hover {

            background: var(--red-dark);

            transform: translateY(-2px);

            box-shadow:
                0 10px 25px
                rgba(0,0,0,0.24);
        }


        .left-button-arrow {

            font-size: 16px;

            line-height: 1;
        }


        /* =====================================================
           COPYRIGHT
           ===================================================== */

        .copyright {

            position: relative;

            z-index: 2;

            font-size: 10px;

            color:
                rgba(255,255,255,0.52);
        }


        /* =====================================================
           RIGHT PANEL
           ===================================================== */

        .right-panel {

            height: 100vh;

            background: var(--paper);

            display: flex;

            align-items: center;

            justify-content: center;

            padding:
                50px
                70px;
        }


        .right-content {

            width: 100%;

            max-width: 400px;
        }


        /* LABEL */

        .right-eyebrow {

            font-family: 'Inter', sans-serif;

            font-size: 11px;

            font-weight: 600;

            color: var(--red);

            text-transform: uppercase;

            margin-bottom: 10px;
        }


        /* TITLE */

        .right-title {

            font-family:
                'Space Grotesk',
                sans-serif;

            font-size: 27px;

            font-weight: 700;

            line-height: 1.2;

            color: var(--text);
        }


        /* DESCRIPTION */

        .right-description {

            margin-top: 10px;

            max-width: 360px;

            font-size: 12.5px;

            line-height: 1.65;

            color: var(--text-soft);
        }


        /* =====================================================
           LOGIN
           ===================================================== */

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 25px;
        }

        .form-field label {
            display: block;
            margin-bottom: 7px;
            color: var(--text);
            font-size: 11px;
            font-weight: 600;
        }

        .form-field input {
            display: block;
            width: 100%;
            height: 44px;
            padding: 0 13px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            color: var(--text);
            font-family: inherit;
            font-size: 12px;
            outline: none;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .form-field input:focus {
            border-color: var(--red);
            box-shadow: 0 0 0 3px rgba(200, 16, 46, 0.10);
        }

        .form-field-error {
            margin-top: 6px;
            color: #B42318;
            font-size: 11px;
            line-height: 1.45;
        }

        .login-alert {
            margin-top: 16px;
            padding: 10px 12px;
            border: 1px solid #FECACA;
            border-radius: 10px;
            background: #FEF2F2;
            color: #991B1B;
            font-size: 11px;
            line-height: 1.55;
        }

        .login-status {
            margin-top: 16px;
            padding: 10px 12px;
            border: 1px solid #BBF7D0;
            border-radius: 10px;
            background: #F0FDF4;
            color: #166534;
            font-size: 11px;
            line-height: 1.55;
        }

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 11px;
        }

        .remember-option {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--text-soft);
            cursor: pointer;
        }

        .remember-option input {
            width: 14px;
            height: 14px;
            accent-color: var(--red);
        }

        .forgot-link {
            color: var(--red);
            font-weight: 600;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .login-button {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 2px;
            padding: 12px 14px;
            border: 0;
            border-radius: 11px;
            background: var(--red);
            color: white;
            font-family: inherit;
            text-align: left;
            cursor: pointer;
            transition:
                transform 0.18s ease,
                box-shadow 0.18s ease,
                background 0.18s ease;
        }


        .login-button:hover {

            background: var(--red-dark);

            transform: translateY(-2px);

            box-shadow:
                0 12px 26px
                rgba(200,16,46,0.25);
        }


        /* Icon */

        .login-icon {

            width: 38px;

            height: 38px;

            flex: 0 0 38px;

            display: grid;

            place-items: center;

            border-radius: 10px;

            background:
                rgba(255,255,255,0.18);
        }


        .login-icon svg {

            width: 17px;

            height: 17px;
        }


        /* Text */

        .login-text {

            display: flex;

            flex-direction: column;

            gap: 3px;
        }


        .login-label {

            font-size: 14px;

            font-weight: 700;
        }


        .login-description {

            font-size: 10px;

            color:
                rgba(255,255,255,0.75);
        }


        /* =====================================================
           LOGIN PROMPT + SPA-LIKE REVEAL
           ===================================================== */

        .login-prompt {
            margin-top: 28px;
            max-height: 100px;
            opacity: 1;
            overflow: hidden;
            transform: translateY(0);
            transition:
                max-height 0.42s cubic-bezier(0.22, 1, 0.36, 1),
                opacity 0.24s ease,
                transform 0.32s ease,
                margin-top 0.32s ease;
        }

        .login-prompt.is-hidden {
            max-height: 0;
            margin-top: 0;
            opacity: 0;
            transform: translateY(-8px);
            pointer-events: none;
        }

        .open-login-button {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px;
            border: 0;
            border-radius: 12px;
            background: var(--red);
            color: #fff;
            font-family: inherit;
            text-align: left;
            cursor: pointer;
            box-shadow: 0 5px 12px rgba(200, 16, 46, 0.10);
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .open-login-button:hover {
            background: var(--red-dark);
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(200, 16, 46, 0.22);
        }

        .open-login-button:focus-visible,
        .login-back-button:focus-visible {
            outline: 3px solid rgba(200, 16, 46, 0.28);
            outline-offset: 3px;
        }

        .open-login-icon {
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.96);
            color: var(--red);
        }

        .open-login-icon svg {
            width: 19px;
            height: 19px;
        }

        .open-login-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .open-login-label {
            font-size: 14px;
            font-weight: 700;
        }

        .open-login-description {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.82);
        }

        .login-reveal {
            max-height: 0;
            margin-top: 0;
            overflow: hidden;
            opacity: 0;
            transform: translateY(12px);
            pointer-events: none;
            transition:
                max-height 0.52s cubic-bezier(0.22, 1, 0.36, 1),
                margin-top 0.34s ease,
                opacity 0.28s ease,
                transform 0.34s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .login-reveal.is-open {
            max-height: 760px;
            margin-top: 20px;
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .login-back-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 14px;
            padding: 4px 0;
            border: 0;
            background: transparent;
            color: var(--text-soft);
            font-family: inherit;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: color 0.18s ease, transform 0.18s ease;
        }

        .login-back-button:hover {
            color: var(--red);
            transform: translateX(-2px);
        }

        @media (prefers-reduced-motion: reduce) {
            .login-prompt,
            .open-login-button,
            .login-reveal,
            .login-back-button {
                transition: none !important;
            }
        }

        /* =====================================================
           SYSTEM INFO
           ===================================================== */

        .system-info {

            margin-top: 25px;

            padding-top: 15px;

            border-top:
                1px solid var(--line);
        }


        .system-info-title {

            font-size: 10px;

            font-weight: 500;

            color: var(--text-soft);

            margin-bottom: 5px;
        }


        .system-info-text {

            font-size: 10px;

            line-height: 1.5;

            color:
                #A09598;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 1100px) {

            .left-panel {

                padding-left: 50px;
                padding-right: 50px;
            }

            .right-panel {

                padding-left: 45px;
                padding-right: 45px;
            }

            .left-title {

                font-size: 52px;
            }
        }


        @media (max-width: 900px) {

            body {
                overflow: auto;
            }


            .welcome-page {

                height: auto;

                min-height: 100vh;

                display: flex;

                flex-direction: column;
            }


            .left-panel {

                min-height: 65vh;

                height: auto;

                padding:
                    35px
                    35px
                    28px;
            }


            .right-panel {

                min-height: 35vh;

                height: auto;

                padding:
                    45px
                    35px;
            }


            .left-logo img {

                height: 58px;
            }


            .left-title {

                font-size: 48px;
            }


            .right-title {

                font-size: 25px;
            }
        }


        @media (max-width: 600px) {

            body {
                overflow-x: hidden;
                overflow-y: auto;
            }

            .welcome-page {
                width: 100%;
                min-width: 0;
                overflow: visible;
            }

            .left-panel {

                width: 100%;

                min-height: 50vh;

                padding:
                    28px
                    20px
                    24px;
            }


            .right-panel {

                padding:
                    35px
                    24px;
            }


            .left-logo img {

                height: 52px;
            }


            .left-eyebrow {

                font-size: 9px;
            }


            .left-title {

                font-size: 40px;
            }


            .left-description {

                font-size: 12px;
            }


            .left-button {

                padding:
                    12px
                    20px;

                font-size: 11px;
            }


            .right-title {

                font-size: 23px;
            }


            .right-description {

                font-size: 11px;
            }

            .left-logo img {
                max-width: 145px;
                height: 46px;
            }

            .left-content {
                width: 100%;
                max-width: 100%;
                padding-top: 12px;
            }

            .left-title {
                font-size: clamp(31px, 9.6vw, 40px);
                line-height: 1.06;
                letter-spacing: -0.035em;
                max-width: 100%;
            }

            .left-description {
                max-width: 100%;
                line-height: 1.6;
            }

            .left-button {
                max-width: 100%;
                justify-content: center;
                white-space: nowrap;
            }

            .right-panel {
                width: 100%;
                min-height: 50vh;
                padding: 34px 20px 40px;
                align-items: flex-start;
            }

            .right-content {
                width: 100%;
                max-width: 420px;
                margin: auto;
            }

            .login-button {
                min-height: 58px;
            }

            .system-info {
                margin-top: 22px;
            }

            .system-info-text {
                overflow-wrap: anywhere;
            }

        }

    </style>

<script src="{{ asset('js/tf-navigation.js') }}" defer></script>
</head>


<body data-tf-nav="public">

<div class="welcome-page">


    <!-- =====================================================
         LEFT PANEL
         ===================================================== -->

    <section class="left-panel">


        <!-- Background -->
        <div class="left-background"></div>


        <!-- Overlay -->
        <div class="left-overlay"></div>


        <!-- Logo -->
        <div class="left-logo">

            <img
                src="{{ asset('images/logo-telkom-white.png') }}"
                alt="Telkom Indonesia"
            >

        </div>


        <!-- Content -->
        <div class="left-content">

            <div class="left-eyebrow">
                TELKOM INDONESIA
            </div>


            <h1 class="left-title">
                Rekap Data —<br>
                Fallout Jakarta
            </h1>


            <p class="left-description">
                Sistem pemantauan dan rekapitulasi data Fallout
                untuk membantu melihat informasi secara lebih
                terstruktur dan mudah.
            </p>


            <a
                href="{{ route('rekap-fallout') }}"
                class="left-button"
            >

                <span>
                    Lihat Data Rekap Fallout
                </span>

                <span class="left-button-arrow">
                    →
                </span>

            </a>

        </div>


        <!-- Copyright -->
        <div class="copyright">

            © 2026 Telkom Indonesia —
            Fallout Management System

        </div>

    </section>



    <!-- =====================================================
         RIGHT PANEL
         ===================================================== -->

    <section class="right-panel">

        <div class="right-content">


            <div class="right-eyebrow">
                TELKOM FALLOUT SYSTEM
            </div>


            <h2 class="right-title">
                Selamat datang
            </h2>


            <p class="right-description">
                Masuk untuk melanjutkan ke dashboard dan
                melakukan pengelolaan data Fallout.
            </p>


            <!-- Tombol ini membuka form di halaman yang sama; tidak menuju /login. -->
            <div id="loginPrompt" class="login-prompt">
                <button
                    id="openLoginButton"
                    type="button"
                    class="open-login-button"
                    aria-expanded="false"
                    aria-controls="loginReveal"
                >
                    <span class="open-login-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            <path d="M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <span class="open-login-text">
                        <span class="open-login-label">Masuk</span>
                        <span class="open-login-description">Masuk ke sistem</span>
                    </span>
                </button>
            </div>

            <!-- Form muncul dengan animasi di panel yang sama. -->
            <div
                id="loginReveal"
                class="login-reveal{{ ($errors->any() || session()->has('status')) ? ' is-open' : '' }}"
                aria-hidden="{{ ($errors->any() || session()->has('status')) ? 'false' : 'true' }}"
                @if (!$errors->any() && !session()->has('status')) inert @endif
            >
                <button id="closeLoginButton" class="login-back-button" type="button">
                    <span aria-hidden="true">←</span> Kembali
                </button>

                <!-- Status dan validasi ditampilkan tanpa berpindah ke login.blade.php. -->
                @if (session('status'))
                    <div class="login-status" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="login-alert" role="alert">
                        <strong>Login belum berhasil.</strong>
                        <ul style="margin: 5px 0 0 16px; padding: 0;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST" class="login-form">
                    @csrf

                    <div class="form-field">
                        <label for="welcome-email">Email</label>
                        <input
                            id="welcome-email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="masukan email"
                            autocomplete="username"
                            required
                        >
                        @error('email')
                            <p class="form-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="welcome-password">Password</label>
                        <input
                            id="welcome-password"
                            type="password"
                            name="password"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >
                        @error('password')
                            <p class="form-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="login-options">
                        <label class="remember-option" for="welcome-remember">
                            <input
                                id="welcome-remember"
                                type="checkbox"
                                name="remember"
                                value="1"
                                {{ old('remember') ? 'checked' : '' }}
                            >
                            <span>Ingat saya</span>
                        </label>

                        <a class="forgot-link" href="{{ route('password.request') }}">
                            Lupa password?
                        </a>
                    </div>

                    <button type="submit" class="login-button">
                        <span class="login-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M5 12h14" stroke="white" stroke-width="2" stroke-linecap="round" />
                                <path d="M13 6l6 6-6 6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="login-text">
                            <span class="login-label">Masuk</span>
                            <span class="login-description">Masuk ke sistem</span>
                        </span>
                    </button>
                </form>
            </div>

            <!-- SYSTEM INFO -->

            <div class="system-info">

                <div class="system-info-title">
                    Akses Sistem Operasional
                </div>

                <div class="system-info-text">
                    Sistem Pemantauan Fallout Regional Jakarta
                </div>

            </div>

        </div>

    </section>

</div>

<script>
    (() => {
        const prompt = document.getElementById('loginPrompt');
        const openButton = document.getElementById('openLoginButton');
        const reveal = document.getElementById('loginReveal');
        const closeButton = document.getElementById('closeLoginButton');
        const emailInput = document.getElementById('welcome-email');

        if (!prompt || !openButton || !reveal || !closeButton) return;

        const setLoginOpen = (isOpen, focusField = false) => {
            prompt.classList.toggle('is-hidden', isOpen);
            reveal.classList.toggle('is-open', isOpen);
            reveal.setAttribute('aria-hidden', String(!isOpen));
            openButton.setAttribute('aria-expanded', String(isOpen));

            if (isOpen) {
                reveal.removeAttribute('inert');
                if (focusField && emailInput) {
                    window.setTimeout(() => {
                        emailInput.focus({ preventScroll: true });
                    }, 220);
                }
            } else {
                reveal.setAttribute('inert', '');
                openButton.focus({ preventScroll: true });
            }
        };

        openButton.addEventListener('click', () => setLoginOpen(true, true));
        closeButton.addEventListener('click', () => setLoginOpen(false));

        // Jika login gagal, buka form kembali setelah Laravel redirect ke welcome.
        const shouldOpenInitially = @json($errors->any() || session()->has('status'));
        if (shouldOpenInitially) {
            setLoginOpen(true, false);
        } else {
            reveal.setAttribute('inert', '');
        }
    })();
</script>

</body>
</html>
