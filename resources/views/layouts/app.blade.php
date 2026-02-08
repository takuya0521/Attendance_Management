<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', '勤怠管理')</title>
  <style>
    :root { --bg:#0b1020; --card:#111833; --text:#e8ecff; --muted:#a7b0d6; --line:#24305d; --danger:#ff6b6b; }

    body{
      margin:0;
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;
      background:var(--bg);
      color:var(--text);
    }
    a{ color:inherit; text-decoration:none; }

    /* ===== Header ===== */
    .header{
      border-bottom:1px solid #111;   /* 見本の黒帯に合わせて濃く */
      background:#000;
      position:sticky;
      top:0;
      z-index:10;
    }
    .wrap{ max-width:1100px; margin:0 auto; padding:16px 16px; } /* 少しだけ高さを見本寄せ */
    .row{ display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .brand{ font-weight:700; letter-spacing:.5px; display:flex; align-items:center; }
    .brand__logo{ height:28px; width:auto; display:block; }

    /* 見本：右側は “白文字テキストリンク” のみ */
    .nav{
      display:flex;
      gap:34px;
      flex-wrap:wrap;
      align-items:center;
      justify-content:flex-end;
    }

    /* pill/btn を“装飾無しリンク”に統一（見本寄せ） */
    .pill{
      padding:0;
      border:0;
      border-radius:0;
      color:#fff;
      font-weight:800;
      letter-spacing:.02em;
      background:transparent;
    }
    .pill:hover{ opacity:.85; }

    .btn{
      padding:0;
      border:0;
      background:transparent;
      color:#fff;
      border-radius:0;
      cursor:pointer;
      font-weight:800;
      letter-spacing:.02em;
    }
    .btn:hover{ opacity:.85; }

    /* form内のボタンもリンクっぽく */
    .nav form{ margin:0; }
    .nav form button{ font:inherit; }

    /* ===== Layout ===== */
    .container{ max-width:1100px; margin:0 auto; padding:18px 16px 40px; }
    .card{ background:var(--card); border:1px solid var(--line); border-radius:14px; padding:14px; }

    /* ===== Flash message (周りの白基調デザインに合わせる) ===== */
    .flash{
      max-width:920px;
      margin:18px auto 0;
      padding:14px 18px;
      border-radius:10px;
      border:1px solid #e6e6e6;
      background:#fff;
      color:#111;
      font-weight:700;
      box-shadow:0 2px 0 rgba(0,0,0,.06);
      display:flex;
      align-items:flex-start;
      gap:12px;
      line-height:1.6;
    }
    .flash::before{
      content:"";
      width:4px;
      height:auto;
      align-self:stretch;
      border-radius:999px;
      background:#111;
      opacity:.85;
      flex:0 0 auto;
    }

    /* ===== Form ===== */
    .field{ margin:12px 0; }
    .field label{ display:block; margin-bottom:6px; color:var(--muted); font-size:14px; }
    .field input, .field textarea, .field select{
      width:100%;
      max-width:100%;
      box-sizing:border-box;
      padding:10px 12px;
      border-radius:10px;
      border:1px solid var(--line);
      background:#0f1633;
      color:var(--text);
      outline:none;
    }
    .field input.invalid, .field textarea.invalid, .field select.invalid{ border-color: var(--danger); }
    .error-text{
      margin-top:6px;
      color: var(--danger);
      font-size: 13px;
      line-height: 1.4;
    }

    /* ===== Auth pages (login/register) ===== */
    body.auth{ background:#fff; color:#111; }
    body.auth .nav{ display:none; } /* ログイン画面はヘッダー：ロゴのみ */
    body.auth .container{
      max-width:none;
      padding:48px 16px 60px;
      min-height: calc(100vh - 58px);
      display:flex;
      justify-content:center;
      align-items:flex-start;
    }

    /* “カード”ではなく、スクショの「白い面」に寄せる */
    body.auth .card{
      background:#fff;
      color:#111;
      border:0;
      border-radius:0;
      padding:56px 64px;
      width:min(980px, 100%);
      box-shadow:none;
    }

    body.auth form{ max-width:520px; margin:0 auto; }
    body.auth h1{
      font-size: 34px;
      font-weight: 900;
      margin: 0 0 34px;
      text-align: center;
      letter-spacing: .02em;
    }

    body.auth .authMsg{
      font-size: 20px;
      font-weight: 900;
      line-height: 1.9;
      letter-spacing: .02em;
      margin: 0 0 34px;
      text-align: center;
    }

    body.auth .authLink{
      display:block;
      margin-top: 18px;
      text-align:center;
      color:#1a73e8;
      font-weight:800;
      text-decoration:none;
      background: transparent;
      border: 0;
      padding: 0;
      cursor: pointer;
    }
    body.auth .authLink:hover{ opacity:.85; }
    body.auth .field{ margin:22px 0; max-width:520px; }
    body.auth .field label{ color:#111; font-size:14px; font-weight:700; }
    body.auth .field input{
      background:#fff;
      color:#111;
      border:1px solid #bdbdbd;
      border-radius:3px;
      padding:12px 12px;
    }
    body.auth .btn{
      background:#000;
      border:1px solid #000;
      color:#fff;
      border-radius:3px;
      padding:14px 16px;
      width:360px;
      display:block;
      margin:30px auto 0;
    }

    /* auth のフラッシュはフォーム幅に合わせる */
    body.auth .flash{
      max-width:520px;
      margin:0 auto 22px;
    }

    /* ===== Attendance List like design ===== */
    body.attendance-list{
      background:#f3f3f3;
      color:#111;
    }
    body.attendance-list .container{
      max-width: 920px;
      padding: 34px 16px 60px;
    }

    .pageTitle{
      display:flex;
      align-items:center;
      gap:14px;
      font-size:20px;
      font-weight:800;
      margin: 0 0 18px;
      color:#111;
    }
    .pageTitle::before{
      content:"";
      width:4px;
      height:22px;
      background:#111;
      border-radius:2px;
      display:inline-block;
    }

    .monthNav{
      background:#fff;
      border-radius:8px;
      padding: 12px 18px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      margin: 0 0 18px;
    }
    .monthNav__link{
      display:inline-flex;
      align-items:center;
      gap:8px;
      color:#666;
      font-weight:700;
      text-decoration:none;
    }
    .monthNav__center{
      display:inline-flex;
      align-items:center;
      gap:10px;
      color:#111;
      font-weight:800;
    }

    .tableCard{
      background:#fff;
      border-radius:8px;
      overflow:hidden;
    }
    .table{
      width:100%;
      border-collapse:collapse;
      table-layout:fixed;
    }
    .table thead th{
      background:#f5f5f5;
      color:#666;
      font-size:12px;
      font-weight:700;
      padding: 12px 14px;
      text-align:center;
    }
    .table tbody td{
      border-top:1px solid #e6e6e6;
      padding: 12px 14px;
      text-align:center;
      color:#333;
      font-weight:600;
    }
    .table tbody td:first-child{
      text-align:left;
      padding-left:18px;
      letter-spacing:.02em;
    }
    .detailLink{
      color:#111;
      font-weight:800;
      text-decoration:none;
    }
  </style>
</head>

<body class="@yield('body_class')">
<header class="header">
  <div class="wrap">
    <div class="row">
      <div class="brand">
        @php
          // 「admin配下」なら is_admin 判定がブレても管理者ナビに寄せる（設計書優先）
          $isAdminContext = auth()->check() && (auth()->user()->is_admin || request()->is('admin/*'));
        @endphp
        <a href="{{ $isAdminContext ? route('admin.attendance.list') : url('/') }}">
          <img class="brand__logo" src="{{ asset('images/coachtech-logo.png') }}" alt="COACHTECH">
        </a>
      </div>

      @php
        $hideNav = request()->is('email/verify')
          || request()->routeIs('login', 'register', 'verification.notice', 'admin.login');
      @endphp
      @if(!$hideNav)
      <nav class="nav">
        @auth
          @if($isAdminContext)
            <a class="pill" href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
            <a class="pill" href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
            <a class="pill" href="{{ route('stamp_request.index', ['status'=>'pending']) }}">申請一覧</a>

            <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
              @csrf
              <button class="btn" type="submit">ログアウト</button>
            </form>
          @else
            <a class="pill" href="{{ route('attendance.index') }}">勤怠</a>
            <a class="pill" href="{{ route('attendance.list') }}">勤怠一覧</a>
            <a class="pill" href="{{ route('stamp_request.index', ['status'=>'pending']) }}">申請</a>

            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
              @csrf
              <button class="btn" type="submit">ログアウト</button>
            </form>
          @endif
        @else
          <a class="pill" href="{{ route('login') }}">ログイン</a>
          <a class="pill" href="{{ route('register') }}">会員登録</a>
        @endauth
      </nav>
      @endif
    </div>
  </div>
</header>

<main class="container">
  @if (session('status'))
    @php $st = session('status'); @endphp

    {{-- 「お疲れ様でした。」は上の帯デザイン不要（（打刻トップでだけ非表示） --}}
    @if (!(request()->routeIs('attendance.index') && \Illuminate\Support\Str::contains($st, 'お疲れ')) && $st !== 'verification-link-sent')
      <div class="flash">{{ $st }}</div>
    @endif
  @endif

  @yield('content')
</main>
</body>
</html>
