<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Commands\DeleteFile;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<null> */
#[HandledBy(DeleteFileHandler::class)]
final readonly class DeleteFile implements Command
{
    public function __construct(
        public string $fileId,
        public string $requestedBy,
    ) {}
}
