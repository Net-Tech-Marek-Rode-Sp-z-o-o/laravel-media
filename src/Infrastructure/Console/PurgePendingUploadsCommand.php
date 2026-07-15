<?php

declare(strict_types=1);

namespace NetCode\Media\Infrastructure\Console;

use Illuminate\Console\Command;
use NetCode\Kit\Clock;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Domain\Contracts\FileRepository;

final class PurgePendingUploadsCommand extends Command
{
    protected $signature = 'app:media:purge-uploads {--hours=24}';

    protected $description = 'Delete uploads that were initiated but never completed.';

    public function handle(Clock $clock, FileRepository $files, ObjectStorage $storage): int
    {
        $threshold = $clock->now()->modify(sprintf('-%d hours', (int) $this->option('hours')));

        $stale = $files->pendingOlderThan($threshold);

        foreach ($stale as $file) {
            $storage->delete($file->key());
            $files->delete($file);
        }

        $this->info(sprintf('Purged %d pending upload(s).', count($stale)));

        return self::SUCCESS;
    }
}
