<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequirePlatformAdmin
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->user()) {
            return redirect('/app/login');
        } abort_unless($request->user()->is_platform_admin, 403);

        return $next($request);
    }
}
