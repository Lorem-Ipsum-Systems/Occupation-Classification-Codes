<?php

declare(strict_types=1);

namespace ClassificationOccupation;

/**
 * @immutable
 */
readonly class ClassificationOccupationDataset
{
    /**
     * @param ClassificationOccupationCode[] $codes
     * @param ClassificationOccupationSearchTerm[] $searchTerms
     */
    public function __construct(
        public ClassificationOccupationSystem $system,
        public string $version,
        public string $jurisdiction,
        public array $codes,
        public array $searchTerms = []
    ) {
    }
}
