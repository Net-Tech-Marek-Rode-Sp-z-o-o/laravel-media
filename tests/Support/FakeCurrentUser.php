<?php

declare(strict_types=1);

namespace NetCode\Media\Tests\Support;

use NetCode\Media\Application\Ports\CurrentUser;

final readonly class FakeCurrentUser implements CurrentUser
{
    public const string ID = '11111111-1111-4111-8111-111111111111';

    public const string OTHER_ID = '22222222-2222-4222-8222-222222222222';

    public function __construct(
        private string $id = self::ID,
    ) {}

    public function id(): string
    {
        return $this->id;
    }
}
