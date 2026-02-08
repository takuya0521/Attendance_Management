<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampCorrectionRequestApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_stamp_correction_request_and_apply_to_attendance(): void
    {
        // 管理者
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => null, // 管理者はverified不要の想定
        ]);

        // 一般ユーザー（申請者）
        $user = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        // 既存勤怠
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-01',
            'start_time' => '2026-02-01 09:00:00',
            'end_time' => '2026-02-01 18:00:00',
            'note' => '元の備考',
        ]);

        // 既存休憩（このあと承認で全置換される想定）
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '2026-02-01 12:00:00',
            'end_time' => '2026-02-01 12:30:00',
        ]);

        // 修正申請（pending）
        $reqStart = Carbon::create(2026, 2, 1, 10, 0, 0, 'Asia/Tokyo');
        $reqEnd   = Carbon::create(2026, 2, 1, 19, 0, 0, 'Asia/Tokyo');

        $req = StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_start_time' => $reqStart,
            'requested_end_time' => $reqEnd,
            'requested_note' => '申請の備考',
        ]);

        // 申請休憩（承認時に勤怠休憩へ反映される）
        $req->breaks()->create([
            'start_time' => Carbon::create(2026, 2, 1, 13, 0, 0, 'Asia/Tokyo'),
            'end_time' => Carbon::create(2026, 2, 1, 13, 15, 0, 'Asia/Tokyo'),
        ]);
        $req->breaks()->create([
            'start_time' => Carbon::create(2026, 2, 1, 16, 0, 0, 'Asia/Tokyo'),
            'end_time' => Carbon::create(2026, 2, 1, 16, 20, 0, 'Asia/Tokyo'),
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('stamp_request.approve.post', $req));

        // コントローラ実装通り：承認後は pending 一覧へ戻す
        $response->assertRedirect(route('stamp_request.index', ['status' => 'pending']));

        // 申請が approved になっている
        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $req->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        // 勤怠が申請内容で更新されている
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'start_time' => '2026-02-01 10:00:00',
            'end_time' => '2026-02-01 19:00:00',
            'note' => '申請の備考',
        ]);

        // 既存休憩が消えている（全置換）
        $this->assertDatabaseMissing('break_times', [
            'attendance_id' => $attendance->id,
            'start_time' => '2026-02-01 12:00:00',
            'end_time' => '2026-02-01 12:30:00',
        ]);

        // 申請休憩が勤怠休憩に反映されている
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'start_time' => '2026-02-01 13:00:00',
            'end_time' => '2026-02-01 13:15:00',
        ]);
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'start_time' => '2026-02-01 16:00:00',
            'end_time' => '2026-02-01 16:20:00',
        ]);
    }

    public function test_non_admin_cannot_access_approve_route(): void
    {
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
            'requested_start_time' => Carbon::create(2026, 2, 1, 10, 0, 0, 'Asia/Tokyo'),
            'requested_end_time' => Carbon::create(2026, 2, 1, 19, 0, 0, 'Asia/Tokyo'),
            'requested_note' => '申請',
        ]);

        $this->actingAs($user);

        $this->get(route('stamp_request.approve.show', $req))->assertStatus(403);
        $this->post(route('stamp_request.approve.post', $req))->assertStatus(403);
    }

    public function test_approve_redirects_when_request_is_not_pending(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => null,
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

        // すでにapproved
        $req = StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'requested_start_time' => Carbon::create(2026, 2, 1, 10, 0, 0, 'Asia/Tokyo'),
            'requested_end_time' => Carbon::create(2026, 2, 1, 19, 0, 0, 'Asia/Tokyo'),
            'requested_note' => '申請',
            'approved_at' => Carbon::now('Asia/Tokyo'),
            'approved_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        $this->post(route('stamp_request.approve.post', $req))
            ->assertRedirect(route('stamp_request.index', ['status' => 'approved']));
    }
}
