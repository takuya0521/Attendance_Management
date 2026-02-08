<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_list(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $u1 = User::factory()->create([
            'name' => '太郎',
            'email' => 'taro@example.com',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
        $u2 = User::factory()->create([
            'name' => '花子',
            'email' => 'hanako@example.com',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.staff.list'));

        $response->assertOk()
            ->assertSeeText('スタッフ一覧')
            ->assertSeeText('太郎')
            ->assertSeeText('taro@example.com')
            ->assertSeeText('花子')
            ->assertSeeText('hanako@example.com');

        $response->assertSee(route('admin.staff.attendance', $u1), false);
        $response->assertSee(route('admin.staff.attendance', $u2), false);
    }
}
