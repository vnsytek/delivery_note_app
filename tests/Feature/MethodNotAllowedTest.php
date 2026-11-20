<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MethodNotAllowedTest extends TestCase
{
    use RefreshDatabase;

    public function test_html_405_page_names_the_request_and_is_logged(): void
    {
        Log::spy();
        $this->actingAs(User::factory()->create());

        $this->get('/api/overrides/HDV')
            ->assertStatus(405)
            ->assertHeader('Allow', 'PUT')
            ->assertSee('Sai phương thức yêu cầu: GET /api/overrides/HDV')
            ->assertSee('chỉ cho phép PUT');

        Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message, $context) => $message === '405 Method Not Allowed'
            && $context['method'] === 'GET' && str_ends_with($context['url'], '/api/overrides/HDV') && $context['allowed'] === 'PUT');
    }

    public function test_json_405_returns_a_message(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson('/api/overrides/HDV')->assertStatus(405)->assertJsonPath('message', 'Sai phương thức yêu cầu (GET); cho phép: PUT.');
    }
}
