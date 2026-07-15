<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Dto;

final readonly class UploadTicket
{
    public function __construct(
        public string $fileId,
        public string $uploadUrl,
    ) {}
}
