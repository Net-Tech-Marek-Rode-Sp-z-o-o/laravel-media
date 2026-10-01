<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Services;

use NetCode\Media\Application\Ports\FileAccess;
use NetCode\Media\Domain\Contracts\FileRepository;
use NetCode\Media\Domain\Exceptions\FileNotFoundException;
use NetCode\Media\Domain\File;
use NetCode\Media\Domain\ValueObjects\FileId;

final readonly class AccessibleFiles
{
    public function __construct(
        private FileAccess $access,
        private FileRepository $files,
    ) {}

    /** @throws FileNotFoundException */
    public function get(string $fileId, string $requestedBy): File
    {
        $id = FileId::fromString($fileId);
        $file = $this->files->getById($id);

        if (! $this->access->allows($requestedBy, $id->value(), $file->uploadedBy())) {
            throw FileNotFoundException::withId($id);
        }

        return $file;
    }
}
