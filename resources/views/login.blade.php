<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

<title>Telkom Fallout System — Login</title>

<link rel="icon" type="image/png" href="{{ asset('images/image.png') }}">

<link rel="preconnect" href="https://fonts.googleapis.com">

<link
  href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap"
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
  }


  *{
    box-sizing:border-box;
    margin:0;
    padding:0;
  }


  html,
  body{
    height:100%;
    min-width:0;
    width:100%;
    max-width:100%;
  }


  body{
    font-family:'Inter', sans-serif;

    display:grid;

    grid-template-columns: minmax(0, 56fr) minmax(0, 44fr);
    grid-template-areas: "brand action";

    min-height:100vh;
  }


  /* =========================================================
     LEFT PANEL
     ========================================================= */

  .brand-panel{
    position:relative;

    color:var(--paper);

    padding:64px 72px;

    display:flex;

    flex-direction:column;

    justify-content:center;

    align-items:center;

    overflow:hidden;

    isolation:isolate;
    grid-area: brand;
    min-width: 0;
    min-height: 100vh;
}


  .brand-photo{
    position:absolute;

    inset:0;

    background-image:
      url('{{ asset("images/gedung-telkom.jpg") }}');

    background-size:cover;

    background-position:65% 35%;

    filter:none;

    z-index:-2;
  }


  .brand-overlay{
    position:absolute;

    inset:0;

    background:
      linear-gradient(
        180deg,
        rgba(58,4,16,0.55) 0%,
        rgba(58,4,16,0.82) 60%,
        var(--maroon-0) 100%
      ),

      radial-gradient(
        120% 100% at 100% 0%,
        rgba(138,15,38,0.55),
        transparent 65%
      );

    z-index:-1;
  }


  /* =========================================================
     LOGO
     ========================================================= */

  .wordmark{
    position:absolute;

    top:32px;

    left:32px;

    z-index:2;
  }


  .wordmark img{
    height:72px;

    width:auto;

    object-fit:contain;
  }


  /* =========================================================
     LEFT CONTENT
     ========================================================= */

  .headline-block{
    position:relative;

    z-index:2;

    width:100%;

    max-width:600px;

    text-align:left;

    margin-top:-10px;
  }


  .left-eyebrow{
    margin-bottom:18px;

    font-size:11px;

    font-weight:600;

    letter-spacing:0.30em;

    color:
      rgba(255,255,255,0.78);

    text-transform:uppercase;
  }


  h1{
    font-family:
      'Space Grotesk',
      sans-serif;

    font-weight:700;

    font-size:46px;

    line-height:1.08;

    letter-spacing:-0.035em;

    color:#fff;

    max-width:560px;
  }


  .left-description{
    margin-top:20px;

    max-width:520px;

    font-size:13px;

    line-height:1.7;

    color:
      rgba(255,255,255,0.80);
  }


  /* =========================================================
     LEFT BUTTON
     ========================================================= */

  .hero-cta{
    display:inline-flex;

    align-items:center;

    gap:9px;

    margin-top:26px;

    padding:13px 22px;

    border-radius:999px;

    background:var(--red);

    color:var(--paper);

    font-family:'Inter', sans-serif;

    font-weight:600;

    font-size:12.5px;

    text-decoration:none;

    position:relative;

    z-index:2;

    transition:
      transform 0.15s ease,
      box-shadow 0.15s ease,
      background 0.15s ease;
  }


  .hero-cta:hover{
    background:#AD0E28;

    box-shadow:
      0 12px 26px -10px
      rgba(200,16,46,0.6);
  }


  .hero-cta:active{
    transform:scale(0.98);
  }


  .hero-arrow{
    font-size:16px;

    line-height:1;
  }


  /* =========================================================
     COPYRIGHT
     ========================================================= */

  .copyright{
    position:absolute;

    left:64px;

    bottom:24px;

    z-index:2;

    font-size:10px;

    line-height:1.4;

    color:
      rgba(255,255,255,0.52);
  }


  /* =========================================================
     RIGHT PANEL
     ========================================================= */

  .action-panel{
    background:var(--paper);

    display:flex;

    align-items:center;

    justify-content:center;

    padding:64px 72px;
    grid-area: action;
    min-width: 0;
    min-height: 100vh;
}


  .form-card{
    width:100%;

    max-width:430px;
  }


  /* =========================================================
     KEMBALI
     ========================================================= */

  .back-link{
    display:inline-flex;

    align-items:center;

    gap:9px;

    margin-bottom:28px;

    color:#A69C9E;

    text-decoration:none;

    font-size:13px;

    font-weight:500;
  }


  .back-link:hover{
    color:var(--red);
  }


  .back-arrow{
    font-size:21px;

    line-height:1;

    font-weight:400;
  }


  /* =========================================================
     LOGIN HEADER
     ========================================================= */

  .login-heading{
    display:flex;

    align-items:center;

    gap:13px;

    margin-bottom:8px;
  }


  .login-icon{
    width:44px;

    height:44px;

    border-radius:11px;

    background:
      rgba(200,16,46,0.08);

    display:grid;

    place-items:center;

    flex:none;
  }


  .login-icon svg{
    width:22px;

    height:22px;
  }


  .form-card h2{
    font-family:
      'Space Grotesk',
      sans-serif;

    font-size:30px;

    font-weight:600;

    color:var(--ink);

    line-height:1.2;
  }


  .form-card p.intro{
    margin-left:57px;

    margin-top:-1px;

    font-size:12px;

    color:var(--ink-lo);

    line-height:1.5;
  }


  /* =========================================================
     STATUS
     ========================================================= */

  .status-banner{
    margin-top:22px;

    padding:13px 16px;

    border-radius:10px;

    background:#DDF3E4;

    border:1px solid #1F8A4C;

    color:#1F6B3E;

    font-size:13px;

    font-weight:600;
  }


  .error-banner{
    margin-top:22px;

    padding:13px 16px;

    border-radius:10px;

    background:#FBE2E2;

    border:1px solid #C8102E;

    color:#7A0C0C;

    font-size:13px;

    font-weight:600;
  }


  /* =========================================================
     FORM
     ========================================================= */

  form{
    margin-top:32px;

    display:flex;

    flex-direction:column;

    gap:20px;
  }


  .field label{
    display:block;

    font-size:12px;

    font-weight:600;

    color:var(--ink);

    margin-bottom:7px;
  }


  .field-input{
    display:flex;

    align-items:center;

    gap:10px;

    border:1.3px solid var(--line);

    border-radius:10px;

    padding:12px 14px;

    min-height:44px;

    background:#fff;

    transition:
      border-color 0.15s ease,
      box-shadow 0.15s ease;
  }


  .field-input:focus-within{
    border-color:var(--red);

    box-shadow:
      0 0 0 3px
      rgba(200,16,46,0.08);
  }


  .field-input input{
    border:none;

    outline:none;

    flex:1;

    font-family:'Inter', sans-serif;

    font-size:13px;

    color:var(--ink);

    background:transparent;
  }


  .field-input input::placeholder{
    color:#B9AEAF;
  }


  .field-input svg{
    flex:none;
  }


  .toggle-eye{
    cursor:pointer;

    opacity:0.55;
  }


  /* =========================================================
     FORGOT PASSWORD
     ========================================================= */

  .forgot-row{
    display:flex;

    justify-content:flex-end;

    margin-top:-6px;
  }


  .forgot{
    color:var(--red);

    text-decoration:none;

    font-size:11px;

    font-weight:600;
  }


  .forgot:hover{
    text-decoration:underline;
  }


  /* =========================================================
     SUBMIT
     ========================================================= */

  .submit-btn{
    margin-top:3px;

    width:100%;

    padding:14px;

    min-height:46px;

    border:none;

    border-radius:10px;

    background:var(--red);

    color:var(--paper);

    font-family:'Inter', sans-serif;

    font-size:14px;

    font-weight:600;

    cursor:pointer;

    transition:
      transform 0.15s ease,
      box-shadow 0.15s ease,
      background 0.15s ease;
  }


  .submit-btn:hover{
    background:#AD0E28;

    box-shadow:
      0 10px 24px -10px
      rgba(200,16,46,0.55);
  }


  .submit-btn:active{
    transform:scale(0.985);
  }


  /* =========================================================
     RESPONSIVE
     ========================================================= */

  @media (max-width:900px){

    html,
    body{
      min-width:0;
      width:100%;
      overflow-x:hidden;
    }

    body{
      display: grid;
      grid-template-columns: minmax(0, 1fr);
      grid-template-areas:
        "brand"
        "action";
      min-height: 100vh;
    }

    .brand-panel{
      width:100%;
      min-height:52vh;
      padding:88px 34px 70px;
      align-items:flex-start;
      justify-content:center;
    }

    .wordmark{
      top:22px;
      left:24px;
    }

    .wordmark img{
      height:54px;
      max-width:180px;
    }

    .headline-block{
      width:100%;
      max-width:620px;
      margin-top:0;
      text-align:left;
    }

    .left-eyebrow{
      font-size:9px;
      letter-spacing:.22em;
      margin-bottom:14px;
    }

    h1{
      font-size:clamp(34px,8vw,46px);
      line-height:1.06;
      max-width:620px;
    }

    .left-description{
      max-width:560px;
      margin-top:16px;
      font-size:12.5px;
      line-height:1.6;
    }

    .hero-cta{
      margin-top:20px;
      padding:12px 18px;
      font-size:11.5px;
    }

    .copyright{
      left:24px;
      right:24px;
      bottom:18px;
      font-size:9px;
    }

    .action-panel{
      width:100%;
      min-height:48vh;
      padding:42px 28px 50px;
      align-items:flex-start;
    }

    .form-card{
      width:100%;
      max-width:520px;
      margin:0 auto;
    }

    .back-link{
      margin-bottom:22px;
    }

    .login-heading{
      gap:11px;
    }

    .login-icon{
      width:40px;
      height:40px;
      border-radius:10px;
    }

    .form-card h2{
      font-size:26px;
    }

    .form-card p.intro{
      margin-left:51px;
      font-size:11.5px;
    }

    form{
      margin-top:26px;
      gap:18px;
    }

  }

  @media (max-width:600px){

    .brand-panel{
      min-height:48vh;
      padding:82px 22px 64px;
    }

    .wordmark{
      top:18px;
      left:20px;
    }

    .wordmark img{
      height:46px;
      max-width:155px;
    }

    .headline-block{
      max-width:100%;
    }

    .left-eyebrow{
      font-size:8px;
      letter-spacing:.18em;
      margin-bottom:12px;
    }

    h1{
      font-size:clamp(30px,10vw,38px);
      line-height:1.08;
      letter-spacing:-.025em;
    }

    .left-description{
      margin-top:14px;
      font-size:11.5px;
      line-height:1.55;
    }

    .hero-cta{
      width:100%;
      justify-content:center;
      margin-top:18px;
      padding:12px 16px;
      font-size:11px;
    }

    .copyright{
      left:20px;
      right:20px;
      bottom:14px;
      font-size:8.5px;
    }

    .action-panel{
      min-height:52vh;
      padding:32px 20px 40px;
    }

    .back-link{
      margin-bottom:20px;
      font-size:12px;
    }

    .form-card h2{
      font-size:24px;
    }

    .form-card p.intro{
      margin-left:0;
      margin-top:7px;
      font-size:11.5px;
    }

    .field label{
      font-size:12px;
    }

    .field-input{
      min-height:46px;
      padding:11px 12px;
    }

    .field-input input{
      font-size:13px;
      min-width:0;
    }

    .forgot-row{
      margin-top:-3px;
    }

    .submit-btn{
      min-height:46px;
      padding:13px;
      font-size:13px;
    }

    .status-banner,
    .error-banner{
      font-size:12px;
      padding:11px 12px;
    }

  }

