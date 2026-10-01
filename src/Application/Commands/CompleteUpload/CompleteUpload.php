<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Commands\CompleteUpload;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;
use NetCode\Media\Domain\Enums\UploadRejection;

/** @implements Command<UploadRejection|null> */
#[HandledBy(CompleteUploadHandler::class)]
final readonly class CompleteUpload implements Command
{
    public function __construct(
        public string $fileId,
        public string $requestedBy,
        public string|null $checksum = null,
    ) {}
}
