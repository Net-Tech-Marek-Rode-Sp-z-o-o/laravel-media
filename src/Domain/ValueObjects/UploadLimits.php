<?php

declare(strict_types=1);

namespace NetCode\Media\Domain\ValueObjects;

use NetCode\Domain\Exception\InvalidArgumentException;
use NetCode\Media\Domain\Enums\UploadRejection;

final readonly class UploadLimits
{
    /** @param array<string, list<string>> $allowedTypes */
    private function __construct(
        public int $maxBytes,
        public array $allowedTypes,
    ) {}

    /** @param array<string, list<string>> $allowedTypes declared MIME type => MIME types detected from the bytes */
    public static function of(int $maxBytes, array $allowedTypes = []): self
    {
        if ($maxBytes < 1) {
            throw new InvalidArgumentException('The upload limit must be at least one byte.');
        }

        foreach ($allowedTypes as $declared => $detected) {
            if (! is_string($declared) || ! is_array($detected) || ! array_is_list($detected)) {
                throw new InvalidArgumentException('Allowed types map a declared MIME type to a list of detected MIME types.');
            }
        }

        return new self(
            maxBytes: $maxBytes,
            allowedTypes: $allowedTypes,
        );
    }

    public function acceptsDeclaredType(string $mime): bool
    {
        return $this->allowedTypes === [] || array_key_exists($mime, $this->allowedTypes);
    }

    public function rejectionFor(int $size, string $declaredMime, string $detectedMime): UploadRejection|null
    {
        if ($size > $this->maxBytes) {
            return UploadRejection::TooLarge;
        }

        if (! $this->acceptsDetectedType($declaredMime, $detectedMime)) {
            return UploadRejection::TypeMismatch;
        }

        return null;
    }

    private function acceptsDetectedType(string $declaredMime, string $detectedMime): bool
    {
        if ($this->allowedTypes === []) {
            return true;
        }

        return in_array($detectedMime, $this->allowedTypes[$declaredMime] ?? [], true);
    }
}
