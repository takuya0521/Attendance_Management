<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, CreatesNewUsers $creator)
    {
        // Fortifyの契約（CreatesNewUsers）を利用して作成＝ fortify利用
        $data = $request->validated() + [
            'password_confirmation' => $request->input('password_confirmation'),
        ];

        $user = $creator->create($data);
        Auth::login($user);

        // メール認証ON：誘導へ
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            return redirect()->route('verification.notice');
        }

        // メール認証OFFの場合（FN005）
        return redirect()->route('attendance.index');
    }
}
