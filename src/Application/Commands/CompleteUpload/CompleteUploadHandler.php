<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Commands\CompleteUpload;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Kit\Clock;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Application\Services\AccessibleFiles;
use NetCode\Media\Domain\Contracts\FileRepository;
use NetCode\Media\Domain\Exceptions\UploadNotConfirmedException;

final readonly class CompleteUploadHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private FileRepository $files,
        private ObjectStorage $storage,
        private AccessibleFiles $accessible,
    ) {}

    public function __invoke(
        CompleteUpload $command,
    ): void {
        $file = $this->accessible->get($command->fileId, $command->requestedBy);

        if (! $this->storage->exists($file->key())) {
            throw UploadNotConfirmedException::forKey($file->key());
        }

        $file->complete($command->checksum, $command->size, $this->clock->now());

        $this->files->save($file);
    }
}
