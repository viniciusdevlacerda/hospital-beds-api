<?php

declare(strict_types=1);

namespace App\Enums;

enum BedStatus: string
{
    case Free = 'free';
    case Occupied = 'occupied';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Livre',
            self::Occupied => 'Ocupado',
        };
    }
}
