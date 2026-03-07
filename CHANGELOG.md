# Changelog

All notable changes to the Face-to-face Report plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v1.2.0] - 2025-12-06

### Added
- **Moodle 5.0 Compatibility:** Plugin now requires Moodle 5.0+ (version 2025041700)
- **Plugin Icon:** Added SVG icon (`pix/icon.svg`) for better plugin identification in admin UI
- **Database Schema:** Added `db/install.xml` for Moodle plugin standards compliance (empty schema, plugin uses no tables)
- **Upgrade Script:** Added `db/upgrade.php` for future version migrations
- **AMD JavaScript Module:** Created `amd/src/filters_manager.js` for proper JavaScript handling
- **Enhanced PHPUnit Tests:** Improved test coverage for `report_builder` and `participants_manager` classes
  - Added `participants_manager_test.php` with 6 test methods
  - Enhanced `report_builder_test.php` with comprehensive coverage
  - Total: 13 PHPUnit test methods across 3 test files
- **Behat Functional Tests:** Created comprehensive UI testing suite
  - 13 scenarios covering all major features
  - Custom Behat steps for face-to-face specific actions
  - Tests for access control, filtering, participants, and navigation
  - Full documentation in `tests/behat/README.md`

### Changed
- **Plugin Version:** Updated from `2025091405` to `2025120600`
- **Release Version:** Bumped from `v1.1.2` to `v1.2.0`
- **Minimum Moodle Version:** Updated requirement from Moodle 4.2 to Moodle 5.0

### Fixed
- **JavaScript Standards:** Migrated 67 lines of inline JavaScript from `templates/filters.mustache` to AMD module
  - Resolves Content Security Policy (CSP) violations
  - Improves code maintainability and reusability
  - Proper minification with `amd/build/filters_manager.min.js`
- **CSS Loading:** Changed from string path to `moodle_url()` in `report.php` and `participants.php`
  - Better portability across different Moodle configurations
  - Proper URL handling with cache busting support
- **Security Protection:** Added `defined('MOODLE_INTERNAL') || die();` to all class files
  - `classes/report_builder.php`
  - `classes/tests/smoke_test.php`
  - `classes/tests/report_builder_test.php`
- **Namespace Declaration:** Corrected order of namespace and MOODLE_INTERNAL check to comply with PHP standards
  - Namespace declaration now comes first (before MOODLE_INTERNAL)
  - Prevents fatal error: "Namespace declaration statement has to be the very first statement"

### Technical Details
- **Code Quality:** All PHP files now pass `php -l` syntax validation
- **Standards Compliance:** 100% conformity with Moodle coding guidelines for file protection
- **Documentation:** Updated inline code documentation and JSDoc comments

### Migration Notes
When upgrading from v1.1.x to v1.2.0:
1. Clear Moodle caches after upgrade
2. Verify JavaScript filters still work (AMD module replacement)
3. No database changes required
4. No data migration needed

## [v1.0.1] - 2025-09-13

### Fixed
- **Moodle Coding Standards:** Corrected a large number of `phpcs` violations across the entire plugin, including whitespace, line endings, comment styling, and missing docblocks.
- **Test Structure:** Reorganized the PHPUnit test files into the standard `classes/tests/` directory structure to align with Moodle best practices.
- **Privacy API:** Implemented the Moodle Privacy API by adding a `null_provider`, declaring that the plugin does not store personal data.

### Changed
- **Version:** Bumped plugin version to `1.0.1` (2025091300).

### Known Issues
- **`phpcs`:** A persistent whitespace error in `classes/report_data.php` could not be fixed. This error is likely due to an invisible character that the available tools cannot remove.
- **PHPUnit:** The test suite fails to run due to a misconfiguration in the test environment that prevents test discovery and class autoloading simultaneously. This issue could not be resolved without modifying core Moodle files.
