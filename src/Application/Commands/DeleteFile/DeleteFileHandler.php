<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Commands\DeleteFile;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Application\Services\AccessibleFiles;
use NetCode\Media\Domain\Contracts\FileRepository;

final readonly class DeleteFileHandler implements CommandHandler
{
    public function __construct(
        private FileRepository $files,
        private ObjectStorage $storage,
        private AccessibleFiles $accessible,
    ) {}

    public function __invoke(
        DeleteFile $command,
    ): void {
        $file = $this->accessible->get($command->fileId, $command->requestedBy);

        $this->storage->delete($file->key());
        $this->files->delete($file);
    }
}
