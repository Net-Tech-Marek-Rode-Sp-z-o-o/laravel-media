<?php

declare(strict_types=1);

namespace NetCode\Media\Infrastructure\Anticorruption;

use NetCode\Domain\Exception\InvalidArgumentException;
use NetCode\Media\Api\Contracts\FileDirectory;
use NetCode\Media\Api\FileSnapshot;
use NetCode\Media\Application\Ports\FileAccess;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Domain\Contracts\FileRepository;
use NetCode\Media\Domain\Exceptions\FileNotFoundException;
use NetCode\Media\Domain\File;
use NetCode\Media\Domain\Policies\UploadPolicy;
use NetCode\Media\Domain\ValueObjects\FileId;

final readonly class FileDirectoryAdapter implements FileDirectory
{
    public function __construct(
        private FileAccess $access,
        private FileRepository $files,
        private ObjectStorage $storage,
    ) {}

    public function snapshots(array $ids, string $requestedBy): array
    {
        $accessible = array_filter(
            $this->files->findByIds($ids),
            fn (File $file): bool => $this->isAccessible($file, $requestedBy),
        );

        return array_values(array_map($this->toSnapshot(...), $accessible));
    }

    public function areCompleted(array $ids, string $requestedBy): bool
    {
        foreach ($ids as $id) {
            $file = $this->find($id);

            if ($file === null || ! $file->isCompleted() || ! $this->isAccessible($file, $requestedBy)) {
                return false;
            }
        }

        return true;
    }

    private function isAccessible(File $file, string $requestedBy): bool
    {
        return $this->access->allows($requestedBy, $file->id()->value(), $file->uploadedBy());
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
