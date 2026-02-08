<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDailyAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_daily_attendance_list_for_selected_date_and_see_prev_next_links(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $u1 = User::factory()->create([
            'name' => '太郎',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
        $u2 = User::factory()->create([
            'name' => '花子',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
        $u3 = User::factory()->create([
            'name' => '次郎',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $a1 = Attendance::create([
            'user_id' => $u1->id,
            'date' => '2026-02-01',
            'start_time' => '2026-02-01 09:00:00',
            'end_time' => '2026-02-01 18:00:00',
            'note' => '',
        ]);

        BreakTime::create([
            'attendance_id' => $a1->id,
            'start_time' => '2026-02-01 12:00:00',
            'end_time' => '2026-02-01 13:00:00',
        ]);

        $a2 = Attendance::create([
            'user_id' => $u2->id,
            'date' => '2026-02-01',
            'start_time' => '2026-02-01 10:00:00',
            'end_time' => '2026-02-01 19:00:00',
            'note' => '',
        ]);

        Attendance::create([
            'user_id' => $u3->id,
            'date' => '2026-02-02',
            'start_time' => '2026-02-02 09:00:00',
            'end_time' => '2026-02-02 18:00:00',
            'note' => '',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.list', ['date' => '2026-02-01']));

        $response->assertOk()
            ->assertSeeText('勤怠一覧')
            ->assertSeeText('2026/02/01')
            ->assertSeeText('太郎')
            ->assertSeeText('花子')
            ->assertDontSeeText('次郎');

        $response->assertSee(route('admin.attendance.list', ['date' => '2026-01-31']), false);
        $response->assertSee(route('admin.attendance.list', ['date' => '2026-02-02']), false);

        $response->assertSeeText('09:00')
            ->assertSeeText('18:00')
            ->assertSeeText('1:00')
            ->assertSeeText('8:00');

        $response->assertSeeText('10:00')
            ->assertSeeText('19:00')
            ->assertSeeText('9:00');

        $response->assertSee(route('admin.attendance.show', $a1), false);
        $response->assertSee(route('admin.attendance.show', $a2), false);
    }

    public function test_admin_sees_empty_message_when_no_attendance_on_date(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.list', ['date' => '2026-02-01']));

        $response->assertOk()
            ->assertSeeText('勤怠一覧');

        $this->assertMatchesRegularExpression(
            '/この日の\\s*勤怠データはありません。/u',
            $response->getContent()
        );
    }
}
