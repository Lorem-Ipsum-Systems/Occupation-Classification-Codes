<?php

declare(strict_types=1);

namespace ClassificationOccupation\Loader;

use ClassificationOccupation\Data\ClassificationOccupationDataCategory;
use ClassificationOccupation\Data\ClassificationOccupationDatasetDefinition;
use ClassificationOccupation\Model\ClassificationOccupationCode;
use ClassificationOccupation\Model\ClassificationOccupationDataset;
use ClassificationOccupation\Model\ClassificationOccupationSearchTerm;
use ClassificationOccupation\Model\ClassificationOccupationSystem;

class DefaultOccupationDataLoader implements ClassificationOccupationDataLoader
{
    public function __construct(
        private readonly string $dataRoot,
        private readonly NdjsonReader $reader = new NdjsonReader()
    ) {
    }

    public function load(ClassificationOccupationDatasetDefinition $definition): ClassificationOccupationDataset
    {
        $codes = [];
        $searchTerms = [];

        switch ($definition->system) {
            case ClassificationOccupationSystem::SOC:
                $codes = $this->loadSoc($definition);
                $codesMap = [];
                foreach ($codes as $c) { $codesMap[$c->code] = true; }
                $searchTerms = $this->loadSocSearch($definition, $codesMap);
                break;
            case ClassificationOccupationSystem::UK_SOC:
                $codes = $this->loadUkSoc($definition);
                $codesMap = [];
                foreach ($codes as $c) { $codesMap[$c->code] = true; }
                $searchTerms = $this->loadUkSocSearch($definition, $codesMap);
                break;
            case ClassificationOccupationSystem::ISCO:
                $codes = $this->loadIsco($definition);
                $codesMap = [];
                foreach ($codes as $c) { $codesMap[$c->code] = true; }
                $searchTerms = $this->loadIscoSearch($definition, $codesMap);
                break;
            case ClassificationOccupationSystem::ESCO:
                $codes = $this->loadEsco($definition);
                $codesMap = [];
                foreach ($codes as $c) { $codesMap[$c->code] = true; }
                $searchTerms = $this->loadEscoSearch($definition, $codesMap);
                break;
        }

        $codes = $this->calculateIsLeaf($codes);

        $codesMap = [];
        foreach ($codes as $code) {
            $codesMap[$code->code] = $code;
        }

        return new ClassificationOccupationDataset(
            $definition->system,
            $definition->version,
            $definition->jurisdiction,
            $codes,
            $codesMap,
            $searchTerms
        );
    }

    /**
     * @param ClassificationOccupationCode[] $codes
     * @return ClassificationOccupationCode[]
     */
    private function calculateIsLeaf(array $codes): array
    {
        $parentCodes = [];
        foreach ($codes as $code) {
            if ($code->parentCode !== null) {
                $parentCodes[$code->parentCode] = true;
            }
        }

        $updatedCodes = [];
        foreach ($codes as $code) {
            $isLeaf = !isset($parentCodes[$code->code]);
            if ($isLeaf !== $code->isLeaf) {
                $updatedCodes[] = new ClassificationOccupationCode(
                    $code->system,
                    $code->version,
                    $code->jurisdiction,
                    $code->code,
                    $code->title,
                    $code->level,
                    $code->parentCode,
                    $isLeaf,
                    $code->isSelectable,
                    $code->description,
                    $code->tasksInclude,
                    $code->includedOccupations,
                    $code->excludedOccupations,
                    $code->notes,
                    $code->sourceMetadata
                );
            } else {
                $updatedCodes[] = $code;
            }
        }

        return $updatedCodes;
    }

    private function getFilePath(ClassificationOccupationDatasetDefinition $definition, ClassificationOccupationDataCategory $category): ?string
    {
        $file = $definition->getFileByCategory($category);
        if (!$file) {
            return null;
        }

        return $this->dataRoot . '/' . $definition->basePath . '/' . $file->filename;
    }

