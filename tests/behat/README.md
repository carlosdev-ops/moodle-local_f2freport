# Behat Tests for local_f2freport

This directory contains Behat functional tests for the Face-to-face Report plugin.

## Test Coverage

The Behat tests cover the following functionality:

### ✅ Access Control
- **Scenario: Manager can access the face-to-face report**
  - Verifies that users with the `local/f2freport:viewreport` capability can access the report
- **Scenario: Student cannot access the face-to-face report**
  - Verifies that users without the capability are denied access

### ✅ Course Filtering
- **Scenario: Filter sessions by course name**
  - Tests filtering by a single course keyword
- **Scenario: Filter sessions by multiple course keywords**
  - Tests filtering with comma-separated course names
  - Uses logical operators (AND, OR, NOT)

### ✅ Date Filtering
- **Scenario: Filter sessions by date range**
  - Tests start date and end date filters
- **Scenario: Filter to show only upcoming sessions**
  - Tests the "Show only upcoming sessions" checkbox
  - Verifies only future sessions are displayed

### ✅ Participants Display
- **Scenario: View participants list for a session**
  - Tests clicking the "View participants" link
  - Verifies participant names and statuses are shown
- **Scenario: Navigate back from participants to report**
  - Tests the "Back to report" link

### ✅ Filter Reset
- **Scenario: Reset all filters to default**
  - Tests the "Reset" button functionality
  - Verifies all filter fields are cleared
  - Verifies all sessions are displayed again

### ✅ Additional Features
- **Scenario: Display message when no sessions match filters**
  - Tests the "No sessions to display" message
- **Scenario: Display session count after filtering**
  - Tests the "Showing X session(s)" counter
- **Scenario: Filters auto-submit when changed**
  - Tests the debounced auto-submit functionality
- **Scenario: Navigate to course from report**
  - Tests the "Go to course" link

---

## Files

### `f2freport_basic.feature`
Contains all Gherkin scenarios for testing the plugin's UI functionality.

**Tags:**
- `@local` - Local plugin test
- `@local_f2freport` - Specific to this plugin
- `@javascript` - Requires JavaScript execution

**Total Scenarios:** 13
**Lines:** 173

### `behat_local_f2freport.php`
Contains custom Behat step definitions specific to this plugin.

**Custom Steps:**
- `I am on the f2freport report page` - Navigate to report
- `I am on the f2freport participants page for session "X"` - Navigate to participants
- `I visit "URL"` - Direct URL navigation
- `I should see the f2freport filters` - Verify filter presence
- `the page should have auto reloaded` - Check auto-submit
- `the following facetoface sessions exist:` - Create test sessions
- `the following facetoface signups exist:` - Create test signups

**Lines:** 198

---

## Running the Tests

### Prerequisites

1. **Moodle Behat environment configured**
   ```bash
   cd /var/www/dev
   php admin/tool/behat/cli/init.php
   ```

2. **mod_facetoface plugin installed**
   - The tests require the Face-to-face activity module
   - Install from Moodle plugins directory or manually

### Run All Tests

```bash
# From Moodle root directory
php admin/tool/behat/cli/run.php --tags=@local_f2freport
```

### Run Specific Test

```bash
# Run a specific scenario by name
php admin/tool/behat/cli/run.php --name="Manager can access the face-to-face report"

# Run a specific scenario by tag
php admin/tool/behat/cli/run.php --tags=@local_f2freport_access

# Run multiple related scenarios
php admin/tool/behat/cli/run.php --tags=@local_f2freport_filter_course
```

### Run with Different Browsers

```bash
# Chrome (default)
php admin/tool/behat/cli/run.php --tags=@local_f2freport

# Firefox
php admin/tool/behat/cli/run.php --tags=@local_f2freport --profile=firefox
```

### Debug Mode

```bash
# Run with verbose output
php admin/tool/behat/cli/run.php --tags=@local_f2freport -vvv

# Stop on first failure
php admin/tool/behat/cli/run.php --tags=@local_f2freport --stop-on-failure
```

---

## Test Data

The tests use the following test data:

### Users
- **manager1** - Site manager with viewreport capability
- **student1, student2** - Regular students (no report access)
- **teacher1** - Course teacher

### Courses
- **Course Math** (C1)
- **Course Physics** (C2)
- **Course Chemistry** (C3)

### Face-to-face Activities
- **Math Workshop** (in Course Math)
- **Physics Seminar** (in Course Physics)

### Sessions
Created dynamically in scenarios with custom dates.

### Signups
Created dynamically to test participant listing.

---

## Troubleshooting

### Common Issues

**Issue: "Capability local/f2freport:viewreport does not exist"**
```bash
# Purge caches and reinitialize Behat
php admin/cli/purge_caches.php
php admin/tool/behat/cli/init.php
```

**Issue: "mod_facetoface not found"**
```bash
# Install the Face-to-face module first
# Download from: https://moodle.org/plugins/mod_facetoface
```

**Issue: "Behat tests failing after code changes"**
```bash
# Clear Behat cache and re-run init
rm -rf /path/to/moodledata/behat/*
php admin/tool/behat/cli/init.php
```

**Issue: "JavaScript not working in tests"**
```bash
# Ensure Selenium/Chrome driver is running
# Check config.php has correct behat_wwwroot and behat_dataroot
```

---

## Adding New Tests

To add new test scenarios:

1. **Add scenario to `f2freport_basic.feature`:**
   ```gherkin
   @local_f2freport_my_test
   Scenario: Test description
     Given I log in as "manager1"
     When I do something
     Then I should see "Expected result"
   ```

2. **Add custom step (if needed) to `behat_local_f2freport.php`:**
   ```php
   /**
    * @Given /^I do something special$/
    */
   public function i_do_something_special() {
       // Implementation
   }
   ```

3. **Re-initialize Behat:**
   ```bash
   php admin/tool/behat/cli/init.php
   ```

4. **Run your new test:**
   ```bash
   php admin/tool/behat/cli/run.php --tags=@local_f2freport_my_test
   ```

---

## Test Maintenance

### When to Update Tests

- After adding new features to the plugin
- After modifying UI elements (forms, tables, links)
- After changing capabilities or permissions
- After updating Moodle version (may require step adjustments)

### Best Practices

1. **Tag scenarios appropriately** - Use specific tags for easy filtering
2. **Keep scenarios independent** - Each scenario should work standalone
3. **Use meaningful names** - Scenario names should describe the test clearly
4. **Avoid hardcoded IDs** - Use dynamic data creation
5. **Test edge cases** - Include tests for error conditions and empty states

---

## Continuous Integration

These tests can be integrated into CI/CD pipelines:

### GitHub Actions Example

```yaml
name: Behat Tests

on: [push, pull_request]

jobs:
  behat:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup Moodle
        run: |
          # Setup Moodle test environment
      - name: Run Behat
        run: |
          php admin/tool/behat/cli/run.php --tags=@local_f2freport
```

---

## Performance

**Average test execution time:**
- Single scenario: ~10-30 seconds
- Full suite (13 scenarios): ~3-5 minutes
- With mod_facetoface setup: +30 seconds

**Optimization tips:**
- Run tests in parallel if Moodle supports it
- Use `@javascript` tag only when necessary
- Minimize database operations in Background steps

---

## Related Documentation

- [Moodle Behat Documentation](https://moodledev.io/general/development/tools/behat)
- [Gherkin Syntax Reference](https://cucumber.io/docs/gherkin/reference/)
- [Behat Best Practices](https://moodledev.io/general/development/tools/behat/writing)

---

**Last Updated:** 2025-12-06
**Plugin Version:** v1.2.0
