<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

cli_heading('Adding session fields (Ville, Lieu, Salle) to Face-to-Face sessions');

// Check if table exists
if (!$DB->get_manager()->table_exists('facetoface_session_field')) {
    cli_error('Table facetoface_session_field does not exist!');
}

// Sample data for testing
$cities = [
    'Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice', 'Nantes',
    'Strasbourg', 'Montpellier', 'Bordeaux', 'Lille', 'Rennes', 'Grenoble'
];

$venues = [
    'Centre de formation principal',
    'Campus Nord',
    'Campus Sud',
    'Site de la République',
    'Bâtiment A',
    'Bâtiment B',
    'Centre ville',
    'Zone industrielle',
    'Pépinière d\'entreprises',
    'Espace coworking',
];

$rooms = [
    'Salle 101', 'Salle 102', 'Salle 103', 'Salle 201', 'Salle 202',
    'Salle A', 'Salle B', 'Salle C', 'Amphithéâtre',
    'Salle de conférence', 'Salle de réunion', 'Laboratoire'
];

// Step 1: Check/Create custom fields
cli_heading('Step 1: Checking/Creating custom fields');

$fieldids = ['city' => null, 'venue' => null, 'room' => null];

// Check for existing fields
$existingfields = $DB->get_records('facetoface_session_field');

foreach ($existingfields as $field) {
    $shortname = strtolower(trim($field->shortname ?? ''));
    if (in_array($shortname, ['city', 'ville'])) {
        $fieldids['city'] = $field->id;
        cli_writeln("  Found existing City field: ID {$field->id}, shortname: {$field->shortname}");
    } else if (in_array($shortname, ['venue', 'lieu'])) {
        $fieldids['venue'] = $field->id;
        cli_writeln("  Found existing Venue field: ID {$field->id}, shortname: {$field->shortname}");
    } else if (in_array($shortname, ['room', 'salle'])) {
        $fieldids['room'] = $field->id;
        cli_writeln("  Found existing Room field: ID {$field->id}, shortname: {$field->shortname}");
    }
}

// Create missing fields
if (!$fieldids['city']) {
    $field = new stdClass();
    $field->shortname = 'ville';
    $field->name = 'Ville';
    $field->type = 0; // Text field
    $field->possiblevalues = '';
    $field->required = 0;
    $field->showinsummary = 1;
    $field->isfilter = 1;

    $fieldids['city'] = $DB->insert_record('facetoface_session_field', $field);
    cli_writeln("  Created City field: ID {$fieldids['city']}");
}

if (!$fieldids['venue']) {
    $field = new stdClass();
    $field->shortname = 'lieu';
    $field->name = 'Lieu';
    $field->type = 0; // Text field
    $field->possiblevalues = '';
    $field->required = 0;
    $field->showinsummary = 1;
    $field->isfilter = 1;

    $fieldids['venue'] = $DB->insert_record('facetoface_session_field', $field);
    cli_writeln("  Created Venue field: ID {$fieldids['venue']}");
}

if (!$fieldids['room']) {
    $field = new stdClass();
    $field->shortname = 'salle';
    $field->name = 'Salle';
    $field->type = 0; // Text field
    $field->possiblevalues = '';
    $field->required = 0;
    $field->showinsummary = 1;
    $field->isfilter = 1;

    $fieldids['room'] = $DB->insert_record('facetoface_session_field', $field);
    cli_writeln("  Created Room field: ID {$fieldids['room']}");
}

// Step 2: Fill session data
cli_heading('Step 2: Filling session data');

// Get all sessions
$sessions = $DB->get_records('facetoface_sessions');
$updated = 0;
$skipped = 0;

foreach ($sessions as $session) {
    // Check if data already exists for this session
    $hasdata = $DB->record_exists('facetoface_session_data', ['sessionid' => $session->id]);

    if ($hasdata) {
        $skipped++;
        continue;
    }

    // Random data
    $city = $cities[array_rand($cities)];
    $venue = $venues[array_rand($venues)];
    $room = $rooms[array_rand($rooms)];

    // Insert city
    $data = new stdClass();
    $data->sessionid = $session->id;
    $data->fieldid = $fieldids['city'];
    $data->data = $city;
    $DB->insert_record('facetoface_session_data', $data);

    // Insert venue
    $data = new stdClass();
    $data->sessionid = $session->id;
    $data->fieldid = $fieldids['venue'];
    $data->data = $venue;
    $DB->insert_record('facetoface_session_data', $data);

    // Insert room
    $data = new stdClass();
    $data->sessionid = $session->id;
    $data->fieldid = $fieldids['room'];
    $data->data = $room;
    $DB->insert_record('facetoface_session_data', $data);

    $updated++;

    if ($updated % 50 == 0) {
        cli_writeln("  Processed {$updated} sessions...");
    }
}

cli_writeln("");
cli_heading("Summary");
cli_writeln("Field IDs:");
cli_writeln("  - Ville (City): {$fieldids['city']}");
cli_writeln("  - Lieu (Venue): {$fieldids['venue']}");
cli_writeln("  - Salle (Room): {$fieldids['room']}");
cli_writeln("");
cli_writeln("Sessions updated: {$updated}");
cli_writeln("Sessions skipped (already have data): {$skipped}");
cli_writeln("");
cli_writeln("You can now see these fields in the report:");
cli_writeln("  {$CFG->wwwroot}/local/f2freport/report.php");
cli_writeln("");

exit(0);
