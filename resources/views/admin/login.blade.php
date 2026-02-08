@extends('layouts.app')

@section('title', '管理者ログイン')
@section('body_class', 'auth')

@section('content')
  <div class="card">
    <h1>管理者ログイン</h1>

    <form method="POST" action="{{ route('admin.login.post') }}" novalidate>
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

      @error('login')
        <div class="error-text" style="margin-top:10px;">{{ $message }}</div>
      @enderror

      <button class="btn" type="submit">管理者ログインする</button>
    </form>
  </div>
@endsection
