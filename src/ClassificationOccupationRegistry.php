<?php

declare(strict_types=1);

namespace ClassificationOccupation;

use ClassificationOccupation\Data\ClassificationOccupationDataCategory;
use ClassificationOccupation\Data\ClassificationOccupationDataFile;
use ClassificationOccupation\Data\ClassificationOccupationDataManifest;
use ClassificationOccupation\Data\ClassificationOccupationDatasetDefinition;
use ClassificationOccupation\Exception\ClassificationOccupationCodeNotFound;
use ClassificationOccupation\Exception\ClassificationOccupationException;
use ClassificationOccupation\Exception\ClassificationOccupationSystemNotFound;
use ClassificationOccupation\Exception\ClassificationOccupationVersionNotFound;
use ClassificationOccupation\Loader\ClassificationOccupationDataLoader;
use ClassificationOccupation\Loader\DefaultOccupationDataLoader;
use ClassificationOccupation\Model\ClassificationOccupationCode;
use ClassificationOccupation\Model\ClassificationOccupationDataset;
use ClassificationOccupation\Model\ClassificationOccupationSearchResult;
use ClassificationOccupation\Model\ClassificationOccupationSearchTerm;
use ClassificationOccupation\Model\ClassificationOccupationSystem;

class ClassificationOccupationRegistry
{
    /** @var array<string, ClassificationOccupationDataset> */
    private array $cache = [];

    private function __construct(
        private readonly ClassificationOccupationDataManifest $manifest,
        private readonly ClassificationOccupationDataLoader $loader
    ) {
    }

