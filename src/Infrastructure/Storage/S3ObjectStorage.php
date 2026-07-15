<?php

declare(strict_types=1);

namespace NetCode\Media\Infrastructure\Storage;

use Illuminate\Support\Facades\Storage;
use NetCode\Media\Application\Ports\ObjectStorage;

final readonly class S3ObjectStorage implements ObjectStorage
{
    public function __construct(
        private string $disk,
        private string $presignDisk,
    ) {}

    public function disk(): string
    {
        return $this->disk;
    }

    public function temporaryUploadUrl(string $key, string $mime, int $minutes): string
    {
        return Storage::disk($this->presignDisk)->temporaryUploadUrl(
            $key,
            now()->addMinutes($minutes),
            ['ContentType' => $mime],
        )['url'];
    }

    public function temporaryDownloadUrl(string $key, int $minutes): string
    {
        return Storage::disk($this->presignDisk)->temporaryUrl($key, now()->addMinutes($minutes));
    }

    public function exists(string $key): bool
    {
        return Storage::disk($this->disk)->exists($key);
    }

    public function delete(string $key): void
    {
        Storage::disk($this->disk)->delete($key);
    }
}
