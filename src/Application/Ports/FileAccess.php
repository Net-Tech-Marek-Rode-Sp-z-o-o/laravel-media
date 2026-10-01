<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Ports;

interface FileAccess
{
    public function allows(string $requestedBy, string $fileId, string|null $uploadedBy): bool;
}
