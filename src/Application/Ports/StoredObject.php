<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Ports;

final readonly class StoredObject
{
    public function __construct(
        public int $size,
        public string $mime,
    ) {}
}
