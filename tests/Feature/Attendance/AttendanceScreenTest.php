<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 打刻画面は Asia/Tokyo 前提
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 10, 0, 0, 'Asia/Tokyo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function verifiedUser(): User
    {
        return User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
    }

    public function test_now_is_rendered_in_ui_format(): void
    {
        $user = $this->verifiedUser();

        $now = Carbon::now('Asia/Tokyo');
        $week = ['日', '月', '火', '水', '木', '金', '土'];
        $expectedDate = $now->format('Y年n月j日') . '(' . $week[$now->dayOfWeek] . ')';
        $expectedTime = $now->format('H:i');

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSeeText($expectedDate)
            ->assertSeeText($expectedTime);
    }

    public function test_status_is_off_duty_when_no_attendance_today(): void
    {
        $user = $this->verifiedUser();

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSeeText('勤務外')
            ->assertSeeText('出勤');
    }

    public function test_status_is_working_when_clocked_in_and_not_on_break(): void
    {
        $user = $this->verifiedUser();

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now('Asia/Tokyo')->toDateString(),
            'start_time' => Carbon::parse('2026-02-06 09:00:00', 'Asia/Tokyo'),
        ]);

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSeeText('出勤中')
            ->assertSeeText('退勤')
            ->assertSeeText('休憩入');
    }

    public function test_status_is_on_break_when_break_is_open(): void
    {
        $user = $this->verifiedUser();

        $a = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now('Asia/Tokyo')->toDateString(),
            'start_time' => Carbon::parse('2026-02-06 09:00:00', 'Asia/Tokyo'),
        ]);

        BreakTime::create([
            'attendance_id' => $a->id,
            'start_time' => Carbon::parse('2026-02-06 12:00:00', 'Asia/Tokyo'),
            'end_time' => null,
        ]);

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSeeText('休憩中')
            ->assertSeeText('休憩戻');
    }

    public function test_status_is_clocked_out_when_end_time_exists(): void
    {
        $user = $this->verifiedUser();

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now('Asia/Tokyo')->toDateString(),
            'start_time' => Carbon::parse('2026-02-06 09:00:00', 'Asia/Tokyo'),
            'end_time' => Carbon::parse('2026-02-06 18:00:00', 'Asia/Tokyo'),
        ]);

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSeeText('退勤済')
            ->assertSeeText('お疲れ様でした。');
    }
}
