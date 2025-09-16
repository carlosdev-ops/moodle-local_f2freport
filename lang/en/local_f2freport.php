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
 * English language strings for the face-to-face report.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Face-to-face sessions report';
$string['trainingreporttitle'] = 'Training sessions';
$string['trainingreportheading'] = 'Training sessions';
$string['filtercourse'] = 'Course';
$string['allcourses'] = 'All courses';
$string['futureonly'] = 'Show future sessions only';
$string['filter'] = 'Filter';
$string['reset'] = 'Reset';
$string['city'] = 'City';
$string['venue'] = 'Venue';
$string['room'] = 'Room';
$string['timestart'] = 'Start';
$string['timefinish'] = 'Finish';
$string['courseid'] = 'Course ID';
$string['sessionid'] = 'Session ID';
$string['coursename'] = 'Course';
$string['totalparticipants'] = 'Participants';
$string['notrainer'] = 'No trainer';
$string['trainer'] = 'Trainer';
$string['notspecified'] = 'Not specified';
$string['notapplicable'] = '—';
$string['invalidcourse'] = 'Invalid course selected. Showing all courses.';
$string['nosessions'] = 'No sessions to display with current filters.';
$string['filters'] = 'Filters';
$string['showingcount'] = 'Showing {$a} session(s)';
$string['missingfields'] = 'Required custom fields (location/venue/room) were not found. Please check Face-to-face configuration.';
$string['f2freport:viewreport'] = 'View Face-to-face report';

// New settings.
$string['settings_columns'] = 'Columns to display';
$string['settings_columns_desc'] = 'Select which columns will be shown in the sessions table.';
$string['settings_aliases_city'] = 'City field aliases';
$string['settings_aliases_city_desc'] = 'Comma-separated shortnames/names treated as “City” (e.g., city, ville, location).';
$string['settings_aliases_venue'] = 'Venue field aliases';
$string['settings_aliases_venue_desc'] = 'Comma-separated shortnames/names treated as “Venue” (e.g., venue, lieu, building, site, centre, center, campus).';
$string['settings_aliases_room'] = 'Room field aliases';
$string['settings_aliases_room_desc'] = 'Comma-separated shortnames/names treated as “Room” (e.g., room, salle, classroom, roomnumber).';
$string['settings_pagesize'] = 'Rows per page';
$string['settings_pagesize_desc'] = 'Default number of rows displayed per page (e.g., 25).';
$string['datefrom'] = 'Start date';
$string['dateto'] = 'End date';
$string['showcustomcols'] = 'Show custom columns';
$string['showcustomcols_desc'] = 'If checked, the report will include additional custom columns.';
$string['datesswapped'] = 'Start date was after end date, dates have been automatically swapped.';
$string['privacy:metadata'] = 'The Face-to-face report plugin does not store any personal data itself. It only displays data from other Moodle plugins.';
$string['gotocourse'] = 'Go to course';
$string['filter_startdate'] = 'Start date';
$string['filter_enddate'] = 'End date';
$string['filter_upcoming'] = 'Show only upcoming sessions';
$string['filter_course']   = 'Course';
