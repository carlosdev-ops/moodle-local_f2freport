<?php
// local/f2freport/report.php
require(__DIR__ . '/../../config.php');

use local_f2freport\form\report_filter_form;
use local_f2freport\table\sessions_table;

$context = context_system::instance();
require_login();
require_capability('local/f2freport:viewreport', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/f2freport/report.php'));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('trainingreporttitle', 'local_f2freport'));
$PAGE->set_heading(get_string('trainingreportheading', 'local_f2freport'));

global $DB;

/**
 * Normalise un champ date venant de date_selector :
 * - si array [day,month,year] -> timestamp 00:00
 * - si int -> int
 * - sinon 0
 */
function f2f_normalize_date($v): int {
    if (is_array($v) && isset($v['day'], $v['month'], $v['year'])) {
        return make_timestamp((int)$v['year'], (int)$v['month'], (int)$v['day'], 0, 0, 0);
    }
    return (int)$v;
}

/** Parse une liste CSV en tableau (lowercase/trim, vide filtré). */
function f2f_parse_aliases(?string $csv, array $fallback): array {
    $csv = trim((string)$csv);
    if ($csv === '') {
        return $fallback;
    }
    $out = [];
    foreach (explode(',', $csv) as $token) {
        $t = core_text::strtolower(trim($token));
        if ($t !== '') { $out[] = $t; }
    }
    return array_values(array_unique($out));
}

// ───── Config plugin ─────
$cfg = get_config('local_f2freport') ?: new stdClass();
$pagesize = !empty($cfg->pagesize) ? max(1, (int)$cfg->pagesize) : 25;
$aliases = [
    'city'  => f2f_parse_aliases($cfg->aliases_city  ?? '', ['city','ville','location']),
    'venue' => f2f_parse_aliases($cfg->aliases_venue ?? '', ['venue','lieu','building','site','centre','center','campus']),
    'room'  => f2f_parse_aliases($cfg->aliases_room  ?? '', ['room','salle','classroom','roomnumber']),
];

// ───── Liste des cours ayant des activités F2F ─────
$courseoptions = [0 => get_string('allcourses', 'local_f2freport')];
$facetomodid = $DB->get_field('modules', 'id', ['name' => 'facetoface'], IGNORE_MISSING);
if ($facetomodid) {
    $cms = $DB->get_records('course_modules', ['module' => $facetomodid], '', 'id, course');
    if ($cms) {
        $courseids = array_values(array_unique(array_map(function($cm){ return (int)$cm->course; }, $cms)));
        if (!empty($courseids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
            $courses = $DB->get_records_select_menu('course', "id $insql", $inparams, 'fullname ASC', 'id, fullname');
            foreach ($courses as $cid => $cname) {
                $courseoptions[(int)$cid] = format_string($cname);
            }
        }
    }
}

// ───── IDs des champs de session (city/venue/room) via alias ─────
$fieldids = ['city' => null, 'venue' => null, 'room' => null];
if ($DB->get_manager()->table_exists('facetoface_session_field')) {
    $fields = $DB->get_records('facetoface_session_field', null, '', 'id, shortname, name');
    foreach ($fields as $f) {
        $sn = core_text::strtolower(trim($f->shortname ?? ''));
        $nm = core_text::strtolower(trim($f->name ?? ''));
        // City
        if ($fieldids['city'] === null) {
            if (in_array($sn, $aliases['city'], true) || in_array($nm, $aliases['city'], true)) {
                $fieldids['city'] = (int)$f->id;
            } else {
                foreach ($aliases['city'] as $needle) {
                    if (($sn !== '' && strpos($sn, $needle) !== false) || ($nm !== '' && strpos($nm, $needle) !== false)) {
                        $fieldids['city'] = (int)$f->id; break;
                    }
                }
            }
        }
        // Venue
        if ($fieldids['venue'] === null) {
            if (in_array($sn, $aliases['venue'], true) || in_array($nm, $aliases['venue'], true)) {
                $fieldids['venue'] = (int)$f->id;
            } else {
                foreach ($aliases['venue'] as $needle) {
                    if (($sn !== '' && strpos($sn, $needle) !== false) || ($nm !== '' && strpos($nm, $needle) !== false)) {
                        $fieldids['venue'] = (int)$f->id; break;
                    }
                }
            }
        }
        // Room
        if ($fieldids['room'] === null) {
            if (in_array($sn, $aliases['room'], true) || in_array($nm, $aliases['room'], true)) {
                $fieldids['room'] = (int)$f->id;
            } else {
                foreach ($aliases['room'] as $needle) {
                    if (($sn !== '' && strpos($sn, $needle) !== false) || ($nm !== '' && strpos($nm, $needle) !== false)) {
                        $fieldids['room'] = (int)$f->id; break;
                    }
                }
            }
        }
    }
}

// ───── Formulaire des filtres ─────
$customdata  = ['courseoptions' => $courseoptions];
$form        = new report_filter_form($PAGE->url, $customdata);

// Reset
if (optional_param('resetbutton', null, PARAM_RAW) !== null) {
    redirect($PAGE->url);
}

// Lecture des filtres (dates peuvent arriver en array)
$courseid   = optional_param('courseid', 0, PARAM_INT);
$futureonly = (bool)optional_param('futureonly', 0, PARAM_BOOL);

$datefromarr = optional_param_array('datefrom', null, PARAM_INT);
$datetoarr   = optional_param_array('dateto',   null, PARAM_INT);

$datefrom = is_array($datefromarr) ? f2f_normalize_date($datefromarr) : optional_param('datefrom', 0, PARAM_INT);
$dateto   = is_array($datetoarr)   ? f2f_normalize_date($datetoarr)   : optional_param('dateto',   0, PARAM_INT);

// Préremplissage form
$form->set_data([
    'courseid'   => $courseid,
    'datefrom'   => $datefrom ?: null,
    'dateto'     => $dateto   ?: null,
    'futureonly' => $futureonly ? 1 : 0,
]);

$filters = [
    'courseid'   => $courseid,
    'datefrom'   => $datefrom ?: null,
    'dateto'     => $dateto   ?: null,
    'futureonly' => $futureonly,
];

// ───── Table paginée + tri ─────
$table = new sessions_table('local_f2freport_sessions', $filters, $fieldids);

// Base URL propre
$params = [];
if (!empty($courseid))   { $params['courseid'] = $courseid; }
if (!empty($datefrom))   { $params['datefrom'] = $datefrom; }
if (!empty($dateto))     { $params['dateto']   = $dateto; }
if (!empty($futureonly)) { $params['futureonly'] = 1; }

$baseurl = new moodle_url('/local/f2freport/report.php', $params);
$table->define_baseurl($baseurl);

// Rendu
ob_start();
$table->out($pagesize, false);
$tablehtml = ob_get_clean();

// Compteur 2D.2
$totalrows    = $table->get_totalrows();
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
