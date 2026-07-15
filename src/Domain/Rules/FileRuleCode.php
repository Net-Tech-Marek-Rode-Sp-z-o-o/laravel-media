<?php

declare(strict_types=1);

namespace NetCode\Media\Domain\Rules;

use NetCode\Domain\Rule\BusinessRuleCode;

enum FileRuleCode: string implements BusinessRuleCode
{
    case NotPending = 'file.not_pending';
}
