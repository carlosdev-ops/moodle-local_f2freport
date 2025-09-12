@local @local_f2freport @javascript
Feature: Face-to-face report
  As a manager
  I need to be able to view the face-to-face report

  Scenario: View the report with no filters
    Given I log in as "admin"
    And I am on site homepage
    And I navigate to "Face-to-face sessions report" node in "Site administration > Reports"
    Then I should see "Face-to-face sessions report"
    And I should see "Showing 0 session(s)"
