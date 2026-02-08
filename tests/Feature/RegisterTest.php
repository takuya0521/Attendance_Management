<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_is_required(): void
    {
        $this->from('/register')
            ->post('/register', [
                'name' => '',
                'email' => 't@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['name' => 'お名前を入力してください']);

        $this->get('/register')->assertSee('お名前を入力してください');
    }

    public function test_email_is_required(): void
    {
        $this->from('/register')
            ->post('/register', [
                'name' => 'Taro',
                'email' => '',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);

        $this->get('/register')->assertSee('メールアドレスを入力してください');
    }

    public function test_password_is_required(): void
    {
        $this->from('/register')
            ->post('/register', [
                'name' => 'Taro',
                'email' => 't@example.com',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);

        $this->get('/register')->assertSee('パスワードを入力してください');
    }

    public function test_password_must_be_at_least_8_chars(): void
    {
        $this->from('/register')
            ->post('/register', [
                'name' => 'Taro',
                'email' => 't@example.com',
                'password' => 'short7',
                'password_confirmation' => 'short7',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);

        $this->get('/register')->assertSee('パスワードは8文字以上で入力してください');
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->from('/register')
            ->post('/register', [
                'name' => 'Taro',
                'email' => 't@example.com',
                'password' => 'password',
                'password_confirmation' => 'different',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['password' => 'パスワードと一致しません']);

        $this->get('/register')->assertSee('パスワードと一致しません');
    }

    public function test_user_can_register_and_verification_email_is_sent(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Taro',
            'email' => 't@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/email/verify');

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 't@example.com',
            'name' => 'Taro',
        ]);

        $user = User::where('email', 't@example.com')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
