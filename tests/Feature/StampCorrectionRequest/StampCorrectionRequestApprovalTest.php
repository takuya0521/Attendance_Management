<?php

namespace Tests\Feature\StampCorrectionRequest;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use App\Models\StampCorrectionRequestBreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampCorrectionRequestApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_approval_screen(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-01',
            'start_time' => '2026-02-01 09:00:00',
            'end_time' => '2026-02-01 18:00:00',
            'note' => '',
        ]);

        $req = StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_start_time' => '2026-02-01 10:00:00',
            'requested_end_time' => '2026-02-01 19:00:00',
            'requested_note' => '申請備考',
        ]);

        StampCorrectionRequestBreakTime::create([
            'stamp_correction_request_id' => $req->id,
            'start_time' => '2026-02-01 12:00:00',
            'end_time' => '2026-02-01 13:00:00',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('stamp_request.approve.show', $req));

        $response->assertOk();
        $response->assertSeeText('修正申請承認');
        $response->assertSeeText('氏名：' . $user->name);
        $response->assertSeeText('日付：2026-02-01');
        $response->assertSeeText('状態：承認待ち');

        $response->assertSeeText('申請内容');
        $response->assertSeeText('出勤：10:00');
        $response->assertSeeText('退勤：19:00');
        $response->assertSeeText('備考：申請備考');

        $response->assertSeeText('休憩');
        $response->assertSeeText('12:00 - 13:00');

        // pending のときだけ承認ボタンが出る
        $response->assertSeeText('承認');
    }

    public function test_admin_can_approve_and_reflect_to_attendance(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-01',
            'start_time' => '2026-02-01 09:00:00',
            'end_time' => '2026-02-01 18:00:00',
            'note' => '元の備考',
        ]);

        // 既存休憩（承認時に全置換される想定）
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '2026-02-01 11:00:00',
            'end_time' => '2026-02-01 11:15:00',
        ]);

        $req = StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_start_time' => '2026-02-01 10:00:00',
            'requested_end_time' => '2026-02-01 19:00:00',
            'requested_note' => '申請後の備考',
        ]);

        StampCorrectionRequestBreakTime::create([
            'stamp_correction_request_id' => $req->id,
            'start_time' => '2026-02-01 12:00:00',
            'end_time' => '2026-02-01 13:00:00',
        ]);
        StampCorrectionRequestBreakTime::create([
            'stamp_correction_request_id' => $req->id,
            'start_time' => '2026-02-01 15:00:00',
            'end_time' => '2026-02-01 15:30:00',
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('stamp_request.approve.post', $req));

        $response->assertRedirect(route('stamp_request.index', ['status' => 'pending']));

        $attendance->refresh();
        $req->refresh();

        // 勤怠へ反映
        $this->assertSame('10:00', $attendance->start_time->format('H:i'));
        $this->assertSame('19:00', $attendance->end_time->format('H:i'));
        $this->assertSame('申請後の備考', $attendance->note);

        // 休憩が全置換されていること（件数と内容）
        $this->assertSame(2, $attendance->breakTimes()->count());
        $breaks = $attendance->breakTimes()->orderBy('start_time')->get();
        $this->assertSame('12:00', $breaks[0]->start_time->format('H:i'));
        $this->assertSame('13:00', $breaks[0]->end_time->format('H:i'));
        $this->assertSame('15:00', $breaks[1]->start_time->format('H:i'));
        $this->assertSame('15:30', $breaks[1]->end_time->format('H:i'));

        // 申請が承認済みに更新されていること
        $this->assertSame('approved', $req->status);
        $this->assertNotNull($req->approved_at);
        $this->assertSame($admin->id, $req->approved_by);
    }

    public function test_approve_post_redirects_when_request_is_not_pending(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-01',
            'start_time' => '2026-02-01 09:00:00',
            'end_time' => '2026-02-01 18:00:00',
            'note' => '',
        ]);

        $req = StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'requested_start_time' => '2026-02-01 10:00:00',
            'requested_end_time' => '2026-02-01 19:00:00',
            'requested_note' => '申請備考',
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('stamp_request.approve.post', $req));

        // Controller実装どおり：approved なら approved 一覧へ
        $response->assertRedirect(route('stamp_request.index', ['status' => 'approved']));
    }
}
