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
 * Unit tests for the report_builder class.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_f2freport\tests;

require_once(__DIR__ . '/../report_builder.php');

use local_f2freport\report_builder;

/**
 * Unit tests for the report_builder class.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_builder_test extends \advanced_testcase {
    /**
     * Tests the constructor and basic setup.
     * @covers \local_f2freport\report_builder
     */
    public function test_smoke(): void {
        $this->resetAfterTest(true);
        $this->assertTrue(true);
    }

    protected function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Tests the normalize_date method.
     * @covers \local_f2freport\report_builder::normalize_date
     */
    public function test_normalize_date() {
        // Test with a valid array from date_selector.
        $datearray = ['day' => '15', 'month' => '10', 'year' => '2023'];
        $expected = make_timestamp(2023, 10, 15, 0, 0, 0);
        $this->assertEquals($expected, report_builder::normalize_date($datearray));

        // Test with a timestamp integer.
        $timestamp = 1697328000;
        $this->assertEquals($timestamp, report_builder::normalize_date($timestamp));
    }

    /**
     * Tests the parse_aliases method.
     * @covers \local_f2freport\report_builder::parse_aliases
     */
    public function test_parse_aliases() {
        $fallback = ['default'];

        // Test with a standard CSV string.
        $csv = 'one,two,three';
        $expected = ['one', 'two', 'three'];
        $this->assertEquals($expected, report_builder::parse_aliases($csv, $fallback));

        // Test with extra whitespace.
        $csvwithspaces = '  one  , two,three ';
        $this->assertEquals($expected, report_builder::parse_aliases($csvwithspaces, $fallback));
    }

    /**
     * Tests the get_course_options method.
     * @covers \local_f2freport\report_builder::get_course_options
     */
    public function test_get_course_options() {
        // Create two courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Create a facetoface activity in the first course.
        $this->getDataGenerator()->create_module('facetoface', ['course' => $course1->id]);

        $builder = new report_builder();
        $options = $builder->get_course_options();

        // Check that the first course is in the options.
        $this->assertArrayHasKey($course1->id, $options);
        $this->assertEquals(format_string($course1->fullname), $options[$course1->id]);

        // Check that the second course is not in the options.
        $this->assertArrayNotHasKey($course2->id, $options);
    }

    /**
     * Tests the get_field_ids method.
     * @covers \local_f2freport\report_builder::get_field_ids
     */
    public function test_get_field_ids() {
        global $DB;

        // Create some facetoface session fields.
        $cityfieldid = $DB->insert_record('facetoface_session_field', (object)['shortname' => 'city', 'name' => 'City']);
        $venuefieldid = $DB->insert_record('facetoface_session_field', (object)['shortname' => 'building', 'name' => 'Building']);
        $roomfieldid = $DB->insert_record(
            'facetoface_session_field',
            (object)['shortname' => 'room_number', 'name' => 'Room Number']
        );

        // Set the aliases in config.
        set_config('aliases_city', 'city,ville', 'local_f2freport');
        set_config('aliases_venue', 'venue,building', 'local_f2freport');
        set_config('aliases_room', 'room,salle,room_number', 'local_f2freport');

        $builder = new report_builder();
        $fieldids = $builder->get_field_ids();

        $this->assertEquals($cityfieldid, $fieldids['city']);
        $this->assertEquals($venuefieldid, $fieldids['venue']);
        $this->assertEquals($roomfieldid, $fieldids['room']);
    }
}
