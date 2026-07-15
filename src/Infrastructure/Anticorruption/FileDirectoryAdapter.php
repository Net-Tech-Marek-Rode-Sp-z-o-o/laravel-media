<?php

declare(strict_types=1);

namespace NetCode\Media\Infrastructure\Anticorruption;

use NetCode\Domain\Exception\InvalidArgumentException;
use NetCode\Media\Api\Contracts\FileDirectory;
use NetCode\Media\Api\FileSnapshot;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Domain\Contracts\FileRepository;
use NetCode\Media\Domain\Exceptions\FileNotFoundException;
use NetCode\Media\Domain\File;
use NetCode\Media\Domain\Policies\UploadPolicy;
use NetCode\Media\Domain\ValueObjects\FileId;

final readonly class FileDirectoryAdapter implements FileDirectory
{
    public function __construct(
        private FileRepository $files,
        private ObjectStorage $storage,
    ) {}

    public function snapshots(array $ids): array
    {
        return array_map(
            $this->toSnapshot(...),
            $this->files->findByIds($ids),
        );
    }

    public function areCompleted(array $ids): bool
    {
        foreach ($ids as $id) {
            $file = $this->find($id);

            if ($file === null || ! $file->isCompleted()) {
                return false;
            }
        }

        return true;
    }

    private function toSnapshot(File $file): FileSnapshot
    {
        return new FileSnapshot(
            id: $file->id()->value(),
            originalName: $file->originalName(),
            mime: $file->mime(),
            size: $file->size(),
            downloadUrl: $this->storage->temporaryDownloadUrl($file->key(), UploadPolicy::URL_TTL_MINUTES),
        );
    }

    private function find(string $id): File|null
    {
        try {
            return $this->files->getById(FileId::fromString($id));
        } catch (FileNotFoundException|InvalidArgumentException) {
            return null;
        }
    }
}
