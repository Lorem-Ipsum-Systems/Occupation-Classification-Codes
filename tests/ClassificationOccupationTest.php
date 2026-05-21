<?php

declare(strict_types=1);

namespace ClassificationOccupation\Tests;

use ClassificationOccupation\ClassificationOccupation;
use ClassificationOccupation\Occupation;
use ClassificationOccupation\System;
use PHPUnit\Framework\TestCase;

class ClassificationOccupationTest extends TestCase
{
    private ClassificationOccupation $api;

    protected function setUp(): void
    {
        $this->api = \ClassificationOccupation\ClassificationOccupationRegistry::fromDefaultData();
    }

    public function testGetOccupationsSoc2018(): void
    {
        $occupations = iterator_to_array($this->api->getOccupations(System::SOC, '2018'));
        $this->assertNotEmpty($occupations);
        $this->assertInstanceOf(Occupation::class, $occupations[0]);
        $this->assertEquals('11-0000', $occupations[0]->code);
        $this->assertEquals('Management Occupations', $occupations[0]->title);
    }

    public function testGetDefinitionsSoc2018(): void
    {
        $definitions = iterator_to_array($this->api->getDefinitions(System::SOC, '2018'));
        $this->assertNotEmpty($definitions);
        $this->assertEquals('11-0000', $definitions[0]->code);
        $this->assertEquals('Management Occupations', $definitions[0]->title);
    }

    public function testGetIndexSoc2018(): void
    {
        $index = iterator_to_array($this->api->getIndex(System::SOC, '2018'));
        $this->assertNotEmpty($index);
        $this->assertIsString($index[0]->code);
        $this->assertIsString($index[0]->title);
    }

    public function testGetOccupationsUkSoc2020(): void
    {
        $occupations = iterator_to_array($this->api->getOccupations(System::UK_SOC, '2020'));
        $this->assertNotEmpty($occupations);
        $this->assertEquals('1', $occupations[0]->code);
        $this->assertEquals('MANAGERS, DIRECTORS AND SENIOR OFFICIALS', $occupations[0]->title);
    }

    public function testGetOccupationsIsco08(): void
    {
        $occupations = iterator_to_array($this->api->getOccupations(System::ISCO, '08'));
        $this->assertNotEmpty($occupations);
        $this->assertEquals('1111', $occupations[0]->code);
        $this->assertEquals('Legislators', $occupations[0]->title);
    }

    public function testPreservesMetadata(): void
    {
        $occupations = iterator_to_array($this->api->getOccupations(System::SOC, '2018'));
        $first = $occupations[0];
        $this->assertArrayHasKey('source_file', $first->metadata);
        $this->assertEquals('soc_structure_2018.xlsx', $first->metadata['source_file']);
    }

    public function testInvalidSystemVersionReturnsEmpty(): void
    {
        $occupations = iterator_to_array($this->api->getOccupations(System::SOC, '9999'));
        $this->assertEmpty($occupations);
    }
}