    /**
     * @return ClassificationOccupationCode[]
     */
    private function loadSoc(ClassificationOccupationDatasetDefinition $definition): array
    {
        $structurePath = $this->getFilePath($definition, ClassificationOccupationDataCategory::STRUCTURE);
        $definitionsPath = $this->getFilePath($definition, ClassificationOccupationDataCategory::DEFINITIONS);

        $definitions = [];
        if ($definitionsPath) {
            foreach ($this->reader->read($definitionsPath) as $record) {
                $code = $record['soc_code'] ?? null;
                if ($code !== null && $code !== '') {
                    $definitions[(string)$code] = $record['soc_definition'] ?? null;
                }
            }
        }

        $codes = [];
        $lastMajor = null;
        $lastMinor = null;
        $lastBroad = null;

        if ($structurePath) {
            foreach ($this->reader->read($structurePath) as $record) {
                $code = null;
                $level = 0;
                $parentCode = null;

                if (isset($record['detailed_occupation']) && $record['detailed_occupation'] !== '') {
                    $code = $record['detailed_occupation'];
                    $level = 4;
                    $parentCode = $lastBroad;
                } elseif (isset($record['broad_group']) && $record['broad_group'] !== '') {
                    $code = $record['broad_group'];
                    $level = 3;
                    $parentCode = $lastMinor;
                    $lastBroad = $code;
                } elseif (isset($record['minor_group']) && $record['minor_group'] !== '') {
                    $code = $record['minor_group'];
                    $level = 2;
                    $parentCode = $lastMajor;
                    $lastMinor = $code;
                } elseif (isset($record['major_group']) && $record['major_group'] !== '') {
                    $code = $record['major_group'];
                    $level = 1;
                    $parentCode = null;
                    $lastMajor = $code;
                }

                if ($code) {
                    $codes[] = new ClassificationOccupationCode(
                        $definition->system,
                        $definition->version,
                        $definition->jurisdiction,
                        (string)$code,
                        (string)($record['title'] ?? ''),
                        $level,
                        $parentCode,
                        false,
                        true,
                        $definitions[$code] ?? null,
                        null,
                        null,
                        null,
                        null,
                        $record
                    );
                }
            }
        }

        return $codes;
    }

    /**
     * @param array<string, bool> $validCodes
     * @return ClassificationOccupationSearchTerm[]
     */
    private function loadSocSearch(ClassificationOccupationDatasetDefinition $definition, array $validCodes): array
    {
        $path = $this->getFilePath($definition, ClassificationOccupationDataCategory::SEARCH);
        if (!$path) {
            return [];
        }

        $terms = [];
        foreach ($this->reader->read($path) as $record) {
            $code = (string)($record['2018_soc_code'] ?? '');
            if ($code === '' || !isset($validCodes[$code])) {
                continue;
            }

            $directMatchTitle = $record['2018_soc_direct_match_title'] ?? null;
            $socTitle = $record['2018_soc_title'] ?? null;
            $isIllustrative = ($record['illustrative_example'] ?? null) === 'x';

            if ($directMatchTitle) {
                $terms[] = new ClassificationOccupationSearchTerm(
                    $definition->system,
                    $definition->version,
                    $definition->jurisdiction,
                    $code,
                    (string)$directMatchTitle,
                    null,
                    $isIllustrative,
                    $record
                );
            }

            if ($socTitle && $socTitle !== $directMatchTitle) {
                $terms[] = new ClassificationOccupationSearchTerm(
                    $definition->system,
                    $definition->version,
                    $definition->jurisdiction,
                    $code,
                    (string)$socTitle,
                    null,
                    $isIllustrative,
                    $record
                );
            }
        }

        return $terms;
    }

