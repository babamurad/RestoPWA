<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Push\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_can_subscribe_to_push(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/push/subscribe', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/some-endpoint-id',
                'keys' => [
                    'p256dh' => 'BNcRbtqGQiavpMgC47JXu5gJ9oEbh3XpPbKmK2VQKfCTXqGK6T2rJpT3JpT3JpT3JpT3JpT3JpT3JpT3Jp',
                    'auth' => 'tJX2rqJpT3JpT3JpT',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_guest_cannot_subscribe_to_push(): void
    {
        $response = $this->postJson('/api/v1/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/some-endpoint-id',
            'keys' => [
                'p256dh' => 'BNcRbtqGQiavpMgC47JXu5gJ9oEbh3XpPbKmK2VQKfCTXqGK6T2rJpT3JpT3JpT3JpT3JpT3JpT3JpT3Jp',
                'auth' => 'tJX2rqJpT3JpT3JpT',
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_unsubscribe_from_push(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/some-endpoint-id';

        PushSubscription::create([
            'user_id' => $this->user->id,
            'endpoint' => $endpoint,
            'p256dh' => 'test-p256dh',
            'auth' => 'test-auth',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/push/unsubscribe', [
                'endpoint' => $endpoint,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => $endpoint,
        ]);
    }

    public function test_push_subscription_requires_valid_endpoint(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/push/subscribe', [
                'endpoint' => 'not-an-url',
                'keys' => [
                    'p256dh' => 'test',
                    'auth' => 'test',
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_push_subscription_requires_keys(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/push/subscribe', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/test',
            ]);

        $response->assertStatus(422);
    }
}