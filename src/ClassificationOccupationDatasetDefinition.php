<?php

declare(strict_types=1);

namespace ClassificationOccupation;

/**
 * @immutable
 */
readonly class ClassificationOccupationDatasetDefinition
{
    /**
     * @param ClassificationOccupationDataFile[] $files
     */
    public function __construct(
        public System $system,
        public string $version,
        public string $jurisdiction,
        public string $directoryKey,
        public string $basePath,
        public array $files
    ) {
    }

    public function getFileByCategory(ClassificationOccupationDataCategory $category): ?ClassificationOccupationDataFile
    {
        foreach ($this->files as $file) {
            if ($file->category === $category) {
                return $file;
            }
        }

        return null;
    }
}
