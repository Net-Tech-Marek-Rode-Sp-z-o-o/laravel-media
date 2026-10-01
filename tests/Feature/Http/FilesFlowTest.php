<?php

declare(strict_types=1);

namespace NetCode\Media\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Tests\Support\FakeObjectStorage;
use NetCode\Media\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class FilesFlowTest extends TestCase
{
    use RefreshDatabase;

    private FakeObjectStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = new FakeObjectStorage;
        $this->app->instance(ObjectStorage::class, $this->storage);
    }

    #[Test]
    public function it_initiates_completes_reads_and_deletes_a_file(): void
    {
        $id = (string) $this->postJson('/files', [
            'filename' => 'contract.pdf',
            'mime' => 'application/pdf',
            'size' => 2048,
        ])->assertCreated()->json('data.file_id');

        $this->postJson("/files/{$id}/complete", ['checksum' => 'sha256:abc', 'size' => 2048])
            ->assertNoContent();

        $this->getJson("/files/{$id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.original_name', 'contract.pdf');

        $this->deleteJson("/files/{$id}")->assertNoContent();
        $this->assertNotEmpty($this->storage->deleted);

        $this->getJson("/files/{$id}")->assertNotFound();
    }

    #[Test]
    public function it_rejects_completing_when_the_object_is_missing(): void
    {
        $this->storage->clientUploads = false;

        $id = (string) $this->postJson('/files', [
            'filename' => 'x.pdf',
            'mime' => 'application/pdf',
            'size' => 10,
        ])->assertCreated()->json('data.file_id');

        $this->postJson("/files/{$id}/complete", [])->assertStatus(409);
    }
}
