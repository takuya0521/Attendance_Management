<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailCorrectionValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_time_is_invalid_when_after_end_time(): void
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

        $this->actingAs($user);

        $response = $this->from(route('attendance.show', $attendance))
            ->post(route('stamp_request.store', $attendance), [
                'start_time' => '19:00',
                'end_time' => '18:00',
                'note' => 'テスト',
                'break_start' => [],
                'break_end' => [],
            ]);

        $response->assertRedirect(route('attendance.show', $attendance));
        $response->assertSessionHasErrors(['start_time']);

        $this->followRedirects($response)
            ->assertSeeText('出勤時間が不適切な値です');
    }

    public function test_break_time_is_invalid_when_start_after_end_time(): void
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

        $this->actingAs($user);

        $response = $this->from(route('attendance.show', $attendance))
            ->post(route('stamp_request.store', $attendance), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'note' => 'テスト',
                'break_start' => ['13:00'],
                'break_end' => ['12:00'],
            ]);

        $response->assertRedirect(route('attendance.show', $attendance));
        $response->assertSessionHasErrors(['break_start.0']);

        $this->followRedirects($response)
            ->assertSeeText('休憩時間が不適切な値です');
    }

    public function test_break_time_is_invalid_when_end_after_end_time(): void
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

        $this->actingAs($user);

        $response = $this->from(route('attendance.show', $attendance))
            ->post(route('stamp_request.store', $attendance), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'note' => 'テスト',
                'break_start' => ['17:30'],
                'break_end' => ['18:30'],
            ]);

        $response->assertRedirect(route('attendance.show', $attendance));
        $response->assertSessionHasErrors(['break_end.0']);

        $this->followRedirects($response)
            ->assertSeeText('休憩時間もしくは退勤時間が不適切な値です');
    }

    public function test_note_is_required_on_correction_request(): void
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

        $this->actingAs($user);

        $response = $this->from(route('attendance.show', $attendance))
            ->post(route('stamp_request.store', $attendance), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'note' => '',
                'break_start' => [],
                'break_end' => [],
            ]);

        $response->assertRedirect(route('attendance.show', $attendance));
        $response->assertSessionHasErrors(['note']);

        $this->followRedirects($response)
            ->assertSeeText('備考を記入してください');
    }

    public function test_correction_request_is_created_successfully(): void
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

        $this->actingAs($user);

        $response = $this->post(route('stamp_request.store', $attendance), [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'note' => '修正申請テスト',
            'break_start' => ['12:00'],
            'break_end' => ['13:00'],
        ]);

        $response->assertRedirect(route('stamp_request.index', ['status' => 'pending']));

        $this->assertDatabaseHas('stamp_correction_requests', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_start_time' => '2026-02-01 09:00:00',
            'requested_end_time' => '2026-02-01 18:00:00',
            'requested_note' => '修正申請テスト',
        ]);

        $req = StampCorrectionRequest::where('attendance_id', $attendance->id)->firstOrFail();

        $this->assertDatabaseHas('stamp_correction_request_break_times', [
            'stamp_correction_request_id' => $req->id,
            'start_time' => '2026-02-01 12:00:00',
            'end_time' => '2026-02-01 13:00:00',
        ]);
    }

    public function test_request_list_defaults_to_pending(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-02',
            'start_time' => '2026-02-02 09:00:00',
            'end_time' => '2026-02-02 18:00:00',
            'note' => '',
        ]);

        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_start_time' => '2026-02-02 09:00:00',
            'requested_end_time' => '2026-02-02 18:00:00',
            'requested_note' => 'PENDING_NOTE',
        ]);

        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'requested_start_time' => '2026-02-02 09:00:00',
            'requested_end_time' => '2026-02-02 18:00:00',
            'requested_note' => 'APPROVED_NOTE',
        ]);

        $this->actingAs($user)
            ->get(route('stamp_request.index')) // status無し=default pending
            ->assertOk()
            ->assertSeeText('申請一覧')
            ->assertSeeText('PENDING_NOTE')
            ->assertDontSeeText('APPROVED_NOTE');
    }

    public function test_admin_can_see_all_pending_requests(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $u1 = User::factory()->create(['email_verified_at' => now()]);
        $u2 = User::factory()->create(['email_verified_at' => now()]);

        $a1 = Attendance::create([
            'user_id' => $u1->id,
            'date' => '2026-02-03',
            'start_time' => '2026-02-03 09:00:00',
            'end_time' => '2026-02-03 18:00:00',
            'note' => '',
        ]);

        $a2 = Attendance::create([
            'user_id' => $u2->id,
            'date' => '2026-02-03',
            'start_time' => '2026-02-03 09:00:00',
            'end_time' => '2026-02-03 18:00:00',
            'note' => '',
        ]);

        StampCorrectionRequest::create([
            'attendance_id' => $a1->id,
            'user_id' => $u1->id,
            'status' => 'pending',
            'requested_start_time' => '2026-02-03 09:00:00',
            'requested_end_time' => '2026-02-03 18:00:00',
            'requested_note' => 'U1_PENDING',
        ]);

        StampCorrectionRequest::create([
            'attendance_id' => $a2->id,
            'user_id' => $u2->id,
            'status' => 'pending',
            'requested_start_time' => '2026-02-03 09:00:00',
            'requested_end_time' => '2026-02-03 18:00:00',
            'requested_note' => 'U2_PENDING',
        ]);

        $this->actingAs($admin)
            ->get(route('stamp_request.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSeeText('U1_PENDING')
            ->assertSeeText('U2_PENDING');
    }

    public function test_user_can_only_see_own_pending_requests(): void
    {
        $u1 = User::factory()->create(['email_verified_at' => now()]);
        $u2 = User::factory()->create(['email_verified_at' => now()]);

        $a1 = Attendance::create([
            'user_id' => $u1->id,
            'date' => '2026-02-04',
            'start_time' => '2026-02-04 09:00:00',
            'end_time' => '2026-02-04 18:00:00',
            'note' => '',
        ]);

        $a2 = Attendance::create([
            'user_id' => $u2->id,
            'date' => '2026-02-04',
            'start_time' => '2026-02-04 09:00:00',
            'end_time' => '2026-02-04 18:00:00',
            'note' => '',
        ]);

        StampCorrectionRequest::create([
            'attendance_id' => $a1->id,
            'user_id' => $u1->id,
            'status' => 'pending',
            'requested_start_time' => '2026-02-04 09:00:00',
            'requested_end_time' => '2026-02-04 18:00:00',
            'requested_note' => 'U1_ONLY',
        ]);

        StampCorrectionRequest::create([
            'attendance_id' => $a2->id,
            'user_id' => $u2->id,
            'status' => 'pending',
            'requested_start_time' => '2026-02-04 09:00:00',
            'requested_end_time' => '2026-02-04 18:00:00',
            'requested_note' => 'U2_HIDDEN',
        ]);

        $this->actingAs($u1)
            ->get(route('stamp_request.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSeeText('U1_ONLY')
            ->assertDontSeeText('U2_HIDDEN');
    }
}
