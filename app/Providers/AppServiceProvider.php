<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use App\Support\Integrations\AdminPanelConsole;
use App\Support\Integrations\BulkSmsGateway;
use App\Support\Integrations\DrutogotiProfiler;
use App\Support\Integrations\LogSmsGateway;
use App\Support\Integrations\PerformanceProfiler;
use App\Support\Integrations\PlatformConsole;
use App\Support\Integrations\SlugService;
use App\Support\Integrations\SmsGateway;
use App\Support\Integrations\UniversalSlugService;
use App\Support\Tenancy\BusinessContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(BusinessContext::class);
        $this->app->bind(SlugService::class, UniversalSlugService::class);
        $this->app->bind(PlatformConsole::class, AdminPanelConsole::class);
        $this->app->bind(PerformanceProfiler::class, DrutogotiProfiler::class);
        $this->app->bind(SmsGateway::class, fn () => config('ledgo.sms.driver') === 'bulksmsbd' ? new BulkSmsGateway : new LogSmsGateway);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
		Schema::defaultStringLength(191);
        //
    }
}
