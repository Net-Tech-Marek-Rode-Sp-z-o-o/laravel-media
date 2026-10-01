<?php

declare(strict_types=1);

namespace NetCode\Media\Tests\Unit\Domain;

use NetCode\Domain\Exception\InvalidArgumentException;
use NetCode\Media\Domain\Enums\UploadRejection;
use NetCode\Media\Domain\ValueObjects\UploadLimits;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UploadLimitsTest extends TestCase
{
    #[Test]
    public function it_accepts_any_type_when_no_types_are_listed(): void
    {
        $limits = UploadLimits::of(1000);

        $this->assertTrue($limits->acceptsDeclaredType('application/zip'));
        $this->assertNull($limits->rejectionFor(1000, 'application/pdf', 'application/zip'));
    }

    #[Test]
    public function it_accepts_a_detected_type_listed_for_the_declared_type(): void
    {
        $limits = UploadLimits::of(1000, ['text/csv' => ['text/csv', 'text/plain']]);

        $this->assertNull($limits->rejectionFor(10, 'text/csv', 'text/plain'));
    }

    #[Test]
    public function it_rejects_a_declared_type_that_is_not_listed(): void
    {
        $limits = UploadLimits::of(1000, ['application/pdf' => ['application/pdf']]);

        $this->assertFalse($limits->acceptsDeclaredType('image/svg+xml'));
        $this->assertSame(UploadRejection::TypeMismatch, $limits->rejectionFor(10, 'image/svg+xml', 'image/svg+xml'));
    }

    #[Test]
    public function it_rejects_a_size_above_the_limit(): void
    {
        $this->assertSame(UploadRejection::TooLarge, UploadLimits::of(1000)->rejectionFor(1001, 'application/pdf', 'application/pdf'));
    }

    #[Test]
    public function it_applies_the_size_limit_of_the_declared_type(): void
    {
        $limits = UploadLimits::of(5000, [
            'application/pdf' => ['application/pdf'],
            'text/csv' => ['detected' => ['text/csv', 'text/plain'], 'max_bytes' => 2000],
        ]);

        $this->assertSame(2000, $limits->maxBytesFor('text/csv'));
        $this->assertSame(5000, $limits->maxBytesFor('application/pdf'));
        $this->assertSame(UploadRejection::TooLarge, $limits->rejectionFor(2001, 'text/csv', 'text/plain'));
        $this->assertNull($limits->rejectionFor(4000, 'application/pdf', 'application/pdf'));
        $this->assertSame(['text/csv', 'text/plain'], $limits->allowedTypes['text/csv']);
    }

    #[Test]
    public function it_uses_the_global_limit_for_a_type_rule_without_max_bytes(): void
    {
        $limits = UploadLimits::of(5000, ['text/csv' => ['detected' => ['text/csv']]]);

        $this->assertSame(5000, $limits->maxBytesFor('text/csv'));
    }

    #[Test]
    public function it_refuses_an_unknown_key_in_a_type_rule(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UploadLimits::of(5000, ['text/csv' => ['detected' => ['text/csv'], 'max_size' => 2000]]);
    }

    #[Test]
    public function it_refuses_a_type_limit_that_is_not_an_integer(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UploadLimits::of(5000, ['text/csv' => ['detected' => ['text/csv'], 'max_bytes' => '2000']]);
    }

    #[Test]
    public function it_refuses_a_type_rule_without_a_detected_list(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UploadLimits::of(1000, ['text/csv' => ['max_bytes' => 2000]]);
    }

    #[Test]
    public function it_refuses_a_type_limit_below_one_byte(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UploadLimits::of(1000, ['text/csv' => ['detected' => ['text/csv'], 'max_bytes' => 0]]);
    }

    #[Test]
    public function it_refuses_a_detected_type_that_is_not_a_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UploadLimits::of(1000, ['text/csv' => ['text/csv', 42]]);
    }

    #[Test]
    public function it_refuses_allowed_types_given_as_a_plain_list(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UploadLimits::of(1000, ['application/pdf']);
    }

    #[Test]
    public function it_refuses_a_limit_below_one_byte(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UploadLimits::of(0);
    }
}
