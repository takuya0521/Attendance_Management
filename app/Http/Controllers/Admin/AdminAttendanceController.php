<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdateAttendanceRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAttendanceController extends Controller
{
    /**
     * 日次勤怠一覧
     * /admin/attendance/list?date=YYYY-MM-DD
     */
    public function index(Request $request)
    {
        try {
            $date = $request->query('date')
                ? Carbon::createFromFormat('Y-m-d', $request->query('date'), 'Asia/Tokyo')
                : Carbon::now('Asia/Tokyo');
        } catch (\Throwable $e) {
            $date = Carbon::now('Asia/Tokyo');
        }

        $attendances = Attendance::with(['user', 'breakTimes'])
            ->where('date', $date->toDateString())
            ->orderBy('user_id')
            ->get();

        return view('admin.attendance.list', [
            'date' => $date,
            'attendances' => $attendances,
            'prev' => $date->copy()->subDay()->toDateString(),
            'next' => $date->copy()->addDay()->toDateString(),
        ]);
    }

    /**
     * 勤怠詳細（管理者）
     */
    public function show(Attendance $attendance)
    {
        $attendance->load(['user', 'breakTimes', 'correctionRequests']);
        $locked = $attendance->pendingCorrectionRequest()->exists();

        return view('admin.attendance.detail', compact('attendance', 'locked'));
    }

    /**
     * 勤怠修正（管理者）
     */
    public function update(AdminUpdateAttendanceRequest $request, Attendance $attendance)
    {
        if ($attendance->pendingCorrectionRequest()->exists()) {
            return back()->withErrors(['locked' => '承認待ちのため修正はできません。']);
        }

        $date = $attendance->date->format('Y-m-d');
        $start = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$request->input('start_time'), 'Asia/Tokyo');
        $end   = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$request->input('end_time'), 'Asia/Tokyo');

        DB::transaction(function () use ($request, $attendance, $start, $end, $date) {
            $attendance->update([
                'start_time' => $start,
                'end_time' => $end,
                'note' => $request->input('note'),
            ]);

            // break_times は全置換（確実）
            $attendance->breakTimes()->delete();

            $bs = $request->input('break_start', []);
            $be = $request->input('break_end', []);

            for ($i = 0; $i < max(count($bs), count($be)); $i++) {
                $s = $bs[$i] ?? null;
                $e = $be[$i] ?? null;

                if (!$s && !$e) continue;
                if (!$s || !$e) continue;

                $attendance->breakTimes()->create([
                    'start_time' => Carbon::createFromFormat('Y-m-d H:i', $date.' '.$s, 'Asia/Tokyo'),
                    'end_time'   => Carbon::createFromFormat('Y-m-d H:i', $date.' '.$e, 'Asia/Tokyo'),
                ]);
            }
        });

        return back();
    }
}
