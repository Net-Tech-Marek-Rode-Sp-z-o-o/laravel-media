<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Commands\InitiateUpload;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Kit\Clock;
use NetCode\Media\Application\Dto\UploadTicket;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Domain\Contracts\FileRepository;
use NetCode\Media\Domain\File;
use NetCode\Media\Domain\Policies\UploadPolicy;
use NetCode\Media\Domain\ValueObjects\FileId;

final readonly class InitiateUploadHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private FileRepository $files,
        private ObjectStorage $storage,
    ) {}

    public function __invoke(
        InitiateUpload $command,
    ): UploadTicket {
        $id = $this->files->nextId();
        $key = $this->keyFor($id, $command->filename);

        $this->files->save(File::initiate(
            id: $id,
            disk: $this->storage->disk(),
            key: $key,
            originalName: $command->filename,
            mime: $command->mime,
            size: $command->size,
            uploadedBy: $command->uploadedBy,
            now: $this->clock->now(),
        ));

        return new UploadTicket(
            fileId: $id->value(),
            uploadUrl: $this->storage->temporaryUploadUrl($key, $command->mime, UploadPolicy::URL_TTL_MINUTES),
        );
    }

    private function keyFor(FileId $id, string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $stem = preg_replace('/[^a-z0-9]+/i', '-', pathinfo($filename, PATHINFO_FILENAME)) ?? '';
        $stem = trim($stem, '-');
        $name = ($stem === '' ? 'file' : strtolower($stem))
            .($extension === '' ? '' : '.'.strtolower($extension));

        return sprintf('uploads/%s/%s', $id->value(), $name);
    }
}
