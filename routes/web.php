<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\AdminStaffController;
use App\Http\Controllers\Admin\AdminStaffAttendanceController;
use App\Http\Controllers\Admin\StampCorrectionRequestApprovalController;

// =====================
// 一般：登録 / ログイン / ログアウト（FormRequestで固定文言対応）
// =====================
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.post');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.post');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// =====================
// メール認証（FN011/FN012）
// =====================
Route::middleware('auth')->group(function () {
    // 認証誘導画面（verifiedミドルウェアの遷移先）
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    // 認証メール再送（FN012）
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        // テスト/実運用ともに確実に認証誘導画面へ戻す（referer依存を避ける）
        return redirect()->route('verification.notice')->with('status', 'verification-link-sent');
    })->middleware('throttle:6,1')->name('verification.send');

    // 認証リンク踏んだとき
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('attendance.index');
    })->middleware('signed')->name('verification.verify');
});

// TOP
Route::get('/', function () {
    if (!auth()->check()) {
        return redirect('/login');
    }

    // 管理者は管理画面へ
    if (auth()->user()->is_admin) {
        return redirect()->route('admin.attendance.list');
    }

    // 一般は打刻へ
    return redirect('/attendance');
});

// =====================
// 一般ユーザー（ログイン＋メール認証）
// =====================
Route::middleware(['auth', 'verified'])->group(function () {
    // 打刻
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/stamp', [AttendanceController::class, 'stamp'])->name('attendance.stamp');

    // 月次一覧
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');

    // 勤怠詳細
    Route::get('/attendance/detail/{attendance}', [AttendanceController::class, 'show'])->name('attendance.show');

    // 修正申請（勤怠詳細から）
    Route::post('/attendance/detail/{attendance}/request', [StampCorrectionRequestController::class, 'store'])
        ->name('stamp_request.store');
});

// =====================
// 申請一覧（一般/管理者 共通パス）
// ※管理者はメール認証を必須にしない想定のため、authのみ
// =====================
Route::middleware(['auth'])->group(function () {
    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])
        ->name('stamp_request.index');
});

// =====================
// 管理者ログイン/ログアウト（要件）
// =====================
Route::get('/admin/login', [AdminAuthController::class, 'show'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// =====================
// 管理者機能（要件）
// =====================
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    // 日次勤怠一覧
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.list');

    // 勤怠詳細（管理者）
    Route::get('/attendance/{attendance}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');

    // 勤怠修正（管理者）
    Route::post('/attendance/{attendance}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');

    // スタッフ一覧
    Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('admin.staff.list');

    // スタッフ別月次勤怠
    Route::get('/attendance/staff/{user}', [AdminStaffAttendanceController::class, 'index'])->name('admin.staff.attendance');

    // スタッフ別月次勤怠 CSV出力
    Route::get('/attendance/staff/{user}/export', [AdminStaffAttendanceController::class, 'export'])
        ->name('admin.staff.attendance.export');
});

// =====================
// 申請承認（要件：/stamp_correction_request/approve/{id}）
// =====================
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{stamp_correction_request}', [StampCorrectionRequestApprovalController::class, 'show'])
        ->name('stamp_request.approve.show');

    Route::post('/stamp_correction_request/approve/{stamp_correction_request}', [StampCorrectionRequestApprovalController::class, 'approve'])
        ->name('stamp_request.approve.post');
});
