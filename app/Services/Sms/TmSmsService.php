<?php

declare(strict_types=1);

namespace App\Services\Sms;

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
        $this->apiUrl = config('services.tmsms.url', 'https://api.tmsms.tm/v1');
        $this->login = config('services.tmsms.login', '');
        $this->password = config('services.tmsms.password', '');
        $this->sender = config('services.tmsms.sender', 'RestoPWA');
    }

    public function send(string $phone, string $message): bool
    {
        // For local development or testing without credentials, mock the send.
        if (empty($this->login) || empty($this->password) || app()->environment('testing')) {
            Log::info("Mock SMS sent to {$phone}: {$message}");
            return true;
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

            Log::error('TM SMS API Error: ' . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error('TM SMS Exception: ' . $e->getMessage());
            return false;
        }
    }
}
