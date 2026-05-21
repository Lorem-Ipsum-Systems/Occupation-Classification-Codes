<?php

declare(strict_types=1);

namespace ClassificationOccupation\Tests;

use ClassificationOccupation\ClassificationOccupationRegistry;
use PHPUnit\Framework\TestCase;

class EscoIntegrationTest extends TestCase
{
    private ClassificationOccupationRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = ClassificationOccupationRegistry::fromDefaultData();
    }

    public function testEscoSystemIsAvailable(): void
    {
        $this->assertContains('ESCO', $this->registry->systems());
        $this->assertContains('1.2.1', $this->registry->versions('ESCO'));
    }

    public function testGetEscoOccupationByUri(): void
    {
        $uri = 'http://data.europa.eu/esco/occupation/31cc4199-703c-4f8a-9b46-a2070a6d35df';
        $code = $this->registry->getCode('ESCO', '1.2.1', $uri);
        
        $this->assertEquals('biology technician', $code->title);
        $this->assertEquals('http://data.europa.eu/esco/occupation/4fef1907-cf82-413b-841a-02ef57e299a6', $code->parentCode);
    }

    public function testEscoHierarchy(): void
    {
        $childUri = 'http://data.europa.eu/esco/occupation/31cc4199-703c-4f8a-9b46-a2070a6d35df';
        $parentUri = 'http://data.europa.eu/esco/occupation/4fef1907-cf82-413b-841a-02ef57e299a6';
        
        $parent = $this->registry->parentOf('ESCO', '1.2.1', $childUri);
        $this->assertNotNull($parent);
        $this->assertEquals($parentUri, $parent->code);
        
        $children = $this->registry->childrenOf('ESCO', '1.2.1', $parentUri);
        $childCodes = array_map(fn($c) => $c->code, $children);
        $this->assertContains($childUri, $childCodes);
    }

    public function testEscoHierarchyToIscoGroup(): void
    {
        // "metal drawing machine operator" has broader uri "http://data.europa.eu/esco/isco/C8121"
        $occupationUri = 'http://data.europa.eu/esco/occupation/000e93a3-d956-4e45-aacb-f12c83fedf84';
        $groupUri = 'http://data.europa.eu/esco/isco/C8121';

        $occupation = $this->registry->getCode('ESCO', '1.2.1', $occupationUri);
        $this->assertEquals($groupUri, $occupation->parentCode);

        $parent = $this->registry->parentOf('ESCO', '1.2.1', $occupationUri);
        $this->assertNotNull($parent, 'Parent ISCO group should be found in the registry');
        $this->assertEquals($groupUri, $parent->code);
        $this->assertEquals('Metal processing plant operators', $parent->title);
    }

    public function testEscoSearchByCode(): void
    {
        // Search by ESCO numeric code
        $results = $this->registry->search('3141.2.3', 'ESCO', '1.2.1');
        $this->assertNotEmpty($results);
        $this->assertEquals('http://data.europa.eu/esco/occupation/31cc4199-703c-4f8a-9b46-a2070a6d35df', $results[0]->code->code);
    }

    public function testEscoSearchByAltLabel(): void
    {
        // Search by one of the alt labels for biology technician
        $results = $this->registry->search('wildlife laboratory technician', 'ESCO', '1.2.1');
        $this->assertNotEmpty($results);
        $this->assertEquals('http://data.europa.eu/esco/occupation/31cc4199-703c-4f8a-9b46-a2070a6d35df', $results[0]->code->code);
    }

    public function testEscoSearchForIscoGroup(): void
    {
        // Search for "Metal processing plant operators"
        $results = $this->registry->search('Metal processing plant operators', 'ESCO', '1.2.1');
        $this->assertNotEmpty($results);
        $this->assertEquals('http://data.europa.eu/esco/isco/C8121', $results[0]->code->code);

        // Search by numeric code part 8121
        $results = $this->registry->search('8121', 'ESCO', '1.2.1');
        $this->assertNotEmpty($results);
        $this->assertEquals('http://data.europa.eu/esco/isco/C8121', $results[0]->code->code);
    }
}
