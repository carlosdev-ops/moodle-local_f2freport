<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Behat custom steps for local_f2freport plugin.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Custom Behat steps for the face-to-face report plugin.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_f2freport extends behat_base {

    /**
     * Navigate to the f2freport report page.
     *
     * @Given /^I am on the f2freport report page$/
     */
    public function i_am_on_the_f2freport_report_page() {
        $this->visit('/local/f2freport/report.php');
    }

    /**
     * Navigate to the f2freport participants page for a specific session.
     *
     * @Given /^I am on the f2freport participants page for session "(?P<sessionid_string>(?:[^"]|\\")*)"$/
     * @param string $sessionid The session ID
     */
    public function i_am_on_the_f2freport_participants_page($sessionid) {
        $this->visit('/local/f2freport/participants.php?sessionid=' . $sessionid);
    }

    /**
     * Visit a URL directly (bypass permission checks for testing).
     *
     * @When /^I visit "(?P<url_string>(?:[^"]|\\")*)"$/
     * @param string $url The URL to visit
     */
    public function i_visit($url) {
        $this->getSession()->visit($this->locate_path($url));
    }

    /**
     * Check that the f2freport filters are visible.
     *
     * @Then /^I should see the f2freport filters$/
     */
    public function i_should_see_the_f2freport_filters() {
        $this->execute('behat_general::should_exist', ['Course', 'field']);
        $this->execute('behat_general::should_exist', ['Start date', 'field']);
        $this->execute('behat_general::should_exist', ['End date', 'field']);
    }

    /**
     * Check that the page auto-reloaded (URL params changed).
     *
     * @Then /^the page should have auto reloaded$/
     */
    public function the_page_should_have_auto_reloaded() {
        // Wait for page to reload.
        $this->getSession()->wait(1000);

        // Check if URL contains query parameters (indicating form submission).
        $currenturl = $this->getSession()->getCurrentUrl();
        if (strpos($currenturl, '?') === false) {
            throw new \Exception('Page did not auto-reload with query parameters');
        }
    }

    /**
     * Create face-to-face sessions with specific dates.
     *
     * @Given /^the following facetoface sessions exist:$/
     * @param \Behat\Gherkin\Node\TableNode $table
     */
    public function the_following_facetoface_sessions_exist(\Behat\Gherkin\Node\TableNode $table) {
        global $DB;

        foreach ($table->getHash() as $data) {
            // Get the facetoface activity.
            $facetoface = $DB->get_record('facetoface', ['idnumber' => $data['facetoface']], '*', MUST_EXIST);

            // Create a new session.
            $session = new stdClass();
            $session->facetoface = $facetoface->id;
            $session->capacity = 10;
            $session->allowoverbook = 0;
            $session->duration = 0;
            $session->normalcost = 0;
            $session->discountcost = 0;
            $session->timecreated = time();
            $session->timemodified = time();
            $sessionid = $DB->insert_record('facetoface_sessions', $session);

            // Parse and create session dates.
            $datestring = $data['sessiondates'];

            // Handle special date keywords.
            if ($datestring === '##yesterday##') {
                $timestart = strtotime('yesterday 09:00:00');
                $timefinish = strtotime('yesterday 17:00:00');
            } else if ($datestring === '##tomorrow##') {
                $timestart = strtotime('tomorrow 09:00:00');
                $timefinish = strtotime('tomorrow 17:00:00');
            } else if (strpos($datestring, ' to ') !== false) {
                // Date range: "2025-12-15 to 2025-12-16".
                list($start, $end) = explode(' to ', $datestring);
                $timestart = strtotime(trim($start) . ' 09:00:00');
                $timefinish = strtotime(trim($end) . ' 17:00:00');
            } else {
                // Single date.
                $timestart = strtotime($datestring . ' 09:00:00');
                $timefinish = strtotime($datestring . ' 17:00:00');
            }

            // Create session date.
            $sessiondate = new stdClass();
            $sessiondate->sessionid = $sessionid;
            $sessiondate->timestart = $timestart;
            $sessiondate->timefinish = $timefinish;
            $DB->insert_record('facetoface_sessions_dates', $sessiondate);
        }
    }

    /**
     * Create face-to-face signups for users.
     *
     * @Given /^the following facetoface signups exist:$/
     * @param \Behat\Gherkin\Node\TableNode $table
     */
    public function the_following_facetoface_signups_exist(\Behat\Gherkin\Node\TableNode $table) {
        global $DB;

        foreach ($table->getHash() as $data) {
            // Get user.
            $user = $DB->get_record('user', ['username' => $data['user']], '*', MUST_EXIST);
            $sessionid = $data['session'];

            // Create signup.
            $signup = new stdClass();
            $signup->sessionid = $sessionid;
            $signup->userid = $user->id;
            $signup->mailedreminder = 0;
            $signup->notificationtype = 0;
            $signup->discountcode = '';
            $signupid = $DB->insert_record('facetoface_signups', $signup);

            // Create signup status.
            $status = new stdClass();
            $status->signupid = $signupid;

            // Map status string to status code.
            $statuscodes = [
                'user_cancelled' => 10,
                'session_cancelled' => 20,
                'declined' => 30,
                'requested' => 40,
                'approved' => 50,
                'waitlisted' => 60,
                'booked' => 70,
                'no_show' => 80,
                'partially_attended' => 90,
                'fully_attended' => 100,
            ];

            $status->statuscode = $statuscodes[$data['status']] ?? 70; // Default to booked.
            $status->superceded = 0;
            $status->grade = null;
            $status->note = '';
            $status->advice = '';
            $status->createdby = 2; // Admin.
            $status->timecreated = time();
            $DB->insert_record('facetoface_signups_status', $status);
        }
    }
}
