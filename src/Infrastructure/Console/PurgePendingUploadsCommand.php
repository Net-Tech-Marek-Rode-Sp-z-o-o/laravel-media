<?php

declare(strict_types=1);

namespace NetCode\Media\Infrastructure\Console;

use Illuminate\Console\Command;
use NetCode\Kit\Clock;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Domain\Contracts\FileRepository;

final class PurgePendingUploadsCommand extends Command
{
    protected $signature = 'media:purge-uploads {--hours=24}';

    protected $description = 'Delete uploads that were never completed or were rejected.';

    public function handle(Clock $clock, FileRepository $files, ObjectStorage $storage): int
    {
        $threshold = $clock->now()->modify(sprintf('-%d hours', (int) $this->option('hours')));

        $stale = $files->unfinishedOlderThan($threshold);

        foreach ($stale as $file) {
            $storage->delete($file->key());
            $storage->delete($file->storedKey());
            $files->delete($file);
        }

        $this->info(sprintf('Purged %d unfinished upload(s).', count($stale)));

        return self::SUCCESS;
    }
}
