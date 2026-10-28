<?php

declare(strict_types=1);

namespace Drutogoti;

use Illuminate\Support\ServiceProvider;

class DrutogotiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/drutogoti.php', 'drutogoti'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/drutogoti.php' => config_path('drutogoti.php'),
            ], 'drutogoti-config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_drutogoti_requests_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_drutogoti_requests_table.php'),
                __DIR__.'/../database/migrations/create_drutogoti_query_logs_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 1).'_create_drutogoti_query_logs_table.php'),
                __DIR__.'/../database/migrations/create_drutogoti_cache_events_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 2).'_create_drutogoti_cache_events_table.php'),
                __DIR__.'/../database/migrations/create_drutogoti_cookie_events_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 3).'_create_drutogoti_cookie_events_table.php'),
                __DIR__.'/../database/migrations/create_drutogoti_image_scans_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 4).'_create_drutogoti_image_scans_table.php'),
                __DIR__.'/../database/migrations/create_drutogoti_suggestions_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 5).'_create_drutogoti_suggestions_table.php'),
            ], 'drutogoti-migrations');
        }

        if (!config('drutogoti.enabled')) {
            return;
        }

        $this->app->singleton(\Drutogoti\CaptureContext::class);
        $this->app->make(\Drutogoti\Watchers\QueryWatcher::class)->register();

        $this->app->terminating(function () {
            /** @var \Drutogoti\CaptureContext $context */
            $context = $this->app->make(\Drutogoti\CaptureContext::class);
            $context->flushAndDispatch();
        });
    }
}
