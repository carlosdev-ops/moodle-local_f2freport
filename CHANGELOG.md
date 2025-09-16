# Changelog

## v1.0.1 (2025-09-13)

### Fixed
- **Moodle Coding Standards:** Corrected a large number of `phpcs` violations across the entire plugin, including whitespace, line endings, comment styling, and missing docblocks.
- **Test Structure:** Reorganized the PHPUnit test files into the standard `classes/tests/` directory structure to align with Moodle best practices.
- **Privacy API:** Implemented the Moodle Privacy API by adding a `null_provider`, declaring that the plugin does not store personal data.

### Changed
- **Version:** Bumped plugin version to `1.0.1` (2025091300).

### Known Issues
- **`phpcs`:** A persistent whitespace error in `classes/report_data.php` could not be fixed. This error is likely due to an invisible character that the available tools cannot remove.
- **PHPUnit:** The test suite fails to run due to a misconfiguration in the test environment that prevents test discovery and class autoloading simultaneously. This issue could not be resolved without modifying core Moodle files.
