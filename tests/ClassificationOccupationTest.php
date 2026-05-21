<?php

declare(strict_types=1);

namespace ClassificationOccupation\Tests;

use ClassificationOccupation\ClassificationOccupationRegistry;
use ClassificationOccupation\Model\ClassificationOccupationCode;
use ClassificationOccupation\Model\ClassificationOccupationSearchTerm;
use ClassificationOccupation\Model\ClassificationOccupationSystem;
use ClassificationOccupation\Model\ClassificationOccupationVersion;
use PHPUnit\Framework\TestCase;

class ClassificationOccupationTest extends TestCase
{
    private ClassificationOccupationRegistry $api;

    protected function setUp(): void
    {
        $this->api = ClassificationOccupationRegistry::fromDefaultData();
    }

    public function testGetOccupationsSoc2018(): void
    {
        $occupations = $this->api->codes('SOC', '2018');
        $this->assertNotEmpty($occupations);
        $this->assertInstanceOf(ClassificationOccupationCode::class, $occupations[0]);
        $this->assertEquals('11-0000', $occupations[0]->code);
        $this->assertEquals('Management Occupations', $occupations[0]->title);
        $this->assertEquals(1, $occupations[0]->level);
        $this->assertNull($occupations[0]->parentCode);
        $this->assertFalse($occupations[0]->isLeaf);
        
        // Find a leaf node
        $leaf = null;
        foreach ($occupations as $occ) {
            if ($occ->isLeaf) {
                $leaf = $occ;
                break;
            }
        }
        $this->assertNotNull($leaf);
        $this->assertNotNull($leaf->parentCode);
    }

    public function testGetSearchTermsSoc2018(): void
    {
        $terms = $this->api->searchTerms('SOC', '2018');
        $this->assertNotEmpty($terms);
        $this->assertInstanceOf(ClassificationOccupationSearchTerm::class, $terms[0]);
        $this->assertIsString($terms[0]->code);
        $this->assertIsString($terms[0]->term);
    }

    public function testGetOccupationsUkSoc2020(): void
    {
        $occupations = $this->api->codes('UK_SOC', '2020');
        $this->assertNotEmpty($occupations);
        $this->assertEquals('1', $occupations[0]->code);
        $this->assertEquals('MANAGERS, DIRECTORS AND SENIOR OFFICIALS', $occupations[0]->title);
        $this->assertEquals(1, $occupations[0]->level);
    }

    public function testGetOccupationsIsco08(): void
    {
        $occupations = $this->api->codes('ISCO', '08');
        $this->assertNotEmpty($occupations);
        
        // Find a specific code to verify
        $code1111 = null;
        foreach ($occupations as $occ) {
            if ($occ->code === '1111') {
                $code1111 = $occ;
                break;
            }
        }
        
        $this->assertNotNull($code1111);
        $this->assertEquals('Legislators', $code1111->title);
        $this->assertEquals(4, $code1111->level);
        $this->assertEquals('111', $code1111->parentCode);
    }

    public function testPreservesSourceMetadata(): void
    {
        $occupations = $this->api->codes('SOC', '2018');
        $first = $occupations[0];
        $this->assertArrayHasKey('source_file', $first->sourceMetadata);
        $this->assertEquals('soc_structure_2018.xlsx', $first->sourceMetadata['source_file']);
    }

    public function testInvalidSystemVersionThrows(): void
    {
        $this->expectException(\ClassificationOccupation\Exception\ClassificationOccupationVersionNotFound::class);
        $this->api->codes('SOC', '9999');
    }

    public function testSystemAndVersionArePresentInCode(): void
    {
        $occupations = $this->api->codes('SOC', '2018');
        $first = $occupations[0];
        $this->assertEquals(ClassificationOccupationSystem::SOC, $first->system);
        $this->assertEquals('2018', $first->version);
        $this->assertEquals('US', $first->jurisdiction);
    }

    public function testClassificationOccupationVersion(): void
    {
        $version = new \ClassificationOccupation\Model\ClassificationOccupationVersion('2018');
        $this->assertEquals('2018', (string)$version);
        $this->assertEquals('2018', $version->version);
    }
}
