<?php

declare(strict_types=1);

namespace NetCode\Media\Tests\Support;

use NetCode\Media\Application\Ports\ObjectStorage;

final class FakeObjectStorage implements ObjectStorage
{
    public bool $objectExists = true;

    /** @var list<string> */
    public array $deleted = [];

    public function disk(): string
    {
        return 's3';
    }

    public function temporaryUploadUrl(string $key, string $mime, int $minutes): string
    {
        return 'https://s3.test/upload/'.$key;
    }

    public function temporaryDownloadUrl(string $key, int $minutes): string
    {
        return 'https://s3.test/'.$key;
    }

    public function exists(string $key): bool
    {
        return $this->objectExists;
    }

    public function delete(string $key): void
    {
        $this->deleted[] = $key;
    }
}
