<?php

declare(strict_types=1);

namespace NetCode\Media\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Application\Ports\StoredObject;
use NetCode\Media\Tests\Support\FakeObjectStorage;
use NetCode\Media\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class UploadLimitsTest extends TestCase
{
    use RefreshDatabase;

    private FakeObjectStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('media.max_upload_bytes', 5_000_000);
        config()->set('media.allowed_types', ['application/pdf' => ['application/pdf']]);

        $this->storage = new FakeObjectStorage;
        $this->app->instance(ObjectStorage::class, $this->storage);
    }

    #[Test]
    public function it_rejects_and_deletes_an_upload_larger_than_the_limit(): void
    {
        $id = $this->initiate();
        $this->storage->putWithUploadUrl($id, new StoredObject(size: 500_000_000, mime: 'application/pdf'));

        $this->postJson("/files/{$id}/complete", [])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'file.too_large');

        $this->assertCount(1, $this->storage->deleted);
        $this->getJson("/files/{$id}")->assertJsonPath('data.status', 'failed');
    }

    #[Test]
    public function it_rejects_an_upload_whose_bytes_do_not_match_the_declared_type(): void
    {
        $id = $this->initiate();
        $this->storage->putWithUploadUrl($id, new StoredObject(size: 2048, mime: 'application/x-msdownload'));

        $this->postJson("/files/{$id}/complete", [])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'file.type_mismatch');

        $this->assertCount(1, $this->storage->deleted);
    }

    #[Test]
    public function it_keeps_the_size_reported_by_the_storage(): void
    {
        $id = $this->initiate();
        $this->storage->putWithUploadUrl($id, new StoredObject(size: 4096, mime: 'application/pdf'));

        $this->postJson("/files/{$id}/complete", ['size' => 1])->assertNoContent();

        $this->getJson("/files/{$id}")->assertJsonPath('data.size', 4096);
    }

    #[Test]
    public function it_ignores_a_new_upload_to_the_same_url_after_completion(): void
    {
        $id = $this->initiate();
        $this->postJson("/files/{$id}/complete", [])->assertNoContent();

        $this->storage->putWithUploadUrl($id, new StoredObject(size: 500_000_000, mime: 'application/x-msdownload'));

        $this->assertEquals(new StoredObject(size: 2048, mime: 'application/pdf'), $this->storage->storedObjectOf($id));
        $this->getJson("/files/{$id}")->assertJsonPath('data.size', 2048);
    }

    #[Test]
    public function it_requires_the_size_and_the_type_to_initiate(): void
    {
        $this->postJson('/files', ['filename' => 'x.pdf'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['size', 'mime']);
    }

    #[Test]
    public function it_refuses_to_initiate_an_upload_above_the_limit(): void
    {
        $this->postJson('/files', ['filename' => 'big.pdf', 'mime' => 'application/pdf', 'size' => 5_000_001])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('size');
    }

    #[Test]
    public function it_refuses_to_initiate_a_type_that_is_not_allowed(): void
    {
        $this->postJson('/files', ['filename' => 'x.svg', 'mime' => 'image/svg+xml', 'size' => 10])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mime');
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
