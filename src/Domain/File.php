<?php

declare(strict_types=1);

namespace NetCode\Media\Domain;

use DateTimeImmutable;
use NetCode\Domain\AggregateRoot;
use NetCode\Domain\Rule\BusinessRuleException;
use NetCode\Domain\Rule\Specification;
use NetCode\Media\Domain\Enums\FileStatus;
use NetCode\Media\Domain\Events\FileUploadCompleted;
use NetCode\Media\Domain\Events\FileUploadInitiated;
use NetCode\Media\Domain\Rules\FileMustBePending;
use NetCode\Media\Domain\ValueObjects\FileId;

final class File extends AggregateRoot
{
    private function __construct(
        private readonly FileId $id,
        private readonly string $disk,
        private readonly string $key,
        private readonly string $originalName,
        private readonly string $mime,
        private int $size,
        private string|null $checksum,
        private FileStatus $status,
        private readonly string|null $uploadedBy,
        private DateTimeImmutable|null $completedAt,
    ) {}

    public static function initiate(
        FileId $id,
        string $disk,
        string $key,
        string $originalName,
        string $mime,
        int $size,
        string|null $uploadedBy,
        DateTimeImmutable $now,
    ): self {
        $file = new self(
            id: $id,
            disk: $disk,
            key: $key,
            originalName: $originalName,
            mime: $mime,
            size: $size,
            checksum: null,
            status: FileStatus::Pending,
            uploadedBy: $uploadedBy,
            completedAt: null,
        );

        $file->recordThat(new FileUploadInitiated(
            fileId: $id,
            occurredOn: $now,
        ));

        return $file;
    }

    public static function reconstitute(
        FileId $id,
        string $disk,
        string $key,
        string $originalName,
        string $mime,
        int $size,
        string|null $checksum,
        FileStatus $status,
        string|null $uploadedBy,
        DateTimeImmutable|null $completedAt,
    ): self {
        return new self(
            id: $id,
            disk: $disk,
            key: $key,
            originalName: $originalName,
            mime: $mime,
            size: $size,
            checksum: $checksum,
            status: $status,
            uploadedBy: $uploadedBy,
            completedAt: $completedAt,
        );
    }

    /** @throws BusinessRuleException */
    public function complete(string|null $checksum, int|null $size, DateTimeImmutable $now): void
    {
        Specification::check(new FileMustBePending($this));

        if ($checksum !== null) {
            $this->checksum = $checksum;
        }

        if ($size !== null) {
            $this->size = $size;
        }

        $this->status = FileStatus::Completed;
        $this->completedAt = $now;

        $this->recordThat(new FileUploadCompleted(
            fileId: $this->id,
            occurredOn: $now,
        ));
    }

    public function isPending(): bool
    {
        return $this->status === FileStatus::Pending;
    }

    public function isCompleted(): bool
    {
        return $this->status === FileStatus::Completed;
    }

    public function id(): FileId
    {
        return $this->id;
    }

    public function disk(): string
    {
        return $this->disk;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function mime(): string
    {
        return $this->mime;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function checksum(): string|null
    {
        return $this->checksum;
    }

    public function status(): FileStatus
    {
        return $this->status;
    }

    public function uploadedBy(): string|null
    {
        return $this->uploadedBy;
    }

    public function completedAt(): DateTimeImmutable|null
    {
        return $this->completedAt;
    }
}
