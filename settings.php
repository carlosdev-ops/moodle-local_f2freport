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
 * Admin settings for the face-to-face report plugin.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_f2freport', get_string('pluginname', 'local_f2freport'));

    if ($ADMIN->fulltree) {
        // 1) Columns to display (multiple checkboxes).
        $colopts = [
            'courseid'          => get_string('courseid', 'local_f2freport'),
            'sessionid'         => get_string('sessionid', 'local_f2freport'),
            'timestart'         => get_string('timestart', 'local_f2freport'),
            'timefinish'        => get_string('timefinish', 'local_f2freport'),
            'city'              => get_string('city', 'local_f2freport'),
            'venue'             => get_string('venue', 'local_f2freport'),
            'room'              => get_string('room', 'local_f2freport'),
            'totalparticipants' => get_string('totalparticipants', 'local_f2freport'), // Displays "present / signed up".
            'coursename'    => get_string('coursename', 'local_f2freport'),
        ];
        $settings->add(new admin_setting_configmulticheckbox(
            'local_f2freport/columns',
            get_string('settings_columns', 'local_f2freport'),
            get_string('settings_columns_desc', 'local_f2freport'),
            array_fill_keys(array_keys($colopts), 1), // All checked by default.
            $colopts
        ));

        // 2) Aliases for City/Venue/Room (CSV).
        $settings->add(new admin_setting_configtext(
            'local_f2freport/aliases_city',
            get_string('settings_aliases_city', 'local_f2freport'),
            get_string('settings_aliases_city_desc', 'local_f2freport'),
            'city,ville,location',
            PARAM_TEXT
        ));
        $settings->add(new admin_setting_configtext(
            'local_f2freport/aliases_venue',
            get_string('settings_aliases_venue', 'local_f2freport'),
            get_string('settings_aliases_venue_desc', 'local_f2freport'),
            'venue,lieu,building,site,centre,center,campus',
            PARAM_TEXT
        ));
        $settings->add(new admin_setting_configtext(
            'local_f2freport/aliases_room',
            get_string('settings_aliases_room', 'local_f2freport'),
            get_string('settings_aliases_room_desc', 'local_f2freport'),
            'room,salle,classroom,roomnumber',
            PARAM_TEXT
        ));

        // 3) Page size.
        $settings->add(new admin_setting_configtext(
            'local_f2freport/pagesize',
            get_string('settings_pagesize', 'local_f2freport'),
            get_string('settings_pagesize_desc', 'local_f2freport'),
            25,
            PARAM_INT
        ));
    }

    $ADMIN->add('localplugins', $settings);
}
