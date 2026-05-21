<?php

declare(strict_types=1);

namespace ClassificationOccupation;

class ClassificationOccupationDataManifest
{
    /**
     * @param ClassificationOccupationDatasetDefinition[] $datasets
     */
    public function __construct(
        private readonly string $dataRoot,
        private readonly array $datasets
    ) {
    }

    public function getDataRoot(): string
    {
        return $this->dataRoot;
    }

    /**
     * @return ClassificationOccupationDatasetDefinition[]
     */
    public function getDatasets(): array
    {
        return $this->datasets;
    }

    public function getDataset(ClassificationOccupationSystem $system, string $version): ?ClassificationOccupationDatasetDefinition
    {
        foreach ($this->datasets as $dataset) {
            if ($dataset->system === $system && $dataset->version === $version) {
                return $dataset;
            }
        }

        return null;
    }

    public function validate(): void
    {
        foreach ($this->datasets as $dataset) {
            foreach ($dataset->files as $file) {
                $path = $this->dataRoot . '/' . $dataset->basePath . '/' . $file->filename;
                if (!file_exists($path)) {
                    throw new ClassificationOccupationDataFileNotFound(sprintf(
                        'Data file missing for %s %s: %s',
                        $dataset->system->value,
                        $dataset->version,
                        $path
                    ));
                }
            }
        }
    }
}
