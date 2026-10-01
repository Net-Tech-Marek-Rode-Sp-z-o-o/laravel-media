<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Dto;

final readonly class FileView
{
    public function __construct(
        public string $id,
        public string $originalName,
        public string $mime,
        public int $size,
        public string $status,
        public string|null $downloadUrl,
    ) {}
}
