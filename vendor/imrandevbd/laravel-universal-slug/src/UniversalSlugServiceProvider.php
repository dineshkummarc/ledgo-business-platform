<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Illuminate\Validation\Rule;
use ImranDev\UniversalSlug\Commands\AuditSlugsCommand;
use ImranDev\UniversalSlug\Commands\GenerateSlugCommand;
use ImranDev\UniversalSlug\Commands\PruneSlugHistoriesCommand;
use ImranDev\UniversalSlug\Commands\RegenerateModelSlugsCommand;
use ImranDev\UniversalSlug\Rules\UniqueUniversalSlugRule;

class UniversalSlugServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/universal-slug.php',
            'universal-slug'
        );

        $this->app->singleton('universal-slug', function ($app) {
            return new UniversalSlug($app['config']->get('universal-slug', []));
        });

        $this->app->alias('universal-slug', UniversalSlug::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/universal-slug.php' => config_path('universal-slug.php'),
            ], 'universal-slug-config');

            if (! class_exists('CreateSlugHistoriesTable')) {
                $this->publishes([
                    __DIR__.'/../database/migrations/create_slug_histories_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_slug_histories_table.php'),
                ], 'universal-slug-migrations');
            }

            $this->commands([
                GenerateSlugCommand::class,
                RegenerateModelSlugsCommand::class,
                PruneSlugHistoriesCommand::class,
                AuditSlugsCommand::class,
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            if (config('universal-slug.schedule.prune', false)) {
                $days = (int) config('universal-slug.history.prune_after_days', 90);
                $schedule->command('slug:prune', ['--days' => $days])->daily();
            }
        });

        // Register Str::universalSlug() macro
        if (! Str::hasMacro('universalSlug')) {
            Str::macro('universalSlug', function (?string $title, ?string $separator = null, ?string $language = null, array $options = []) {
                return UniversalSlug::generate($title, $separator, $language, $options);
            });
        }

        // Register Stringable::universalSlug() macro
        if (! Stringable::hasMacro('universalSlug')) {
            Stringable::macro('universalSlug', function (?string $separator = null, ?string $language = null, array $options = []) {
                return new Stringable(UniversalSlug::generate($this->value, $separator, $language, $options));
            });
        }

        // Register Rule::universalSlug() macro
        if (class_exists(Rule::class) && ! Rule::hasMacro('universalSlug')) {
            Rule::macro('universalSlug', function (?string $separator = '-', ?string $language = null, array $options = []) {
                return UniversalSlug::rule($separator, $language, $options);
            });
        }

        if (class_exists(Rule::class) && ! Rule::hasMacro('uniqueUniversalSlug')) {
            Rule::macro('uniqueUniversalSlug', function (string $model, string $column = 'slug') {
                return new UniqueUniversalSlugRule($model, $column);
            });
        }

        // Register Blade directive @slug
        Blade::directive('slug', function ($expression) {
            return "<?php echo \ImranDev\UniversalSlug\UniversalSlug::generate({$expression}); ?>";
        });
    }
}
