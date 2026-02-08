@extends('layouts.app')

@section('title', 'メール認証')

@section('content')
  <style>
    /* ===== Verify Email (見本寄せ) ===== */
    body { background:#fff; color:#111; }

    .verifyWrap{
      max-width: 980px;
      margin: 0 auto;
      padding: 120px 16px 140px;
      text-align: center;
    }

    .verifyMsg{
      font-size: 20px;
      font-weight: 900;
      line-height: 1.9;
      letter-spacing: .02em;
      margin: 0 0 34px;
    }

    /* フラッシュ（再送しました等） */
    .flash{
      max-width: 820px;
      margin: 0 auto 26px;
      padding: 14px 18px;
      border: 1px solid #e6e6e6;
      border-radius: 12px;
      background: #fafafa;
      color: #111;
      font-weight: 800;
      text-align: left;
    }

    .verifyActions{
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 18px;
      margin-top: 10px;
    }

    .primaryBtn{
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 260px;
      height: 56px;
      padding: 0 34px;
      border-radius: 10px;
      background: #e6e6e6;
      border: 2px solid #222;
      color: #111;
      font-weight: 900;
      font-size: 18px;
      text-decoration: none;
      box-shadow: 0 2px 0 rgba(0,0,0,.12);
    }
    .primaryBtn:hover{ opacity: .92; }

    .linkLike{
      background: transparent;
      border: 0;
      padding: 0;
      color: #1a73e8;
      font-weight: 800;
      font-size: 16px;
      text-decoration: none;
      cursor: pointer;
    }
    .linkLike:hover{ opacity: .85; }

    .subLinks{
      margin-top: 16px;
      display: flex;
      justify-content: center;
      gap: 18px;
      flex-wrap: wrap;
    }
    .subLinks a{
      color: #666;
      font-weight: 700;
      text-decoration: none;
      font-size: 14px;
    }
    .subLinks a:hover{ opacity: .85; }
  </style>

  <div class="verifyWrap">
    @if (session('status') === 'verification-link-sent')
      <div class="flash">認証メールを再送しました。</div>
    @endif

    <p class="verifyMsg">
      登録していただいたメールアドレスに認証メールを送付しました。<br>
      メール認証を完了してください。
    </p>

    <div class="verifyActions">
      {{-- 認証メールを確認（ローカルMailpit想定） --}}
      <a class="primaryBtn" href="http://localhost:8025" target="_blank" rel="noopener">
        認証はこちらから
      </a>

      {{-- 認証メールを再送（POST） --}}
      <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="linkLike">認証メールを再送する</button>
      </form>
    </div>
  </div>
@endsection
