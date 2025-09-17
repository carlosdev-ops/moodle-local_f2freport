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
$PAGE->set_heading('');

// Add custom CSS for participants page
$PAGE->requires->css('/local/f2freport/styles.css');

// Get participants grouped by status
$participantsByStatus = participants_manager::get_participants_by_status($sessionid);
$totalParticipants = 0;
foreach ($participantsByStatus as $statusCode => $statusGroup) {
    // Exclude user cancelled participants (status code 10) from total count
    if ($statusCode != 10) {
        $totalParticipants += count($statusGroup);
    }
}

echo $OUTPUT->header();

// Add participants page class for styling
echo html_writer::start_tag('div', ['class' => 'participants-page']);

// Session information card
echo html_writer::start_tag('div', ['class' => 'card mb-4 shadow-sm']);
echo html_writer::start_tag('div', ['class' => 'card-header bg-secondary text-white']);
echo html_writer::tag('h3', get_string('sessioninfo', 'local_f2freport'), ['class' => 'card-title mb-0']);
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', ['class' => 'card-body']);

// First row: Course information
echo html_writer::start_tag('div', ['class' => 'row mb-3']);
echo html_writer::start_tag('div', ['class' => 'col-12']);
echo html_writer::start_tag('div', ['class' => 'course-info bg-light p-3 rounded']);
echo html_writer::tag('h4', format_string($session->coursename), ['class' => 'mb-1']);
echo html_writer::tag('p', '<strong>' . get_string('courseid', 'local_f2freport') . ':</strong> ' . $session->courseid, ['class' => 'mb-0 text-muted']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Second row: Session details
echo html_writer::start_tag('div', ['class' => 'row']);
echo html_writer::start_tag('div', ['class' => 'col-md-4']);
echo html_writer::start_tag('div', ['class' => 'info-item']);
echo html_writer::tag('div', get_string('courseid', 'local_f2freport'), ['class' => 'info-label text-muted small']);
echo html_writer::tag('div', $session->courseid, ['class' => 'info-value h5 mb-0']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'col-md-4']);
if ($session->timestart) {
    echo html_writer::start_tag('div', ['class' => 'info-item']);
    echo html_writer::tag('div', get_string('timestart', 'local_f2freport'), ['class' => 'info-label text-muted small']);
    echo html_writer::tag('div', userdate($session->timestart, get_string('strftimedatetime', 'langconfig')), ['class' => 'info-value h6 mb-0']);
    echo html_writer::end_tag('div');
}
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'col-md-4']);
echo html_writer::start_tag('div', ['class' => 'info-item']);
echo html_writer::tag('div', get_string('totalparticipants', 'local_f2freport'), ['class' => 'info-label text-muted small']);
echo html_writer::tag('div', $totalParticipants, ['class' => 'info-value h5 mb-0 text-primary']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Participants by status
if (!empty($participantsByStatus)) {
    foreach ($participantsByStatus as $statusCode => $participants) {
        $statusText = participants_manager::get_status_text($statusCode);
        $badgeClass = participants_manager::get_status_badge_class($statusCode);
        $count = count($participants);

        // Status group header
        echo html_writer::start_tag('div', ['class' => 'card mb-3']);
        echo html_writer::start_tag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
        echo html_writer::tag('h5', $statusText, ['class' => 'mb-0']);
        echo html_writer::tag('span', $count, ['class' => "badge $badgeClass"]);
        echo html_writer::end_tag('div');

        // Participants table for this status
        echo html_writer::start_tag('div', ['class' => 'card-body p-0']);
        $table = new html_table();
        $table->head = [
            get_string('fullname'),
            get_string('email'),
            get_string('signuptime', 'local_f2freport')
        ];
        $table->attributes['class'] = 'table table-striped mb-0';

        foreach ($participants as $participant) {
            $signuptime = $participant->timecreated ? userdate($participant->timecreated) : '-';

            $table->data[] = [
                fullname($participant),
                $participant->email,
                $signuptime
            ];
        }

        echo html_writer::table($table);
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    }
} else {
    echo html_writer::tag('div', get_string('noparticipants', 'local_f2freport'), ['class' => 'alert alert-info']);
}

// Back link
$backurl = new moodle_url('/local/f2freport/report.php');
echo html_writer::link($backurl, get_string('backtoreport', 'local_f2freport'), ['class' => 'btn btn-secondary mt-3']);

// Close participants page div
echo html_writer::end_tag('div');

echo $OUTPUT->footer();