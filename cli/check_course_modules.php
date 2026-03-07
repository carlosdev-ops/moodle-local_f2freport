<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$courseid = 1137;

cli_heading("Checking course ID: {$courseid}");

// Get course info
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
cli_writeln("Course: {$course->fullname} ({$course->shortname})");
cli_writeln("");

// Get face-to-face activities for this course
$f2fs = $DB->get_records('facetoface', ['course' => $courseid]);
cli_writeln("Face-to-Face activities in facetoface table:");
foreach ($f2fs as $f2f) {
    cli_writeln("  - F2F ID: {$f2f->id} | Name: {$f2f->name}");

    // Check if there's a course_modules entry
    $modulerecord = $DB->get_record('modules', ['name' => 'facetoface']);
    if ($modulerecord) {
        $cm = $DB->get_record('course_modules', [
            'course' => $courseid,
            'module' => $modulerecord->id,
            'instance' => $f2f->id
        ]);

        if ($cm) {
            cli_writeln("    -> Course module exists: CM ID = {$cm->id}, Section = {$cm->section}, Visible = {$cm->visible}");
        } else {
            cli_writeln("    -> ERROR: No course_modules entry found! The activity won't appear in the course.");
        }
    }
}

cli_writeln("");
cli_writeln("All course modules for this course:");
$cms = $DB->get_records('course_modules', ['course' => $courseid]);
foreach ($cms as $cm) {
    $module = $DB->get_record('modules', ['id' => $cm->module]);
    cli_writeln("  - Module: {$module->name} | Instance: {$cm->instance} | Section: {$cm->section} | Visible: {$cm->visible}");
}

exit(0);