</style>

<script src="{{ asset('js/tf-navigation.js') }}" defer></script>
</head>


<body data-tf-nav="public">


  <!-- =======================================================
       LEFT PANEL
       ======================================================= -->

  <section class="brand-panel">

    <div class="brand-photo"></div>

    <div class="brand-overlay"></div>


    <div class="wordmark">

      <img
        src="{{ asset('images/logo-telkom-white.png') }}"
        alt="Telkom Indonesia"
      >

    </div>


    <div class="headline-block">

      <div class="left-eyebrow">
        TELKOM INDONESIA
      </div>


      <h1>
        Rekap Data -<br>
        Fallout Jakarta Southern
      </h1>


      <p class="left-description">
        Sistem pemantauan dan rekapitulasi data Fallout
        untuk membantu melihat informasi secara lebih
        terstruktur dan mudah.
      </p>


      <a
        class="hero-cta"
        href="{{ route('rekap-fallout') }}"
      >

        <span>
          Lihat Data Rekap Fallout
        </span>

        <span class="hero-arrow">
          →
        </span>

      </a>

    </div>


    <div class="copyright">
      © 2026 Telkom Indonesia — Fallout Management System
    </div>

  </section>



  <!-- =======================================================
       RIGHT PANEL
       ======================================================= -->

  <section class="action-panel">

    <div class="form-card">


      <!-- KEMBALI -->

      <a
        class="back-link"
        href="{{ route('welcome') }}"
      >

        <span class="back-arrow">
          ←
        </span>

        <span>
          Kembali
        </span>

      </a>



      <!-- LOGIN HEADER -->

      <div class="login-heading">

        <div class="login-icon">

          <svg
            width="22"
            height="22"
            viewBox="0 0 24 24"
            fill="none"
          >

            <rect
              x="5"
              y="10"
              width="14"
              height="10"
              rx="2"
              stroke="#C8102E"
              stroke-width="1.6"
            />

            <path
              d="M8 10V7a4 4 0 0 1 8 0v3"
              stroke="#C8102E"
              stroke-width="1.6"
            />

          </svg>

        </div>


        <h2>
          Masuk Akun
        </h2>

      </div>


      <p class="intro">
        Masuk ke Telkom Fallout System
      </p>



      <!-- STATUS -->

      @if (session('status'))

        <div class="status-banner">
          {{ session('status') }}
        </div>

      @endif



      <!-- ERROR -->

      @if ($errors->any())

        <div class="error-banner">
          {{ $errors->first() }}
        </div>

      @endif



      <!-- FORM -->

      <form
        method="POST"
        action="{{ route('login') }}"
      >

        @csrf


        <!-- EMAIL -->

        <div class="field">

          <label for="email">
            Email
          </label>


          <div class="field-input">

            <svg
              width="17"
              height="17"
              viewBox="0 0 24 24"
              fill="none"
            >

              <path
                d="M4 6h16v12H4z"
                stroke="#A8A0A2"
                stroke-width="1.4"
              />

              <path
                d="M4 7l8 6 8-6"
                stroke="#A8A0A2"
                stroke-width="1.4"
                stroke-linecap="round"
              />

            </svg>


            <input
              id="email"
              name="email"
              type="email"
              placeholder="Masukkan email"
              value="{{ old('email') }}"
              required
              autocomplete="email"
            >

          </div>

        </div>



        <!-- PASSWORD -->

        <div class="field">

          <label for="password">
            Password
          </label>


          <div class="field-input">

            <svg
              width="17"
              height="17"
              viewBox="0 0 24 24"
              fill="none"
            >

              <rect
                x="5"
                y="10"
                width="14"
                height="10"
                rx="2"
                stroke="#A8A0A2"
                stroke-width="1.4"
              />

              <path
                d="M8 10V7a4 4 0 0 1 8 0v3"
                stroke="#A8A0A2"
                stroke-width="1.4"
              />

            </svg>


            <input
              id="password"
              name="password"
              type="password"
              placeholder="Masukkan password"
              required
              autocomplete="current-password"
            >


            <svg
              class="toggle-eye"
              id="eye-password"
              onclick="togglePassword(
                'password',
                'eye-password'
              )"
              width="17"
              height="17"
              viewBox="0 0 24 24"
              fill="none"
            >

              <path
                d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"
                stroke="#7A6B6F"
                stroke-width="1.5"
              />

              <circle
                cx="12"
                cy="12"
                r="3"
                stroke="#7A6B6F"
                stroke-width="1.5"
              />

            </svg>

          </div>

        </div>



        <!-- LUPA PASSWORD -->

        <div class="forgot-row">

          <a
            class="forgot"
            href="{{ route('password.request') }}"
          >
            Lupa password?
          </a>

        </div>



        <!-- LOGIN -->

        <button
          class="submit-btn"
          type="submit"
        >
          Masuk Dashboard
        </button>


      </form>

    </div>

  </section>



  <!-- =======================================================
       JAVASCRIPT
       ======================================================= -->

  <script>

    window.togglePassword = function togglePassword(
      inputId,
      eyeId
    ){

      const input =
        document.getElementById(
          inputId
        );


      const eye =
        document.getElementById(
          eyeId
        );


      const isHidden =
        input.type === 'password';


      input.type =
        isHidden
          ? 'text'
          : 'password';


      eye.innerHTML = isHidden

        ? '<path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8" stroke="#7A6B6F" stroke-width="1.5" stroke-linecap="round"/><path d="M9.9 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a15.6 15.6 0 0 1-3.1 3.9M6.2 6.2A15.6 15.6 0 0 0 2 12s3.6 7 10 7c1.2 0 2.3-.2 3.3-.5" stroke="#7A6B6F" stroke-width="1.5" stroke-linecap="round"/>'

        : '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z" stroke="#7A6B6F" stroke-width="1.5"/><circle cx="12" cy="12" r="3" stroke="#7A6B6F" stroke-width="1.5"/>';

    }

  </script>


</body>
</html>

