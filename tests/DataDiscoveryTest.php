<?php

declare(strict_types=1);

namespace ClassificationOccupation\Tests;

use ClassificationOccupation\ClassificationOccupationDataCategory;
use ClassificationOccupation\ClassificationOccupationDataManifest;
use ClassificationOccupation\ClassificationOccupationRegistry;
use PHPUnit\Framework\TestCase;

class DataDiscoveryTest extends TestCase
{
    public function testDefaultManifestResolvesToRealFiles(): void
    {
        // This test ensures that the Registry::fromDefaultData() doesn't throw and everything is valid
        $api = ClassificationOccupationRegistry::fromDefaultData();
        $this->assertNotNull($api);

        // We can inspect the manifest via reflection or by making it public if needed, 
        // but Registry::fromDefaultData() already calls $manifest->validate().
    }

    public function testManifestValidationFailsOnMissingFile(): void
    {
        $manifest = new ClassificationOccupationDataManifest(
            __DIR__,
            [
                new \ClassificationOccupation\ClassificationOccupationDatasetDefinition(
                    \ClassificationOccupation\ClassificationOccupationSystem::SOC,
                    '2018',
                    'US',
                    'soc',
                    'soc/2018',
                    [
                        new \ClassificationOccupation\ClassificationOccupationDataFile('non_existent.ndjson', ClassificationOccupationDataCategory::STRUCTURE)
                    ]
                )
            ]
        );

        $this->expectException(\ClassificationOccupation\ClassificationOccupationDataFileNotFound::class);
        $this->expectExceptionMessage('Data file missing for SOC 2018');
        $manifest->validate();
    }

    public function testManifestRootDiscovery(): void
    {
        $api = ClassificationOccupationRegistry::fromDefaultData();
        
        // Use reflection to get the manifest and then the data root
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('manifest');
        $property->setAccessible(true);
        /** @var ClassificationOccupationDataManifest $manifest */
        $manifest = $property->getValue($api);
        
        $this->assertDirectoryExists($manifest->getDataRoot());
        $this->assertStringEndsWith('data', $manifest->getDataRoot());
    }

    public function testDatasetDefinitionHasRequiredMetadata(): void
    {
        $api = ClassificationOccupationRegistry::fromDefaultData();
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('manifest');
        $property->setAccessible(true);
        /** @var ClassificationOccupationDataManifest $manifest */
        $manifest = $property->getValue($api);

        $soc2018 = $manifest->getDataset(\ClassificationOccupation\ClassificationOccupationSystem::SOC, '2018');
        $this->assertNotNull($soc2018);
        $this->assertEquals('US', $soc2018->jurisdiction);
        $this->assertEquals('soc', $soc2018->directoryKey);
        $this->assertEquals('soc/2018', $soc2018->basePath);

        $uksoc2020 = $manifest->getDataset(\ClassificationOccupation\ClassificationOccupationSystem::UK_SOC, '2020');
        $this->assertNotNull($uksoc2020);
        $this->assertEquals('GB', $uksoc2020->jurisdiction);

        $isco08 = $manifest->getDataset(\ClassificationOccupation\ClassificationOccupationSystem::ISCO, '08');
        $this->assertNotNull($isco08);
        $this->assertEquals('INTL', $isco08->jurisdiction);
    }
}