    public static function fromDefaultData(): self
    {
        $dataRoot = realpath(__DIR__ . '/../data');
        if (!$dataRoot) {
            $dataRoot = __DIR__ . '/../data';
            if (!is_dir($dataRoot)) {
                throw new ClassificationOccupationException('Could not discover bundled data root.');
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

        return new self($manifest, $loader);
    }

    /**
     * @return string[]
     */
    public function systems(): array
    {
        $systems = [];
        foreach ($this->manifest->getDatasets() as $dataset) {
            $systems[] = $dataset->system->value;
        }
        return array_values(array_unique($systems));
    }

    /**
     * @return string[]
     */
    public function versions(?string $system = null): array
    {
        $versions = [];
        $systemEnum = $system !== null ? ClassificationOccupationSystem::tryFrom($system) : null;
        
        if ($system !== null && $systemEnum === null) {
            return [];
        }

        foreach ($this->manifest->getDatasets() as $dataset) {
            if ($systemEnum === null || $dataset->system === $systemEnum) {
                $versions[] = $dataset->version;
            }
        }
        return array_values(array_unique($versions));
    }

    /**
     * @return ClassificationOccupationDatasetDefinition[]
     */
    public function datasets(): array
    {
        return $this->manifest->getDatasets();
    }

    /**
     * @return ClassificationOccupationCode[]
     */
    public function codes(string $system, string $version): array
    {
        return $this->getDataset($system, $version)->codes;
    }

    /**
     * @return ClassificationOccupationSearchTerm[]
     */
    public function searchTerms(string $system, string $version): array
    {
        return $this->getDataset($system, $version)->searchTerms;
    }

    /**
     * @return ClassificationOccupationCode[]
     */
    public function selectableCodes(string $system, string $version): array
    {
        return array_values(array_filter(
            $this->getDataset($system, $version)->codes,
            fn(ClassificationOccupationCode $code) => $code->isSelectable
        ));
    }

    public function findCode(string $system, string $version, string $code): ?ClassificationOccupationCode
    {
        try {
            $dataset = $this->getDataset($system, $version);
            return $dataset->codesMap[$code] ?? null;
        } catch (ClassificationOccupationSystemNotFound|ClassificationOccupationVersionNotFound) {
            return null;
        }
    }

    public function getCode(string $system, string $version, string $code): ClassificationOccupationCode
    {
        $found = $this->findCode($system, $version, $code);
        if (!$found) {
            // Re-throw specific exception if findCode failed because of system/version
            // Or if system/version are ok, throw CodeNotFound
            $this->getDataset($system, $version); // This will throw if system/version invalid
            throw ClassificationOccupationCodeNotFound::forCode($system, $version, $code);
        }

        return $found;
    }

    public function isValidCode(string $system, string $version, string $code): bool
    {
        return $this->findCode($system, $version, $code) !== null;
    }

    /**
     * @return ClassificationOccupationCode[]
     */
    public function childrenOf(string $system, string $version, string $code): array
    {
        $dataset = $this->getDataset($system, $version);
        $children = [];
        foreach ($dataset->codes as $item) {
            if ($item->parentCode === $code) {
                $children[] = $item;
            }
        }
        return $children;
    }

    public function parentOf(string $system, string $version, string $code): ?ClassificationOccupationCode
    {
        $item = $this->getCode($system, $version, $code);
        if ($item->parentCode === null) {
            return null;
        }

        return $this->findCode($system, $version, $item->parentCode);
    }

    /**
     * @return ClassificationOccupationCode[]
     */
    public function ancestorsOf(string $system, string $version, string $code): array
    {
        $ancestors = [];
        $current = $this->findCode($system, $version, $code);
        
        while ($current !== null && $current->parentCode !== null) {
            $parent = $this->findCode($system, $version, $current->parentCode);
            if ($parent) {
                $ancestors[] = $parent;
                $current = $parent;
            } else {
                break;
            }
        }

        return $ancestors;
    }

    /**
     * @return ClassificationOccupationCode[]
     */
    public function descendantsOf(string $system, string $version, string $code): array
    {
        $dataset = $this->getDataset($system, $version);
        $descendants = [];
        
        $this->collectDescendants($code, $dataset, $descendants);
        
        return $descendants;
    }

    private function collectDescendants(string $parentCode, ClassificationOccupationDataset $dataset, array &$descendants): void
    {
        foreach ($dataset->codes as $item) {
            if ($item->parentCode === $parentCode) {
                $descendants[] = $item;
                $this->collectDescendants($item->code, $dataset, $descendants);
            }
        }
    }

    /**
     * @return ClassificationOccupationSearchResult[]
     */
    public function search(string $query, ?string $system = null, ?string $version = null, int $limit = 20): array
    {
        $normalizedQuery = $this->normalize($query);
        if ($normalizedQuery === '' && $query === '') {
            return [];
        }

        $datasets = $this->getDatasetsForSearch($system, $version);
        $results = [];

        foreach ($datasets as $dataset) {
            foreach ($dataset->codes as $code) {
                $score = 0;
                $matchedTerm = '';

                // Exact code match
                if ($code->code === $query || $this->normalize($code->code) === $normalizedQuery) {
                    $score = 1000;
                    $matchedTerm = $code->code;
                } else {
                    // Title matches
                    $normTitle = $this->normalize($code->title);
                    if ($normTitle === $normalizedQuery) {
                        $score = 900;
                        $matchedTerm = $code->title;
                    } elseif (str_starts_with($normTitle, $normalizedQuery)) {
                        $score = 800;
                        $matchedTerm = $code->title;
                    } elseif (str_contains($normTitle, $normalizedQuery)) {
                        $score = 700;
                        $matchedTerm = $code->title;
                    }

                    // Description/Notes matches
                    if ($score < 600) {
                        if ($code->description && str_contains($this->normalize($code->description), $normalizedQuery)) {
                            $score = 600;
                            $matchedTerm = $code->title;
                        } elseif ($code->notes && str_contains($this->normalize($code->notes), $normalizedQuery)) {
                            $score = 600;
                            $matchedTerm = $code->title;
                        }
                    }
                }

                if ($score > 0) {
                    $this->addResult($results, $code, $matchedTerm, (float)$score, 'code_or_title');
                }
            }

            foreach ($dataset->searchTerms as $term) {
                $score = 0;
                $normTerm = $this->normalize($term->term);

                if ($normTerm === $normalizedQuery) {
                    $score = 900;
                } elseif (str_starts_with($normTerm, $normalizedQuery)) {
                    $score = 800;
                } elseif (str_contains($normTerm, $normalizedQuery)) {
                    $score = 700;
                }

                if ($score > 0) {
                    $code = $dataset->codesMap[$term->code] ?? null;
                    if ($code) {
                        $this->addResult($results, $code, $term->term, (float)$score, 'alias');
                    }
                }
            }
        }

        return $this->finalizeResults($results, $limit);
    }

    /**
     * @return ClassificationOccupationSearchResult[]
     */
    public function autocomplete(string $query, ?string $system = null, ?string $version = null, int $limit = 10): array
    {
        $normalizedQuery = $this->normalize($query);
        if ($normalizedQuery === '' && $query === '') {
            return [];
        }

        $datasets = $this->getDatasetsForSearch($system, $version);
        $results = [];

        foreach ($datasets as $dataset) {
            foreach ($dataset->codes as $code) {
                $score = 0;
                $matchedTerm = '';

                // Code prefix
                if ($code->code === $query || $this->normalize($code->code) === $normalizedQuery) {
                    $score = 1000;
                    $matchedTerm = $code->code;
                } elseif (str_starts_with($this->normalize($code->code), $normalizedQuery)) {
                    $score = 950;
                    $matchedTerm = $code->code;
                } else {
                    // Title prefix
                    $normTitle = $this->normalize($code->title);
                    if ($normTitle === $normalizedQuery) {
                        $score = 900;
                        $matchedTerm = $code->title;
                    } elseif (str_starts_with($normTitle, $normalizedQuery)) {
                        $score = 800;
                        $matchedTerm = $code->title;
                    }
                }

                if ($score > 0) {
                    $this->addResult($results, $code, $matchedTerm, (float)$score, 'code_or_title');
                }
            }

            foreach ($dataset->searchTerms as $term) {
                $score = 0;
                $normTerm = $this->normalize($term->term);

                if ($normTerm === $normalizedQuery) {
                    $score = 900;
                } elseif (str_starts_with($normTerm, $normalizedQuery)) {
                    $score = 800;
                }

                if ($score > 0) {
                    $code = $dataset->codesMap[$term->code] ?? null;
                    if ($code) {
                        $this->addResult($results, $code, $term->term, (float)$score, 'alias');
                    }
                }
            }
        }

        return $this->finalizeResults($results, $limit);
    }

    /**
     * @return ClassificationOccupationSearchTerm[]
     */
    public function searchTermsFor(string $system, string $version, string $code): array
    {
        $dataset = $this->getDataset($system, $version);
        return array_values(array_filter(
            $dataset->searchTerms,
            fn(ClassificationOccupationSearchTerm $term) => $term->code === $code
        ));
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        // Replace punctuation with space
        $text = preg_replace('/[[:punct:]]/u', ' ', $text);
        // Collapse multiple spaces
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * @param array<string, ClassificationOccupationSearchResult> $results
     */
    private function addResult(array &$results, ClassificationOccupationCode $code, string $matchedTerm, float $score, string $matchType): void
    {
        $key = $code->system->value . ':' . $code->version . ':' . $code->code;
        if (!isset($results[$key]) || $results[$key]->score < $score) {
            $results[$key] = new ClassificationOccupationSearchResult($code, $matchedTerm, $score, $matchType);
        }
    }

    /**
     * @param array<string, ClassificationOccupationSearchResult> $results
     * @return ClassificationOccupationSearchResult[]
     */
    private function finalizeResults(array $results, int $limit): array
    {
        $final = array_values($results);
        usort($final, function (ClassificationOccupationSearchResult $a, ClassificationOccupationSearchResult $b) {
            if (abs($b->score - $a->score) > 0.0001) {
                return $b->score <=> $a->score;
            }
            // Deterministic secondary sort
            $titleCompare = strcmp($a->code->title, $b->code->title);
            if ($titleCompare !== 0) {
                return $titleCompare;
            }
            return strcmp($a->code->code, $b->code->code);
        });

        return array_slice($final, 0, $limit);
    }

    /**
     * @return ClassificationOccupationDataset[]
     */
    private function getDatasetsForSearch(?string $system = null, ?string $version = null): array
    {
        $datasets = [];
        if ($system !== null && $version !== null) {
            $datasets[] = $this->getDataset($system, $version);
        } elseif ($system !== null) {
            foreach ($this->versions($system) as $v) {
                $datasets[] = $this->getDataset($system, $v);
            }
        } else {
            foreach ($this->manifest->getDatasets() as $def) {
                $datasets[] = $this->getDataset($def->system->value, $def->version);
            }
        }
        return $datasets;
    }

    private function getDataset(string $system, string $version): ClassificationOccupationDataset
    {
        $systemEnum = ClassificationOccupationSystem::tryFrom($system);
        if (!$systemEnum) {
            throw ClassificationOccupationSystemNotFound::forSystem($system);
        }

        $key = $system . ':' . $version;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $definition = $this->manifest->getDataset($systemEnum, $version);
        if (!$definition) {
            throw ClassificationOccupationVersionNotFound::forVersion($system, $version);
        }

        $dataset = $this->loader->load($definition);
        $this->cache[$key] = $dataset;

        return $dataset;
    }
}
