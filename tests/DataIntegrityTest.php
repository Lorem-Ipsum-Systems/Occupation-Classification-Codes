<?php

declare(strict_types=1);

namespace ClassificationOccupation\Tests;

use ClassificationOccupation\ClassificationOccupationRegistry;
use ClassificationOccupation\Loader\NdjsonReader;
use PHPUnit\Framework\TestCase;

class DataIntegrityTest extends TestCase
{
    private ClassificationOccupationRegistry $api;

    protected function setUp(): void
    {
        $this->api = ClassificationOccupationRegistry::fromDefaultData();
    }

    public function testDataFilesExistAndAreValidNdjson(): void
    {
        $files = [
            'data/soc/2018/soc_structure_2018.ndjson',
            'data/soc/2018/soc_2018_definitions.ndjson',
            'data/soc/2018/soc_2018_direct_match_title_file.ndjson',
            'data/isco/08/isco_08_en.ndjson',
            'data/isco/08/isco_08_en_structure_and_definitions.ndjson',
            'data/isco/08/isco_08_88_en_index.ndjson',
            'data/uk_soc/2020/soc2020_framework.ndjson',
            'data/uk_soc/2020/soc2020_volume2_thecodingindex.ndjson',
        ];

        $reader = new NdjsonReader();
        foreach ($files as $file) {
            $this->assertFileExists($file);
            $count = 0;
            foreach ($reader->read($file) as $record) {
                $this->assertIsArray($record);
                $count++;
            }
            $this->assertGreaterThan(0, $count, "File $file is empty");
        }
    }

    public function testExpectedCodeCounts(): void
    {
        $this->assertCount(1447, $this->api->codes('SOC', '2018'));
        $this->assertCount(551, $this->api->codes('UK_SOC', '2020'));
        $this->assertCount(619, $this->api->codes('ISCO', '08'));
    }

    public function testSocDefinitionsMatchStructure(): void
    {
        $reader = new NdjsonReader();
        $structureCodes = [];
        foreach ($this->api->codes('SOC', '2018') as $code) {
            $structureCodes[] = $code->code;
        }

        foreach ($reader->read('data/soc/2018/soc_2018_definitions.ndjson') as $record) {
            $code = $record['soc_code'] ?? null;
            if ($code !== null && $code !== '') {
                $this->assertContains($code, $structureCodes, "SOC definition code $code not found in structure");
            }
        }
    }

    public function testIscoDefinitionsEnrichCodes(): void
    {
        $code = $this->api->getCode('ISCO', '08', '2512');
        $this->assertNotEmpty($code->description);
        $this->assertStringContainsStringIgnoringCase('Software developers', $code->title);
    }

    public function testUkSocIndexRowsOnlyForValidCodes(): void
    {
        $reader = new NdjsonReader();
        $validCodes = [];
        foreach ($this->api->codes('UK_SOC', '2020') as $code) {
            $validCodes[] = $code->code;
        }

        foreach ($reader->read('data/uk_soc/2020/soc2020_volume2_thecodingindex.ndjson') as $record) {
            $code = (string)($record['soc_2020'] ?? '');
            if ($code !== '' && $code !== '}}}}') {
                $this->assertContains($code, $validCodes, "UK SOC index code $code not found in framework");
            }
        }
    }

    public function testNoForbiddenApisExist(): void
    {
        $forbidden = [
            'Soc2018Registry',
            'UkSoc2020Registry',
            'Isco08Registry',
            'SocMapper',
            'Crosswalk',
            'Mapping',
            'Correspondence',
            'Migration'
        ];

        foreach ($forbidden as $word) {
            $this->assertFalse(class_exists("ClassificationOccupation\\$word"), "Forbidden class $word should not exist");
        }
    }

    public function testReadmeExamplesWork(): void
    {
        // SOC 2018: 15-1252 exists and is titled Software Developers
        $soc = $this->api->getCode('SOC', '2018', '15-1252');
        $this->assertEquals('Software Developers', $soc->title);
        $this->assertEquals('15-1250', $soc->parentCode);

        // UK_SOC 2020 code 2134 exists and is titled Programmers and software development professionals
        $uk = $this->api->getCode('UK_SOC', '2020', '2134');
        $this->assertEquals('Programmers and software development professionals', $uk->title);

        // ISCO-08 code 2512 exists and is titled Software Developers
        $isco = $this->api->getCode('ISCO', '08', '2512');
        $this->assertEquals('Software Developers', $isco->title);
        
        // Search
        $results = $this->api->search('software developer');
        $this->assertNotEmpty($results);
        $found = false;
        foreach ($results as $result) {
            if ($result->code->code === '15-1252' || $result->code->code === '2134' || $result->code->code === '2512') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Search for 'software developer' should return relevant codes");
    }
}
