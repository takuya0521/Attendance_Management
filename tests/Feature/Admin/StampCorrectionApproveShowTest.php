<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampCorrectionApproveShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_approval_screen(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $user = User::factory()->create([
            'name' => '一般ユーザー',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-01',
            'start_time' => '2026-02-01 09:00:00',
            'end_time' => '2026-02-01 18:00:00',
            'note' => '',
        ]);

        $req = StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_start_time' => '2026-02-01 10:00:00',
            'requested_end_time' => '2026-02-01 19:00:00',
            'requested_note' => 'テスト',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('stamp_request.approve.show', $req));

        $response->assertOk()
            ->assertSeeText('修正申請承認')
            // 画面のラベルは「氏名」ではなく「名前」
            ->assertSeeText('名前')
            ->assertSeeText('一般ユーザー');

        // 日付は YYYY-MM-DD ではなく「2026年」「2月1日」形式で表示される
        $response->assertSeeText('2026年')
                ->assertSeeText('2月1日');
    }
}
