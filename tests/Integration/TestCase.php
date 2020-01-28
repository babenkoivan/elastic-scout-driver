<?php
declare(strict_types=1);

namespace ElasticScoutDriver\Tests\Integration;

use ElasticClient\ServiceProvider as ClientServiceProvider;
use ElasticMigrations\ServiceProvider as MigrationsServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

class TestCase extends TestbenchTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ClientServiceProvider::class,
            MigrationsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('elastic.migrations.storage_directory', __DIR__.'/../app/elastic/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../app/database/migrations');
        $this->withFactories(__DIR__.'/../app/database/factories');

        $this->artisan('migrate')->run();
        $this->artisan('elastic:migrate')->run();
    }

    protected function tearDown(): void
    {
        $this->artisan('elastic:migrate:reset')->run();
        $this->artisan('migrate:reset')->run();

        parent::tearDown();
    }
}
