<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_required(): void
    {
        User::factory()->create();

        $this->from('/login')
            ->post('/login', [
                'email' => '',
                'password' => 'password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);

        $this->get('/login')->assertSee('メールアドレスを入力してください');
    }

    public function test_password_is_required(): void
    {
        $this->from('/login')
            ->post('/login', [
                'email' => 't@example.com',
                'password' => '',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);

        $this->get('/login')->assertSee('パスワードを入力してください');
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        User::factory()->create([
            'email' => 'real@example.com',
            'password' => 'password',
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'wrong@example.com',
                'password' => 'password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);

        $this->get('/login')->assertSee('ログイン情報が登録されていません');
    }

    public function test_user_can_login_and_is_redirected_to_attendance_screen(): void
    {
        User::factory()->create([
            'email' => 't@example.com',
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => 't@example.com',
            'password' => 'password',
        ])->assertRedirect('/attendance');

        $this->assertAuthenticated();
    }
}
