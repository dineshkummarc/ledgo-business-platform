<?php

namespace App\Modules\Auth;

use App\Support\Integrations\SmsGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function __construct(private SmsGateway $sms) {}

    public function send(string $phone): void
    {
        $code = (string) random_int(100000, 999999);
        Cache::put('otp:'.$phone, ['hash' => Hash::make($code), 'attempts' => 0], now()->addMinutes(5));
        try {
            $this->sms->send($phone, __('ledgo.otp_message', ['code' => $code]));
        } catch (\Throwable $e) {
            Cache::forget('otp:'.$phone);
            throw $e;
        }
    }

    public function verify(string $phone, string $code): void
    {
        Cache::lock('otp-lock:'.$phone, 10)->block(3, function () use ($phone, $code) {
            $entry = Cache::get('otp:'.$phone);
            if (! $entry || $entry['attempts'] >= 5) {
                throw ValidationException::withMessages(['code' => __('ledgo.invalid_code')]);
            }
            if (! Hash::check($code, $entry['hash'])) {
                $entry['attempts']++;
                Cache::put('otp:'.$phone, $entry, now()->addMinutes(5));
                throw ValidationException::withMessages(['code' => __('ledgo.invalid_code')]);
            }
            Cache::forget('otp:'.$phone);
        });
    }
}
