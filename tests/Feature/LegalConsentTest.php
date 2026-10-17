<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * Legal pages are public, and sign-up records which edition of the
 * documents the user accepted and when.
 */
class LegalConsentTest extends TestCase
{
    use RefreshDatabase;

    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Тест',
            'email' => 'new-user@example.com',
            'phone' => '61234567',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'terms_accepted' => true,
        ], $overrides);
    }

    public function test_legal_pages_are_public(): void
    {
        foreach (['terms', 'privacy', 'offer'] as $document) {
            $this->get("/legal/{$document}")
                ->assertOk()
                ->assertSee(config('legal.documents')[$document]);
        }
    }

    public function test_unknown_legal_page_is_not_found(): void
    {
        $this->get('/legal/cookies')->assertNotFound();
    }

    public function test_registration_requires_terms_acceptance(): void
    {
        $this->postJson('/api/v1/register', $this->registrationPayload(['terms_accepted' => false]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('terms_accepted');

        $this->postJson('/api/v1/register', $this->registrationPayload(['terms_accepted' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('terms_accepted');

        $this->assertDatabaseMissing('users', ['email' => 'new-user@example.com']);
    }

    public function test_registration_stores_acceptance_time_and_version(): void
    {
        $this->freezeSecond();

        $this->postJson('/api/v1/register', $this->registrationPayload())->assertCreated();

        $user = User::where('email', 'new-user@example.com')->firstOrFail();
        $this->assertTrue($user->terms_accepted_at->equalTo(now()));
        $this->assertSame(config('legal.version'), $user->terms_version);
    }

    public function test_new_google_user_gets_acceptance_recorded(): void
    {
        $googleUser = (new SocialiteUser)->map([
            'id' => 'google-123',
            'name' => 'Google Тест',
            'email' => 'google-user@example.com',
            'avatar' => null,
        ]);
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->get('/auth/google/callback');

        $user = User::where('email', 'google-user@example.com')->firstOrFail();
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertSame(config('legal.version'), $user->terms_version);
    }
}
