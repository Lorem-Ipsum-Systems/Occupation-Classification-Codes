<?php

declare(strict_types=1);

namespace ClassificationOccupation;

enum System: string
{
    case SOC = 'SOC';
    case UK_SOC = 'UK_SOC';
    case ISCO = 'ISCO';

    public function directory(): string
    {
        return match ($this) {
            self::SOC => 'soc',
            self::UK_SOC => 'uk_soc',
            self::ISCO => 'isco',
        };
    }
}