    /**
     * @return ClassificationOccupationCode[]
     */
    private function loadUkSoc(ClassificationOccupationDatasetDefinition $definition): array
    {
        $path = $this->getFilePath($definition, ClassificationOccupationDataCategory::STRUCTURE);
        if (!$path) {
            return [];
        }

        $codes = [];
        $lastMajor = null;
        $lastSubMajor = null;
        $lastMinor = null;

        foreach ($this->reader->read($path) as $record) {
            $code = null;
            $level = 0;
            $parentCode = null;

            if (isset($record['soc2020_unit_group']) && $record['soc2020_unit_group'] !== '') {
                $code = $record['soc2020_unit_group'];
                $level = 4;
                $parentCode = $lastMinor;
            } elseif (isset($record['soc2020_minor_group']) && $record['soc2020_minor_group'] !== '') {
                $code = $record['soc2020_minor_group'];
                $level = 3;
                $parentCode = $lastSubMajor;
                $lastMinor = $code;
            } elseif (isset($record['soc2020_sub_major_group']) && $record['soc2020_sub_major_group'] !== '') {
                $code = $record['soc2020_sub_major_group'];
                $level = 2;
                $parentCode = $lastMajor;
                $lastSubMajor = $code;
            } elseif (isset($record['soc2020_major_group']) && $record['soc2020_major_group'] !== '') {
                $code = $record['soc2020_major_group'];
                $level = 1;
                $parentCode = null;
                $lastMajor = $code;
            }

            if ($code) {
                $codes[] = new ClassificationOccupationCode(
                    $definition->system,
                    $definition->version,
                    $definition->jurisdiction,
                    (string)$code,
                    (string)($record['soc2020_group_title'] ?? ''),
                    $level,
                    $parentCode,
                    false,
                    true,
                    null,
                    null,
                    null,
                    null,
                    null,
                    $record
                );
            }
        }

        return $codes;
    }

    /**
     * @param array<string, bool> $validCodes
     * @return ClassificationOccupationSearchTerm[]
     */
    private function loadUkSocSearch(ClassificationOccupationDatasetDefinition $definition, array $validCodes): array
    {
        $path = $this->getFilePath($definition, ClassificationOccupationDataCategory::SEARCH);
        if (!$path) {
            return [];
        }

        $terms = [];
        foreach ($this->reader->read($path) as $record) {
            $code = (string)($record['soc_2020'] ?? '');
            if ($code === '' || !isset($validCodes[$code])) {
                continue;
            }

            $fields = [
                'indexocc',
                'indexocc_natural_word_order',
                'soc2020_ext_sug_title',
                'soc2020_unit_group_title'
            ];

            foreach ($fields as $field) {
                $term = $record[$field] ?? null;
                if ($term !== null && $term !== '') {
                    $terms[] = new ClassificationOccupationSearchTerm(
                        $definition->system,
                        $definition->version,
                        $definition->jurisdiction,
                        $code,
                        (string)$term,
                        null,
                        false,
                        $record
                    );
                }
            }
        }

        return $terms;
    }

