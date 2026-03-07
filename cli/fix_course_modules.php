<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');

cli_heading('Fixing missing course_modules entries for Face-to-Face activities');

// Get the module ID for facetoface
$modulerecord = $DB->get_record('modules', ['name' => 'facetoface'], '*', MUST_EXIST);
cli_writeln("Face-to-Face module ID: {$modulerecord->id}");
cli_writeln("");

// Get all face-to-face activities
$f2fs = $DB->get_records('facetoface', null, 'course, id');

$fixed = 0;
$alreadyexists = 0;

foreach ($f2fs as $f2f) {
    // Check if course_modules entry exists
    $cm = $DB->get_record('course_modules', [
        'course' => $f2f->course,
        'module' => $modulerecord->id,
        'instance' => $f2f->id
    ]);

    if ($cm) {
        $alreadyexists++;
        continue;
    }

    // Get the course
    $course = $DB->get_record('course', ['id' => $f2f->course]);
    if (!$course) {
        cli_writeln("Warning: Course {$f2f->course} not found for F2F {$f2f->id}");
        continue;
    }

    cli_writeln("Fixing F2F ID {$f2f->id} in course {$course->shortname} (ID: {$course->id})");

    // Create course_modules entry
    $cm = new stdClass();
    $cm->course = $f2f->course;
    $cm->module = $modulerecord->id;
    $cm->instance = $f2f->id;
    $cm->section = 0; // Will be set properly by add_moduleinfo
    $cm->idnumber = '';
    $cm->added = time();
    $cm->score = 0;
    $cm->indent = 0;
    $cm->visible = 1;
    $cm->visibleoncoursepage = 1;
    $cm->visibleold = 1;
    $cm->groupmode = 0;
    $cm->groupingid = 0;
    $cm->completion = 0;
    $cm->completiongradeitemnumber = null;
    $cm->completionview = 0;
    $cm->completionexpected = 0;
    $cm->showdescription = 0;
    $cm->availability = null;
    $cm->deletioninprogress = 0;

    $cm->id = $DB->insert_record('course_modules', $cm);

    // Get the first section (usually "General" or section 0)
    $section = $DB->get_record('course_sections', ['course' => $f2f->course, 'section' => 0]);

    if ($section) {
        // Update section with new module
        $cm->section = $section->id;
        $DB->update_record('course_modules', $cm);

        // Add module to section sequence
        $sequence = $section->sequence;
        if (empty($sequence)) {
            $sequence = (string)$cm->id;
        } else {
            $sequence .= ',' . $cm->id;
        }
        $DB->set_field('course_sections', 'sequence', $sequence, ['id' => $section->id]);

        cli_writeln("  -> Created course_module ID {$cm->id} in section {$section->section}");
    } else {
        cli_writeln("  -> Warning: Section 0 not found for course {$course->id}");
    }

    // Rebuild course cache
    rebuild_course_cache($f2f->course, true);

    $fixed++;
}

cli_writeln("");
cli_heading("Summary");
cli_writeln("Fixed: {$fixed}");
cli_writeln("Already existed: {$alreadyexists}");
cli_writeln("");
cli_writeln("Activities should now appear in their respective courses!");

exit(0);
