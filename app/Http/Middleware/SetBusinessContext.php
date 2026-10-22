<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\BusinessContext;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class SetBusinessContext
{
    public function handle(Request $request, Closure $next): mixed
    {
        $business = Filament::getTenant();
        abort_unless($business && $request->user()?->canAccessTenant($business), 403);
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $locale = app()->getLocale();
        $registrar->setPermissionsTeamId($business->id);
        $request->user()->unsetRelation('roles')->unsetRelation('permissions');
        app()->setLocale($business->locale);
        try {
            return app(BusinessContext::class)->run($business, fn () => $next($request));
        } finally {
            $registrar->setPermissionsTeamId($previous);
            $request->user()->unsetRelation('roles')->unsetRelation('permissions');
            app()->setLocale($locale);
        }
    }
}
