<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Order\Models\Order;
use App\Enums\UserRole;
use App\Jobs\SendSmsJob;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * The customer app speaks Russian (default) and Turkmen; the restaurant side stays Russian.
 */
class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_messages_follow_the_x_locale_header(): void
    {
        $this->getJson('/api/v1/orders/'.fake()->uuid(), ['X-Locale' => 'tk'])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Ulgama girilmedik');

        $this->getJson('/api/v1/orders/'.fake()->uuid())
            ->assertStatus(401)
            ->assertJsonPath('message', 'Не авторизован');
    }

    public function test_unsupported_locale_falls_back_to_russian(): void
    {
        $this->putJson('/api/v1/profile/locale', [], ['X-Locale' => 'en'])
            ->assertJsonPath('message', 'Не авторизован');
    }

    public function test_validation_errors_are_translated_to_turkmen(): void
    {
        $this->postJson('/api/v1/register', [], ['X-Locale' => 'tk'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Maglumatlar nädogry')
            ->assertJsonPath('errors.terms_accepted.0', 'Ulanyjy ylalaşygyny we gizlinlik syýasatyny kabul etmeli.');
    }

    public function test_registration_saves_the_chosen_language(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Aýna',
            'email' => 'ayna@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'terms_accepted' => true,
            'locale' => 'tk',
        ])->assertCreated();

        $this->assertSame('tk', User::where('email', 'ayna@example.com')->value('locale'));
    }

    public function test_user_switches_language_and_it_wins_over_the_header(): void
    {
        $user = User::factory()->create();
        $this->assertSame('ru', $user->fresh()->locale);

        $this->actingAs($user)
            ->putJson('/api/v1/profile/locale', ['locale' => 'tk'])
            ->assertOk();
        $this->assertSame('tk', $user->fresh()->locale);

        $this->actingAs($user->fresh())
            ->getJson('/api/v1/user', ['X-Locale' => 'ru'])
            ->assertJsonPath('user.locale', 'tk');

        $this->actingAs($user->fresh())
            ->getJson('/api/v1/orders/'.fake()->uuid(), ['X-Locale' => 'ru'])
            ->assertJsonPath('message', 'Tapylmady');

        $this->actingAs($user)
            ->putJson('/api/v1/profile/locale', ['locale' => 'en'])
            ->assertStatus(422);
    }

    public function test_restaurant_staff_always_get_russian(): void
    {
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR, 'locale' => 'tk']);

        $this->actingAs($owner)
            ->getJson('/api/v1/orders/'.fake()->uuid(), ['X-Locale' => 'tk'])
            ->assertJsonPath('message', 'Ресурс не найден');
    }

    public function test_push_and_sms_are_sent_in_the_customers_language(): void
    {
        Queue::fake();
        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')->once()
            ->with(Mockery::any(), 'Sargydyňyz ýolda', 'Kurýer size tarap ýola çykdy', Mockery::any())
            ->andReturn(1);
        $this->app->instance(PushNotificationService::class, $push);

        $customer = User::factory()->create(['locale' => 'tk', 'phone' => '+99361234567']);
        $courier = Courier::create(['user_id' => User::factory()->create(['role' => UserRole::COURIER])->id]);
        $order = Order::factory()->create(['user_id' => $customer->id, 'status' => Order::STATUS_READY_FOR_PICKUP]);

        $order->update(['courier_id' => $courier->id, 'status' => Order::STATUS_DELIVERING]);

        Queue::assertPushed(SendSmsJob::class, function (SendSmsJob $job) {
            $message = (new \ReflectionProperty($job, 'message'))->getValue($job);

            return $message === 'RestoPWA: Kurýer size tarap ýola çykdy';
        });
    }

    public function test_every_russian_server_string_has_a_turkmen_translation(): void
    {
        foreach (glob(lang_path('ru/*.php')) as $file) {
            $name = basename($file);
            $this->assertFileExists(lang_path("tk/{$name}"), "lang/tk/{$name} is missing");

            $ru = array_keys(\Illuminate\Support\Arr::dot(require $file));
            $tk = array_keys(\Illuminate\Support\Arr::dot(require lang_path("tk/{$name}")));
            $this->assertSame([], array_values(array_diff($ru, $tk)), "lang/tk/{$name} misses keys");
        }
    }
}
