<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ config('app.name') }}</title>
<style>body{font:18px system-ui;background:#f0fdf4;color:#12382a;margin:0;padding:24px}main{max-width:700px;margin:12vh auto}a{display:inline-block;padding:14px 24px;border-radius:12px;background:#166534;color:white;text-decoration:none;min-height:24px}p{line-height:1.7}</style></head>
<body><main><h1>{{ config('app.name') }}</h1><p>{{ __('ledgo.promise') }}</p><a href="/app/login">{{ __('ledgo.sign_in') }}</a><p>{{ __('ledgo.foundation_notice') }}</p></main></body></html>
