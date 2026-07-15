<?php

declare(strict_types=1);

namespace NetCode\Media\Tests\Unit\Domain;

use DateTimeImmutable;
use NetCode\Domain\Rule\BusinessRuleException;
use NetCode\Media\Domain\Events\FileUploadCompleted;
use NetCode\Media\Domain\Events\FileUploadInitiated;
use NetCode\Media\Domain\File;
use NetCode\Media\Domain\Rules\FileRuleCode;
use NetCode\Media\Domain\ValueObjects\FileId;
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

        $file->complete('sha256:abc', 2048, new DateTimeImmutable(self::NOW));

        $this->assertTrue($file->isCompleted());
        $this->assertSame('sha256:abc', $file->checksum());
        $this->assertSame(2048, $file->size());
        $this->assertInstanceOf(FileUploadCompleted::class, $file->releaseEvents()[0]);
    }

    #[Test]
    public function it_rejects_completing_a_non_pending_upload(): void
    {
        $file = $this->pendingFile();
        $file->complete(null, null, new DateTimeImmutable(self::NOW));

        try {
            $file->complete(null, null, new DateTimeImmutable(self::NOW));
            $this->fail('Expected a BusinessRuleException.');
        } catch (BusinessRuleException $e) {
            $this->assertSame(FileRuleCode::NotPending, $e->ruleCode);
        }
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
