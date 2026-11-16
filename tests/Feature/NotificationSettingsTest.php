<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\Account\AccountDeletionService;
use App\Support\NotificationPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_customer_gets_order_notifications_and_no_marketing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/profile/notifications')
            ->assertOk()
            ->assertJsonPath('data.order_status.locked', true)
            ->assertJsonPath('data.order_status.push', true)
            ->assertJsonPath('data.order_status.sms', true)
            ->assertJsonPath('data.marketing', ['push' => false, 'email' => false, 'sms' => false]);

        $this->assertFalse(NotificationPreferences::allowsMarketing($user, 'push'));
    }

    public function test_marketing_channels_are_switched_one_by_one_and_persist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/profile/notifications', ['marketing' => ['email' => true]])
            ->assertOk()
            ->assertJsonPath('data.marketing', ['push' => false, 'email' => true, 'sms' => false]);

        // Another call that names only one channel leaves the rest as they were
        $this->putJson('/api/v1/profile/notifications', ['marketing' => ['sms' => true]])->assertOk();

        $user = $user->fresh();
        $this->assertTrue(NotificationPreferences::allowsMarketing($user, 'email'));
        $this->assertTrue(NotificationPreferences::allowsMarketing($user, 'sms'));
        $this->assertFalse(NotificationPreferences::allowsMarketing($user, 'push'));

        $this->putJson('/api/v1/profile/notifications', ['marketing' => ['email' => false]])->assertOk();
        $this->assertFalse(NotificationPreferences::allowsMarketing($user->fresh(), 'email'));
    }

    public function test_order_notifications_cannot_be_switched_off(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/profile/notifications', [
            'marketing' => ['push' => true],
            'order_status' => ['sms' => false],
        ])->assertStatus(422)->assertJsonPath('message', __('api.notifications.status_locked'));

        // Nothing was saved from the refused request
        $this->assertFalse(NotificationPreferences::allowsMarketing($user->fresh(), 'push'));

        // Sending them as "on" is harmless
        $this->putJson('/api/v1/profile/notifications', [
            'marketing' => ['push' => true],
            'order_status' => ['sms' => true, 'locked' => true],
        ])->assertOk();
    }

    public function test_input_is_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/profile/notifications', [])->assertStatus(422);
        $this->putJson('/api/v1/profile/notifications', ['marketing' => ['push' => 'maybe']])->assertStatus(422);
        // Unknown channels are not stored
        $this->putJson('/api/v1/profile/notifications', ['marketing' => ['fax' => true]])->assertOk();
        $this->assertSame(['push' => false, 'email' => false, 'sms' => false], NotificationPreferences::for($user->fresh())['marketing']);
        $this->assertFalse(NotificationPreferences::allowsMarketing($user, 'fax'));
    }

    public function test_guest_has_no_settings(): void
    {
        $this->getJson('/api/v1/profile/notifications')->assertStatus(401);
        $this->putJson('/api/v1/profile/notifications', ['marketing' => ['push' => true]])->assertStatus(401);
    }

    public function test_settings_are_wiped_with_the_account(): void
    {
        $user = User::factory()->create();
        NotificationPreferences::updateMarketing($user, ['push' => true, 'sms' => true]);

        app(AccountDeletionService::class)->delete($user->fresh());

        $this->assertNull($user->fresh()->notification_preferences);
    }
}
