<?php

declare(strict_types=1);

namespace NetCode\Media\Tests\Support;

use NetCode\Media\Application\Ports\CurrentUser;

final class FakeCurrentUser implements CurrentUser
{
    public const string ID = '11111111-1111-4111-8111-111111111111';

    public function id(): string
    {
        return self::ID;
    }
}
