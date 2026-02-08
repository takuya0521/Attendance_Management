<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_see_request_form_when_not_clocked_out(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        // 退勤前（end_time = null）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-01',
            'start_time' => '2026-02-01 09:00:00',
            'end_time' => null,
            'note' => '',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.show', $attendance));

        $response->assertOk()
            ->assertSeeText('修正申請');

        // 改行/空白が混ざっても落ちないように正規表現で確認
        $this->assertMatchesRegularExpression(
            '/退勤していないため\s*修正申請はできません。/u',
            $response->getContent()
        );
    }

    public function test_user_cannot_see_request_form_when_pending_request_exists(): void
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

        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_start_time' => '2026-02-01 10:00:00',
            'requested_end_time' => '2026-02-01 18:00:00',
            'requested_note' => 'メモ',
        ]);

        $this->actingAs($user);

        $this->get(route('attendance.show', $attendance))
            ->assertOk()
            ->assertSeeText('修正申請')
            ->assertSeeText('承認待ちのため修正はできません。');
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
                'note' => '', // 必須
                'break_start' => [],
                'break_end' => [],
            ]);

        $response->assertRedirect(route('attendance.show', $attendance));
        $response->assertSessionHasErrors(['note']);

        $this->followRedirects($response)
            ->assertSeeText('備考を記入してください');
    }

    public function test_user_can_create_correction_request_successfully(): void
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
            'note' => '申請メモ',
            'break_start' => ['12:00'],
            'break_end' => ['13:00'],
        ]);

        $response->assertRedirect(route('stamp_request.index', ['status' => 'pending']));

        $this->assertDatabaseHas('stamp_correction_requests', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_note' => '申請メモ',
        ]);
    }

    public function test_admin_request_list_has_link_to_detail_page(): void
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
            'requested_start_time' => '2026-02-01 09:00:00',
            'requested_end_time' => '2026-02-01 18:00:00',
            'requested_note' => '申請',
        ]);

        $this->actingAs($admin);

        $this->get(route('stamp_request.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee(route('stamp_request.approve.show', $req));
    }
}
