<?php

declare(strict_types=1);

namespace NetCode\Media\Api\Contracts;

use NetCode\Media\Api\FileSnapshot;

interface FileDirectory
{
    /**
     * @param list<string> $ids
     * @return list<FileSnapshot>
     */
    public function snapshots(array $ids, string $requestedBy): array;

    /** @param list<string> $ids */
    public function areCompleted(array $ids, string $requestedBy): bool;
}
