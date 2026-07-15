<?php

declare(strict_types=1);

namespace NetCode\Media\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use NetCode\Media\Application\Dto\UploadTicket;

final class UploadTicketResource extends JsonResource
{
    public function __construct(
        private readonly UploadTicket $ticket,
    ) {
        parent::__construct($ticket);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'file_id' => $this->ticket->fileId,
            'upload_url' => $this->ticket->uploadUrl,
        ];
    }
}
