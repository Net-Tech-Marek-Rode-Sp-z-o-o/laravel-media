<?php

declare(strict_types=1);

namespace NetCode\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Media\Api\Contracts\FileDirectory;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Tests\Support\FakeCurrentUser;
use NetCode\Media\Tests\Support\FakeObjectStorage;
use NetCode\Media\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class FileDirectoryAccessTest extends TestCase
{
    use RefreshDatabase;

    private FileDirectory $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(ObjectStorage::class, new FakeObjectStorage);
        $this->directory = $this->app->make(FileDirectory::class);
    }

    #[Test]
    public function it_returns_snapshots_of_files_the_requester_may_access(): void
    {
        $id = $this->completedUploadOfTheOwner();

        $snapshots = $this->directory->snapshots([$id], FakeCurrentUser::ID);

        $this->assertCount(1, $snapshots);
        $this->assertSame($id, $snapshots[0]->id);
        $this->assertSame("https://s3.test/files/{$id}/receipt.pdf", $snapshots[0]->downloadUrl);
    }

    #[Test]
    public function it_gives_no_download_url_for_a_pending_file(): void
    {
        $id = (string) $this->postJson('/files', [
            'filename' => 'receipt.pdf',
            'mime' => 'application/pdf',
            'size' => 2048,
        ])->assertCreated()->json('data.file_id');

        $snapshots = $this->directory->snapshots([$id], FakeCurrentUser::ID);

        $this->assertNull($snapshots[0]->downloadUrl);
    }

    #[Test]
    public function it_leaves_out_files_the_requester_may_not_access(): void
    {
        $id = $this->completedUploadOfTheOwner();

        $this->assertSame([], $this->directory->snapshots([$id], FakeCurrentUser::OTHER_ID));
    }

    #[Test]
    public function it_treats_a_file_the_requester_may_not_access_as_not_completed(): void
    {
        $id = $this->completedUploadOfTheOwner();

        $this->assertTrue($this->directory->areCompleted([$id], FakeCurrentUser::ID));
        $this->assertFalse($this->directory->areCompleted([$id], FakeCurrentUser::OTHER_ID));
    }

    private function completedUploadOfTheOwner(): string
    {
        $id = (string) $this->postJson('/files', [
            'filename' => 'receipt.pdf',
            'mime' => 'application/pdf',
            'size' => 2048,
        ])->assertCreated()->json('data.file_id');

        $this->postJson("/files/{$id}/complete", [])->assertNoContent();

        return $id;
    }
}
