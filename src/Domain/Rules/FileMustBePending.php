<?php

declare(strict_types=1);

namespace NetCode\Media\Domain\Rules;

use NetCode\Domain\Rule\BusinessRule;
use NetCode\Domain\Rule\BusinessRuleCode;
use NetCode\Media\Domain\File;

final readonly class FileMustBePending implements BusinessRule
{
    public function __construct(
        private File $file,
    ) {}

    public function isBroken(): bool
    {
        return ! $this->file->isPending();
    }

    public function code(): BusinessRuleCode
    {
        return FileRuleCode::NotPending;
    }

    public function message(): string
    {
        return 'Only a pending upload can be completed.';
    }
}
