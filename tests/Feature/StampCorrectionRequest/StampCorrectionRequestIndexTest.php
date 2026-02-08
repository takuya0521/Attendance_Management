<?php

namespace Tests\Feature\StampCorrectionRequest;

use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampCorrectionRequestIndexTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => null,
        ]);
    }

    private function user(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
    }

    private function attendance(User $user, string $date): Attendance
    {
        return Attendance::create([
            'user_id' => $user->id,
            'date' => $date,
            'start_time' => $date . ' 09:00:00',
            'end_time' => $date . ' 18:00:00',
            'note' => '',
        ]);
    }

    private function makeRequest(Attendance $attendance, User $user, string $status): StampCorrectionRequest
    {
        // Attendance::date は Carbon なので必ず日付文字列化して使う（時刻二重を防ぐ）
        $date = $attendance->date->format('Y-m-d');

        // approved_by は admin のIDが必要な想定だが、一覧テストなので最小限にする
        $approvedBy = null;
        $approvedAt = null;

        if ($status === 'approved') {
            $admin = $this->admin();
            $approvedBy = $admin->id;
            $approvedAt = Carbon::now('Asia/Tokyo');
        }

        return StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => $status,
            'requested_start_time' => Carbon::createFromFormat('Y-m-d H:i:s', $date . ' 10:00:00', 'Asia/Tokyo'),
            'requested_end_time' => Carbon::createFromFormat('Y-m-d H:i:s', $date . ' 19:00:00', 'Asia/Tokyo'),
            'requested_note' => '申請',
            'approved_at' => $approvedAt,
            'approved_by' => $approvedBy,
        ]);
    }

    public function test_user_sees_only_own_pending_requests(): void
    {
        $u1 = $this->user('User One');
        $u2 = $this->user('User Two');

        $a1 = $this->attendance($u1, '2026-02-01');
        $a2 = $this->attendance($u2, '2026-02-02');

        $u1Pending = $this->makeRequest($a1, $u1, 'pending');
        $u2Pending = $this->makeRequest($a2, $u2, 'pending');

        // approved も混ぜる（pending一覧には出ない）
        $this->makeRequest($a1, $u1, 'approved');

        $response = $this->actingAs($u1)
            ->get(route('stamp_request.index', ['status' => 'pending']));

        $response->assertOk();
        $response->assertViewHas('status', 'pending');

        $response->assertViewHas('requests', function ($requests) use ($u1Pending, $u2Pending) {
            $ids = $requests->pluck('id')->all();
            return in_array($u1Pending->id, $ids, true)
                && !in_array($u2Pending->id, $ids, true);
        });
    }

    public function test_user_sees_only_own_approved_requests(): void
    {
        $u1 = $this->user('User One');
        $u2 = $this->user('User Two');

        $a1 = $this->attendance($u1, '2026-02-03');
        $a2 = $this->attendance($u2, '2026-02-04');

        $u1Approved = $this->makeRequest($a1, $u1, 'approved');
        $u2Approved = $this->makeRequest($a2, $u2, 'approved');

        // pending も混ぜる（approved一覧には出ない）
        $this->makeRequest($a1, $u1, 'pending');

        $response = $this->actingAs($u1)
            ->get(route('stamp_request.index', ['status' => 'approved']));

        $response->assertOk();
        $response->assertViewHas('status', 'approved');

        $response->assertViewHas('requests', function ($requests) use ($u1Approved, $u2Approved) {
            $ids = $requests->pluck('id')->all();
            return in_array($u1Approved->id, $ids, true)
                && !in_array($u2Approved->id, $ids, true);
        });
    }

    public function test_admin_sees_all_pending_requests(): void
    {
        $admin = $this->admin();

        $u1 = $this->user('User One');
        $u2 = $this->user('User Two');

        $a1 = $this->attendance($u1, '2026-02-05');
        $a2 = $this->attendance($u2, '2026-02-06');

        $r1 = $this->makeRequest($a1, $u1, 'pending');
        $r2 = $this->makeRequest($a2, $u2, 'pending');

        // approved も混ぜる（pending一覧には出ない）
        $this->makeRequest($a1, $u1, 'approved');

        $response = $this->actingAs($admin)
            ->get(route('stamp_request.index', ['status' => 'pending']));

        $response->assertOk();
        $response->assertViewHas('status', 'pending');

        $response->assertViewHas('requests', function ($requests) use ($r1, $r2) {
            $ids = $requests->pluck('id')->all();
            return in_array($r1->id, $ids, true) && in_array($r2->id, $ids, true);
        });
    }

    public function test_admin_sees_all_approved_requests(): void
    {
        $admin = $this->admin();

        $u1 = $this->user('User One');
        $u2 = $this->user('User Two');

        $a1 = $this->attendance($u1, '2026-02-07');
        $a2 = $this->attendance($u2, '2026-02-08');

        $r1 = $this->makeRequest($a1, $u1, 'approved');
        $r2 = $this->makeRequest($a2, $u2, 'approved');

        // pending も混ぜる（approved一覧には出ない）
        $this->makeRequest($a1, $u1, 'pending');

        $response = $this->actingAs($admin)
            ->get(route('stamp_request.index', ['status' => 'approved']));

        $response->assertOk();
        $response->assertViewHas('status', 'approved');

        $response->assertViewHas('requests', function ($requests) use ($r1, $r2) {
            $ids = $requests->pluck('id')->all();
            return in_array($r1->id, $ids, true) && in_array($r2->id, $ids, true);
        });
    }

    public function test_invalid_status_defaults_to_pending(): void
    {
        $admin = $this->admin();

        $u = $this->user('User One');
        $a = $this->attendance($u, '2026-02-09');

        $pending = $this->makeRequest($a, $u, 'pending');
        $this->makeRequest($a, $u, 'approved');

        $response = $this->actingAs($admin)
            ->get(route('stamp_request.index', ['status' => 'xxx']));

        $response->assertOk();
        $response->assertViewHas('status', 'pending');

        $response->assertViewHas('requests', function ($requests) use ($pending) {
            $ids = $requests->pluck('id')->all();
            return in_array($pending->id, $ids, true);
        });
    }

    public function test_user_list_has_detail_link_to_attendance_detail(): void
    {
        $u1 = $this->user('User One');

        $a1 = $this->attendance($u1, '2026-02-10');
        $this->makeRequest($a1, $u1, 'pending');

        $response = $this->actingAs($u1)
            ->get(route('stamp_request.index', ['status' => 'pending']));

        $response->assertOk()
            ->assertSee(route('attendance.show', $a1), false)
            ->assertDontSee('/stamp_correction_request/approve/', false);
    }

    public function test_admin_list_has_detail_link_to_approval_screen_with_status_query(): void
    {
        $admin = $this->admin();

        $u = $this->user('User One');
        $a = $this->attendance($u, '2026-02-11');

        $r = $this->makeRequest($a, $u, 'pending');

        $response = $this->actingAs($admin)
            ->get(route('stamp_request.index', ['status' => 'pending']));

        $response->assertOk()
            ->assertSee(route('stamp_request.approve.show', $r) . '?status=pending', false);
    }
}
