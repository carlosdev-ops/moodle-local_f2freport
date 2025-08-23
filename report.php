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
 * Normalise une valeur issue d'un date_selector :
 * - si array [day,month,year] -> timestamp (00:00:00)
 * - si int -> renvoie tel quel
 * - sinon 0
 */
function f2f_normalize_date($v): int {
    if (is_array($v) && isset($v['day'], $v['month'], $v['year'])) {
        return make_timestamp((int)$v['year'], (int)$v['month'], (int)$v['day'], 0, 0, 0);
    }
    return (int)$v;
}

// ───── Liste des cours ayant des activités Face-to-face ─────
$courseoptions = [0 => get_string('allcourses', 'local_f2freport')];
$facetomodid = $DB->get_field('modules', 'id', ['name' => 'facetoface'], IGNORE_MISSING);
if ($facetomodid) {
    $cms = $DB->get_records('course_modules', ['module' => $facetomodid], '', 'id, course');
    if ($cms) {
        $courseids = array_values(array_unique(array_map(function($cm) {
            return (int)$cm->course;
        }, $cms)));
        if (!empty($courseids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
            $courses = $DB->get_records_select_menu('course', "id $insql", $inparams, 'fullname ASC', 'id, fullname');
            foreach ($courses as $cid => $cname) {
                $courseoptions[(int)$cid] = format_string($cname);
            }
        }
    }
}

// ───── IDs des champs de session (city/venue/room) — FR/EN + alias (location, …) ─────
$fieldids = ['city' => null, 'venue' => null, 'room' => null];
if ($DB->get_manager()->table_exists('facetoface_session_field')) {
    $fields = $DB->get_records('facetoface_session_field', null, '', 'id, shortname, name');

    // Correspondances exactes en priorité, puis partielles.
    $candidates = [
        'city'  => ['city', 'ville', 'location'],
        'venue' => ['venue', 'lieu', 'building', 'site', 'centre', 'center', 'campus'],
        'room'  => ['room', 'salle', 'classroom', 'roomnumber'],
    ];

    foreach ($fields as $f) {
        $sn = core_text::strtolower(trim($f->shortname ?? ''));
        $nm = core_text::strtolower(trim($f->name ?? ''));
        foreach ($candidates as $key => $list) {
            if (!empty($fieldids[$key])) {
                continue; // déjà trouvé
            }
            if (in_array($sn, $list, true) || in_array($nm, $list, true)) {
                $fieldids[$key] = (int)$f->id;
                continue;
            }
            foreach ($list as $needle) {
                if (($sn !== '' && strpos($sn, $needle) !== false) ||
                    ($nm !== '' && strpos($nm, $needle) !== false)) {
                    $fieldids[$key] = (int)$f->id;
                    break;
                }
            }
        }
    }
}

// ───── Formulaire des filtres ─────
$customdata  = ['courseoptions' => $courseoptions];
$form        = new report_filter_form($PAGE->url, $customdata);

// Bouton "Réinitialiser"
if (optional_param('resetbutton', null, PARAM_RAW) !== null) {
    redirect($PAGE->url);
}

// ───── Lecture des filtres (dates possibles en tableau via date_selector) ─────
$courseid   = optional_param('courseid', 0, PARAM_INT);
$futureonly = (bool)optional_param('futureonly', 0, PARAM_BOOL);

// Les dates peuvent arriver en array lors de la soumission du form.
$datefromarr = optional_param_array('datefrom', null, PARAM_INT);
$datetoarr   = optional_param_array('dateto',   null, PARAM_INT);

if (is_array($datefromarr)) {
    $datefrom = f2f_normalize_date($datefromarr);
} else {
    $datefrom = optional_param('datefrom', 0, PARAM_INT); // scalaire (timestamp) depuis l’URL
}
if (is_array($datetoarr)) {
    $dateto = f2f_normalize_date($datetoarr);
} else {
    $dateto = optional_param('dateto', 0, PARAM_INT); // scalaire (timestamp) depuis l’URL
}

// Préremplir le form (toujours des scalaires)
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

// Base URL propre (ne mettre que des scalaires non vides)
$params = [];
if (!empty($courseid))   { $params['courseid']   = $courseid; }
if (!empty($datefrom))   { $params['datefrom']   = $datefrom; }
if (!empty($dateto))     { $params['dateto']     = $dateto; }
if (!empty($futureonly)) { $params['futureonly'] = 1; }

$baseurl = new moodle_url('/local/f2freport/report.php', $params);
$table->define_baseurl($baseurl);

$pagesize = 25;
ob_start();
$table->out($pagesize, false);
$tablehtml = ob_get_clean();

// Contexte pour Mustache
$templatectx = [
    'filtershtml'  => $form->render(),
    'tablehtml'    => $tablehtml,
    'hasresults'   => (trim($tablehtml) !== '' && strpos($tablehtml, 'generaltable') !== false),
    'countsummary' => null,
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_f2freport/report', $templatectx);
echo $OUTPUT->footer();
