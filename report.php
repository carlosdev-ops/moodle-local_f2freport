<?php
// This file is part of Moodle - https://moodle.org/.
// Display page for local_f2freport (controller/presentation only).

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/classes/report_data.php');

use local_f2freport\report_data;

$context = context_system::instance();
require_login();
require_capability('local/f2freport:viewreport', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/f2freport/report.php'));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('trainingreporttitle', 'local_f2freport'));
$PAGE->set_heading(get_string('trainingreportheading', 'local_f2freport'));

// Parameters.
$courseid   = optional_param('courseid', 0, PARAM_INT);
$futureonly = optional_param('futureonly', 0, PARAM_BOOL);

// Validate course (if provided).
$invalidcourse = false;
if ($courseid > 0) {
    try {
        get_course($courseid);
    } catch (Exception $e) {
        $invalidcourse = true;
        $courseid = 0;
    }
}

echo $OUTPUT->header();

if ($invalidcourse) {
    echo $OUTPUT->notification(get_string('invalidcourse', 'local_f2freport'), \core\output\notification::NOTIFY_WARNING);
}

// Data (pure business moved to classes/report_data.php).
list($records, $coursesmenu) = report_data::get_sessions_with_meta($courseid, (bool)$futureonly);

// Filters form.
echo html_writer::start_div('card mb-3');
echo html_writer::div(get_string('filters', 'local_f2freport'), 'card-header h5');
echo html_writer::start_div('card-body');

echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out(false)]);
echo html_writer::start_div('form-inline');

echo html_writer::label(get_string('filtercourse', 'local_f2freport'), 'menu-courseid', false, ['class' => 'mr-2']);
echo html_writer::select(
    [0 => get_string('allcourses', 'local_f2freport')] + $coursesmenu,
    'courseid',
    $courseid,
    null,
    ['class' => 'form-control mr-3', 'id' => 'menu-courseid']
);

echo html_writer::checkbox('futureonly', 1, $futureonly, get_string('futureonly', 'local_f2freport'), ['class' => 'mr-3', 'id' => 'cb-futureonly']);

echo html_writer::empty_tag('input', [
    'type'  => 'submit',
    'value' => get_string('filter', 'local_f2freport'),
    'class' => 'btn btn-primary mr-2'
]);

echo html_writer::link(new moodle_url('/local/f2freport/report.php'),
    get_string('reset', 'local_f2freport'),
    ['class' => 'btn btn-secondary']
);

echo html_writer::end_div(); // form-inline
echo html_writer::end_tag('form');
echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Table.
$table = new flexible_table('f2freport-table');
$table->define_baseurl($PAGE->url);
$table->define_columns(['courseid','sessionid','timestart','timefinish','city','venue','room','totalparticipants','coursefullname','trainer']);
$table->define_headers([
    get_string('courseid', 'local_f2freport'),
    get_string('sessionid', 'local_f2freport'),
    get_string('timestart', 'local_f2freport'),
    get_string('timefinish', 'local_f2freport'),
    get_string('city', 'local_f2freport'),
    get_string('venue', 'local_f2freport'),
    get_string('room', 'local_f2freport'),
    get_string('totalparticipants', 'local_f2freport'),
    get_string('coursefullname', 'local_f2freport'),
    get_string('trainer', 'local_f2freport')
]);
$table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthwide table-striped');
$table->sortable(true, 'timestart');
$table->setup();

$shown = 0;
foreach ($records as $r) {
    $table->add_data([
        $r->courseid,
        $r->sessionid,
        userdate($r->timestart),
        userdate($r->timefinish),
        format_string($r->city),
        format_string($r->venue),
        format_string($r->room),
        (int)$r->totalparticipants,
        format_string($r->coursefullname),
        !empty($r->trainer) ? format_string($r->trainer) : get_string('notrainer', 'local_f2freport'),
    ]);
    $shown++;
}

if ($shown === 0) {
    echo $OUTPUT->notification(get_string('nosessions', 'local_f2freport'), \core\output\notification::NOTIFY_INFO);
} else {
    echo html_writer::div(get_string('showingcount', 'local_f2freport', $shown), 'alert alert-info');
    $table->finish_output();
}

echo $OUTPUT->footer();
