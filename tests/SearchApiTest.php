<?php

declare(strict_types=1);

namespace ClassificationOccupation\Tests;

use ClassificationOccupation\ClassificationOccupationRegistry;
use ClassificationOccupation\ClassificationOccupationSearchResult;
use ClassificationOccupation\ClassificationOccupationSystem;
use PHPUnit\Framework\TestCase;

class SearchApiTest extends TestCase
{
    private ClassificationOccupationRegistry $api;

    protected function setUp(): void
    {
        $this->api = ClassificationOccupationRegistry::fromDefaultData();
    }

    public function testSearchSoftwareDeveloper(): void
    {
        $results = $this->api->search('software developer');
        $this->assertNotEmpty($results);
        $this->assertInstanceOf(ClassificationOccupationSearchResult::class, $results[0]);
        
        // Should find Software Developers in SOC
        $foundSoc = false;
        foreach ($results as $result) {
            if ($result->code->system === ClassificationOccupationSystem::SOC && $result->code->code === '15-1252') {
                $foundSoc = true;
                break;
            }
        }
        $this->assertTrue($foundSoc, 'Should find Software Developers in SOC 2018');
    }

    public function testSearchSoftwareEngineer(): void
    {
        $results = $this->api->search('software engineer');
        $this->assertNotEmpty($results);
        
        // 'Software Engineer' is an alias for 15-1252 in SOC 2018 search file
        $found = false;
        foreach ($results as $result) {
            if ($result->code->code === '15-1252') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testSearchProgrammer(): void
    {
        $results = $this->api->search('programmer');
        $this->assertNotEmpty($results);
        
        // UK_SOC 2134 is "Programmers and software development professionals"
        $foundUk = false;
        foreach ($results as $result) {
            if ($result->code->system === ClassificationOccupationSystem::UK_SOC && $result->code->code === '2134') {
                $foundUk = true;
                break;
            }
        }
        $this->assertTrue($foundUk);
    }

    public function testSearchAdmiral(): void
    {
        $results = $this->api->search('Admiral');
        $this->assertNotEmpty($results);
        
        // Admiral should be an alias/search term in one of the systems (probably UK or SOC)
    }

    public function testSearchChiefExecutives(): void
    {
        $results = $this->api->search('Chief Executives');
        $this->assertNotEmpty($results);
        
        // Exact match should be high score
        $this->assertEquals('Chief Executives', $results[0]->code->title);
    }

    public function testSearchByCode(): void
    {
        $results = $this->api->search('2134');
        $this->assertNotEmpty($results);
        $this->assertEquals('2134', $results[0]->code->code);
        $this->assertEquals(ClassificationOccupationSystem::UK_SOC, $results[0]->code->system);

        $resultsIsco = $this->api->search('2512');
        $this->assertNotEmpty($resultsIsco);
        $this->assertEquals('2512', $resultsIsco[0]->code->code);
        $this->assertEquals(ClassificationOccupationSystem::ISCO, $resultsIsco[0]->code->system);
    }

    public function testSearchSoft(): void
    {
        $results = $this->api->search('soft');
        $this->assertNotEmpty($results);
        // Prefix match for "Software ..."
    }

    public function testFilteredSearch(): void
    {
        $socResults = $this->api->search('software developer', system: 'SOC', version: '2018');
        foreach ($socResults as $r) {
            $this->assertEquals(ClassificationOccupationSystem::SOC, $r->code->system);
            $this->assertEquals('2018', $r->code->version);
        }

        $iscoResults = $this->api->search('software developer', system: 'ISCO', version: '08');
        foreach ($iscoResults as $r) {
            $this->assertEquals(ClassificationOccupationSystem::ISCO, $r->code->system);
            $this->assertEquals('08', $r->code->version);
        }

        $ukResults = $this->api->search('programmer', system: 'UK_SOC', version: '2020');
        foreach ($ukResults as $r) {
            $this->assertEquals(ClassificationOccupationSystem::UK_SOC, $r->code->system);
            $this->assertEquals('2020', $r->code->version);
        }
    }

    public function testAutocomplete(): void
    {
        $results = $this->api->autocomplete('soft');
        $this->assertNotEmpty($results);
        foreach ($results as $r) {
            $this->assertTrue(
                str_starts_with($this->normalize($r->code->title), 'soft') ||
                str_starts_with($this->normalize($r->matchedTerm), 'soft') ||
                str_starts_with($this->normalize($r->code->code), 'soft')
            );
        }
    }

    public function testSearchTermsFor(): void
    {
        $terms = $this->api->searchTermsFor('SOC', '2018', '15-1252');
        $this->assertNotEmpty($terms);
        foreach ($terms as $term) {
            $this->assertEquals('15-1252', $term->code);
        }
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[[:punct:]]/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
