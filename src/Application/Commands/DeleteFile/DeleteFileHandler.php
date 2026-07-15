<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Commands\DeleteFile;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Domain\Contracts\FileRepository;
use NetCode\Media\Domain\ValueObjects\FileId;

final readonly class DeleteFileHandler implements CommandHandler
{
    public function __construct(
        private FileRepository $files,
        private ObjectStorage $storage,
    ) {}

    public function __invoke(
        DeleteFile $command,
    ): void {
        $file = $this->files->getById(FileId::fromString($command->fileId));

        $this->storage->delete($file->key());
        $this->files->delete($file);
    }
}
