<?php

declare(strict_types=1);

namespace App\Enums;

enum ReleaseReason: string
{
    case Discharge = 'discharge';
    case Transfer = 'transfer';
}
