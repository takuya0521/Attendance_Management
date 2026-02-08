<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_required(): void
    {
        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => '',
                'password' => 'password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);

        $this->get('/admin/login')->assertSee('メールアドレスを入力してください');
    }

    public function test_password_is_required(): void
    {
        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'admin@example.com',
                'password' => '',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);

        $this->get('/admin/login')->assertSee('パスワードを入力してください');
    }

    public function test_non_admin_cannot_login_to_admin_screen(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password',
            'is_admin' => false,
        ]);

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'user@example.com',
                'password' => 'password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);

        $this->get('/admin/login')->assertSee('ログイン情報が登録されていません');
    }

    public function test_wrong_credentials_cannot_login_to_admin_screen(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'wrong@example.com',
                'password' => 'password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);

        $this->get('/admin/login')->assertSee('ログイン情報が登録されていません');
    }

    public function test_admin_can_login_and_is_redirected_to_admin_attendance_list(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect('/admin/attendance/list');

        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->is_admin);
    }
}
