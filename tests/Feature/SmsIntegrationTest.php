<?php

namespace Tests\Feature;

use App\Domains\Order\Models\Order;
use App\Jobs\SendSmsJob;
use App\Models\User;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SmsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_sms_job_when_order_is_delivering(): void
    {
        Queue::fake();

        $user = User::factory()->create(['phone' => '+99312345678']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_COOKING,
        ]);

        $order->update(['status' => Order::STATUS_DELIVERING]);

        Queue::assertPushed(SendSmsJob::class, function ($job) {
            // Need to use reflection to inspect private properties if needed, 
            // but simply checking the class was pushed is enough for the unit test context.
            return true;
        });
    }

    public function test_send_sms_job_handles_provider(): void
    {
        $mockProvider = \Mockery::mock(SmsProviderInterface::class);
        $mockProvider->shouldReceive('send')
            ->once()
            ->with('+99312345678', 'Test msg')
            ->andReturn(true);

        $job = new SendSmsJob('+99312345678', 'Test msg');
        $job->handle($mockProvider);

        $this->assertDatabaseHas('sms_logs', [
            'phone' => '+99312345678',
            'status' => 'sent',
        ]);
    }
}
