<?php

declare(strict_types=1);

namespace ClassificationOccupation\Exception;

class ClassificationOccupationCodeNotFound extends ClassificationOccupationException
{
    public static function forCode(string $system, string $version, string $code): self
    {
        return new self(sprintf('Occupation code "%s" not found in system "%s" version "%s".', $code, $system, $version));
    }
}
