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
 * Report for listing face-to-face sessions with filters.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_f2freport\report_builder;
use local_f2freport\table\sessions_table;

global $DB, $OUTPUT, $PAGE;

$context = context_system::instance();
require_login();
require_capability('local/f2freport:viewreport', $context);

$pageurl = new moodle_url('/local/f2freport/report.php');
$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('trainingreporttitle', 'local_f2freport'));
$PAGE->set_heading(get_string('trainingreportheading', 'local_f2freport'));

// --- Filter parameters processing ---

$coursetext    = optional_param('coursetext', '', PARAM_TEXT);
$datefrom      = optional_param('datefrom', '', PARAM_RAW_TRIMMED);
$dateto        = optional_param('dateto', '', PARAM_RAW_TRIMMED);
$upcomingonly  = optional_param('futureonly', 0, PARAM_BOOL);

// Parse dates into timestamps.
$startts = 0;
$endts   = 0;

// Parse date from format YYYY-MM-DD (HTML date input)
if (!empty($datefrom) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $datefrom)) {
    $startts = strtotime($datefrom . ' 00:00:00');
    if ($startts === false) {
        $startts = 0;
        $datefrom = ''; // Clear invalid date
    }
}
if (!empty($dateto) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateto)) {
    $endts = strtotime($dateto . ' 23:59:59');
    if ($endts === false) {
        $endts = 0;
        $dateto = ''; // Clear invalid date
    }
}

// Validate date range: start date should not be after end date
$dates_swapped = false;
if ($startts > 0 && $endts > 0 && $startts > $endts) {
    // Swap dates if start is after end
    $temp = $startts;
    $startts = $endts;
    $endts = $temp;

    $temp = $datefrom;
    $datefrom = $dateto;
    $dateto = $temp;

    $dates_swapped = true;
}

// If 'upcoming_only' is checked, the start date is at least today.
if ($upcomingonly) {
    $todayts = strtotime('today 00:00:00');
    $startts = ($startts > 0) ? max($startts, $todayts) : $todayts;
}

// --- Guards and data loading ---

// No specific course validation needed for text search.

$builder = new report_builder();

// --- Table and data setup ---

$filters = [
    'coursetext' => $coursetext,
    'startts'  => $startts,
    'endts'    => $endts,
];

$fieldids = $builder->get_field_ids();
$table = new sessions_table('local_f2freport_sessions', $builder, $filters, $fieldids);

$urlparams = [];
if (!empty($coursetext)) {
    $urlparams['coursetext'] = $coursetext;
}
if (!empty($datefrom)) {
    $urlparams['datefrom'] = $datefrom;
}
if (!empty($dateto)) {
    $urlparams['dateto'] = $dateto;
}
if ($upcomingonly) {
    $urlparams['futureonly'] = 1;
}
$baseurl = new moodle_url('/local/f2freport/report.php', $urlparams);
$table->define_baseurl($baseurl);

// --- Rendering ---

$cfg = get_config('local_f2freport') ?: new stdClass();
$pagesize = !empty($cfg->pagesize) ? max(1, (int)$cfg->pagesize) : 25;
ob_start();
$table->out($pagesize, false);
$tablehtml = ob_get_clean();

$totalrows = $table->get_totalrows();
$countsummary = get_string('showingcount', 'local_f2freport', $totalrows);

// Date values are already in YYYY-MM-DD format from HTML date inputs
$datefromvalue = $datefrom;
$datetovalue = $dateto;

$templatectx = [
    'coursetext'     => $coursetext,
    'datefrom'       => $datefromvalue,
    'dateto'         => $datetovalue,
    'futureonly'     => (bool) $upcomingonly,
    'actionurl'      => $pageurl->out(),
    'sesskey'        => sesskey(),
    'tablehtml'      => $tablehtml,
    'hasresults'     => $totalrows > 0,
    'countsummary'   => $countsummary,
    'dates_swapped'  => $dates_swapped,
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_f2freport/report', $templatectx);
echo $OUTPUT->footer();
