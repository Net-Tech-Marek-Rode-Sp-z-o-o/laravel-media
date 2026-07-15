<?php

declare(strict_types=1);

namespace NetCode\Media\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use NetCode\Media\Application\Dto\FileView;

final class FileResource extends JsonResource
{
    public function __construct(
        private readonly FileView $view,
    ) {
        parent::__construct($view);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->view->id,
            'original_name' => $this->view->originalName,
            'mime' => $this->view->mime,
            'size' => $this->view->size,
            'status' => $this->view->status,
            'download_url' => $this->view->downloadUrl,
        ];
    }
}
