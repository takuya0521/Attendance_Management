@extends('layouts.app')

@section('title', 'ログイン')
@section('body_class', 'auth')

@section('content')
  <style>
    /* login 画面だけ微調整（layouts の auth 共通CSSはそのまま使う） */
    body.auth .authBox h1{
      font-size: 34px;
      font-weight: 900;
      margin: 0 0 34px;
      text-align: center;
      letter-spacing: .02em;
    }
    body.auth .authBox .authLink{
      display:block;
      margin-top: 18px;
      text-align:center;
      color:#1a73e8;
      font-weight:800;
      text-decoration:none;
    }
    body.auth .authBox .authLink:hover{ opacity:.85; }
  </style>

  <div class="card authBox" style="margin:0 auto;">
    <h1>ログイン</h1>

    <form method="POST" action="{{ route('login') }}" novalidate>
      @csrf

      <div class="field">
        <label>メールアドレス</label>
        <input type="email" name="email" value="{{ old('email') }}">
        @error('email')
          <div class="error-text">{{ $message }}</div>
        @enderror
      </div>

      <div class="field">
        <label>パスワード</label>
        <input type="password" name="password">
        @error('password')
          <div class="error-text">{{ $message }}</div>
        @enderror
      </div>

      {{-- 認証失敗（例: withErrors(['login'=>...])） --}}
      @error('login')
        <div class="error-text" style="margin-top:10px;">{{ $message }}</div>
      @enderror

      <button class="btn" type="submit">ログインする</button>

      <a class="authLink" href="{{ route('register') }}">会員登録はこちら</a>
    </form>
  </div>
@endsection
