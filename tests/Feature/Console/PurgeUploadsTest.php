<?php

declare(strict_types=1);

namespace NetCode\Media\Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Application\Ports\StoredObject;
use NetCode\Media\Tests\Support\FakeObjectStorage;
use NetCode\Media\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class PurgeUploadsTest extends TestCase
{
    use RefreshDatabase;

    private FakeObjectStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('media.allowed_types', ['application/pdf' => ['application/pdf']]);
        $this->storage = new FakeObjectStorage;
        $this->app->instance(ObjectStorage::class, $this->storage);
    }

    #[Test]
    public function it_purges_old_pending_and_rejected_uploads_and_keeps_completed_ones(): void
    {
        $pending = $this->initiate();
        $rejected = $this->initiate();
        $this->storage->putWithUploadUrl($rejected, new StoredObject(size: 10, mime: 'application/x-msdownload'));
        $this->postJson("/files/{$rejected}/complete", [])->assertUnprocessable();
        $completed = $this->initiate();
        $this->postJson("/files/{$completed}/complete", [])->assertNoContent();
        $this->storage->objects["files/{$pending}/receipt.pdf"] = new StoredObject(size: 10, mime: 'application/pdf');
        DB::table('files')->update(['created_at' => now()->subDays(2)]);

        $this->artisan('media:purge-uploads')->assertSuccessful();

        $this->getJson("/files/{$pending}")->assertNotFound();
        $this->getJson("/files/{$rejected}")->assertNotFound();
        $this->getJson("/files/{$completed}")->assertOk();
        $this->assertNull($this->storage->storedObjectOf($pending));
    }

    #[Test]
    public function it_keeps_a_pending_upload_younger_than_the_threshold(): void
    {
        $pending = $this->initiate();

        $this->artisan('media:purge-uploads')->assertSuccessful();

        $this->getJson("/files/{$pending}")->assertOk();
    }

    private function initiate(): string
    {
        return (string) $this->postJson('/files', [
            'filename' => 'receipt.pdf',
            'mime' => 'application/pdf',
            'size' => 2048,
        ])->assertCreated()->json('data.file_id');
    }
}
