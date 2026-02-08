<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 10, 0, 0, 'Asia/Tokyo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_clock_in_creates_attendance_and_status_becomes_working(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/attendance')
            ->post('/attendance/stamp', ['action' => 'clock_in'])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => '2026-02-06',
            'start_time' => '2026-02-06 10:00:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('ステータス：出勤中');
    }

    public function test_clock_in_is_only_once_per_day(): void
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-06',
            'start_time' => Carbon::parse('2026-02-06 09:00:00', 'Asia/Tokyo'),
        ]);

        $this->actingAs($user)
            ->post('/attendance/stamp', ['action' => 'clock_in'])
            ->assertRedirect();

        $this->assertSame(1, Attendance::where('user_id', $user->id)->where('date', '2026-02-06')->count());
    }

    public function test_break_start_creates_break_and_status_becomes_on_break(): void
    {
        $user = User::factory()->create();

        $a = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-06',
            'start_time' => Carbon::parse('2026-02-06 09:00:00', 'Asia/Tokyo'),
        ]);

        $this->actingAs($user)
            ->from('/attendance')
            ->post('/attendance/stamp', ['action' => 'break_start'])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $a->id,
            'start_time' => '2026-02-06 10:00:00',
            'end_time' => null,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('ステータス：休憩中');
    }

    public function test_break_end_closes_break_and_status_returns_to_working(): void
    {
        $user = User::factory()->create();

        $a = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-06',
            'start_time' => Carbon::parse('2026-02-06 09:00:00', 'Asia/Tokyo'),
        ]);

        $b = BreakTime::create([
            'attendance_id' => $a->id,
            'start_time' => Carbon::parse('2026-02-06 09:30:00', 'Asia/Tokyo'),
            'end_time' => null,
        ]);

        $this->actingAs($user)
            ->from('/attendance')
            ->post('/attendance/stamp', ['action' => 'break_end'])
            ->assertRedirect('/attendance');

        $b->refresh();
        $this->assertNotNull($b->end_time);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('ステータス：出勤中');
    }

    public function test_multiple_breaks_can_be_recorded_in_a_day(): void
    {
        $user = User::factory()->create();

        $a = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-06',
            'start_time' => Carbon::parse('2026-02-06 09:00:00', 'Asia/Tokyo'),
        ]);

        $this->actingAs($user)->post('/attendance/stamp', ['action' => 'break_start']);
        $this->actingAs($user)->post('/attendance/stamp', ['action' => 'break_end']);

        Carbon::setTestNow(Carbon::create(2026, 2, 6, 15, 0, 0, 'Asia/Tokyo'));
        $this->actingAs($user)->post('/attendance/stamp', ['action' => 'break_start']);
        $this->actingAs($user)->post('/attendance/stamp', ['action' => 'break_end']);

        $this->assertSame(2, BreakTime::where('attendance_id', $a->id)->count());
    }

    public function test_clock_out_sets_end_time_and_shows_message(): void
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-06',
            'start_time' => Carbon::parse('2026-02-06 09:00:00', 'Asia/Tokyo'),
        ]);

        $this->actingAs($user)
            ->from('/attendance')
            ->post('/attendance/stamp', ['action' => 'clock_out'])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => '2026-02-06',
            'end_time' => '2026-02-06 10:00:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('お疲れ様でした。');
    }

    public function test_clock_in_time_is_visible_on_monthly_list(): void
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-06',
            'start_time' => Carbon::parse('2026-02-06 09:12:00', 'Asia/Tokyo'),
        ]);

        $this->actingAs($user)
            ->get('/attendance/list?month=2026-02')
            ->assertOk()
            ->assertSee('09:12');
    }
}
