# Occupation Classification Codes

A framework-agnostic PHP library for occupational and employee classification codes. This package provides a unified API for lookup, validation, hierarchy browsing, descriptions, and autocomplete across multiple classification systems.

## Supported Systems

| System | Version | Jurisdiction | Data Path |
|--------|---------|--------------|-----------|
| SOC    | 2018    | United States | `data/soc/2018/` |
| UK_SOC | 2020    | United Kingdom | `data/uk_soc/2020/` |
| ISCO   | 08      | International (ILO/UN) | `data/isco/08/` |
| ESCO   | 1.2.1   | European Union | `data/esco/1.2.1/` |

## Installation

```bash
composer require lorem-ipsum-systems/occupation-classification-codes
```

## Quickstart

```php
use ClassificationOccupation\ClassificationOccupationRegistry;

// Initialize the Registry with bundled data
$registry = ClassificationOccupationRegistry::fromDefaultData();

// 1. Validate a code
if ($registry->isValidCode('SOC', '2018', '15-1252')) {
    echo "Valid SOC code!\n";
}

// 2. Find a specific occupation
$code = $registry->findCode('UK_SOC', '2020', '2134');
if ($code) {
    echo "Title: " . $code->title . "\n"; // Programmers and software development professionals
}

// 3. Hierarchy traversal
$children = $registry->childrenOf('ISCO', '08', '2512');
foreach ($children as $child) {
    echo $child->code . ": " . $child->title . "\n";
}

// 4. Search and Autocomplete
$results = $registry->search('software developer');
foreach ($results as $result) {
    echo $result->code->title . " (Score: " . $result->score . ")\n";
}

$suggestions = $registry->autocomplete('soft');
```

## Data Directory Layout

The package uses a version-aware data layout to support multiple systems and future versions without breaking the API:

```text
data/
  soc/
    2018/
      soc_structure_2018.ndjson
      soc_2018_definitions.ndjson
      soc_2018_direct_match_title_file.ndjson
  uk_soc/
    2020/
      soc2020_framework.ndjson
      soc2020_volume2_thecodingindex.ndjson
  isco/
    08/
      isco_08_en.ndjson
      isco_08_en_structure_and_definitions.ndjson
      isco_08_88_en_index.ndjson
  esco/
    1.2.1/
      occupations_en.ndjson
      broaderRelationsOccPillar_en.ndjson
```

## v1 Limitations

- No crosswalks or framework-to-framework mappings.
- No version migration tools.
- No support for NOC or ANZSCO yet.
- Historical fields in source files (e.g., ISCO-88, SOC 2010) are preserved as internal metadata only.

## Future Roadmap

Planned additive features:
- Support for NOC (Canada) and ANZSCO (Australia/New Zealand).
- Multi-language translations.
- Relationship and crosswalk files.
- Optional exporters and framework adapters.

## API Reference

### `ClassificationOccupationRegistry`

- `fromDefaultData()`: Factory to create a registry with bundled data.
- `systems()`: Returns array of supported system identifiers.
- `versions(?string $system)`: Returns array of supported versions for a system.
- `codes(string $system, string $version)`: Returns all normalized codes for a dataset.
- `findCode(string $system, string $version, string $code)`: Returns a `ClassificationOccupationCode` or null.
- `getCode(...)`: Same as `findCode` but throws exception if not found.
- `isValidCode(...)`: Returns boolean.
- `childrenOf(...)`, `parentOf(...)`, `ancestorsOf(...)`, `descendantsOf(...)`: Hierarchy traversal.
- `search(string $query, ...)`: Returns `ClassificationOccupationSearchResult[]`.
- `autocomplete(string $query, ...)`: Returns suggestions optimized for short input.

### Value Objects

- `ClassificationOccupation\Model\ClassificationOccupationCode`: Immutable object containing code, title, level, parent, description, and metadata.
- `ClassificationOccupation\Model\ClassificationOccupationSearchResult`: Contains matched code, term, score, and match type.

## Licensing and Attribution

This package is licensed under the MIT License. Users should review upstream source data licensing and attribution requirements for SOC, UK SOC, and ISCO. Source metadata is preserved in `source_file` and `source_sheet` fields.
