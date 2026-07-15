<?php

declare(strict_types=1);

namespace NetCode\Media\Domain\Events;

use DateTimeImmutable;
use NetCode\Domain\DomainEvent;
use NetCode\Media\Domain\ValueObjects\FileId;

final readonly class FileUploadInitiated implements DomainEvent
{
    public function __construct(
        public FileId $fileId,
        private DateTimeImmutable $occurredOn,
    ) {}

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
