<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_login_is_blocked_after_five_wrong_passwords(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong'])
                ->assertStatus(401);
        }

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'correct-password'])
            ->assertStatus(429)
            ->assertJsonPath('message', __('api.too_many_requests'));
    }

    public function test_login_limit_of_one_account_does_not_block_another(): void
    {
        $victim = User::factory()->create();
        $other = User::factory()->create(['password' => 'correct-password']);

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/login', ['email' => $victim->email, 'password' => 'wrong']);
        }

        $this->postJson('/api/v1/login', ['email' => $other->email, 'password' => 'correct-password'])
            ->assertOk();
    }

    public function test_web_and_mobile_login_are_limited_too(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(401);
        }
        $this->postJson('/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(429);

        $this->postJson('/api/v1/mobile/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_registration_is_limited_per_address(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/register', [])->assertStatus(422);
        }

        $this->postJson('/api/v1/register', [])->assertStatus(429);
    }

    #[DataProvider('redirectTargets')]
    public function test_google_login_only_redirects_inside_the_site(?string $requested, string $stored): void
    {
        Socialite::shouldReceive('driver->redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        $this->get('/auth/google/redirect'.($requested === null ? '' : '?redirect='.urlencode($requested)));

        $this->assertSame($stored, session('google_login_redirect'));
    }

    public static function redirectTargets(): array
    {
        return [
            'none' => [null, '/profile'],
            'local path' => ['/checkout?step=2', '/checkout?step=2'],
            'absolute url' => ['https://evil.example/login', '/profile'],
            'protocol-relative' => ['//evil.example', '/profile'],
            'backslash' => ['/\\evil.example', '/profile'],
            'no leading slash' => ['evil.example', '/profile'],
        ];
    }
}
