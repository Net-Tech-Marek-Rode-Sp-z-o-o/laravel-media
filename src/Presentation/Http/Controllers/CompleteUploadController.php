<?php

declare(strict_types=1);

namespace NetCode\Media\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Media\Application\Commands\CompleteUpload\CompleteUpload;
use NetCode\Media\Application\Ports\CurrentUser;
use NetCode\Media\Presentation\Http\Data\CompleteUploadData;
use Symfony\Component\HttpFoundation\Response;

final readonly class CompleteUploadController
{
    public function __construct(
        private CommandBus $bus,
        private CurrentUser $currentUser,
    ) {}

    public function __invoke(
        CompleteUploadData $data,
        string $fileId,
    ): JsonResponse {
        $this->bus->dispatch(new CompleteUpload(
            fileId: $fileId,
            requestedBy: $this->currentUser->id(),
            checksum: $data->checksum,
            size: $data->size,
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
