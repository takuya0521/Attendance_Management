<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStampUiAndListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0, 'Asia/Tokyo'));
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

    public function test_buttons_change_by_status_and_times_are_recorded_and_shown_in_monthly_list(): void
    {
        $user = $this->verifiedUser();

        // 勤務外：出勤ボタンのみ
        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSeeText('勤務外')
            ->assertSeeText('出勤')
            ->assertSee('value="clock_in"', false)
            ->assertDontSee('value="clock_out"', false)
            ->assertDontSee('value="break_start"', false)
            ->assertDontSee('value="break_end"', false);

        // 出勤（1日1回）
        $this->post(route('attendance.stamp'), ['action' => 'clock_in'])
            ->assertStatus(302)
            ->assertSessionHas('status', '出勤しました。');

        $this->get(route('attendance.index'))
            ->assertOk()
            ->assertSeeText('出勤中')
            ->assertSeeText('退勤')
            ->assertSeeText('休憩入')
            ->assertDontSee('value="clock_in"', false)
            ->assertSee('value="clock_out"', false)
            ->assertSee('value="break_start"', false)
            ->assertDontSee('value="break_end"', false);

        // 休憩入
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 12, 0, 0, 'Asia/Tokyo'));

        $this->post(route('attendance.stamp'), ['action' => 'break_start'])
            ->assertStatus(302)
            ->assertSessionHas('status', '休憩に入りました。');

        $this->get(route('attendance.index'))
            ->assertOk()
            ->assertSeeText('休憩中')
            ->assertSeeText('休憩戻')
            ->assertSee('value="break_end"', false)
            ->assertDontSee('value="break_start"', false)
            ->assertDontSee('value="clock_out"', false)
            ->assertDontSee('value="clock_in"', false);

        // 休憩戻
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 13, 0, 0, 'Asia/Tokyo'));

        $this->post(route('attendance.stamp'), ['action' => 'break_end'])
            ->assertStatus(302)
            ->assertSessionHas('status', '休憩から戻りました。');

        // 休憩終了後は再び「休憩入」が出る（複数回休憩OK）
        $this->get(route('attendance.index'))
            ->assertOk()
            ->assertSeeText('出勤中')
            ->assertSeeText('休憩入')
            ->assertSee('value="break_start"', false)
            ->assertSee('value="clock_out"', false)
            ->assertDontSee('value="break_end"', false);

        // 退勤
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 18, 0, 0, 'Asia/Tokyo'));

        $this->post(route('attendance.stamp'), ['action' => 'clock_out'])
            ->assertStatus(302)
            ->assertSessionHas('status', 'お疲れ様でした。');

        $this->get(route('attendance.index'))
            ->assertOk()
            ->assertSeeText('退勤済')
            ->assertSeeText('お疲れ様でした。')
            ->assertDontSee('value="clock_in"', false)
            ->assertDontSee('value="clock_out"', false)
            ->assertDontSee('value="break_start"', false)
            ->assertDontSee('value="break_end"', false);

        // 退勤済みの状態では再出勤できない（1日1回）
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 19, 0, 0, 'Asia/Tokyo'));

        $this->post(route('attendance.stamp'), ['action' => 'clock_in'])
            ->assertStatus(302);

        $this->assertSame(1, Attendance::where('user_id', $user->id)->count());

        // 勤怠一覧（当月）に、打刻した時刻・休憩（分）が表示される
        $this->get(route('attendance.list', ['month' => '2026-02']))
            ->assertOk()
            ->assertSeeText('2026-02-06')
            ->assertSeeText('09:00')
            ->assertSeeText('18:00')
            ->assertSeeText('60');
    }
}
