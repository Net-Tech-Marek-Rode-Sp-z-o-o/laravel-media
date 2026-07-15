<?php

declare(strict_types=1);

namespace NetCode\Media\Laravel;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use NetCode\Domain\Rule\BusinessRuleException;
use NetCode\Kit\Clock;
use NetCode\Kit\SystemClock;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Contract\FileDirectory;
use NetCode\Media\Domain\Contracts\FileRepository;
use NetCode\Media\Domain\Exceptions\FileNotFoundException;
use NetCode\Media\Domain\Exceptions\UploadNotConfirmedException;
use NetCode\Media\Infrastructure\Anticorruption\FileDirectoryAdapter;
use NetCode\Media\Infrastructure\Console\PurgePendingUploadsCommand;
use NetCode\Media\Infrastructure\DataAccess\Repositories\EloquentFileRepository;
use NetCode\Media\Infrastructure\Storage\S3ObjectStorage;
use Symfony\Component\HttpFoundation\Response;

final class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/media.php', 'media');

        $this->app->bind(Clock::class, SystemClock::class);
        $this->app->bind(FileRepository::class, EloquentFileRepository::class);
        $this->app->bind(FileDirectory::class, FileDirectoryAdapter::class);

        $this->app->bind(ObjectStorage::class, fn (): ObjectStorage => new S3ObjectStorage(
            disk: (string) config('media.disk'),
            presignDisk: (string) config('media.presign_disk'),
        ));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Route::prefix((string) config('media.route_prefix'))
            ->middleware((array) config('media.middleware'))
            ->group(__DIR__.'/../../routes/api.php');

        $this->registerExceptionRendering();
        $this->registerScheduledPurge();

        if ($this->app->runningInConsole()) {
            $this->commands([PurgePendingUploadsCommand::class]);

            $this->publishes([
                __DIR__.'/../../config/media.php' => $this->app->configPath('media.php'),
            ], 'media-config');

            $this->publishes([
                __DIR__.'/../../database/migrations' => $this->app->databasePath('migrations'),
            ], 'media-migrations');
        }
    }

    private function registerScheduledPurge(): void
    {
        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->command(PurgePendingUploadsCommand::class)->dailyAt('03:00');
        });
    }

    private function registerExceptionRendering(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        if (! method_exists($handler, 'renderable')) {
            return;
        }

        $handler->renderable(fn (FileNotFoundException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND));
        $handler->renderable(fn (UploadNotConfirmedException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_CONFLICT));
        $handler->renderable(fn (BusinessRuleException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_CONFLICT));
    }
}
