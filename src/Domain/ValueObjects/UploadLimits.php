<?php

declare(strict_types=1);

namespace NetCode\Media\Domain\ValueObjects;

use NetCode\Domain\Exception\InvalidArgumentException;
use NetCode\Media\Domain\Enums\UploadRejection;

final readonly class UploadLimits
{
    /**
     * @param array<string, list<string>> $allowedTypes
     * @param array<string, int> $typeMaxBytes
     */
    private function __construct(
        public int $maxBytes,
        public array $allowedTypes,
        private array $typeMaxBytes,
    ) {}

    /**
     * @param array<mixed> $allowedTypes declared MIME type => list of MIME types detected from the bytes,
     *                                   or ['detected' => list<string>, 'max_bytes' => int]
     */
    public static function of(int $maxBytes, array $allowedTypes = []): self
    {
        self::assertPositive($maxBytes);

        $detected = [];
        $typeMaxBytes = [];

        foreach ($allowedTypes as $declared => $rule) {
            if (! is_string($declared) || ! is_array($rule)) {
                throw self::invalidShape();
            }

            [$detected[$declared], $typeMax] = self::parseRule($rule);

            if ($typeMax !== null) {
                $typeMaxBytes[$declared] = $typeMax;
            }
        }

        return new self(
            maxBytes: $maxBytes,
            allowedTypes: $detected,
            typeMaxBytes: $typeMaxBytes,
        );
    }

    public function acceptsDeclaredType(string $mime): bool
    {
        return $this->allowedTypes === [] || array_key_exists($mime, $this->allowedTypes);
    }

    public function maxBytesFor(string $declaredMime): int
    {
        return $this->typeMaxBytes[$declaredMime] ?? $this->maxBytes;
    }

    public function rejectionFor(int $size, string $declaredMime, string $detectedMime): UploadRejection|null
    {
        if ($size > $this->maxBytesFor($declaredMime)) {
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

    /**
     * @param array<mixed> $rule
     * @return array{list<string>, int|null}
     */
    private static function parseRule(array $rule): array
    {
        if (array_is_list($rule)) {
            return [self::mimeList($rule), null];
        }

        $typeMax = $rule['max_bytes'] ?? null;
        $unknownKeys = array_diff(array_keys($rule), ['detected', 'max_bytes']);

        if ($unknownKeys !== [] || ! is_array($rule['detected'] ?? null) || ($typeMax !== null && ! is_int($typeMax))) {
            throw self::invalidShape();
        }

        if ($typeMax !== null) {
            self::assertPositive($typeMax);
        }

        return [self::mimeList($rule['detected']), $typeMax];
    }

    /**
     * @param array<mixed> $mimes
     * @return list<string>
     */
    private static function mimeList(array $mimes): array
    {
        if (! array_is_list($mimes) || array_filter($mimes, static fn (mixed $mime): bool => ! is_string($mime)) !== []) {
            throw self::invalidShape();
        }

        return $mimes;
    }

    private static function assertPositive(int $bytes): void
    {
        if ($bytes < 1) {
            throw new InvalidArgumentException('An upload limit must be at least one byte.');
        }
    }

    private static function invalidShape(): InvalidArgumentException
    {
        return new InvalidArgumentException(
            'Allowed types map a declared MIME type to a list of detected MIME types, or to [\'detected\' => [...], \'max_bytes\' => int].',
        );
    }
}
