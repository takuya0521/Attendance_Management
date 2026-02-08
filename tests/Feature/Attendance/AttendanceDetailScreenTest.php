<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_is_login_user_name_on_detail_screen()
    {
        [$user, $attendance] = $this->prepareAttendanceWithBreaks();

        $this->actingAs($user)
            ->get(route('attendance.show', $attendance))
            ->assertStatus(200)
            ->assertSee('名前：' . $user->name);
    }

    public function test_date_is_selected_date_on_detail_screen()
    {
        [$user, $attendance] = $this->prepareAttendanceWithBreaks();

        $this->actingAs($user)
            ->get(route('attendance.show', $attendance))
            ->assertStatus(200)
            ->assertSee('日付：2026-02-03');
    }

    public function test_start_and_end_times_match_user_stamp()
    {
        [$user, $attendance] = $this->prepareAttendanceWithBreaks();

        $this->actingAs($user)
            ->get(route('attendance.show', $attendance))
            ->assertStatus(200)
            ->assertSee('出勤：')
            ->assertSee('09:00')
            ->assertSee('退勤：')
            ->assertSee('18:00');
    }

    public function test_break_times_match_user_stamp()
    {
        [$user, $attendance] = $this->prepareAttendanceWithBreaks();

        $this->actingAs($user)
            ->get(route('attendance.show', $attendance))
            ->assertStatus(200)
            ->assertSee('12:00 - 13:00')
            ->assertSee('15:00 - 15:15');
    }

    private function prepareAttendanceWithBreaks(): array
    {
        $user = User::factory()->create([
            'name' => 'テスト太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-03',
            'start_time' => Carbon::parse('2026-02-03 09:00:00', 'Asia/Tokyo'),
            'end_time' => Carbon::parse('2026-02-03 18:00:00', 'Asia/Tokyo'),
            'note' => 'note',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::parse('2026-02-03 12:00:00', 'Asia/Tokyo'),
            'end_time' => Carbon::parse('2026-02-03 13:00:00', 'Asia/Tokyo'),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::parse('2026-02-03 15:00:00', 'Asia/Tokyo'),
            'end_time' => Carbon::parse('2026-02-03 15:15:00', 'Asia/Tokyo'),
        ]);

        return [$user, $attendance];
    }
}
