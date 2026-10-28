<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ImranDev\UniversalSlug\Facades\UniversalSlug;
use ImranDev\UniversalSlug\UniversalSlugServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            UniversalSlugServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'UniversalSlug' => UniversalSlug::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function setUpDatabase(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('slug')->nullable();
            $table->string('custom_slug')->nullable();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->string('locale')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('slug_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('sluggable');
            $table->string('slug')->index();
            $table->string('field')->default('slug');
            $table->timestamps();
        });
    }
}
