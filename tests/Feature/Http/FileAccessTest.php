<?php

declare(strict_types=1);

namespace NetCode\Media\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Media\Application\Ports\CurrentUser;
use NetCode\Media\Application\Ports\FileAccess;
use NetCode\Media\Application\Ports\ObjectStorage;
use NetCode\Media\Tests\Support\AllowEveryoneFileAccess;
use NetCode\Media\Tests\Support\FakeCurrentUser;
use NetCode\Media\Tests\Support\FakeObjectStorage;
use NetCode\Media\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class FileAccessTest extends TestCase
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
    public function it_hides_a_file_from_another_user(): void
    {
        $id = $this->uploadAsOwner();
        $this->actAs(FakeCurrentUser::OTHER_ID);

        $this->getJson("/files/{$id}")->assertNotFound();
    }

    #[Test]
    public function it_does_not_let_another_user_delete_a_file(): void
    {
        $id = $this->uploadAsOwner();
        $this->actAs(FakeCurrentUser::OTHER_ID);

        $this->deleteJson("/files/{$id}")->assertNotFound();

        $this->assertSame([], $this->storage->deleted);
        $this->actAs(FakeCurrentUser::ID);
        $this->getJson("/files/{$id}")->assertOk();
    }

    #[Test]
    public function it_does_not_let_another_user_complete_an_upload(): void
    {
        $id = $this->uploadAsOwner();
        $this->actAs(FakeCurrentUser::OTHER_ID);

        $this->postJson("/files/{$id}/complete", [])->assertNotFound();

        $this->actAs(FakeCurrentUser::ID);
        $this->getJson("/files/{$id}")
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.download_url', null);
    }

    #[Test]
    public function it_lets_the_host_widen_access(): void
    {
        $id = $this->uploadAsOwner();
        $this->app->instance(FileAccess::class, new AllowEveryoneFileAccess);
        $this->actAs(FakeCurrentUser::OTHER_ID);

        $this->getJson("/files/{$id}")->assertOk();
    }

    private function uploadAsOwner(): string
    {
        return (string) $this->postJson('/files', [
            'filename' => 'receipt.pdf',
            'mime' => 'application/pdf',
            'size' => 2048,
        ])->assertCreated()->json('data.file_id');
    }

    private function actAs(string $userId): void
    {
        $this->app->instance(CurrentUser::class, new FakeCurrentUser($userId));
    }
}