    /**
     * @return ClassificationOccupationCode[]
     */
    private function loadIsco(ClassificationOccupationDatasetDefinition $definition): array
    {
        $hierarchyPath = $this->getFilePath($definition, ClassificationOccupationDataCategory::STRUCTURE);
        $richPath = $this->getFilePath($definition, ClassificationOccupationDataCategory::DEFINITIONS);

        $richData = [];
        if ($richPath) {
            foreach ($this->reader->read($richPath) as $record) {
                $code = $record['isco_08_code'] ?? null;
                if ($code !== null && $code !== '') {
                    $richData[(string)$code] = $record;
                }
            }
        }

        $uniqueCodes = [];

        if (!empty($richData)) {
            foreach ($richData as $code => $record) {
                $uniqueCodes[$code] = [
                    'title' => $record['title_en'] ?? '',
                    'record' => $record
                ];
            }
        } elseif ($hierarchyPath) {
            foreach ($this->reader->read($hierarchyPath) as $record) {
                if (($record['isco_version'] ?? '') !== 'ISCO-08') {
                    continue;
                }

                $levels = [
                    'major' => 'major_label',
                    'sub_major' => 'sub_major_label',
                    'minor' => 'minor_label',
                    'unit' => 'description'
                ];

                foreach ($levels as $codeKey => $labelKey) {
                    $code = $record[$codeKey] ?? null;
                    if ($code === null) {
                        continue;
                    }

                    $code = (string)$code;
                    if (isset($uniqueCodes[$code])) {
                        continue;
                    }

                    $uniqueCodes[$code] = [
                        'title' => $record[$labelKey] ?? '',
                        'record' => $record
                    ];
                }
            }
        }

        $finalCodes = [];
        $allValidCodes = array_keys($uniqueCodes);

        foreach ($uniqueCodes as $code => $data) {
            $code = (string)$code;
            $level = strlen($code);
            
            $parentCode = null;
            if ($level > 1) {
                $parentCode = substr($code, 0, $level - 1);
                // For ISCO, 2nd level can be 2 chars (sub-major), 3rd level 3 chars (minor), 4th level 4 chars (unit)
                // Wait, ISCO levels are 1, 2, 3, 4 digits.
                // Major: 1 digit
                // Sub-major: 2 digits
                // Minor: 3 digits
                // Unit: 4 digits
                if (!in_array($parentCode, $allValidCodes)) {
                    $parentCode = null;
                }
            }

            $rich = $richData[$code] ?? [];
            
            $finalCodes[] = new ClassificationOccupationCode(
                $definition->system,
                $definition->version,
                $definition->jurisdiction,
                $code,
                (string)($rich['title_en'] ?? $data['title']),
                $level,
                $parentCode,
                false,
                true,
                $rich['definition'] ?? null,
                $rich['tasks_include'] ?? null,
                $rich['included_occupations'] ?? null,
                $rich['excluded_occupations'] ?? null,
                $rich['notes'] ?? null,
                $data['record']
            );
        }

        return $finalCodes;
    }

    /**
     * @param array<string, bool> $validCodes
     * @return ClassificationOccupationSearchTerm[]
     */
    private function loadIscoSearch(ClassificationOccupationDatasetDefinition $definition, array $validCodes): array
    {
        $path = $this->getFilePath($definition, ClassificationOccupationDataCategory::SEARCH);
        if (!$path) {
            return [];
        }

        $terms = [];
        foreach ($this->reader->read($path) as $record) {
            $code = (string)($record['isco_08'] ?? '');
            if ($code === '' || !isset($validCodes[$code])) {
                continue;
            }

            $term = $record['english_title'] ?? null;
            if ($term !== null && $term !== '') {
                $terms[] = new ClassificationOccupationSearchTerm(
                    $definition->system,
                    $definition->version,
                    $definition->jurisdiction,
                    $code,
                    (string)$term,
                    null,
                    false,
                    $record
                );
            }
        }

        return $terms;
    }

    /**
     * @return ClassificationOccupationCode[]
     */
    private function loadEsco(ClassificationOccupationDatasetDefinition $definition): array
    {
        $occupationsPath = null;
        $relationsPath = null;

        foreach ($definition->files as $file) {
            if ($file->filename === 'occupations_en.ndjson') {
                $occupationsPath = $this->dataRoot . '/' . $definition->basePath . '/' . $file->filename;
            } elseif ($file->filename === 'broaderRelationsOccPillar_en.ndjson') {
                $relationsPath = $this->dataRoot . '/' . $definition->basePath . '/' . $file->filename;
            }
        }

        if (!$occupationsPath) {
            return [];
        }

        $relations = [];
        if ($relationsPath) {
            foreach ($this->reader->read($relationsPath) as $record) {
                $conceptUri = $record['concepturi'] ?? null;
                $broaderUri = $record['broaderuri'] ?? null;
                if ($conceptUri && $broaderUri) {
                    $relations[$conceptUri] = $broaderUri;
                }
            }
        }

        $codes = [];
        foreach ($this->reader->read($occupationsPath) as $record) {
            $uri = $record['concepturi'] ?? null;
            if (!$uri) {
                continue;
            }

            $codes[] = new ClassificationOccupationCode(
                $definition->system,
                $definition->version,
                $definition->jurisdiction,
                (string)$uri,
                (string)($record['preferredlabel'] ?? ''),
                0,
                $relations[$uri] ?? null,
                false,
                true,
                $record['description'] ?? null,
                null,
                null,
                null,
                null,
                $record
            );
        }

        return $this->calculateEscoLevels($codes);
    }

