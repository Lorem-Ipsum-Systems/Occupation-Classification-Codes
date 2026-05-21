<?php

declare(strict_types=1);

namespace ClassificationOccupation\Model;

/**
 * @immutable
 */
readonly class ClassificationOccupationDataset
{
    /**
     * @param ClassificationOccupationCode[] $codes
     * @param array<string, ClassificationOccupationCode> $codesMap
     * @param ClassificationOccupationSearchTerm[] $searchTerms
     */
    public function __construct(
        public ClassificationOccupationSystem $system,
        public string $version,
        public string $jurisdiction,
        public array $codes,
        public array $codesMap = [],
        public array $searchTerms = []
    ) {
    }
}
