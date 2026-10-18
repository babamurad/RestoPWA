<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /** The SMS gateway may be briefly down: retry after 1 and 5 minutes */
    public int $tries = 3;

    public array $backoff = [60, 300];

    public int $timeout = 30;

    public function __construct(
        private string $phone,
        private string $message
    ) {}

    public function handle(SmsProviderInterface $smsService): void
    {
        $log = SmsLog::create([
            'phone' => $this->phone,
            'message' => $this->message,
            'status' => 'pending',
            'provider' => get_class($smsService),
        ]);

        try {
            $success = $smsService->send($this->phone, $this->message);

            $log->update([
                'status' => $success ? 'sent' : 'failed',
                'error_message' => $success ? null : 'Provider failed to deliver.',
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 500),
            ]);
            Log::error('SendSmsJob failed: '.$e->getMessage());

            // Re-throw so Laravel queue can attempt retries
            throw $e;
        }
    }
}
