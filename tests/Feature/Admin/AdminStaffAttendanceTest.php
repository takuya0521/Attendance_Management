<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_staff_attendance_list_with_month_navigation_and_detail_links(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $staff = User::factory()->create([
            'name' => '太郎',
            'email' => 'taro@example.com',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'date' => '2026-02-06',
            'start_time' => '2026-02-06 09:00:00',
            'end_time' => '2026-02-06 18:00:00',
            'note' => '',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => '2026-02-06 12:00:00',
            'end_time' => '2026-02-06 13:00:00',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.staff.attendance', $staff) . '?month=2026-02');

        $response->assertOk()
            ->assertSeeText('太郎さんの勤怠')
            ->assertSeeText('2026/02');

        $response->assertSee(route('admin.staff.attendance', $staff) . '?month=2026-01', false);
        $response->assertSee(route('admin.staff.attendance', $staff) . '?month=2026-03', false);

        $response->assertSeeText('02/06')
            ->assertSeeText('09:00')
            ->assertSeeText('18:00')
            ->assertSeeText('1:00')
            ->assertSeeText('8:00');

        $response->assertSee(route('admin.attendance.show', $attendance), false);

        $response->assertDontSeeText('01/31');
        $response->assertDontSeeText('03/01');
    }
}
