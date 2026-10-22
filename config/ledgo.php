<?php

return ['sms' => ['driver' => env('SMS_DRIVER', 'log'), 'endpoint' => 'https://bulksmsbd.net/api/smsapi', 'key' => env('BULKSMSBD_API_KEY'), 'sender' => env('BULKSMSBD_SENDER_ID')], 'monthly_volume' => ['sales', 'purchases', 'expenses']];
