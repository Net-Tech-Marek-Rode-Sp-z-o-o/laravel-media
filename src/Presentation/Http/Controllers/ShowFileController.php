<?php

declare(strict_types=1);

namespace NetCode\Media\Presentation\Http\Controllers;

use NetCode\Bus\Query\QueryBus;
use NetCode\Media\Application\Queries\GetFile\GetFile;
use NetCode\Media\Presentation\Http\Resources\FileResource;

final readonly class ShowFileController
{
    public function __construct(
        private QueryBus $queries,
    ) {}

    public function __invoke(
        string $fileId,
    ): FileResource {
        return new FileResource($this->queries->ask(new GetFile(
            fileId: $fileId,
        )));
    }
}
