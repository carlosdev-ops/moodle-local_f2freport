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
 * Unit tests for the participants_manager class.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_f2freport\tests;

defined('MOODLE_INTERNAL') || die();

use local_f2freport\participants_manager;

/**
 * Unit tests for the participants_manager class.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_f2freport\participants_manager
 */
final class participants_manager_test extends \advanced_testcase {

    /**
     * Setup before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test get_session_participants with no participants.
     *
     * @covers \local_f2freport\participants_manager::get_session_participants
     */
    public function test_get_session_participants_empty(): void {
        global $DB;

        // Create a course and facetoface activity.
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', ['course' => $course->id]);

        // Create a session.
        $sessionid = $DB->insert_record('facetoface_sessions', (object)[
            'facetoface' => $facetoface->id,
            'capacity' => 10,
        ]);

        // Get participants for empty session.
        $participants = participants_manager::get_session_participants($sessionid);

        $this->assertIsArray($participants);
        $this->assertEmpty($participants);
    }

    /**
     * Test get_session_participants with multiple participants.
     *
     * @covers \local_f2freport\participants_manager::get_session_participants
     */
    public function test_get_session_participants_with_users(): void {
        global $DB;

        // Create a course and facetoface activity.
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', ['course' => $course->id]);

        // Create a session.
        $sessionid = $DB->insert_record('facetoface_sessions', (object)[
            'facetoface' => $facetoface->id,
            'capacity' => 10,
        ]);

        // Create test users.
        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'Alice', 'lastname' => 'Anderson']);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Bob', 'lastname' => 'Brown']);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'Charlie', 'lastname' => 'Clark']);

        // Enrol users in course.
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);

        // Create signups for users.
        $signup1 = $DB->insert_record('facetoface_signups', (object)[
            'sessionid' => $sessionid,
            'userid' => $user1->id,
        ]);
        $signup2 = $DB->insert_record('facetoface_signups', (object)[
            'sessionid' => $sessionid,
            'userid' => $user2->id,
        ]);
        $signup3 = $DB->insert_record('facetoface_signups', (object)[
            'sessionid' => $sessionid,
            'userid' => $user3->id,
        ]);

        // Create signup statuses (70 = booked, 90 = fully attended).
        $time = time();
        $DB->insert_record('facetoface_signups_status', (object)[
            'signupid' => $signup1,
            'statuscode' => 70, // Booked.
            'timecreated' => $time,
            'superceded' => 0,
        ]);
        $DB->insert_record('facetoface_signups_status', (object)[
            'signupid' => $signup2,
            'statuscode' => 90, // Fully attended.
            'timecreated' => $time,
            'superceded' => 0,
        ]);
        $DB->insert_record('facetoface_signups_status', (object)[
            'signupid' => $signup3,
            'statuscode' => 70, // Booked.
            'timecreated' => $time,
            'superceded' => 0,
        ]);

        // Get participants.
        $participants = participants_manager::get_session_participants($sessionid);

        $this->assertIsArray($participants);
        $this->assertCount(3, $participants);

        // Verify user data is present.
        $userids = array_column($participants, 'id');
        $this->assertContains($user1->id, $userids);
        $this->assertContains($user2->id, $userids);
        $this->assertContains($user3->id, $userids);

        // Verify status codes are present.
        $statuscodes = array_column($participants, 'statuscode');
        $this->assertContains(70, $statuscodes); // Booked.
        $this->assertContains(90, $statuscodes); // Fully attended.
    }

    /**
     * Test get_session_participants excludes deleted users.
     *
     * @covers \local_f2freport\participants_manager::get_session_participants
     */
    public function test_get_session_participants_excludes_deleted(): void {
        global $DB;

        // Create a course and facetoface activity.
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', ['course' => $course->id]);

        // Create a session.
        $sessionid = $DB->insert_record('facetoface_sessions', (object)[
            'facetoface' => $facetoface->id,
            'capacity' => 10,
        ]);

        // Create test users.
        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'Active', 'lastname' => 'User']);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Deleted', 'lastname' => 'User']);

        // Enrol users.
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // Create signups.
        $signup1 = $DB->insert_record('facetoface_signups', (object)[
            'sessionid' => $sessionid,
            'userid' => $user1->id,
        ]);
        $signup2 = $DB->insert_record('facetoface_signups', (object)[
            'sessionid' => $sessionid,
            'userid' => $user2->id,
        ]);

        // Create statuses.
        $time = time();
        $DB->insert_record('facetoface_signups_status', (object)[
            'signupid' => $signup1,
            'statuscode' => 70,
            'timecreated' => $time,
            'superceded' => 0,
        ]);
        $DB->insert_record('facetoface_signups_status', (object)[
            'signupid' => $signup2,
            'statuscode' => 70,
            'timecreated' => $time,
            'superceded' => 0,
        ]);

        // Mark user2 as deleted.
        $DB->set_field('user', 'deleted', 1, ['id' => $user2->id]);

        // Get participants - should only return active user.
        $participants = participants_manager::get_session_participants($sessionid);

        $this->assertCount(1, $participants);
        $participant = reset($participants);
        $this->assertEquals($user1->id, $participant->id);
    }

    /**
     * Test get_participants_by_status groups correctly.
     *
     * @covers \local_f2freport\participants_manager::get_participants_by_status
     */
    public function test_get_participants_by_status(): void {
        global $DB;

        // Create a course and facetoface activity.
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', ['course' => $course->id]);

        // Create a session.
        $sessionid = $DB->insert_record('facetoface_sessions', (object)[
            'facetoface' => $facetoface->id,
            'capacity' => 10,
        ]);

        // Create users with different statuses.
        $bookeduser = $this->getDataGenerator()->create_user();
        $attendeduser = $this->getDataGenerator()->create_user();
        $waitlisteduser = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($bookeduser->id, $course->id);
        $this->getDataGenerator()->enrol_user($attendeduser->id, $course->id);
        $this->getDataGenerator()->enrol_user($waitlisteduser->id, $course->id);

        // Create signups.
        $signup1 = $DB->insert_record('facetoface_signups', (object)[
            'sessionid' => $sessionid,
            'userid' => $bookeduser->id,
        ]);
        $signup2 = $DB->insert_record('facetoface_signups', (object)[
            'sessionid' => $sessionid,
            'userid' => $attendeduser->id,
        ]);
        $signup3 = $DB->insert_record('facetoface_signups', (object)[
            'sessionid' => $sessionid,
            'userid' => $waitlisteduser->id,
        ]);

        // Create different status codes.
        $time = time();
        $DB->insert_record('facetoface_signups_status', (object)[
            'signupid' => $signup1,
            'statuscode' => 70, // Booked.
            'timecreated' => $time,
            'superceded' => 0,
        ]);
        $DB->insert_record('facetoface_signups_status', (object)[
            'signupid' => $signup2,
            'statuscode' => 100, // Fully attended.
            'timecreated' => $time,
            'superceded' => 0,
        ]);
        $DB->insert_record('facetoface_signups_status', (object)[
            'signupid' => $signup3,
            'statuscode' => 30, // Waitlisted.
            'timecreated' => $time,
            'superceded' => 0,
        ]);

        // Get participants grouped by status.
        $grouped = participants_manager::get_participants_by_status($sessionid);

        // Verify grouping.
        $this->assertIsArray($grouped);
        $this->assertArrayHasKey(100, $grouped); // Fully attended.
        $this->assertArrayHasKey(70, $grouped);  // Booked.
        $this->assertArrayHasKey(30, $grouped);  // Waitlisted.

        $this->assertCount(1, $grouped[100]);
        $this->assertCount(1, $grouped[70]);
        $this->assertCount(1, $grouped[30]);

        // Verify correct users in each group.
        $this->assertEquals($attendeduser->id, $grouped[100][0]->id);
        $this->assertEquals($bookeduser->id, $grouped[70][0]->id);
        $this->assertEquals($waitlisteduser->id, $grouped[30][0]->id);
    }

    /**
     * Test get_status_text returns correct labels.
     *
     * @covers \local_f2freport\participants_manager::get_status_text
     */
    public function test_get_status_text(): void {
        // Test known status codes (comparing with language strings).
        $this->assertEquals(get_string('status_booked', 'local_f2freport'),
            participants_manager::get_status_text(70));
        $this->assertEquals(get_string('status_fully_attended', 'local_f2freport'),
            participants_manager::get_status_text(100));
        $this->assertEquals(get_string('status_partially_attended', 'local_f2freport'),
            participants_manager::get_status_text(90));
        $this->assertEquals(get_string('status_waitlisted', 'local_f2freport'),
            participants_manager::get_status_text(60));
        $this->assertEquals(get_string('status_user_cancelled', 'local_f2freport'),
            participants_manager::get_status_text(10));

        // Test unknown status code.
        $this->assertEquals(get_string('status_unknown', 'local_f2freport'),
            participants_manager::get_status_text(999));
    }

    /**
     * Test get_status_badge_class returns correct CSS classes.
     *
     * @covers \local_f2freport\participants_manager::get_status_badge_class
     */
    public function test_get_status_badge_class(): void {
        // Test known status codes.
        $this->assertEquals('badge-success', participants_manager::get_status_badge_class(70));  // Booked.
        $this->assertEquals('badge-success', participants_manager::get_status_badge_class(100)); // Fully attended.
        $this->assertEquals('badge-warning', participants_manager::get_status_badge_class(90));  // Partially attended.
        $this->assertEquals('badge-warning', participants_manager::get_status_badge_class(60));  // Waitlisted.
        $this->assertEquals('badge-secondary', participants_manager::get_status_badge_class(10)); // User cancelled.

        // Test unknown status code - should return default.
        $this->assertEquals('badge-secondary', participants_manager::get_status_badge_class(999));
    }
}
