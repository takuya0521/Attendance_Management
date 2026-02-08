<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_is_blocked_from_verified_routes(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => null, // 未認証
        ]);

        $this->actingAs($user);

        $this->get(route('attendance.index'))
            ->assertRedirect('/email/verify');
    }

    public function test_verified_user_can_access_attendance_screen(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(), // 認証済
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.index'));

        $response->assertOk();

        // 画面タイトルが layout/改行の影響で一致しないケースがあるので OR で確認
        $this->assertMatchesRegularExpression('/(勤怠登録|出勤)/u', $response->getContent());
    }
}
