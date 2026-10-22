<?php

namespace App\Support\Integrations;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class LogSmsGateway implements SmsGateway
{
    public function send(string $phone, string $message): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Log SMS driver is restricted to development.');
        } Log::info('Development SMS', ['phone' => $phone, 'message' => $message]);
    }
}
