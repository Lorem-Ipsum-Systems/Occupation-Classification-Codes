<?php

declare(strict_types=1);

namespace ClassificationOccupation;

class ClassificationOccupation
{
    /** @var array<string, ClassificationOccupationDataset> */
    private array $cache = [];

    public function __construct(
        private readonly ClassificationOccupationDataManifest $manifest,
        private readonly ClassificationOccupationDataLoader $loader
    ) {
    }

    /**
     * @return iterable<ClassificationOccupationCode>
     */
    public function getOccupations(ClassificationOccupationSystem $system, string $version): iterable
    {
        $dataset = $this->getDataset($system, $version);
        if (!$dataset) {
            return [];
        }

        return $dataset->codes;
    }

    /**
     * @return iterable<ClassificationOccupationSearchTerm>
     */
    public function getSearchTerms(ClassificationOccupationSystem $system, string $version): iterable
    {
        $dataset = $this->getDataset($system, $version);
        if (!$dataset) {
            return [];
        }

        return $dataset->searchTerms;
    }

    private function getDataset(ClassificationOccupationSystem $system, string $version): ?ClassificationOccupationDataset
    {
        $key = $system->value . ':' . $version;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $definition = $this->manifest->getDataset($system, $version);
        if (!$definition) {
            return null;
        }

        $dataset = $this->loader->load($definition);
        $this->cache[$key] = $dataset;

        return $dataset;
    }
}
