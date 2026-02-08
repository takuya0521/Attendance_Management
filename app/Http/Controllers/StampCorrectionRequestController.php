<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStampCorrectionRequest;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StampCorrectionRequestController extends Controller
{
    /**
     * 申請一覧（一般/管理者 共通パス）
     * /stamp_correction_request/list?status=pending|approved
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        if (!in_array($status, ['pending', 'approved'], true)) {
            $status = 'pending';
        }

        $q = StampCorrectionRequest::with(['attendance.user', 'breaks'])
            ->when(!auth()->user()->is_admin, function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->where('status', $status)
            ->latest();

        $requests = $q->get();

        return view('stamp_correction_request.list', compact('requests', 'status'));
    }

    /**
     * 修正申請作成（一般）
     * POST /attendance/detail/{attendance}/request
     */
    public function store(StoreStampCorrectionRequest $request, Attendance $attendance)
    {
        abort_unless($attendance->user_id === auth()->id(), 403);

        if ($attendance->pendingCorrectionRequest()->exists()) {
            return back()->withErrors(['locked' => '承認待ちのため修正はできません。']);
        }

        // FormRequest でバリデーション（ここで確定）
        $data = $request->validated();

        $date = $attendance->date->format('Y-m-d');

        $start = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $data['start_time'], 'Asia/Tokyo');
        $end   = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $data['end_time'], 'Asia/Tokyo');

        DB::transaction(function () use ($data, $attendance, $start, $end, $date) {
            $sr = StampCorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'user_id' => auth()->id(),
                'status' => 'pending',
                'requested_start_time' => $start,
                'requested_end_time' => $end,
                'requested_note' => $data['note'],
            ]);

            $bs = $data['break_start'] ?? [];
            $be = $data['break_end'] ?? [];

            for ($i = 0; $i < max(count($bs), count($be)); $i++) {
                $s = $bs[$i] ?? null;
                $e = $be[$i] ?? null;

                if (!$s && !$e) {
                    continue;
                }
                if (!$s || !$e) {
                    continue;
                }

                $sr->breaks()->create([
                    'start_time' => Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $s, 'Asia/Tokyo'),
                    'end_time'   => Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $e, 'Asia/Tokyo'),
                ]);
            }
        });

        return redirect()->route('stamp_request.index', ['status' => 'pending']);
    }
}
