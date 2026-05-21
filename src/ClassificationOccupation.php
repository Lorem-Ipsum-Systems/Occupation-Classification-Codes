<?php

declare(strict_types=1);

namespace ClassificationOccupation;

class ClassificationOccupation
{
    public function __construct(private readonly ClassificationOccupationDataManifest $manifest)
    {
    }

    /**
     * @return iterable<Occupation>
     */
    public function getOccupations(System $system, string $version): iterable
    {
        $dataset = $this->manifest->getDataset($system, $version);
        if (!$dataset) {
            return [];
        }

        $file = $dataset->getFileByCategory(ClassificationOccupationDataCategory::STRUCTURE);
        if (!$file) {
            return [];
        }

        return $this->load($dataset, $file);
    }

    /**
     * @return iterable<Occupation>
     */
    public function getDefinitions(System $system, string $version): iterable
    {
        $dataset = $this->manifest->getDataset($system, $version);
        if (!$dataset) {
            return [];
        }

        $file = $dataset->getFileByCategory(ClassificationOccupationDataCategory::DEFINITIONS);
        if (!$file) {
            return [];
        }

        return $this->load($dataset, $file);
    }

    /**
     * @return iterable<Occupation>
     */
    public function getIndex(System $system, string $version): iterable
    {
        $dataset = $this->manifest->getDataset($system, $version);
        if (!$dataset) {
            return [];
        }

        $file = $dataset->getFileByCategory(ClassificationOccupationDataCategory::SEARCH);
        if (!$file) {
            return [];
        }

        return $this->load($dataset, $file);
    }

    /**
     * @return iterable<Occupation>
     */
    private function load(ClassificationOccupationDatasetDefinition $dataset, ClassificationOccupationDataFile $file): iterable
    {
        $filePath = $this->manifest->getDataRoot() . '/' . $dataset->basePath . '/' . $file->filename;
        if (!file_exists($filePath)) {
            return;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return;
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $record = json_decode($line, true);
                if (!is_array($record)) {
                    continue;
                }

                yield $this->mapRecord($dataset->system, $file->filename, $record);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<string, mixed> $record
     */
    private function mapRecord(System $system, string $filename, array $record): Occupation
    {
        $code = '';
        $title = '';

        if ($system === System::SOC) {
            if ($filename === 'soc_structure_2018.ndjson') {
                $code = $record['detailed_occupation'] ?? $record['broad_group'] ?? $record['minor_group'] ?? $record['major_group'] ?? '';
                $title = $record['title'] ?? '';
            } elseif ($filename === 'soc_2018_definitions.ndjson') {
                $code = $record['soc_code'] ?? '';
                $title = $record['soc_title'] ?? '';
            } elseif ($filename === 'soc_2018_direct_match_title_file.ndjson') {
                $code = $record['2018_soc_code'] ?? '';
                $title = $record['2018_soc_direct_match_title'] ?? '';
            }
        } elseif ($system === System::UK_SOC) {
            if ($filename === 'soc2020_framework.ndjson') {
                $code = $record['soc2020_unit_group'] ?? $record['soc2020_minor_group'] ?? $record['soc2020_sub_major_group'] ?? $record['soc2020_major_group'] ?? '';
                $title = $record['soc2020_group_title'] ?? '';
            } elseif ($filename === 'soc2020_volume2_thecodingindex.ndjson') {
                $code = $record['soc_2020'] ?? '';
                $title = $record['indexocc'] ?? '';
            }
        } elseif ($system === System::ISCO) {
            if ($filename === 'isco_08_en.ndjson') {
                $code = $record['unit'] ?? $record['minor'] ?? $record['sub_major'] ?? $record['major'] ?? '';
                $title = $record['description'] ?? $record['minor_label'] ?? $record['sub_major_label'] ?? $record['major_label'] ?? '';
            } elseif ($filename === 'isco_08_en_structure_and_definitions.ndjson') {
                $code = $record['isco_08_code'] ?? '';
                $title = $record['title_en'] ?? '';
            } elseif ($filename === 'isco_08_88_en_index.ndjson') {
                $code = $record['isco_08'] ?? '';
                $title = $record['english_title'] ?? '';
            }
        }

        return new Occupation((string) $code, (string) $title, $record);
    }
}
