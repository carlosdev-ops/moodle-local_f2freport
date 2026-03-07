<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

cli_heading('Face-to-Face Data Summary');

// Count all face-to-face activities
$totalactivities = $DB->count_records('facetoface');
cli_writeln("Total face-to-face activities: {$totalactivities}");

// Count all sessions
$totalsessions = $DB->count_records('facetoface_sessions');
cli_writeln("Total sessions: {$totalsessions}");

// Get courses with face-to-face activities
$sql = "SELECT c.id, c.shortname, c.fullname, f.id as f2fid, f.name as f2fname
        FROM {course} c
        JOIN {facetoface} f ON f.course = c.id
        ORDER BY c.id ASC";

$courses = $DB->get_records_sql($sql);

cli_writeln("");
cli_heading("Courses with Face-to-Face activities:");

foreach ($courses as $course) {
    // Count sessions for this activity
    $sessioncount = $DB->count_records('facetoface_sessions', ['facetoface' => $course->f2fid]);

    // Count signups
    $signupcount = $DB->count_records_sql("
        SELECT COUNT(DISTINCT fsu.id)
        FROM {facetoface_signups} fsu
        JOIN {facetoface_sessions} fs ON fs.id = fsu.sessionid
        WHERE fs.facetoface = ?
    ", [$course->f2fid]);

    cli_writeln(sprintf(
        "Course ID: %4d | %-30s | F2F ID: %3d | %-30s | Sessions: %2d | Signups: %3d",
        $course->id,
        substr($course->shortname, 0, 30),
        $course->f2fid,
        substr($course->f2fname, 0, 30),
        $sessioncount,
        $signupcount
    ));
}

cli_writeln("");
cli_writeln("To view the report, go to:");
cli_writeln("  {$CFG->wwwroot}/local/f2freport/report.php");
cli_writeln("");
cli_writeln("To view a specific course's F2F activity:");
cli_writeln("  {$CFG->wwwroot}/mod/facetoface/view.php?f=<f2f_id>");
cli_writeln("");

exit(0);
