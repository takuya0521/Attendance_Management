<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUpdateAttendanceRequiredAndAuthTest extends TestCase
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

    private function attendance(User $user, string $date = '2026-02-10'): Attendance
    {
        return Attendance::create([
            'user_id' => $user->id,
            'date' => $date,
            'start_time' => $date . ' 09:00:00',
            'end_time' => $date . ' 18:00:00',
            'note' => 'old',
        ]);
    }

    public function test_start_time_is_required(): void
    {
        $admin = $this->admin();
        $attendance = $this->attendance($this->user(), '2026-02-10');

        $response = $this->actingAs($admin)
            ->from(route('admin.attendance.show', $attendance))
            ->post(route('admin.attendance.update', $attendance), [
                // start_time なし
                'end_time' => '18:00',
                'note' => 'new',
                'break_start' => [],
                'break_end' => [],
            ]);

        $response->assertRedirect(route('admin.attendance.show', $attendance));
        $response->assertSessionHasErrors(['start_time']);
    }

    public function test_end_time_is_required(): void
    {
        $admin = $this->admin();
        $attendance = $this->attendance($this->user(), '2026-02-11');

        $response = $this->actingAs($admin)
            ->from(route('admin.attendance.show', $attendance))
            ->post(route('admin.attendance.update', $attendance), [
                'start_time' => '09:00',
                // end_time なし
                'note' => 'new',
                'break_start' => [],
                'break_end' => [],
            ]);

        $response->assertRedirect(route('admin.attendance.show', $attendance));
        $response->assertSessionHasErrors(['end_time']);
    }

    public function test_note_is_required(): void
    {
        $admin = $this->admin();
        $attendance = $this->attendance($this->user(), '2026-02-12');

        $response = $this->actingAs($admin)
            ->from(route('admin.attendance.show', $attendance))
            ->post(route('admin.attendance.update', $attendance), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                // note なし
                'break_start' => [],
                'break_end' => [],
            ]);

        $response->assertRedirect(route('admin.attendance.show', $attendance));
        $response->assertSessionHasErrors(['note']);
    }

    public function test_non_admin_cannot_update_admin_attendance(): void
    {
        $user = $this->user();
        $attendance = $this->attendance($this->user(), '2026-02-13');

        $response = $this->actingAs($user)
            ->post(route('admin.attendance.update', $attendance), [
                'start_time' => '10:00',
                'end_time' => '19:00',
                'note' => 'new',
                'break_start' => [],
                'break_end' => [],
            ]);

        // ミドルウェア実装により 302/403 があり得るため両方許容
        $this->assertTrue(in_array($response->getStatusCode(), [302, 403], true));

        // 更新されてないこと
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'start_time' => '2026-02-13 09:00:00',
            'end_time' => '2026-02-13 18:00:00',
            'note' => 'old',
        ]);
    }

    public function test_break_is_invalid_when_only_start_is_filled(): void
    {
        $admin = $this->admin();
        $attendance = $this->attendance($this->user(), '2026-02-14');

        $response = $this->actingAs($admin)
            ->from(route('admin.attendance.show', $attendance))
            ->post(route('admin.attendance.update', $attendance), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'note' => 'new',
                'break_start' => ['12:00'],
                'break_end' => [''], // 片側だけ
            ]);

        $response->assertRedirect(route('admin.attendance.show', $attendance));
        $response->assertSessionHasErrors(['break_start.0']);
    }

    public function test_break_is_invalid_when_only_end_is_filled(): void
    {
        $admin = $this->admin();
        $attendance = $this->attendance($this->user(), '2026-02-15');

        $response = $this->actingAs($admin)
            ->from(route('admin.attendance.show', $attendance))
            ->post(route('admin.attendance.update', $attendance), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'note' => 'new',
                'break_start' => [''], // 片側だけ
                'break_end' => ['12:30'],
            ]);

        $response->assertRedirect(route('admin.attendance.show', $attendance));
        $response->assertSessionHasErrors(['break_start.0']);
    }

    public function test_admin_update_is_blocked_when_pending_request_exists_even_if_required_fields_are_present(): void
    {
        $admin = $this->admin();
        $user = $this->user();

        $attendance = $this->attendance($user, '2026-02-16');

        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_start_time' => '2026-02-16 09:00:00',
            'requested_end_time' => '2026-02-16 18:00:00',
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
    }
}
