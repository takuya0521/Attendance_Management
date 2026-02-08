<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationFlowTest extends TestCase
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

    public function test_verify_notice_has_mailpit_button(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_admin' => false,
        ]);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSeeText('認証はこちらから')
            ->assertSee('http://localhost:8025', false);
    }

    public function test_resend_verification_email_shows_flash_message(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_admin' => false,
        ]);

        $this->actingAs($user)
            ->followingRedirects()
            ->post(route('verification.send'))
            ->assertOk()
            ->assertSeeText('認証メールを再送しました。');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_clicking_verification_link_marks_user_verified_and_redirects_to_attendance_screen(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_admin' => false,
        ]);

        $this->actingAs($user);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $this->get($url)
            ->assertRedirect(route('attendance.index'));

        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());

        $this->get(route('attendance.index'))
            ->assertOk()
            ->assertSeeText('勤怠登録');
    }
}
