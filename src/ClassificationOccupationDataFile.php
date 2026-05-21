<?php

declare(strict_types=1);

namespace ClassificationOccupation;

/**
 * @immutable
 */
readonly class ClassificationOccupationDataFile
{
    public function __construct(
        public string $filename,
        public ClassificationOccupationDataCategory $category
    ) {
    }
}
