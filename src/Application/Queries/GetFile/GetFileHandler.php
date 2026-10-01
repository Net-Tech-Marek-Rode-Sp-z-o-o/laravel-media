<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Queries\GetFile;

use NetCode\Bus\Query\QueryHandler;
use NetCode\Media\Application\Dto\FileView;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Application\Services\AccessibleFiles;
use NetCode\Media\Domain\Policies\UploadPolicy;

final readonly class GetFileHandler implements QueryHandler
{
    public function __construct(
        private ObjectStorage $storage,
        private AccessibleFiles $accessible,
    ) {}

    public function __invoke(
        GetFile $query,
    ): FileView {
        $file = $this->accessible->get($query->fileId, $query->requestedBy);

        return new FileView(
            id: $file->id()->value(),
            originalName: $file->originalName(),
            mime: $file->mime(),
            size: $file->size(),
            status: $file->status()->value,
            downloadUrl: $this->storage->temporaryDownloadUrl($file->key(), UploadPolicy::URL_TTL_MINUTES),
        );
    }
}
