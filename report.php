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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/classes/report_builder.php');

use local_f2freport\form\report_filter_form;
use local_f2freport\report_builder;
use local_f2freport\table\sessions_table;

$context = context_system::instance();
require_login();
require_capability('local/f2freport:viewreport', $context);

$pageurl = new moodle_url('/local/f2freport/report.php');
$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('trainingreporttitle', 'local_f2freport'));
$PAGE->set_heading(get_string('trainingreportheading', 'local_f2freport'));

// --- Report Builder ---
$builder = new report_builder();

// --- Filter Form ---
$courseoptions = $builder->get_course_options();
$customdata = ['courseoptions' => $courseoptions];
$form = new report_filter_form($pageurl, $customdata);

if (optional_param('resetbutton', null, PARAM_RAW) !== null) {
    redirect($pageurl);
}

// --- Read Filters ---
$courseid   = optional_param('courseid', 0, PARAM_INT);
$futureonly = (bool)optional_param('futureonly', 0, PARAM_BOOL);
$datefromarr = optional_param_array('datefrom', null, PARAM_INT);
$datetoarr   = optional_param_array('dateto', null, PARAM_INT);

$datefrom = is_array($datefromarr) ? report_builder::normalize_date($datefromarr) : optional_param('datefrom', 0, PARAM_INT);
$dateto   = is_array($datetoarr) ? report_builder::normalize_date($datetoarr) : optional_param('dateto', 0, PARAM_INT);

$form->set_data([
    'courseid'   => $courseid,
    'datefrom'   => $datefrom ?: null,
    'dateto'     => $dateto ?: null,
    'futureonly' => $futureonly ? 1 : 0,
]);

$filters = [
    'courseid'   => $courseid,
    'datefrom'   => $datefrom ?: null,
    'dateto'     => $dateto ?: null,
    'futureonly' => $futureonly,
];

// --- Sessions Table ---
$fieldids = $builder->get_field_ids();
$table = new sessions_table('local_f2freport_sessions', $builder, $filters, $fieldids);

$params = [];
if (!empty($courseid)) {
    $params['courseid'] = $courseid;
}
if (!empty($datefrom)) {
    $params['datefrom'] = $datefrom;
}
if (!empty($dateto)) {
    $params['dateto']   = $dateto;
}
if (!empty($futureonly)) {
    $params['futureonly'] = 1;
}

$baseurl = new moodle_url('/local/f2freport/report.php', $params);
$table->define_baseurl($baseurl);

// --- Rendering ---
$cfg = get_config('local_f2freport') ?: new stdClass();
$pagesize = !empty($cfg->pagesize) ? max(1, (int)$cfg->pagesize) : 25;

ob_start();
$table->out($pagesize, false);
$tablehtml = ob_get_clean();

$totalrows = $table->get_totalrows();
$countsummary = get_string('showingcount', 'local_f2freport', $totalrows);

$templatectx = [
    'filtershtml'  => $form->render(),
    'tablehtml'    => $tablehtml,
    'hasresults'   => (trim($tablehtml) !== '' && strpos($tablehtml, 'generaltable') !== false),
    'countsummary' => $countsummary,
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_f2freport/report', $templatectx);
echo $OUTPUT->footer();