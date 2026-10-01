<?php

declare(strict_types=1);

namespace NetCode\Media\Infrastructure\DataAccess\Repositories;

use DateTimeImmutable;
use NetCode\Domain\DomainEventPublisher;
use NetCode\Media\Domain\Contracts\FileRepository;
use NetCode\Media\Domain\Enums\FileStatus;
use NetCode\Media\Domain\Exceptions\FileNotFoundException;
use NetCode\Media\Domain\File;
use NetCode\Media\Domain\ValueObjects\FileId;
use NetCode\Media\Infrastructure\DataAccess\Mappers\FileMapper;
use NetCode\Media\Infrastructure\DataAccess\Models\FileModel;

final readonly class EloquentFileRepository implements FileRepository
{
    public function __construct(
        private FileMapper $mapper,
        private DomainEventPublisher $events,
    ) {}

    public function nextId(): FileId
    {
        return FileId::random();
    }

    public function getById(FileId $id): File
    {
        $model = FileModel::query()->find($id->value());

        return $model === null ? throw FileNotFoundException::withId($id) : $this->mapper->toDomain($model);
    }

    /**
     * @param list<string> $ids
     * @return list<File>
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return array_values(FileModel::query()
            ->whereIn('id', $ids)
            ->get()
            ->map($this->mapper->toDomain(...))
            ->all());
    }

    public function save(File $file): void
    {
        $model = FileModel::query()->findOrNew($file->id()->value());
        $this->mapper->hydrate($file, $model);
        $model->save();

        $this->events->publish(...$file->releaseEvents());
    }

    public function delete(File $file): void
    {
        FileModel::query()->whereKey($file->id()->value())->delete();

        $this->events->publish(...$file->releaseEvents());
    }

    /** @return array<int, File> */
    public function unfinishedOlderThan(DateTimeImmutable $threshold): array
    {
        return FileModel::query()
            ->whereIn('status', [FileStatus::Pending->value, FileStatus::Failed->value])
            ->where('created_at', '<', $threshold)
            ->get()
            ->map($this->mapper->toDomain(...))
            ->all();
    }
}
