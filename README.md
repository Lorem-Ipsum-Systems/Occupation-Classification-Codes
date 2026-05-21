# Occupation Classification Codes

A framework-agnostic PHP library for occupational and employee classification codes.

Supported systems:
- SOC 2018 (United States)
- UK SOC 2020 (United Kingdom)
- ISCO-08 (International)

## Installation

```bash
composer require lorem-ipsum-systems/occupation-classification-codes
```

## Usage

```php
use ClassificationOccupation\ClassificationOccupationRegistry;
use ClassificationOccupation\ClassificationOccupationSystem;

// Initialize the API
$api = ClassificationOccupationRegistry::fromDefaultData();

// Get occupations for a specific system and version
$occupations = $api->codes('SOC', '2018');

foreach ($occupations as $occupation) {
    echo $occupation->code . ': ' . $occupation->title . PHP_EOL;
}

// Find a specific code
$code = $api->findCode('SOC', '2018', '15-1252');
if ($code) {
    echo "Found: " . $code->title . PHP_EOL;
}

// Hierarchy traversal
$parent = $api->parentOf('SOC', '2018', '15-1252');
$ancestors = $api->ancestorsOf('SOC', '2018', '15-1252');
$children = $api->childrenOf('SOC', '2018', '15-1250');

// Search and Autocomplete
$results = $api->search('software developer');
foreach ($results as $result) {
    echo $result->code->title . " (Score: " . $result->score . ")\n";
}

$suggestions = $api->autocomplete('soft');
```

## Features

- Framework-agnostic
- Version-aware
- Ships with normalized NDJSON data
- No database required
- Immutable readonly value objects

## License

MIT
