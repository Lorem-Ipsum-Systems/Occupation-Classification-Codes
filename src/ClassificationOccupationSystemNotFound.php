<?php

declare(strict_types=1);

namespace ClassificationOccupation;

class ClassificationOccupationSystemNotFound extends ClassificationOccupationException
{
    public static function forSystem(string $system): self
    {
        return new self(sprintf('System "%s" not found.', $system));
    }
}
