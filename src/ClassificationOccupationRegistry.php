<?php

declare(strict_types=1);

namespace ClassificationOccupation;

class ClassificationOccupationRegistry
{
    public static function fromDefaultData(): ClassificationOccupation
    {
        $dataRoot = realpath(__DIR__ . '/../data');
        if (!$dataRoot) {
            // Fallback for when we might be in vendor or other structure
            $dataRoot = __DIR__ . '/../data';
            if (!is_dir($dataRoot)) {
                throw new \RuntimeException('Could not discover bundled data root.');
            }
        }

        $manifest = new ClassificationOccupationDataManifest(
            $dataRoot,
            [
                new ClassificationOccupationDatasetDefinition(
                    ClassificationOccupationSystem::SOC,
                    '2018',
                    'US',
                    'soc',
                    'soc/2018',
                    [
                        new ClassificationOccupationDataFile('soc_structure_2018.ndjson', ClassificationOccupationDataCategory::STRUCTURE),
                        new ClassificationOccupationDataFile('soc_2018_definitions.ndjson', ClassificationOccupationDataCategory::DEFINITIONS),
                        new ClassificationOccupationDataFile('soc_2018_direct_match_title_file.ndjson', ClassificationOccupationDataCategory::SEARCH),
                    ]
                ),
                new ClassificationOccupationDatasetDefinition(
                    ClassificationOccupationSystem::UK_SOC,
                    '2020',
                    'GB',
                    'uk_soc',
                    'uk_soc/2020',
                    [
                        new ClassificationOccupationDataFile('soc2020_framework.ndjson', ClassificationOccupationDataCategory::STRUCTURE),
                        new ClassificationOccupationDataFile('soc2020_volume2_thecodingindex.ndjson', ClassificationOccupationDataCategory::SEARCH),
                    ]
                ),
                new ClassificationOccupationDatasetDefinition(
                    ClassificationOccupationSystem::ISCO,
                    '08',
                    'INTL',
                    'isco',
                    'isco/08',
                    [
                        new ClassificationOccupationDataFile('isco_08_en.ndjson', ClassificationOccupationDataCategory::STRUCTURE),
                        new ClassificationOccupationDataFile('isco_08_en_structure_and_definitions.ndjson', ClassificationOccupationDataCategory::DEFINITIONS),
                        new ClassificationOccupationDataFile('isco_08_88_en_index.ndjson', ClassificationOccupationDataCategory::SEARCH),
                    ]
                ),
            ]
        );

        $manifest->validate();

        $loader = new DefaultOccupationDataLoader($dataRoot);

        return new ClassificationOccupation($manifest, $loader);
    }
}
