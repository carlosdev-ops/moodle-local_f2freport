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
 * Page to display participants of a face-to-face session.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_f2freport\participants_manager;

global $DB, $OUTPUT, $PAGE;

$context = context_system::instance();
require_login();
require_capability('local/f2freport:viewreport', $context);

$sessionid = required_param('sessionid', PARAM_INT);

$pageurl = new moodle_url('/local/f2freport/participants.php', ['sessionid' => $sessionid]);
$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('report');

// Get session information
$sql = "SELECT fs.id, fs.facetoface, c.id as courseid, c.fullname as coursename,
               fsd.timestart, fsd.timefinish
        FROM {facetoface_sessions} fs
        JOIN {facetoface} f ON f.id = fs.facetoface
        JOIN {course} c ON c.id = f.course
        LEFT JOIN {facetoface_sessions_dates} fsd ON fsd.sessionid = fs.id
        WHERE fs.id = ?
        ORDER BY fsd.timestart ASC";

$session = $DB->get_record_sql($sql, [$sessionid], IGNORE_MULTIPLE);

if (!$session) {
    throw new moodle_exception('invalidsession', 'local_f2freport');
}

$PAGE->set_title(get_string('participantstitle', 'local_f2freport', $session->coursename));
$PAGE->set_heading(get_string('participantsheading', 'local_f2freport'));

// Get participants and their status
$participants = participants_manager::get_session_participants($sessionid);

echo $OUTPUT->header();

// Session information
echo html_writer::tag('h3', get_string('sessioninfo', 'local_f2freport'));
echo html_writer::start_tag('div', ['class' => 'session-info mb-3']);
echo html_writer::tag('p', get_string('course', 'core') . ': ' . format_string($session->coursename));
echo html_writer::tag('p', get_string('sessionid', 'local_f2freport') . ': ' . $session->id);
if ($session->timestart) {
    echo html_writer::tag('p', get_string('timestart', 'local_f2freport') . ': ' .
        userdate($session->timestart, get_string('strftimedatetime', 'langconfig')));
}
echo html_writer::end_tag('div');

// Participants table
if (!empty($participants)) {
    echo html_writer::tag('h3', get_string('participantslist', 'local_f2freport') . ' (' . count($participants) . ')');

    $table = new html_table();
    $table->head = [
        get_string('fullname'),
        get_string('email'),
        get_string('status', 'local_f2freport'),
        get_string('signuptime', 'local_f2freport')
    ];
    $table->attributes['class'] = 'table table-striped';

    foreach ($participants as $participant) {
        $statustext = participants_manager::get_status_text($participant->statuscode);
        $signuptime = $participant->timecreated ? userdate($participant->timecreated) : '-';

        $table->data[] = [
            fullname($participant),
            $participant->email,
            $statustext,
            $signuptime
        ];
    }

    echo html_writer::table($table);
} else {
    echo html_writer::tag('p', get_string('noparticipants', 'local_f2freport'), ['class' => 'alert alert-info']);
}

// Back link
$backurl = new moodle_url('/local/f2freport/report.php');
echo html_writer::link($backurl, get_string('backtoreport', 'local_f2freport'), ['class' => 'btn btn-secondary mt-3']);

echo $OUTPUT->footer();