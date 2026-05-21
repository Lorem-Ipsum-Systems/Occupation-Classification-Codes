<?php

declare(strict_types=1);

namespace ClassificationOccupation\Model;

/**
 * @immutable
 */
readonly class ClassificationOccupationVersion
{
    public function __construct(
        public string $version
    ) {
    }

    public function __toString(): string
    {
        return $this->version;
    }
}
