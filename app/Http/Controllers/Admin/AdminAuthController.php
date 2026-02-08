<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    // 管理者ログイン画面
    public function show(Request $request)
    {
        // 既に管理者ログイン済みなら管理画面へ
        if (auth()->check() && auth()->user()->is_admin) {
            return redirect()->route('admin.attendance.list');
        }

        // 一般ユーザーでログイン済みの場合は混線防止でログアウト
        if (auth()->check() && !auth()->user()->is_admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return view('admin.login');
    }

    // 管理者ログイン処理（is_admin=true のみ許可）
    public function login(AdminLoginRequest $request)
    {
        $ok = Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
            'is_admin' => true,
        ]);

        if (!$ok) {
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が登録されていません',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('admin.attendance.list');
    }

    // 管理者ログアウト
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
