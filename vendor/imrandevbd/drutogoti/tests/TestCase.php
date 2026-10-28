<?php

declare(strict_types=1);

namespace Drutogoti\Tests;

use Drutogoti\DrutogotiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            DrutogotiServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('drutogoti.enabled', true);
        $app['config']->set('drutogoti.watchers.queries.enabled', true);
        $app['config']->set('drutogoti.storage.driver', 'sync');
    }

    protected function setUpDatabase(): void
    {
        $migrations = [
            'create_drutogoti_requests_table.php',
            'create_drutogoti_query_logs_table.php',
            'create_drutogoti_cache_events_table.php',
            'create_drutogoti_cookie_events_table.php',
            'create_drutogoti_image_scans_table.php',
            'create_drutogoti_suggestions_table.php',
        ];

        foreach ($migrations as $migrationFile) {
            $migration = include __DIR__.'/../database/migrations/'.$migrationFile.'.stub';
            $migration->up();
        }
    }
}
