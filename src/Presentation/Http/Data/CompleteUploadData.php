<?php

declare(strict_types=1);

namespace NetCode\Media\Presentation\Http\Data;

use Spatie\LaravelData\Data;

final class CompleteUploadData extends Data
{
    public function __construct(
        public string|null $checksum = null,
        public int|null $size = null,
    ) {}
}
