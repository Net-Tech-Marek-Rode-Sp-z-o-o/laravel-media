<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Ports;

interface ObjectStorage
{
    public function disk(): string;

    public function temporaryUploadUrl(string $key, string $mime, int $minutes): string;

    public function temporaryDownloadUrl(string $key, int $minutes): string;

    public function exists(string $key): bool;

    public function delete(string $key): void;
}
