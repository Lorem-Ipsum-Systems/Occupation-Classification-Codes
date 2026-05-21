<?php

declare(strict_types=1);

namespace ClassificationOccupation\Tests;

use ClassificationOccupation\ClassificationOccupationRegistry;
use ClassificationOccupation\ClassificationOccupationCodeNotFound;
use ClassificationOccupation\ClassificationOccupationSystemNotFound;
use ClassificationOccupation\ClassificationOccupationVersionNotFound;
use PHPUnit\Framework\TestCase;

class RegistryApiTest extends TestCase
{
    private ClassificationOccupationRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = ClassificationOccupationRegistry::fromDefaultData();
    }

    public function testSystems(): void
    {
        $systems = $this->registry->systems();
        $this->assertContains('SOC', $systems);
        $this->assertContains('UK_SOC', $systems);
        $this->assertContains('ISCO', $systems);
    }

    public function testVersions(): void
    {
        $this->assertEquals(['2018'], $this->registry->versions('SOC'));
        $this->assertEquals(['2020'], $this->registry->versions('UK_SOC'));
        $this->assertEquals(['08'], $this->registry->versions('ISCO'));
        
        // Unsupported system should return empty array
        $this->assertEquals([], $this->registry->versions('INVALID'));
    }

    public function testSoc2018Code15_1252(): void
    {
        $code = $this->registry->getCode('SOC', '2018', '15-1252');
        $this->assertEquals('Software Developers', $code->title);
        $this->assertEquals('15-1250', $code->parentCode);
    }

    public function testUkSoc2020Code2134(): void
    {
        $code = $this->registry->getCode('UK_SOC', '2020', '2134');
        $this->assertEquals('Programmers and software development professionals', $code->title);
    }

    public function testIsco08Code2512(): void
    {
        $code = $this->registry->getCode('ISCO', '08', '2512');
        $this->assertEquals('Software Developers', $code->title);
    }

    public function testIsValidCode(): void
    {
        $this->assertTrue($this->registry->isValidCode('SOC', '2018', '15-1252'));
        $this->assertFalse($this->registry->isValidCode('SOC', '2018', '99-9999'));
    }

    public function testGetCodeThrowsForInvalidCode(): void
    {
        $this->expectException(ClassificationOccupationCodeNotFound::class);
        $this->registry->getCode('SOC', '2018', '99-9999');
    }

    public function testUnsupportedVersionThrows(): void
    {
        $this->expectException(ClassificationOccupationVersionNotFound::class);
        $this->registry->getCode('SOC', '9999', '15-1252');
    }

    public function testUnsupportedSystemThrows(): void
    {
        $this->expectException(ClassificationOccupationSystemNotFound::class);
        $this->registry->getCode('INVALID', '2018', '15-1252');
    }

    public function testHierarchyTraversalSoc(): void
    {
        $codeId = '15-1252';
        $code = $this->registry->getCode('SOC', '2018', $codeId);
        
        $parent = $this->registry->parentOf('SOC', '2018', $codeId);
        $this->assertNotNull($parent);
        $this->assertEquals('15-1250', $parent->code);

        $ancestors = $this->registry->ancestorsOf('SOC', '2018', $codeId);
        $this->assertCount(3, $ancestors);
        $this->assertEquals('15-1250', $ancestors[0]->code); // Broad
        $this->assertEquals('15-1200', $ancestors[1]->code); // Minor
        $this->assertEquals('15-0000', $ancestors[2]->code); // Major

        $children = $this->registry->childrenOf('SOC', '2018', '15-1250');
        $this->assertNotEmpty($children);
        $found = false;
        foreach ($children as $child) {
            if ($child->code === '15-1252') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
        
        $descendants = $this->registry->descendantsOf('SOC', '2018', '15-1200');
        $this->assertNotEmpty($descendants);
        $descendantCodes = array_map(fn($d) => $d->code, $descendants);
        $this->assertContains('15-1252', $descendantCodes);
        $this->assertContains('15-1250', $descendantCodes);
    }

    public function testHierarchyTraversalUkSoc(): void
    {
        $codeId = '2134';
        $parent = $this->registry->parentOf('UK_SOC', '2020', $codeId);
        $this->assertNotNull($parent);
        $this->assertEquals('213', $parent->code);

        $ancestors = $this->registry->ancestorsOf('UK_SOC', '2020', $codeId);
        $this->assertCount(3, $ancestors);
        $this->assertEquals('213', $ancestors[0]->code); // Minor
        $this->assertEquals('21', $ancestors[1]->code);  // Sub-major
        $this->assertEquals('2', $ancestors[2]->code);   // Major
    }

    public function testHierarchyTraversalIsco(): void
    {
        $codeId = '2512';
        $parent = $this->registry->parentOf('ISCO', '08', $codeId);
        $this->assertNotNull($parent);
        $this->assertEquals('251', $parent->code);

        $ancestors = $this->registry->ancestorsOf('ISCO', '08', $codeId);
        $this->assertCount(3, $ancestors);
        $this->assertEquals('251', $ancestors[0]->code); // Minor
        $this->assertEquals('25', $ancestors[1]->code);  // Sub-major
        $this->assertEquals('2', $ancestors[2]->code);   // Major
    }

    public function testDataIsLoadedThroughManifest(): void
    {
        // This is indirectly tested by the fact that getCode works, 
        // but we can verify that the registry uses the manifest.
        $reflection = new \ReflectionClass($this->registry);
        $property = $reflection->getProperty('manifest');
        $property->setAccessible(true);
        $manifest = $property->getValue($this->registry);
        
        $this->assertInstanceOf(\ClassificationOccupation\ClassificationOccupationDataManifest::class, $manifest);
        $this->assertDirectoryExists($manifest->getDataRoot());
    }
}
