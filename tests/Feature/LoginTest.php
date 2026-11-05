<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // trang đăng nhập dùng Vite; test không cần chạy npm run build
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Đăng nhập')
            ->assertSee('Tên đăng nhập')->assertSee('Quên mật khẩu?')->assertSee('Ghi nhớ đăng nhập')
            ->assertSee('Cần hỗ trợ? Liên hệ');
    }

    public function test_remember_me_sets_the_remember_cookie(): void
    {
        $user = User::factory()->create(['password' => 'correct-horse-battery']);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'correct-horse-battery', 'remember' => '1']);

        $response->assertRedirect('/');
        $response->assertCookie(Auth::guard()->getRecallerName());
    }

    public function test_valid_credentials_log_in(): void
    {
        $user = User::factory()->create(['password' => 'correct-horse-battery']);

        $this->post('/login', ['email' => $user->email, 'password' => 'correct-horse-battery'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected_with_generic_message(): void
    {
        $user = User::factory()->create(['password' => 'correct-horse-battery']);

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email hoặc mật khẩu không đúng.']);
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'a@b.test', 'password' => 'x']);
        }
        $this->post('/login', ['email' => 'a@b.test', 'password' => 'x'])->assertTooManyRequests();
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
