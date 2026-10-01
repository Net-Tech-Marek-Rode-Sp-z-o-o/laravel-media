<?php

declare(strict_types=1);

namespace NetCode\Media\Tests\Feature\Storage;

use Illuminate\Support\Facades\Storage;
use NetCode\Media\Infrastructure\Storage\S3ObjectStorage;
use NetCode\Media\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class S3ObjectStorageTest extends TestCase
{
    #[Test]
    public function it_reports_the_stored_size_and_the_type_detected_from_the_bytes(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('uploads/a/receipt.pdf', "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n1 0 obj\n<<>>\nendobj\n");

        $stored = new S3ObjectStorage(disk: 's3', presignDisk: 's3')->inspect('uploads/a/receipt.pdf');

        $this->assertNotNull($stored);
        $this->assertSame(Storage::disk('s3')->size('uploads/a/receipt.pdf'), $stored->size);
        $this->assertSame('application/pdf', $stored->mime);
    }

    #[Test]
    public function it_moves_an_object_and_skips_a_missing_source(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('uploads/a/receipt.pdf', '%PDF-1.4');
        $storage = new S3ObjectStorage(disk: 's3', presignDisk: 's3');

        $storage->move('uploads/a/receipt.pdf', 'files/a/receipt.pdf');
        $storage->move('uploads/a/receipt.pdf', 'files/a/receipt.pdf');

        Storage::disk('s3')->assertMissing('uploads/a/receipt.pdf');
        Storage::disk('s3')->assertExists('files/a/receipt.pdf');
    }

    #[Test]
    public function it_reports_nothing_for_a_missing_object(): void
    {
        Storage::fake('s3');

        $this->assertNull(new S3ObjectStorage(disk: 's3', presignDisk: 's3')->inspect('uploads/missing.pdf'));
    }
}
