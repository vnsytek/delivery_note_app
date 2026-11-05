<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_user_with_hashed_password(): void
    {
        $this->artisan('erp:create-user', ['email' => 'boss@example.com', '--name' => 'Boss'])
            ->expectsQuestion('Mật khẩu (tối thiểu 12 ký tự)', 'a-long-enough-password')
            ->assertSuccessful();

        $user = User::where('email', 'boss@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('a-long-enough-password', $user->password));
    }

    public function test_rejects_short_password(): void
    {
        $this->artisan('erp:create-user', ['email' => 'boss@example.com'])
            ->expectsQuestion('Mật khẩu (tối thiểu 12 ký tự)', 'short')
            ->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }
}
