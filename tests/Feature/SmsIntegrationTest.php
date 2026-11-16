<?php

namespace Tests\Feature;

use App\Domains\Order\Models\Order;
use App\Jobs\SendSmsJob;
use App\Models\User;
use App\Services\Sms\SmsProviderInterface;
use App\Services\Sms\TmSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SmsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_sms_job_when_order_is_delivering(): void
    {
        // The status notification job runs (sync queue in tests); only the SMS is faked
        Queue::fake([SendSmsJob::class]);

        $user = User::factory()->create(['phone' => '+99312345678']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PREPARING,
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
        $mockProvider->shouldReceive('isConfigured')->andReturn(true);
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

    public function test_sms_is_recorded_as_skipped_when_the_gateway_is_not_configured(): void
    {
        config(['services.tmsms.enabled' => false]);
        Http::fake();

        (new SendSmsJob('+99312345678', 'Test msg'))->handle(new TmSmsService);

        $this->assertDatabaseHas('sms_logs', ['phone' => '+99312345678', 'status' => 'skipped']);
        $this->assertDatabaseMissing('sms_logs', ['status' => 'sent']);
        Http::assertNothingSent();
    }

    public function test_enabled_gateway_without_credentials_does_not_claim_success(): void
    {
        config(['services.tmsms.enabled' => true, 'services.tmsms.login' => '', 'services.tmsms.password' => '']);

        $service = new TmSmsService;

        $this->assertFalse($service->isConfigured());
        $this->assertFalse($service->send('+99312345678', 'Test msg'));
    }

    public function test_configured_gateway_sends_and_is_recorded_as_sent(): void
    {
        config(['services.tmsms.enabled' => true, 'services.tmsms.login' => 'l', 'services.tmsms.password' => 'p']);
        Http::fake(['*' => Http::response(['ok' => true])]);

        (new SendSmsJob('+99312345678', 'Test msg'))->handle(new TmSmsService);

        $this->assertDatabaseHas('sms_logs', ['phone' => '+99312345678', 'status' => 'sent']);
        Http::assertSentCount(1);
    }
}
