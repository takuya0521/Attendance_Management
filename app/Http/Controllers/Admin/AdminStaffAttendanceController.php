<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStaffAttendanceController extends Controller
{
    /**
     * スタッフ別 月次勤怠
     * /admin/attendance/staff/{user}?month=YYYY-MM
     */
    public function index(Request $request, User $user)
    {
        abort_if($user->is_admin, 404);

        $month = $request->query('month');

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
            ->where('user_id', $user->id)
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

        return view('admin.staff.attendance', [
            'user' => $user,
            'base' => $base,
            'days' => $days,
            'prevMonth' => $base->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $base->copy()->addMonth()->format('Y-m'),
        ]);
    }

    /**
     * CSV出力
     * /admin/attendance/staff/{user}/export?month=YYYY-MM
     */
    public function export(Request $request, User $user): StreamedResponse
    {
        abort_if($user->is_admin, 404);

        $month = $request->query('month');

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
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        $filename = 'attendance_' . $user->id . '_' . $base->format('Y-m') . '.csv';

        return response()->streamDownload(function () use ($start, $end, $attendances) {
            $out = fopen('php://output', 'w');

            // ヘッダ
            fputcsv($out, ['日付', '出勤', '退勤', '休憩（分）', '備考']);

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $a = $attendances->get($d->toDateString());

                $breakMinutes = 0;
                if ($a) {
                    foreach ($a->breakTimes as $b) {
                        if ($b->end_time) {
                            $breakMinutes += $b->end_time->diffInMinutes($b->start_time);
                        }
                    }
                }

                fputcsv($out, [
                    $d->toDateString(),
                    $a?->start_time?->format('H:i') ?? '',
                    $a?->end_time?->format('H:i') ?? '',
                    $breakMinutes ? (string)$breakMinutes : '',
                    $a?->note ?? '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
