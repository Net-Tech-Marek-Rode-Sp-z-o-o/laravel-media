<?php

declare(strict_types=1);

namespace NetCode\Media\Presentation\Http\Data;

use Illuminate\Validation\Rule;
use NetCode\Media\Domain\ValueObjects\UploadLimits;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

final class InitiateUploadData extends Data
{
    public function __construct(
        #[Max(255)]
        public string $filename,
        public string $mime,
        public int $size,
    ) {}

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        $limits = app(UploadLimits::class);

        return [
            'size' => ['required', 'integer', 'min:1', 'max:'.$limits->maxBytes],
            'mime' => $limits->allowedTypes === []
                ? ['required', 'string']
                : ['required', 'string', Rule::in(array_keys($limits->allowedTypes))],
        ];
    }
}
