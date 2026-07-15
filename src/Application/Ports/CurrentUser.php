<?php

declare(strict_types=1);

namespace NetCode\Media\Application\Ports;

/** Who is uploading — the host binds this to its authentication (returns the subject id). */
interface CurrentUser
{
    public function id(): string;
}
