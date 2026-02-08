<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUpdateAttendanceValidationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function user(): User
    {
        return User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_update_attendance_successfully(): void
    {
        $admin = $this->admin();
        $user = $this->user();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-05',
            'start_time' => '2026-02-05 09:00:00',
            'end_time' => '2026-02-05 18:00:00',
            'note' => 'old',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.attendance.update', $attendance), [
                'start_time' => '10:00',
                'end_time' => '19:00',
                'note' => 'new note',
                'break_start' => ['12:00'],
                'break_end' => ['13:00'],
            ]);

        // どこにリダイレクトする仕様かは実装に依存するため、200/302を許容しつつDBを検証
        $response->assertStatus(in_array($response->getStatusCode(), [200, 302], true) ? $response->getStatusCode() : 302);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'user_id' => $user->id,
            'date' => '2026-02-05',
            'note' => 'new note',
        ]);

        // 時刻はDB側がdatetimeで保持している想定
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'start_time' => '2026-02-05 10:00:00',
            'end_time' => '2026-02-05 19:00:00',
        ]);

        // 休憩テーブル名は実装依存なので、既存の break_times がある前提で確認
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'start_time' => '2026-02-05 12:00:00',
            'end_time' => '2026-02-05 13:00:00',
        ]);
    }

    public function test_admin_update_is_blocked_when_pending_request_exists(): void
    {
        $admin = $this->admin();
        $user = $this->user();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-06',
            'start_time' => '2026-02-06 09:00:00',
            'end_time' => '2026-02-06 18:00:00',
            'note' => 'old',
        ]);

        // 承認待ち申請を作る（ロック条件）
        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_start_time' => '2026-02-06 09:00:00',
            'requested_end_time' => '2026-02-06 18:00:00',
            'requested_note' => 'req',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.attendance.show', $attendance))
            ->post(route('admin.attendance.update', $attendance), [
                'start_time' => '10:00',
                'end_time' => '19:00',
                'note' => 'new',
                'break_start' => [],
                'break_end' => [],
            ]);

        $response->assertRedirect(route('admin.attendance.show', $attendance));
        $response->assertSessionHasErrors(['locked']);

        // DBが更新されていないこと
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'start_time' => '2026-02-06 09:00:00',
            'end_time' => '2026-02-06 18:00:00',
            'note' => 'old',
        ]);
    }

    public function test_admin_start_time_is_invalid_when_after_end_time(): void
    {
        $admin = $this->admin();
        $user = $this->user();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-07',
            'start_time' => '2026-02-07 09:00:00',
            'end_time' => '2026-02-07 18:00:00',
            'note' => 'old',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.attendance.show', $attendance))
            ->post(route('admin.attendance.update', $attendance), [
                'start_time' => '19:00',
                'end_time' => '18:00',
                'note' => 'new',
                'break_start' => [],
                'break_end' => [],
            ]);

        $response->assertRedirect(route('admin.attendance.show', $attendance));
        $response->assertSessionHasErrors(['start_time']);

        $this->followRedirects($response)
            ->assertSeeText('出勤時間もしくは退勤時間が不適切な値です');
    }

    public function test_admin_break_time_is_invalid_when_start_after_end_time(): void
    {
        $admin = $this->admin();
        $user = $this->user();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-08',
            'start_time' => '2026-02-08 09:00:00',
            'end_time' => '2026-02-08 18:00:00',
            'note' => 'old',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.attendance.show', $attendance))
            ->post(route('admin.attendance.update', $attendance), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'note' => 'new',
                'break_start' => ['13:00'],
                'break_end' => ['12:00'],
            ]);

        $response->assertRedirect(route('admin.attendance.show', $attendance));
        $response->assertSessionHasErrors(['break_start.0']);

        $this->followRedirects($response)
            ->assertSeeText('休憩時間が不適切な値です');
    }

    public function test_admin_break_time_is_invalid_when_end_after_end_time(): void
    {
        $admin = $this->admin();
        $user = $this->user();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-09',
            'start_time' => '2026-02-09 09:00:00',
            'end_time' => '2026-02-09 18:00:00',
            'note' => 'old',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.attendance.show', $attendance))
            ->post(route('admin.attendance.update', $attendance), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'note' => 'new',
                'break_start' => ['17:30'],
                'break_end' => ['18:30'],
            ]);

        $response->assertRedirect(route('admin.attendance.show', $attendance));
        $response->assertSessionHasErrors(['break_end.0']);

        $this->followRedirects($response)
            ->assertSeeText('休憩時間もしくは退勤時間が不適切な値です');
    }
}
