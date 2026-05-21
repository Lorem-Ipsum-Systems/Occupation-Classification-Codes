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
$occupations = $api->getOccupations(ClassificationOccupationSystem::SOC, '2018');

foreach ($occupations as $occupation) {
    echo $occupation->code . ': ' . $occupation->title . PHP_EOL;
}
```

## Features

- Framework-agnostic
- Version-aware
- Ships with normalized NDJSON data
- No database required
- Immutable readonly value objects

## License

MIT
