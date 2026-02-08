@extends('layouts.app')

@section('title', '会員登録')
@section('body_class', 'auth')

@section('content')
  <style>
    /* 会員登録：ログイン画面と同じ見た目に寄せる */
    body.auth .authLink{
      display:block;
      text-align:center;
      margin:18px auto 0;
      color:#1a73e8;
      font-weight:900;
      text-decoration:none;
    }
    body.auth .authLink:hover{ opacity:.85; }
  </style>

  <div class="card" style="margin:0 auto;">
    <h1>会員登録</h1>

    <form method="POST" action="{{ route('register') }}" novalidate>
      @csrf

      <div class="field">
        <label>名前</label>
        <input
          type="text"
          name="name"
          value="{{ old('name') }}"
          class="@error('name') invalid @enderror"
        >
        @error('name')
          <div class="error-text">{{ $message }}</div>
        @enderror
      </div>

      <div class="field">
        <label>メールアドレス</label>
        <input
          type="email"
          name="email"
          value="{{ old('email') }}"
          class="@error('email') invalid @enderror"
        >
        @error('email')
          <div class="error-text">{{ $message }}</div>
        @enderror
      </div>

      <div class="field">
        <label>パスワード</label>
        <input
          type="password"
          name="password"
          class="@error('password') invalid @enderror"
        >
        @error('password')
          <div class="error-text">{{ $message }}</div>
        @enderror
      </div>

      <div class="field">
        <label>パスワード確認</label>
        <input
          type="password"
          name="password_confirmation"
        >
      </div>

      <button class="btn" type="submit">登録する</button>
    </form>

    <a class="authLink" href="{{ route('login') }}">ログインはこちら</a>
  </div>
@endsection
