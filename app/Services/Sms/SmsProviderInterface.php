<?php

declare(strict_types=1);

namespace App\Services\Sms;

interface SmsProviderInterface
{
    /**
     * Send an SMS message to a given phone number.
     *
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function send(string $phone, string $message): bool;
}
