@local @local_f2freport @javascript
Feature: Face-to-face report basic functionality
  In order to view and filter face-to-face sessions
  As a manager
  I need to be able to access the report and use filters

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | One      | manager1@example.com |
      | student1 | Student   | One      | student1@example.com |
      | student2 | Student   | Two      | student2@example.com |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname        | shortname | category |
      | Course Math     | C1        | 0        |
      | Course Physics  | C2        | 0        |
      | Course Chemistry| C3        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student1 | C2     | student        |
    And the following "activities" exist:
      | activity   | name              | intro                    | course | idnumber |
      | facetoface | Math Workshop     | Math workshop session    | C1     | f2f1     |
      | facetoface | Physics Seminar   | Physics seminar session  | C2     | f2f2     |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |

  @local_f2freport_access
  Scenario: Manager can access the face-to-face report
    Given I log in as "manager1"
    When I navigate to "Reports > Face-to-face sessions report" in site administration
    Then I should see "Training sessions"
    And I should not see "You do not have permission"

  @local_f2freport_no_access
  Scenario: Student cannot access the face-to-face report
    Given I log in as "student1"
    When I visit "/local/f2freport/report.php"
    Then I should see "You do not have the required permission"

  @local_f2freport_filter_course
  Scenario: Filter sessions by course name
    Given I log in as "manager1"
    And I am on the f2freport report page
    When I set the field "Course" to "Math"
    And I wait "1" seconds
    Then I should see "Math Workshop"
    And I should not see "Physics Seminar"

  @local_f2freport_filter_course_multiple
  Scenario: Filter sessions by multiple course keywords
    Given I log in as "manager1"
    And I am on the f2freport report page
    When I set the field "Course" to "Math, Physics"
    And I wait "1" seconds
    Then I should see "Math Workshop"
    And I should see "Physics Seminar"
    And I should not see "Chemistry"

  @local_f2freport_filter_dates
  Scenario: Filter sessions by date range
    Given I log in as "manager1"
    And I am on the f2freport report page
    And the following facetoface sessions exist:
      | facetoface | sessiondates            |
      | f2f1       | 2025-12-15 to 2025-12-16 |
      | f2f2       | 2026-01-20 to 2026-01-21 |
    When I set the field "Start date" to "2025-12-01"
    And I set the field "End date" to "2025-12-31"
    And I wait "1" seconds
    Then I should see "Math Workshop"
    And I should not see "Physics Seminar"

  @local_f2freport_filter_upcoming
  Scenario: Filter to show only upcoming sessions
    Given I log in as "manager1"
    And I am on the f2freport report page
    And the following facetoface sessions exist:
      | facetoface | sessiondates            |
      | f2f1       | ##yesterday##           |
      | f2f2       | ##tomorrow##            |
    When I set the following fields to these values:
      | Show only upcoming sessions | 1 |
    And I wait "1" seconds
    Then I should see "Physics Seminar"
    And I should not see "Math Workshop"

  @local_f2freport_reset_filters
  Scenario: Reset all filters to default
    Given I log in as "manager1"
    And I am on the f2freport report page
    When I set the field "Course" to "Math"
    And I set the field "Show only upcoming sessions" to "1"
    And I wait "1" seconds
    And I should see "Math Workshop"
    When I click on "Reset" "button"
    And I wait "1" seconds
    Then the field "Course" matches value ""
    And the field "Show only upcoming sessions" matches value "0"
    And I should see "Math Workshop"
    And I should see "Physics Seminar"

  @local_f2freport_participants
  Scenario: View participants list for a session
    Given I log in as "manager1"
    And I am on the f2freport report page
    And the following facetoface sessions exist:
      | facetoface | sessiondates            |
      | f2f1       | 2025-12-15 to 2025-12-16 |
    And the following facetoface signups exist:
      | user     | session | status |
      | student1 | 1       | booked |
      | student2 | 1       | booked |
    When I click on "View participants list" "link" in the "Math Workshop" "table_row"
    Then I should see "Participants - Course Math"
    And I should see "Student One"
    And I should see "Student Two"
    And I should see "Booked"

  @local_f2freport_participants_back
  Scenario: Navigate back from participants to report
    Given I log in as "manager1"
    And I am on the f2freport participants page for session "1"
    When I click on "Back to report" "link"
    Then I should see "Training sessions"
    And I should see the f2freport filters

  @local_f2freport_no_sessions
  Scenario: Display message when no sessions match filters
    Given I log in as "manager1"
    And I am on the f2freport report page
    When I set the field "Course" to "NonExistentCourse12345"
    And I wait "1" seconds
    Then I should see "No sessions to display with current filters"

  @local_f2freport_session_count
  Scenario: Display session count after filtering
    Given I log in as "manager1"
    And I am on the f2freport report page
    And the following facetoface sessions exist:
      | facetoface | sessiondates            |
      | f2f1       | 2025-12-15 to 2025-12-16 |
      | f2f1       | 2025-12-20 to 2025-12-21 |
      | f2f2       | 2026-01-15 to 2026-01-16 |
    When I set the field "Course" to "Math"
    And I wait "1" seconds
    Then I should see "Showing 2 session(s)"

  @local_f2freport_auto_submit
  Scenario: Filters auto-submit when changed
    Given I log in as "manager1"
    And I am on the f2freport report page
    When I set the field "Show only upcoming sessions" to "1"
    And I wait "1" seconds
    Then the page should have auto reloaded
    And the field "Show only upcoming sessions" matches value "1"

  @local_f2freport_course_link
  Scenario: Navigate to course from report
    Given I log in as "manager1"
    And I am on the f2freport report page
    And the following facetoface sessions exist:
      | facetoface | sessiondates            |
      | f2f1       | 2025-12-15 to 2025-12-16 |
    When I click on "Go to course" "link" in the "Math Workshop" "table_row"
    Then I should see "Course Math"
    And I should see "Math Workshop"
