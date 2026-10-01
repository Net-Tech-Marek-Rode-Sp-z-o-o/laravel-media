<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Queries\GetFile;

use NetCode\Bus\Query\HandledBy;
use NetCode\Bus\Query\Query;
use NetCode\Media\Application\Dto\FileView;

/** @implements Query<FileView> */
#[HandledBy(GetFileHandler::class)]
final readonly class GetFile implements Query
{
    public function __construct(
        public string $fileId,
        public string $requestedBy,
    ) {}
}
