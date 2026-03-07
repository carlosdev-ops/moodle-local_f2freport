<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * CLI script to generate test data for f2freport plugin.
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
require_once($CFG->dirroot . '/enrol/manual/locallib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'courses' => 50,
        'users' => 20,
        'sessions' => 3,
    ],
    [
        'h' => 'help',
        'c' => 'courses',
        'u' => 'users',
        's' => 'sessions',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "Generate test data for face-to-face activities.

Options:
-h, --help          Print out this help
-c, --courses=N     Number of courses to create (default: 50)
-u, --users=N       Number of test users to create (default: 20)
-s, --sessions=N    Number of sessions per activity (default: 3)

Example:
\$ php generate_test_data.php --courses=50 --users=20 --sessions=3
";

    echo $help;
    exit(0);
}

$nbcourses = (int)$options['courses'];
$nbusers = (int)$options['users'];
$nbsessions = (int)$options['sessions'];

cli_heading('Generating test data for face-to-face activities');

// Step 1: Create test users.
cli_heading('Step 1: Creating test users');
$users = [];
for ($i = 1; $i <= $nbusers; $i++) {
    $username = 'testuser' . $i;

    // Check if user already exists.
    if ($existinguser = $DB->get_record('user', ['username' => $username])) {
        $users[] = $existinguser;
        cli_writeln("  User '$username' already exists (ID: {$existinguser->id})");
        continue;
    }

    $user = new stdClass();
    $user->username = $username;
    $user->password = hash_internal_user_password('Test123!');
    $user->firstname = 'Test';
    $user->lastname = 'User ' . $i;
    $user->email = 'testuser' . $i . '@example.com';
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $user->auth = 'manual';

    $user->id = user_create_user($user, false, false);
    $users[] = $user;
    cli_writeln("  Created user: {$user->username} (ID: {$user->id})");
}

// Step 2: Create courses with face-to-face activities.
cli_heading('Step 2: Creating courses with face-to-face activities');

// Get default category.
$category = $DB->get_record('course_categories', ['id' => 1], '*', MUST_EXIST);

for ($i = 1; $i <= $nbcourses; $i++) {
    $shortname = 'F2F_TEST_' . $i;

    // Check if course already exists.
    if ($existingcourse = $DB->get_record('course', ['shortname' => $shortname])) {
        cli_writeln("  Course '$shortname' already exists (ID: {$existingcourse->id}), skipping...");
        continue;
    }

    // Create course.
    $coursedata = new stdClass();
    $coursedata->category = $category->id;
    $coursedata->fullname = 'Face-to-Face Test Course ' . $i;
    $coursedata->shortname = $shortname;
    $coursedata->summary = 'Test course for face-to-face reporting';
    $coursedata->summaryformat = FORMAT_HTML;
    $coursedata->format = 'topics';
    $coursedata->visible = 1;
    $coursedata->startdate = time();

    $course = create_course($coursedata);
    cli_writeln("  Created course: {$course->shortname} (ID: {$course->id})");

    // Enroll users in the course.
    $enrol = enrol_get_plugin('manual');
    $instances = enrol_get_instances($course->id, true);
    $manualinstance = null;
    foreach ($instances as $instance) {
        if ($instance->enrol === 'manual') {
            $manualinstance = $instance;
            break;
        }
    }

    if (!$manualinstance) {
        $enrolid = $enrol->add_default_instance($course);
        $manualinstance = $DB->get_record('enrol', ['id' => $enrolid]);
    }

    $studentrole = $DB->get_record('role', ['shortname' => 'student']);

    // Enroll random users (between 5 and all users).
    $nbenrolled = rand(5, min($nbusers, 15));
    $shuffledusers = $users;
    shuffle($shuffledusers);
    $enrolledusers = array_slice($shuffledusers, 0, $nbenrolled);

    foreach ($enrolledusers as $user) {
        $enrol->enrol_user($manualinstance, $user->id, $studentrole->id);
    }
    cli_writeln("    Enrolled {$nbenrolled} users");

    // Create face-to-face activity.
    $f2f = new stdClass();
    $f2f->course = $course->id;
    $f2f->name = 'Face-to-Face Session ' . $i;
    $f2f->intro = 'Test face-to-face activity for reporting';
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
    $f2f->id = $f2fid;
    cli_writeln("    Created face-to-face activity (ID: {$f2fid})");

    // Create sessions for this face-to-face activity.
    for ($s = 1; $s <= $nbsessions; $s++) {
        $session = new stdClass();
        $session->facetoface = $f2fid;
        $session->capacity = 20;
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
        cli_writeln("      Created session {$s} (ID: {$sessionid})");

        // Create session date.
        $sessiondate = new stdClass();
        $sessiondate->sessionid = $sessionid;
        $sessiondate->sessiontimezone = 'Europe/Paris';
        // Session in the future (next 30 days).
        $starttime = time() + rand(1, 30) * 86400;
        $sessiondate->timestart = $starttime;
        $sessiondate->timefinish = $starttime + 7200; // 2 hours.

        $DB->insert_record('facetoface_sessions_dates', $sessiondate);

        // Sign up random enrolled users to this session (50-80% of enrolled users).
        $nbsignups = rand((int)($nbenrolled * 0.5), (int)($nbenrolled * 0.8));
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

        cli_writeln("        Signed up {$nbsignups} users to session {$s}");
    }
}

cli_heading('Test data generation completed successfully!');
cli_writeln('');
cli_writeln("Summary:");
cli_writeln("  - Users created/verified: {$nbusers}");
cli_writeln("  - Courses created: {$nbcourses}");
cli_writeln("  - Sessions per activity: {$nbsessions}");
cli_writeln('');
cli_writeln("You can now test the report at:");
cli_writeln("  {$CFG->wwwroot}/local/f2freport/report.php");
cli_writeln('');

exit(0);
