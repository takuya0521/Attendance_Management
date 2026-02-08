<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceShowTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function staffUser(): User
    {
        return User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_attendance_detail_displays_selected_attendance_values(): void
    {
        $admin = $this->adminUser();
        $staff = $this->staffUser();

        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'date' => '2026-02-05',
            'start_time' => '2026-02-05 09:00:00',
            'end_time' => '2026-02-05 18:00:00',
            'note' => 'テストメモ',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '2026-02-05 12:00:00',
            'end_time' => '2026-02-05 13:00:00',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '2026-02-05 15:00:00',
            'end_time' => '2026-02-05 15:30:00',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.show', $attendance));

        $response->assertOk()
            ->assertSeeText('勤怠詳細')
            ->assertSeeText($staff->name)
            ->assertSeeText('2026年')
            ->assertSeeText('2月5日');

        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);

        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);

        $response->assertSee('value="15:00"', false);
        $response->assertSee('value="15:30"', false);

        $response->assertSee('テストメモ', false);
    }

    public function test_admin_attendance_detail_shows_pending_message_and_hides_update_form_when_request_is_pending(): void
    {
        $admin = $this->adminUser();
        $staff = $this->staffUser();

        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'date' => '2026-02-05',
            'start_time' => '2026-02-05 09:00:00',
            'end_time' => '2026-02-05 18:00:00',
            'note' => '',
        ]);

        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $staff->id,
            'status' => 'pending',
            'requested_start_time' => '2026-02-05 09:00:00',
            'requested_end_time' => '2026-02-05 18:00:00',
            'requested_note' => '申請中',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.show', $attendance));

        $response->assertOk()
            ->assertSeeText('承認待ちのため修正はできません。')
            ->assertDontSee(route('admin.attendance.update', $attendance), false);
    }
}
