<?php

declare(strict_types=1);

namespace ClassificationOccupation\Model;

/**
 * @immutable
 */
readonly class ClassificationOccupationSearchResult
{
    public function __construct(
        public ClassificationOccupationCode $code,
        public string $matchedTerm,
        public float $score,
        public string $matchType
    ) {
    }
}
