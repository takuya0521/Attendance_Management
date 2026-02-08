<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class AdminStaffController extends Controller
{
    /**
     * スタッフ一覧（一般ユーザーのみ）
     */
    public function index()
    {
        $users = User::where('is_admin', false)
            ->orderBy('id')
            ->get();

        return view('admin.staff.list', compact('users'));
    }
}
