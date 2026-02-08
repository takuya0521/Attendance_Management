<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        // 既にログイン済みなら役割に応じてトップへ
        if (auth()->check()) {
            return auth()->user()->is_admin
                ? redirect()->route('admin.attendance.list')
                : redirect()->route('attendance.index');
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        $ok = Auth::attempt($request->only('email', 'password'));

        if (!$ok) {
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が登録されていません',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        // メール認証が必要なユーザーは認証画面へ
        if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // ★ここが重要：設計書通り、管理者は管理画面へ
        $default = $user->is_admin
            ? route('admin.attendance.list')
            : route('attendance.index');

        return redirect()->intended($default);
    }

    public function destroy(Request $request)
    {
        // logout後は user() が消えるので先に保持
        $user = $request->user();
        $isAdmin = $user && $user->is_admin;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $isAdmin
            ? redirect()->route('admin.login')
            : redirect()->route('login');
    }
}
