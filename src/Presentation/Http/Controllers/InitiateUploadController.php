<?php

declare(strict_types=1);

namespace NetCode\Media\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Media\Application\Commands\InitiateUpload\InitiateUpload;
use NetCode\Media\Application\Ports\CurrentUser;
use NetCode\Media\Presentation\Http\Data\InitiateUploadData;
use NetCode\Media\Presentation\Http\Resources\UploadTicketResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class InitiateUploadController
{
    public function __construct(
        private CommandBus $bus,
        private CurrentUser $currentUser,
    ) {}

    public function __invoke(
        InitiateUploadData $data,
    ): JsonResponse {
        $ticket = $this->bus->dispatch(new InitiateUpload(
            filename: $data->filename,
            mime: $data->mime,
            size: $data->size,
            uploadedBy: $this->currentUser->id(),
        ));

        return new UploadTicketResource($ticket)->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
