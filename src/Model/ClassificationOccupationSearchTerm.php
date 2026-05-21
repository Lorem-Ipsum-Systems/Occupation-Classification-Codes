<?php

declare(strict_types=1);

namespace ClassificationOccupation\Model;

/**
 * @immutable
 */
readonly class ClassificationOccupationSearchTerm
{
    /**
     * @param array<string, mixed> $sourceMetadata
     */
    public function __construct(
        public ClassificationOccupationSystem $system,
        public string $version,
        public string $jurisdiction,
        public string $code,
        public string $term,
        public ?string $context = null,
        public bool $isIllustrativeExample = false,
        public array $sourceMetadata = []
    ) {
    }
}
