<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Logistics\Jobs\ExpireCourierOffer;
use App\Domains\Order\Models\Order;
use App\Jobs\SendOrderStatusNotification;
use App\Jobs\SendSmsJob;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * Production queue: slow work (push, SMS) leaves the request; time-critical
 * courier offers get their own queue; a stopped worker gets noticed.
 */
class QueueSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_change_queues_the_notification_instead_of_sending_it_inline(): void
    {
        Queue::fake();
        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldNotReceive('sendToUser');
        $this->app->instance(PushNotificationService::class, $push);

        $order = Order::factory()->create(['user_id' => User::factory()->create()->id, 'status' => Order::STATUS_PREPARING]);
        $order->update(['status' => Order::STATUS_DELIVERING]);

        Queue::assertPushed(SendOrderStatusNotification::class, fn ($job) => $job->orderId === $order->id && $job->status === Order::STATUS_DELIVERING);
        Queue::assertNotPushed(SendSmsJob::class);
    }

    public function test_failed_push_does_not_cost_the_sms(): void
    {
        Queue::fake([SendSmsJob::class]);
        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')->andThrow(new \RuntimeException('VAPID keys missing'));
        $this->app->instance(PushNotificationService::class, $push);

        $user = User::factory()->create(['phone' => '+99361234567']);
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => Order::STATUS_DELIVERING]);

        (new SendOrderStatusNotification($order->id, Order::STATUS_DELIVERED))->handle();

        Queue::assertPushed(SendSmsJob::class);
    }

    public function test_cooking_status_sends_push_but_no_sms(): void
    {
        Queue::fake([SendSmsJob::class]);
        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')->once()->andReturn(1);
        $this->app->instance(PushNotificationService::class, $push);

        $order = Order::factory()->create(['user_id' => User::factory()->create(['phone' => '+99361234567'])->id]);

        (new SendOrderStatusNotification($order->id, 'cooking'))->handle();

        Queue::assertNotPushed(SendSmsJob::class);
    }

    public function test_courier_offer_expiry_runs_on_the_high_priority_queue(): void
    {
        $this->assertSame('high', (new ExpireCourierOffer('offer-id'))->queue);
    }

    public function test_sms_is_retried_with_backoff(): void
    {
        $job = new SendSmsJob('+99361234567', 'text');

        $this->assertSame(3, $job->tries);
        $this->assertSame([60, 300], $job->backoff);
    }

    public function test_jobs_wait_for_the_transaction_to_commit(): void
    {
        $this->assertTrue(config('queue.connections.database.after_commit'));
        $this->assertTrue(config('queue.connections.redis.after_commit'));
    }

    public function test_piled_up_queue_is_reported(): void
    {
        Log::spy();

        Event::dispatch(new QueueBusy('database', 'default', 120));

        Log::shouldHaveReceived('error')->withArgs(fn ($message) => str_contains($message, 'database:default has 120 waiting jobs'));
    }
}