    /**
     * @param array<string, bool> $validCodes
     * @return ClassificationOccupationSearchTerm[]
     */
    private function loadEscoSearch(ClassificationOccupationDatasetDefinition $definition, array $validCodes): array
    {
        $occupationsPath = null;
        foreach ($definition->files as $file) {
            if ($file->filename === 'occupations_en.ndjson') {
                $occupationsPath = $this->dataRoot . '/' . $definition->basePath . '/' . $file->filename;
                break;
            }
        }

        if (!$occupationsPath) {
            return [];
        }

        $terms = [];
        foreach ($this->reader->read($occupationsPath) as $record) {
            $uri = $record['concepturi'] ?? null;
            if (!$uri || !isset($validCodes[$uri])) {
                continue;
            }

            // Preferred Label
            if (isset($record['preferredlabel']) && $record['preferredlabel'] !== '') {
                $terms[] = new ClassificationOccupationSearchTerm(
                    $definition->system,
                    $definition->version,
                    $definition->jurisdiction,
                    $uri,
                    (string)$record['preferredlabel'],
                    null,
                    false,
                    $record
                );
            }

            // Alt Labels
            if (isset($record['altlabels']) && $record['altlabels'] !== '') {
                $altLabels = explode("\n", $record['altlabels']);
                foreach ($altLabels as $altLabel) {
                    $altLabel = trim($altLabel);
                    if ($altLabel !== '') {
                        $terms[] = new ClassificationOccupationSearchTerm(
                            $definition->system,
                            $definition->version,
                            $definition->jurisdiction,
                            $uri,
                            $altLabel,
                            null,
                            false,
                            $record
                        );
                    }
                }
            }

            // Numeric Code
            if (isset($record['code']) && $record['code'] !== '') {
                $terms[] = new ClassificationOccupationSearchTerm(
                    $definition->system,
                    $definition->version,
                    $definition->jurisdiction,
                    $uri,
                    (string)$record['code'],
                    null,
                    false,
                    $record
                );
            }
        }

        return $terms;
    }

    /**
     * @param ClassificationOccupationCode[] $codes
     * @return ClassificationOccupationCode[]
     */
    private function calculateEscoLevels(array $codes): array
    {
        $codesByUri = [];
        foreach ($codes as $code) {
            $codesByUri[$code->code] = $code;
        }

        $levels = [];
        $getLevel = function ($uri, array $visited = []) use (&$getLevel, &$levels, $codesByUri) {
            if (isset($levels[$uri])) {
                return $levels[$uri];
            }

            if (isset($visited[$uri])) {
                return $levels[$uri] = 1;
            }
            $visited[$uri] = true;

            $code = $codesByUri[$uri] ?? null;
            if (!$code || $code->parentCode === null) {
                return $levels[$uri] = 1;
            }

            return $levels[$uri] = 1 + $getLevel($code->parentCode, $visited);
        };

        $updatedCodes = [];
        foreach ($codes as $code) {
            $level = $getLevel($code->code);
            $updatedCodes[] = new ClassificationOccupationCode(
                $code->system,
                $code->version,
                $code->jurisdiction,
                $code->code,
                $code->title,
                $level,
                $code->parentCode,
                $code->isLeaf,
                $code->isSelectable,
                $code->description,
                $code->tasksInclude,
                $code->includedOccupations,
                $code->excludedOccupations,
                $code->notes,
                $code->sourceMetadata
            );
        }

        return $updatedCodes;
    }
}
