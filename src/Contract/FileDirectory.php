<?php

declare(strict_types=1);

namespace NetCode\Media\Contract;

interface FileDirectory
{
    /**
     * @param list<string> $ids
     * @return list<FileSnapshot>
     */
    public function snapshots(array $ids): array;

    /** @param list<string> $ids */
    public function areCompleted(array $ids): bool;
}
