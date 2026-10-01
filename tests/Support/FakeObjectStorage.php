<?php

declare(strict_types=1);

namespace NetCode\Media\Tests\Support;

use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Application\Ports\StoredObject;

final class FakeObjectStorage implements ObjectStorage
{
    /** @var array<string, StoredObject> */
    public array $objects = [];

    /** @var list<string> */
    public array $deleted = [];

    public bool $clientUploads = true;

    /** @var list<string> */
    private array $uploadKeys = [];

    public StoredObject $nextUpload;

    public function __construct()
    {
        $this->nextUpload = new StoredObject(size: 2048, mime: 'application/pdf');
    }

    public function disk(): string
    {
        return 's3';
    }

    public function temporaryUploadUrl(string $key, string $mime, int $minutes): string
    {
        $this->uploadKeys[] = $key;

        if ($this->clientUploads) {
            $this->objects[$key] = $this->nextUpload;
        }

        return 'https://s3.test/upload/'.$key;
    }

    public function temporaryDownloadUrl(string $key, int $minutes): string
    {
        return 'https://s3.test/'.$key;
    }

    public function inspect(string $key): StoredObject|null
    {
        return $this->objects[$key] ?? null;
    }

    public function move(string $from, string $to): void
    {
        if (! isset($this->objects[$from])) {
            return;
        }

        $this->objects[$to] = $this->objects[$from];
        unset($this->objects[$from]);
    }

    public function delete(string $key): void
    {
        $this->deleted[] = $key;
        unset($this->objects[$key]);
    }

    public function putWithUploadUrl(string $fileId, StoredObject $object): void
    {
        foreach ($this->uploadKeys as $key) {
            if (str_starts_with($key, 'uploads/'.$fileId.'/')) {
                $this->objects[$key] = $object;
            }
        }
    }

    public function storedObjectOf(string $fileId): StoredObject|null
    {
        foreach ($this->objects as $key => $object) {
            if (str_starts_with($key, 'files/'.$fileId.'/')) {
                return $object;
            }
        }

        return null;
    }
}
