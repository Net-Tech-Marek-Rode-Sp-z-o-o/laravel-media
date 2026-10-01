<?php

declare(strict_types=1);

namespace NetCode\Media\Infrastructure\Access;

use NetCode\Media\Application\Ports\FileAccess;

final readonly class UploaderOnlyFileAccess implements FileAccess
{
    public function allows(string $requestedBy, string $fileId, string|null $uploadedBy): bool
    {
        return $uploadedBy !== null && $uploadedBy === $requestedBy;
    }
}
