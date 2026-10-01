<?php

declare(strict_types=1);

namespace NetCode\Media\Tests\Support;

use NetCode\Media\Application\Ports\FileAccess;

final readonly class AllowEveryoneFileAccess implements FileAccess
{
    public function allows(string $requestedBy, string $fileId, string|null $uploadedBy): bool
    {
        return true;
    }
}
