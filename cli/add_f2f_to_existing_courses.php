<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * CLI script to add face-to-face activities to existing courses.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'courses' => 50,
        'sessions' => 3,
    ],
    [
        'h' => 'help',
        'c' => 'courses',
        's' => 'sessions',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "Add face-to-face activities to existing courses.

Options:
-h, --help          Print out this help
-c, --courses=N     Number of courses to process (default: 50)
-s, --sessions=N    Number of sessions per activity (default: 3)

Example:
\$ php add_f2f_to_existing_courses.php --courses=50 --sessions=3
";

    echo $help;
    exit(0);
}

$nbcourses = (int)$options['courses'];
$nbsessions = (int)$options['sessions'];

cli_heading('Adding face-to-face activities to existing courses');

// Get existing courses (excluding site course).
$courses = $DB->get_records_select('course', 'id > 1', null, 'id ASC', '*', 0, $nbcourses);

if (empty($courses)) {
    cli_error('No courses found in the database!');
}

$actualcount = count($courses);
cli_writeln("Found {$actualcount} courses to process");
cli_writeln('');

$processed = 0;
$skipped = 0;

foreach ($courses as $course) {
    cli_writeln("Processing course: {$course->shortname} (ID: {$course->id})");

    // Check if course already has a face-to-face activity.
    $existingf2f = $DB->get_record('facetoface', ['course' => $course->id]);
    if ($existingf2f) {
        cli_writeln("  Skipped: Course already has a face-to-face activity (ID: {$existingf2f->id})");
        $skipped++;
        continue;
    }

    // Get enrolled users in this course.
    $context = context_course::instance($course->id);
    $enrolledusers = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname', null, 0, 0, true);

    if (empty($enrolledusers)) {
        cli_writeln("  Warning: No users enrolled in this course, creating activity anyway");
        $enrolledusers = [];
    } else {
        cli_writeln("  Found " . count($enrolledusers) . " enrolled users");
    }

    // Create face-to-face activity.
    $f2f = new stdClass();
    $f2f->course = $course->id;
    $f2f->name = 'Face-to-Face Training';
    $f2f->intro = 'Face-to-face training sessions for this course';
    $f2f->introformat = FORMAT_HTML;
    $f2f->thirdparty = '';
    $f2f->thirdpartywaitlist = 0;
    $f2f->display = 0;
    $f2f->confirmationsubject = '';
    $f2f->confirmationinstrmngr = '';
    $f2f->confirmationmessage = '';
    $f2f->waitlistedsubject = '';
    $f2f->waitlistedmessage = '';
    $f2f->cancellationsubject = '';
    $f2f->cancellationinstrmngr = '';
    $f2f->cancellationmessage = '';
    $f2f->remindersubject = '';
    $f2f->reminderinstrmngr = '';
    $f2f->remindermessage = '';
    $f2f->reminderperiod = 0;
    $f2f->requestsubject = '';
    $f2f->requestinstrmngr = '';
    $f2f->requestmessage = '';
    $f2f->approvalreqd = 0;
    $f2f->timecreated = time();
    $f2f->timemodified = time();
    $f2f->shortname = '';
    $f2f->showoncalendar = F2F_CAL_COURSE;
    $f2f->usercalentry = 1;
    $f2f->multiplesessions = 0;
    $f2f->completionstatusrequired = null;
    $f2f->managerreserve = 0;
    $f2f->maxmanagerreserves = 1;
    $f2f->reservecanceldays = 1;
    $f2f->reservedays = 2;
    $f2f->declareinterest = 0;
    $f2f->interestonlyiffull = 0;
    $f2f->selectpositiononsignup = 0;
    $f2f->forceselectposition = 0;

    $f2fid = $DB->insert_record('facetoface', $f2f);
    cli_writeln("  Created face-to-face activity (ID: {$f2fid})");

    // Create sessions for this face-to-face activity.
    for ($s = 1; $s <= $nbsessions; $s++) {
        $session = new stdClass();
        $session->facetoface = $f2fid;
        $session->capacity = 30;
        $session->allowoverbook = 0;
        $session->duration = 7200; // 2 hours.
        $session->normalcost = 100;
        $session->discountcost = 80;
        $session->details = '';
        $session->datetimeknown = 1;
        $session->timecreated = time();
        $session->timemodified = time();
        $session->usermodified = $USER->id;

        $sessionid = $DB->insert_record('facetoface_sessions', $session);
        cli_writeln("    Created session {$s} (ID: {$sessionid})");

        // Create session date.
        $sessiondate = new stdClass();
        $sessiondate->sessionid = $sessionid;
        $sessiondate->sessiontimezone = 'Europe/Paris';
        // Session in the future (next 60 days).
        $starttime = time() + rand(1, 60) * 86400;
        $sessiondate->timestart = $starttime;
        $sessiondate->timefinish = $starttime + 7200; // 2 hours.

        $DB->insert_record('facetoface_sessions_dates', $sessiondate);

        // Sign up enrolled users to this session (if there are any).
        if (!empty($enrolledusers)) {
            // Sign up between 30% and 80% of enrolled users.
            $nbenrolled = count($enrolledusers);
            $nbsignups = rand((int)($nbenrolled * 0.3), (int)($nbenrolled * 0.8));
            $nbsignups = max(1, min($nbsignups, $session->capacity)); // At least 1, max capacity.

            $shuffledenrolled = $enrolledusers;
            shuffle($shuffledenrolled);
            $signupusers = array_slice($shuffledenrolled, 0, $nbsignups);

            foreach ($signupusers as $signupuser) {
                // Create signup record.
                $signup = new stdClass();
                $signup->sessionid = $sessionid;
                $signup->userid = $signupuser->id;
                $signup->mailedreminder = 0;
                $signup->notificationtype = MDL_F2F_TEXT;
                $signup->discountcode = '';
                $signup->bookedby = $USER->id;

                $signupid = $DB->insert_record('facetoface_signups', $signup);

                // Create signup status.
                $signupstatus = new stdClass();
                $signupstatus->signupid = $signupid;
                $signupstatus->statuscode = MDL_F2F_STATUS_BOOKED;
                $signupstatus->superceded = 0;
                $signupstatus->grade = 0;
                $signupstatus->note = '';
                $signupstatus->createdby = $USER->id;
                $signupstatus->timecreated = time();

                $DB->insert_record('facetoface_signups_status', $signupstatus);
            }

            cli_writeln("      Signed up {$nbsignups} users to session {$s}");
        }
    }

    $processed++;
}

cli_writeln('');
cli_heading('Process completed successfully!');
cli_writeln('');
cli_writeln("Summary:");
cli_writeln("  - Courses processed: {$actualcount}");
cli_writeln("  - Activities created: {$processed}");
cli_writeln("  - Courses skipped (already have F2F): {$skipped}");
cli_writeln("  - Sessions per activity: {$nbsessions}");
cli_writeln('');
cli_writeln("You can now test the report at:");
cli_writeln("  {$CFG->wwwroot}/local/f2freport/report.php");
cli_writeln('');

exit(0);
