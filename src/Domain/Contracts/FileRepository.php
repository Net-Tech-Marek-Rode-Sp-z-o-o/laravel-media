<?php

declare(strict_types=1);

namespace NetCode\Media\Domain\Contracts;

use DateTimeImmutable;
use NetCode\Media\Domain\File;
use NetCode\Media\Domain\ValueObjects\FileId;

interface FileRepository
{
    public function nextId(): FileId;

    public function getById(FileId $id): File;

    /**
     * @param list<string> $ids
     * @return list<File>
     */
    public function findByIds(array $ids): array;

    public function save(File $file): void;

    public function delete(File $file): void;

    /** @return array<int, File> */
    public function pendingOlderThan(DateTimeImmutable $threshold): array;
}
