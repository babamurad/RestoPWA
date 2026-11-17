<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_link_is_sent_to_a_registered_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/v1/password/forgot', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('message', __('api.password.link_sent'));

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_gets_the_same_answer_and_no_mail(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/password/forgot', ['email' => 'nobody@example.com'])
            ->assertOk()
            ->assertJsonPath('message', __('api.password.link_sent'));

        Notification::assertNothingSent();
    }

    public function test_password_is_changed_with_a_valid_token(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password-1', $user->fresh()->password));
    }

    public function test_wrong_token_is_rejected_with_a_readable_message(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->postJson('/api/v1/password/reset', [
            'token' => 'wrong',
            'email' => $user->email,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertStatus(422)->assertJsonPath('message', __('api.password.invalid_token'));

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
