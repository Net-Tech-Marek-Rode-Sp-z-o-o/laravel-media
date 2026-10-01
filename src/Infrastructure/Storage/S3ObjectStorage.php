<?php

declare(strict_types=1);

namespace NetCode\Media\Infrastructure\Storage;

use finfo;
use Illuminate\Support\Facades\Storage;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Application\Ports\StoredObject;

final readonly class S3ObjectStorage implements ObjectStorage
{
    private const int SNIFF_BYTES = 4096;

    private const string UNKNOWN_MIME = 'application/octet-stream';

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

    public function inspect(string $key): StoredObject|null
    {
        $disk = Storage::disk($this->disk);

        if (! $disk->exists($key)) {
            return null;
        }

        return new StoredObject(
            size: $disk->size($key),
            mime: $this->detectMime($key, $disk->readStream($key)),
        );
    }

    public function move(string $from, string $to): void
    {
        $disk = Storage::disk($this->disk);

        if ($disk->exists($from) && ! $disk->move($from, $to)) {
            throw ObjectStorageException::notMoved($from);
        }
    }

    public function delete(string $key): void
    {
        if (! Storage::disk($this->disk)->delete($key)) {
            throw ObjectStorageException::notDeleted($key);
        }
    }

    /** @param resource|null $stream */
    private function detectMime(string $key, $stream): string
    {
        if (! is_resource($stream)) {
            throw ObjectStorageException::unreadable($key);
        }

        try {
            $head = stream_get_contents($stream, self::SNIFF_BYTES);
        } finally {
            fclose($stream);
        }

        if ($head === false) {
            throw ObjectStorageException::unreadable($key);
        }

        return new finfo(FILEINFO_MIME_TYPE)->buffer($head) ?: self::UNKNOWN_MIME;
    }
}
