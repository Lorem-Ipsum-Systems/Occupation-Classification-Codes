<?php

declare(strict_types=1);

namespace ClassificationOccupation;

/**
 * @immutable
 */
readonly class ClassificationOccupationCode
{
    /**
     * @param array<string, mixed> $sourceMetadata
     */
    public function __construct(
        public ClassificationOccupationSystem $system,
        public string $version,
        public string $jurisdiction,
        public string $code,
        public string $title,
        public int $level,
        public ?string $parentCode = null,
        public bool $isLeaf = false,
        public bool $isSelectable = true,
        public ?string $description = null,
        public ?string $tasksInclude = null,
        public ?string $includedOccupations = null,
        public ?string $excludedOccupations = null,
        public ?string $notes = null,
        public array $sourceMetadata = []
    ) {
    }
}
