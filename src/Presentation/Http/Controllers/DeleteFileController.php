<?php

declare(strict_types=1);

namespace NetCode\Media\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Media\Application\Commands\DeleteFile\DeleteFile;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteFileController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        string $fileId,
    ): JsonResponse {
        $this->bus->dispatch(new DeleteFile(
            fileId: $fileId,
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
