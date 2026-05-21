<?php

declare(strict_types=1);

namespace ClassificationOccupation;

/**
 * @immutable
 */
readonly class Occupation
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $code,
        public string $title,
        public array $metadata = []
    ) {
    }
}
