<?php

declare(strict_types=1);

namespace App\Services\Sms;

interface SmsProviderInterface
{
    /** Is sending switched on and are the gateway credentials set? */
    public function isConfigured(): bool;

    /** Send one message; true only if the gateway accepted it. */
    public function send(string $phone, string $message): bool;
}
