<?php

declare(strict_types=1);

namespace NetCode\Media\Tests\Unit\Domain;

use DateTimeImmutable;
use NetCode\Domain\Rule\BusinessRuleException;
use NetCode\Media\Domain\Enums\UploadRejection;
use NetCode\Media\Domain\Events\FileUploadCompleted;
use NetCode\Media\Domain\Events\FileUploadInitiated;
use NetCode\Media\Domain\Events\FileUploadRejected;
use NetCode\Media\Domain\File;
use NetCode\Media\Domain\Rules\FileRuleCode;
use NetCode\Media\Domain\ValueObjects\FileId;
use NetCode\Media\Domain\ValueObjects\UploadLimits;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FileTest extends TestCase
{
    private const string NOW = '2026-06-06T12:00:00+00:00';

    #[Test]
    public function it_initiates_a_pending_upload(): void
    {
        $file = $this->pendingFile();

        $this->assertTrue($file->isPending());
        $this->assertInstanceOf(FileUploadInitiated::class, $file->releaseEvents()[0]);
    }

    #[Test]
    public function it_completes_a_pending_upload(): void
    {
        $file = $this->pendingFile();
        $file->releaseEvents();

        $rejection = $this->completeWith($file, size: 2048, detectedMime: 'application/pdf');

        $this->assertNull($rejection);

        $this->assertTrue($file->isCompleted());
        $this->assertSame('sha256:abc', $file->checksum());
        $this->assertSame(2048, $file->size());
        $this->assertInstanceOf(FileUploadCompleted::class, $file->releaseEvents()[0]);
    }

    #[Test]
    public function it_rejects_completing_a_non_pending_upload(): void
    {
        $file = $this->pendingFile();
        $this->completeWith($file, size: 1024, detectedMime: 'application/pdf');

        try {
            $this->completeWith($file, size: 1024, detectedMime: 'application/pdf');
            $this->fail('Expected a BusinessRuleException.');
        } catch (BusinessRuleException $e) {
            $this->assertSame(FileRuleCode::NotPending, $e->ruleCode);
        }
    }

    #[Test]
    public function it_rejects_an_upload_larger_than_the_limit(): void
    {
        $file = $this->pendingFile();
        $file->releaseEvents();

        $rejection = $this->completeWith($file, size: 5_000_001, detectedMime: 'application/pdf');

        $this->assertSame(UploadRejection::TooLarge, $rejection);
        $this->assertTrue($file->isFailed());
        $this->assertSame(5_000_001, $file->size());
        $this->assertInstanceOf(FileUploadRejected::class, $file->releaseEvents()[0]);
    }

    #[Test]
    public function it_rejects_an_upload_whose_bytes_do_not_match_the_declared_type(): void
    {
        $file = $this->pendingFile();

        $rejection = $this->completeWith($file, size: 1024, detectedMime: 'application/x-msdownload');

        $this->assertSame(UploadRejection::TypeMismatch, $rejection);
        $this->assertTrue($file->isFailed());
    }

    private function completeWith(File $file, int $size, string $detectedMime): UploadRejection|null
    {
        return $file->complete(
            checksum: 'sha256:abc',
            storedSize: $size,
            detectedMime: $detectedMime,
            limits: UploadLimits::of(5_000_000, ['application/pdf' => ['application/pdf']]),
            now: new DateTimeImmutable(self::NOW),
        );
    }

    private function pendingFile(): File
    {
        return File::initiate(
            id: FileId::random(),
            disk: 's3',
            key: 'uploads/x/doc.pdf',
            originalName: 'doc.pdf',
            mime: 'application/pdf',
            size: 1024,
            uploadedBy: null,
            now: new DateTimeImmutable(self::NOW),
        );
    }
}
