<?php

declare(strict_types=1);

namespace ClassificationOccupation;

class ClassificationOccupationVersionNotFound extends ClassificationOccupationException
{
    public static function forVersion(string $system, string $version): self
    {
        return new self(sprintf('Version "%s" not found for system "%s".', $version, $system));
    }
}
