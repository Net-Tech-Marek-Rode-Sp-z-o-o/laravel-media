<?php

declare(strict_types=1);

namespace NetCode\Media\Domain\Enums;

enum FileStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
