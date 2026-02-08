<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StampCorrectionRequestApprovalController extends Controller
{
    /**
     * 承認画面
     */
    public function show(StampCorrectionRequest $stamp_correction_request)
    {
        $stamp_correction_request->load(['attendance.user', 'attendance.breakTimes', 'breaks']);

        return view('stamp_correction_request.approve', [
            'req' => $stamp_correction_request,
        ]);
    }

    /**
     * 承認処理（勤怠へ反映）
     */
    public function approve(StampCorrectionRequest $stamp_correction_request)
    {
        if ($stamp_correction_request->status !== 'pending') {
            return redirect()->route('stamp_request.index', ['status' => 'approved']);
        }

        DB::transaction(function () use ($stamp_correction_request) {
            $stamp_correction_request->load(['attendance', 'breaks']);

            $attendance = $stamp_correction_request->attendance;

            // 勤怠（出勤/退勤/備考）を申請内容で更新
            $attendance->update([
                'start_time' => $stamp_correction_request->requested_start_time,
                'end_time'   => $stamp_correction_request->requested_end_time,
                'note'       => $stamp_correction_request->requested_note,
            ]);

            // 休憩は申請内容に合わせて全置換
            $attendance->breakTimes()->delete();
            foreach ($stamp_correction_request->breaks as $b) {
                $attendance->breakTimes()->create([
                    'start_time' => $b->start_time,
                    'end_time'   => $b->end_time,
                ]);
            }

            // 申請を承認済みに
            $stamp_correction_request->update([
                'status'      => 'approved',
                'approved_at' => Carbon::now('Asia/Tokyo'),
                'approved_by' => auth()->id(),
            ]);
        });

        return redirect()->route('stamp_request.index', ['status' => 'pending']);
    }
}
