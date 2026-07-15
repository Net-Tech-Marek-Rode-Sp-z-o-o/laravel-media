<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Commands\InitiateUpload;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;
use NetCode\Media\Application\Dto\UploadTicket;

/** @implements Command<UploadTicket> */
#[HandledBy(InitiateUploadHandler::class)]
final readonly class InitiateUpload implements Command
{
    public function __construct(
        public string $filename,
        public string $mime,
        public int $size,
        public string|null $uploadedBy,
    ) {}
}
