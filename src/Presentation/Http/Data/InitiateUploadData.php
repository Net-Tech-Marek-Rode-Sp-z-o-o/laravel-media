<?php

declare(strict_types=1);

namespace NetCode\Media\Presentation\Http\Data;

use NetCode\Media\Domain\Policies\UploadPolicy;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

final class InitiateUploadData extends Data
{
    public function __construct(
        #[Max(255)]
        public string $filename,
        public string $mime,
        #[Max(UploadPolicy::MAX_UPLOAD_BYTES)]
        public int $size,
    ) {}
}
