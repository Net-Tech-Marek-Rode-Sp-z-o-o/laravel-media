<?php

declare(strict_types=1);

namespace NetCode\Media\Domain\Events;

use DateTimeImmutable;
use NetCode\Domain\DomainEvent;
use NetCode\Media\Domain\Enums\UploadRejection;
use NetCode\Media\Domain\ValueObjects\FileId;

final readonly class FileUploadRejected implements DomainEvent
{
    public function __construct(
        public FileId $fileId,
        public UploadRejection $reason,
        private DateTimeImmutable $occurredOn,
    ) {}

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
