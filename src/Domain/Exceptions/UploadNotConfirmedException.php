<?php

declare(strict_types=1);

namespace NetCode\Media\Domain\Exceptions;

use NetCode\Domain\Exception\DomainException;

final class UploadNotConfirmedException extends DomainException
{
    public static function forKey(string $key): self
    {
        return new self(sprintf('No uploaded object found at <%s>; cannot complete.', $key));
    }
}
