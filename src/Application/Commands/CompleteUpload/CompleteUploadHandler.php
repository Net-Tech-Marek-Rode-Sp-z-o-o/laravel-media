<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Commands\CompleteUpload;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Domain\Rule\Specification;
use NetCode\Kit\Clock;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Application\Services\AccessibleFiles;
use NetCode\Media\Domain\Contracts\FileRepository;
use NetCode\Media\Domain\Enums\UploadRejection;
use NetCode\Media\Domain\Exceptions\UploadNotConfirmedException;
use NetCode\Media\Domain\Rules\FileMustBePending;
use NetCode\Media\Domain\ValueObjects\UploadLimits;

final readonly class CompleteUploadHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private FileRepository $files,
        private ObjectStorage $storage,
        private UploadLimits $limits,
        private AccessibleFiles $accessible,
    ) {}

    public function __invoke(
        CompleteUpload $command,
    ): UploadRejection|null {
        $file = $this->accessible->get($command->fileId, $command->requestedBy);
        Specification::check(new FileMustBePending($file));

        $this->storage->move($file->key(), $file->storedKey());
        $stored = $this->storage->inspect($file->storedKey())
            ?? throw UploadNotConfirmedException::forKey($file->key());

        $rejection = $file->complete(
            checksum: $command->checksum,
            storedSize: $stored->size,
            detectedMime: $stored->mime,
            limits: $this->limits,
            now: $this->clock->now(),
        );

        if ($rejection !== null) {
            $this->storage->delete($file->key());
        }

        $this->files->save($file);

        return $rejection;
    }
}
