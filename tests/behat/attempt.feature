@mod @mod_taskchain
Feature: Students can attempt TaskChain activities, and teachers can see the results
  In order to use chains of Hot Potatoes exercises in Moodle
  As a teacher
  I need students to be able to attempt TaskChain activities, and to see their results

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry     | Teacher  | teacher1@example.com |
      | student1 | Sam       | Student  | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity  | course | idnumber   | name     | sourcefile                                  | outputformat       |
      | taskchain | C1     | taskchain1 | Gap-fill | mod/taskchain/tests/fixtures/hp7_jcloze.jcl | hp_7_jcloze_xml_v7 |
      | taskchain | C1     | taskchain2 | Matching | mod/taskchain/tests/fixtures/hp7_jmatch.jmt | hp_7_jmatch_xml_v7 |

  @javascript
  Scenario: A student completes a JCloze task, and the teacher sees the result
    Given I log in as "student1"
    And I am on the "Gap-fill" TaskChain view page
    When I set the field with xpath "//input[@id='Gap0']" to "pigs"
    And I set the field with xpath "//input[@id='Gap1']" to "wolf"
    And I set the field with xpath "//input[@id='Gap2']" to "after"
    And I press "Check"
    And I wait "3" seconds
    And I log out
    And I log in as "teacher1"
    And I am on the "Gap-fill" TaskChain report page
    Then I should see "Completed"
    And I should see "100%"

  @javascript
  Scenario: A student completes a JMatch task, and the teacher sees the result
    Given I log in as "student1"
    And I am on the "Matching" TaskChain view page
    When I set the field with xpath "//select[@id='R_0']" to "low"
    And I set the field with xpath "//select[@id='R_1']" to "cold"
    And I set the field with xpath "//select[@id='R_2']" to "out"
    And I set the field with xpath "//select[@id='R_3']" to "short"
    And I press "Check"
    And I wait "3" seconds
    And I log out
    And I log in as "teacher1"
    And I am on the "Matching" TaskChain report page
    Then I should see "Completed"
    And I should see "100%"
