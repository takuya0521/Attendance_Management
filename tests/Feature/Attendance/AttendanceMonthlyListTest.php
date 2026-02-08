<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceMonthlyListTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => Carbon::now('Asia/Tokyo'),
            'is_admin' => false,
        ], $overrides));
    }

    /**
     * ID9: 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_current_month_is_displayed_on_monthly_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 7, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->createVerifiedUser();

        $this->actingAs($user)
            ->get(route('attendance.list'))
            ->assertOk()
            ->assertSee('勤怠一覧（2026-02）');
    }

    /**
     * ID9: 自分の勤怠情報がすべて表示されている（他人の勤怠は表示されない）
     */
    public function test_only_my_attendances_are_displayed_in_current_month(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 7, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->createVerifiedUser();
        $other = $this->createVerifiedUser(['email' => 'other@example.com']);

        // 自分の勤怠（2件）
        $a1 = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-03',
            'start_time' => Carbon::create(2026, 2, 3, 9, 0, 0, 'Asia/Tokyo'),
            'end_time' => Carbon::create(2026, 2, 3, 18, 0, 0, 'Asia/Tokyo'),
        ]);
        BreakTime::create([
            'attendance_id' => $a1->id,
            'start_time' => Carbon::create(2026, 2, 3, 12, 0, 0, 'Asia/Tokyo'),
            'end_time' => Carbon::create(2026, 2, 3, 13, 0, 0, 'Asia/Tokyo'), // 60分
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-04',
            'start_time' => Carbon::create(2026, 2, 4, 10, 30, 0, 'Asia/Tokyo'),
            'end_time' => Carbon::create(2026, 2, 4, 19, 0, 0, 'Asia/Tokyo'),
        ]);

        // 他人の勤怠（表示されないことの確認用。時刻を目印にする）
        Attendance::create([
            'user_id' => $other->id,
            'date' => '2026-02-03',
            'start_time' => Carbon::create(2026, 2, 3, 7, 7, 0, 'Asia/Tokyo'), // 目印
            'end_time' => Carbon::create(2026, 2, 3, 8, 8, 0, 'Asia/Tokyo'),
        ]);

        $res = $this->actingAs($user)->get(route('attendance.list'));

        $res->assertOk();

        // 自分の勤怠が表示されている（時刻で確認）
        $res->assertSee('09:00');
        $res->assertSee('18:00');
        $res->assertSee('60'); // 休憩（分）

        $res->assertSee('10:30');
        $res->assertSee('19:00');

        // 他人の勤怠の目印時刻は表示されない
        $res->assertDontSee('07:07');
    }

    /**
     * ID9: 「前月」を押下した時に前月の情報が表示される
     */
    public function test_prev_month_link_displays_prev_month_attendances(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 7, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->createVerifiedUser();

        // 前月（2026-01）の勤怠
        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'start_time' => Carbon::create(2026, 1, 15, 8, 0, 0, 'Asia/Tokyo'),
            'end_time' => Carbon::create(2026, 1, 15, 17, 0, 0, 'Asia/Tokyo'),
        ]);

        // 当月（2026-02）の勤怠（前月画面には出ないことの確認用）
        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-03',
            'start_time' => Carbon::create(2026, 2, 3, 9, 0, 0, 'Asia/Tokyo'),
            'end_time' => Carbon::create(2026, 2, 3, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        // 当月の一覧に「前月」リンクがある
        $current = $this->actingAs($user)->get(route('attendance.list'));
        $current->assertOk()
            ->assertSee(route('attendance.list', ['month' => '2026-01']), false);

        // 前月表示
        $prev = $this->actingAs($user)->get(route('attendance.list', ['month' => '2026-01']));
        $prev->assertOk()
            ->assertSee('勤怠一覧（2026-01）')
            ->assertSee('2026-01-15')
            ->assertSee('08:00')
            ->assertDontSee('2026-02-03');
    }

    /**
     * ID9: 「翌月」を押下した時に翌月の情報が表示される
     */
    public function test_next_month_link_displays_next_month_attendances(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 7, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->createVerifiedUser();

        // 翌月（2026-03）の勤怠
        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-03-05',
            'start_time' => Carbon::create(2026, 3, 5, 9, 15, 0, 'Asia/Tokyo'),
            'end_time' => Carbon::create(2026, 3, 5, 18, 15, 0, 'Asia/Tokyo'),
        ]);

        // 当月（2026-02）の勤怠（翌月画面には出ないことの確認用）
        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-03',
            'start_time' => Carbon::create(2026, 2, 3, 9, 0, 0, 'Asia/Tokyo'),
            'end_time' => Carbon::create(2026, 2, 3, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        // 当月の一覧に「翌月」リンクがある
        $current = $this->actingAs($user)->get(route('attendance.list'));
        $current->assertOk()
            ->assertSee(route('attendance.list', ['month' => '2026-03']), false);

        // 翌月表示
        $next = $this->actingAs($user)->get(route('attendance.list', ['month' => '2026-03']));
        $next->assertOk()
            ->assertSee('勤怠一覧（2026-03）')
            ->assertSee('2026-03-05')
            ->assertSee('09:15')
            ->assertDontSee('2026-02-03');
    }

    /**
     * ID9: 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_detail_link_goes_to_attendance_detail_page(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 7, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->createVerifiedUser();

        $a = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-03',
            'start_time' => Carbon::create(2026, 2, 3, 9, 0, 0, 'Asia/Tokyo'),
            'end_time' => Carbon::create(2026, 2, 3, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $list = $this->actingAs($user)->get(route('attendance.list'));
        $list->assertOk()
            ->assertSee(route('attendance.show', $a), false);

        $this->actingAs($user)
            ->get(route('attendance.show', $a))
            ->assertOk()
            ->assertSee('勤怠詳細（2026-02-03）');
    }
}
