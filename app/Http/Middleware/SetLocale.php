<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next): mixed
    {
        $locale = $request->user()?->locale ?? $request->session()->get('locale', 'en');
        app()->setLocale(in_array($locale, ['en', 'bn']) ? $locale : 'en');

        return $next($request);
    }
}
