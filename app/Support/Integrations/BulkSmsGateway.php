<?php

namespace App\Support\Integrations;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class BulkSmsGateway implements SmsGateway
{
    public function send(string $phone, string $message): void
    {
        if (! config('ledgo.sms.key') || ! config('ledgo.sms.sender')) {
            throw new RuntimeException('BulkSMSBD credentials are not configured.');
        }
        $response = Http::asForm()->timeout(15)->post(config('ledgo.sms.endpoint'), ['api_key' => config('ledgo.sms.key'), 'senderid' => config('ledgo.sms.sender'), 'number' => $phone, 'message' => $message])->throw();
        if ((int) $response->json('response_code') !== 202) {
            throw new RuntimeException('SMS submission was rejected.');
        }
    }
}
