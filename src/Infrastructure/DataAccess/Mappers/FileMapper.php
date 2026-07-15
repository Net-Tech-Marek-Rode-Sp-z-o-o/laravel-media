<?php

declare(strict_types=1);

namespace NetCode\Media\Infrastructure\DataAccess\Mappers;

use NetCode\Media\Domain\File;
use NetCode\Media\Infrastructure\DataAccess\Models\FileModel;

final class FileMapper
{
    public function toDomain(FileModel $model): File
    {
        return File::reconstitute(
            id: $model->id,
            disk: $model->disk,
            key: $model->key,
            originalName: $model->original_name,
            mime: $model->mime,
            size: $model->size,
            checksum: $model->checksum,
            status: $model->status,
            uploadedBy: $model->uploaded_by,
            completedAt: $model->completed_at,
        );
    }

    public function hydrate(File $file, FileModel $model): void
    {
        $model->id = $file->id();
        $model->disk = $file->disk();
        $model->key = $file->key();
        $model->original_name = $file->originalName();
        $model->mime = $file->mime();
        $model->size = $file->size();
        $model->checksum = $file->checksum();
        $model->status = $file->status();
        $model->uploaded_by = $file->uploadedBy();
        $model->completed_at = $file->completedAt();
    }
}
