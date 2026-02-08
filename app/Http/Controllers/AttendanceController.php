<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * 打刻画面
     */
    public function index()
    {
        $now = Carbon::now('Asia/Tokyo');
        $today = $now->toDateString();

        $attendance = Attendance::with('breakTimes')
            ->where('user_id', auth()->id())
            ->where('date', $today)
            ->first();

        $status = '勤務外';
        if ($attendance) {
            if ($attendance->end_time) {
                $status = '退勤済';
            } else {
                $onBreak = $attendance->breakTimes()->whereNull('end_time')->exists();
                $status = $onBreak ? '休憩中' : '出勤中';
            }
        }

        return view('attendance.index', compact('now', 'attendance', 'status'));
    }

    /**
     * 打刻処理（出勤/休憩入/休憩戻/退勤）
     */
    public function stamp(Request $request)
    {
        $request->validate([
            'action' => ['required', 'in:clock_in,break_start,break_end,clock_out'],
        ]);

        $now = Carbon::now('Asia/Tokyo');
        $today = $now->toDateString();
        $userId = auth()->id();
        $action = $request->input('action');

        $flash = null;

        DB::transaction(function () use ($action, $now, $today, $userId, &$flash) {
            $attendance = Attendance::where('user_id', $userId)
                ->where('date', $today)
                ->lockForUpdate()
                ->first();

            // 出勤（1日1回）
            if ($action === 'clock_in') {
                if ($attendance) return;

                Attendance::create([
                    'user_id' => $userId,
                    'date' => $today,
                    'start_time' => $now,
                ]);

                $flash = '出勤しました。';
                return;
            }

            // 出勤してない or 退勤済み は何もしない
            if (!$attendance || $attendance->end_time) return;

            $openBreak = BreakTime::where('attendance_id', $attendance->id)
                ->whereNull('end_time')
                ->lockForUpdate()
                ->first();

            // 休憩入（何回でも / ただし休憩中は不可）
            if ($action === 'break_start') {
                if ($openBreak) return;

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'start_time' => $now,
                ]);

                $flash = '休憩に入りました。';
                return;
            }

            // 休憩戻（休憩中のみ）
            if ($action === 'break_end') {
                if (!$openBreak) return;

                $openBreak->update([
                    'end_time' => $now,
                ]);

                $flash = '休憩から戻りました。';
                return;
            }

            // 退勤（1日1回 / 休憩中は不可）
            if ($action === 'clock_out') {
                if ($openBreak) return;

                $attendance->update([
                    'end_time' => $now,
                ]);

                // ★FN022-3 固定文言
                $flash = 'お疲れ様でした。';
                return;
            }
        });

        if ($flash) {
            session()->flash('status', $flash);
        }

        return back();
    }

    /**
     * 月次勤怠一覧
     * /attendance/list?month=YYYY-MM
     */
    public function list(Request $request)
    {
        $month = $request->query('month'); // YYYY-MM

        try {
            $base = $month
                ? Carbon::createFromFormat('Y-m', $month, 'Asia/Tokyo')
                : Carbon::now('Asia/Tokyo');
        } catch (\Throwable $e) {
            $base = Carbon::now('Asia/Tokyo');
        }

        $start = $base->copy()->startOfMonth();
        $end = $base->copy()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', auth()->id())
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->toDateString();
            $days[] = [
                'date' => $d->copy(),
                'attendance' => $attendances->get($key),
            ];
        }

        return view('attendance.list', [
            'base' => $base,
            'days' => $days,
            'prevMonth' => $base->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $base->copy()->addMonth()->format('Y-m'),
        ]);
    }

    /**
     * 勤怠詳細（一般）
     */
    public function show(Attendance $attendance)
    {
        abort_unless($attendance->user_id === auth()->id(), 403);

        $attendance->load(['user', 'breakTimes', 'correctionRequests']);

        $pending = $attendance->pendingCorrectionRequest()->exists();

        return view('attendance.detail', compact('attendance', 'pending'));
    }
}
