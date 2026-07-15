<?php

declare(strict_types=1);

namespace NetCode\Media\Contract;

final readonly class FileSnapshot
{
    public function __construct(
        public string $id,
        public string $originalName,
        public string $mime,
        public int $size,
        public string $downloadUrl,
    ) {}
}
