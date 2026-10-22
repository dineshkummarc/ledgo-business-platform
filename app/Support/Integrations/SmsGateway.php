<?php

namespace App\Support\Integrations;

interface SmsGateway
{
    public function send(string $phone, string $message): void;
}
