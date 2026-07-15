<?php

declare(strict_types=1);

namespace NetCode\Media\Tests;

use Illuminate\Foundation\Application;
use NetCode\Bus\Laravel\BusServiceProvider;
use NetCode\Domain\Laravel\DomainServiceProvider;
use NetCode\Media\Application\Ports\CurrentUser;
use NetCode\Media\Laravel\MediaServiceProvider;
use NetCode\Media\Tests\Support\FakeCurrentUser;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @param Application $app */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            DomainServiceProvider::class,
            BusServiceProvider::class,
            MediaServiceProvider::class,
        ];
    }

    /** @param Application $app */
    protected function defineEnvironment($app): void
    {
        $config = $app['config'];

        $config->set('database.default', 'pgsql');
        $config->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'testing'),
            'username' => env('DB_USERNAME', 'test'),
            'password' => env('DB_PASSWORD', 'test'),
            'charset' => 'utf8',
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ]);

        $app->bind(CurrentUser::class, FakeCurrentUser::class);
    }
}
