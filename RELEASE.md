# Release Checklist

Follow this checklist when releasing a new version of the package.

## Pre-release

- [ ] Run `composer validate`
- [ ] Run `composer quality` (includes tests and data integrity checks)
- [ ] Confirm version-aware data files exist in `data/`
- [ ] Confirm data files are NOT export-ignored in `.gitattributes`
- [ ] Update `CHANGELOG.md` with the new version and date
- [ ] Commit all changes

## Release

- [ ] Create git tag: `git tag v1.0.0`
- [ ] Push tag: `git push origin v1.0.0`
- [ ] Confirm Packagist sees the new version
- [ ] Verify the package can be installed via `composer require`
