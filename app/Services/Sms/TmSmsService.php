<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Support\PIIMasker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TmSmsService implements SmsProviderInterface
{
    private string $apiUrl;

    private string $login;

    private string $password;

    private string $sender;

    public function __construct()
    {
        $this->apiUrl = (string) (config('services.tmsms.url') ?: 'https://api.tmsms.tm/v1');
        $this->login = (string) config('services.tmsms.login', '');
        $this->password = (string) config('services.tmsms.password', '');
        $this->sender = (string) (config('services.tmsms.sender') ?: 'RestoPWA');
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.tmsms.enabled') && $this->login !== '' && $this->password !== '';
    }

    public function send(string $phone, string $message): bool
    {
        // Never report a message as sent when nothing was sent
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::timeout(5)->post("{$this->apiUrl}/send", [
                'login' => $this->login,
                'password' => $this->password,
                'sender' => $this->sender,
                'phone' => $phone,
                'text' => $message,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('[SMS] Gateway error', [
                'phone' => PIIMasker::maskPhone($phone),
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 300),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('[SMS] Gateway exception: '.$e->getMessage(), ['phone' => PIIMasker::maskPhone($phone)]);

            return false;
        }
    }
}
