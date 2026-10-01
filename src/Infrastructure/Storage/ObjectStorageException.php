<?php

declare(strict_types=1);

namespace NetCode\Media\Infrastructure\Storage;

use RuntimeException;

final class ObjectStorageException extends RuntimeException
{
    public static function unreadable(string $key): self
    {
        return new self(sprintf('The stored object <%s> could not be read; try again.', $key));
    }

    public static function notMoved(string $key): self
    {
        return new self(sprintf('The stored object <%s> could not be moved; try again.', $key));
    }

    public static function notDeleted(string $key): self
    {
        return new self(sprintf('The stored object <%s> could not be deleted.', $key));
    }
}
