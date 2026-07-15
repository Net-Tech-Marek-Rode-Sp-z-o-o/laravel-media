<?php

declare(strict_types=1);

namespace NetCode\Media\Domain\Exceptions;

use NetCode\Domain\Exception\DomainException;
use NetCode\Media\Domain\ValueObjects\FileId;

final class FileNotFoundException extends DomainException
{
    public static function withId(FileId $id): self
    {
        return new self(sprintf('No file exists with id <%s>.', $id));
    }
}
