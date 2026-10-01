<?php

declare(strict_types=1);

namespace NetCode\Media\Domain;

use DateTimeImmutable;
use NetCode\Domain\AggregateRoot;
use NetCode\Domain\Rule\BusinessRuleException;
use NetCode\Domain\Rule\Specification;
use NetCode\Media\Domain\Enums\FileStatus;
use NetCode\Media\Domain\Enums\UploadRejection;
use NetCode\Media\Domain\Events\FileUploadCompleted;
use NetCode\Media\Domain\Events\FileUploadInitiated;
use NetCode\Media\Domain\Events\FileUploadRejected;
use NetCode\Media\Domain\Rules\FileMustBePending;
use NetCode\Media\Domain\ValueObjects\FileId;
use NetCode\Media\Domain\ValueObjects\UploadLimits;

final class File extends AggregateRoot
{
    private function __construct(
        private readonly FileId $id,
        private readonly string $disk,
        private string $key,
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
    public function complete(
        string|null $checksum,
        int $storedSize,
        string $detectedMime,
        UploadLimits $limits,
        DateTimeImmutable $now,
    ): UploadRejection|null {
        Specification::check(new FileMustBePending($this));

        $this->key = $this->storedKey();
        $this->size = $storedSize;
        $rejection = $limits->rejectionFor($storedSize, $this->mime, $detectedMime);

        if ($rejection !== null) {
            $this->reject($rejection, $now);

            return $rejection;
        }

        if ($checksum !== null) {
            $this->checksum = $checksum;
        }

        $this->status = FileStatus::Completed;
        $this->completedAt = $now;

        $this->recordThat(new FileUploadCompleted(
            fileId: $this->id,
            occurredOn: $now,
        ));

        return null;
    }

    public function isPending(): bool
    {
        return $this->status === FileStatus::Pending;
    }

    public function isCompleted(): bool
    {
        return $this->status === FileStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === FileStatus::Failed;
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

    public function storedKey(): string
    {
        return sprintf('files/%s/%s', $this->id->value(), basename($this->key));
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

    private function reject(UploadRejection $reason, DateTimeImmutable $now): void
    {
        $this->status = FileStatus::Failed;

        $this->recordThat(new FileUploadRejected(
            fileId: $this->id,
            reason: $reason,
            occurredOn: $now,
        ));
    }
}
