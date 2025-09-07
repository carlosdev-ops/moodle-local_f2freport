<?php
// Strings for component 'local_f2freport', language 'en'.
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Face-to-Face Sessions Report';
$string['report_heading'] = 'F2F – Sessions report';
$string['report_title'] = 'Training sessions';

// Capability (shows nicely in roles UI).
$string['f2freport:view'] = 'View F2F report';

// Errors.
$string['errortablenotfound'] = 'Sessions table class not found. Please ensure classes/table/sessions_table.php exists.';

// ─────────────────────────────────────────────────────────────────────────────
// Admin settings (used by local/f2freport/settings.php)
// Column keys (labels)
$string['courseid']         = 'Course ID';
$string['sessionid']        = 'Session ID';
$string['timestart']        = 'Start time';
$string['timefinish']       = 'End time';
$string['city']             = 'City';
$string['venue']            = 'Venue';
$string['room']             = 'Room';
$string['totalparticipants']= 'Total participants';
$string['coursefullname']   = 'Course full name';

// Settings page & descriptions
$string['settings_columns'] = 'Default columns to display';
$string['settings_columns_desc'] =
    'Select which columns are shown by default in the F2F report. Users may override via table preferences if enabled.';

$string['settings_aliases_city'] = 'City field alias';
$string['settings_aliases_city_desc'] =
    'If your data model uses a different field name for the city, enter it here (e.g., <code>location_city</code>). Leave empty to use the default.';

$string['settings_aliases_venue'] = 'Venue field alias';
$string['settings_aliases_venue_desc'] =
    'If your data model uses a different field name for the venue, enter it here (e.g., <code>location_venue</code>). Leave empty to use the default.';

$string['settings_aliases_room'] = 'Room field alias';
$string['settings_aliases_room_desc'] =
    'If your data model uses a different field name for the room, enter it here (e.g., <code>location_room</code>). Leave empty to use the default.';

$string['settings_pagesize'] = 'Page size';
$string['settings_pagesize_desc'] =
    'Number of rows per page for the sessions table.';

// ─────────────────────────────────────────────────────────────────────────────
// Filters UI (form)
$string['filters_title'] = 'Filters';
$string['filters_intro'] = 'Refine the dataset before listing sessions.';

$string['applyfilters'] = 'Apply filters';
$string['resetfilters'] = 'Reset';

$string['filter_period_header'] = 'Period';
$string['filter_datefrom'] = 'Start date';
$string['filter_datefrom_help'] = 'Only sessions occurring on or after this date will be shown.';
$string['filter_dateto'] = 'End date';
$string['filter_dateto_help'] = 'Only sessions occurring on or before this date will be shown.';

$string['filter_other_header'] = 'Additional filters';
$string['filter_location'] = 'Location (contains)';
$string['filter_location_help'] = 'Filter sessions whose location contains this text.';
$string['filter_trainers'] = 'Trainers';
$string['filter_trainers_help'] = 'Filter sessions taught by one or more selected trainers.';
$string['filter_trainers_noselect'] = 'Select trainers...';
$string['filter_status'] = 'Status';
$string['filter_any'] = 'Any';

$string['status_planned'] = 'Planned';
$string['status_completed'] = 'Completed';
$string['status_cancelled'] = 'Cancelled';

// Table headers (if you use them in the UI)
$string['th_session'] = 'Session';
$string['th_date'] = 'Date';
$string['th_location'] = 'Location';
$string['th_trainer'] = 'Trainer';
$string['th_status'] = 'Status';

$string['error_daterange'] = 'End date must be greater than or equal to the start date.';
$string['notspecified'] = 'Not specified';
