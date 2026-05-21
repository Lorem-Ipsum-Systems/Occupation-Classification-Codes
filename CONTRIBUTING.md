# Contributing to Occupation Classification Codes

Thank you for your interest in contributing!

## How to contribute

1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Implement your changes.
4. Ensure all tests pass: `composer quality`.
5. Submit a pull request.

## Development Rules

- Follow PSR-12 coding standards.
- Add tests for every new feature or bugfix.
- Maintain the version-aware data layout: `data/{system}/{version}/`.
- Do not add external dependencies without strong justification.
- Keep the public API framework-agnostic.

## Data Updates

If you are adding a new classification system or a new version:
1. Place the NDJSON files under `data/{system}/{version}/`.
2. Update `ClassificationOccupationRegistry::fromDefaultData()` to include the new dataset in the manifest.
3. Update `DefaultOccupationDataLoader` if the new system requires custom normalization logic.
4. Add data integrity tests in `tests/DataIntegrityTest.php`.
