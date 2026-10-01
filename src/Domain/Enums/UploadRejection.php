<?php

declare(strict_types=1);

namespace NetCode\Media\Domain\Enums;

enum UploadRejection: string
{
    case TooLarge = 'file.too_large';
    case TypeMismatch = 'file.type_mismatch';

    public function message(): string
    {
        return match ($this) {
            self::TooLarge => 'The uploaded file is larger than allowed.',
            self::TypeMismatch => 'The uploaded file is not of an allowed type.',
        };
    }
}
