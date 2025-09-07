<?php
// Ce fichier fait partie du plugin local_f2freport.
// Contrôle & présentation uniquement (pas de logique métier ici).

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/f2freport:view', $context);

// URL de base (sans paramètres).
$url = new moodle_url('/local/f2freport/report.php');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'local_f2freport'));
$PAGE->set_heading(get_string('report_heading', 'local_f2freport'));

// Espace de session pour persister les filtres (compatible pagination/tri en POST).
global $SESSION, $DB;

if (!isset($SESSION->local_f2freport)) {
    $SESSION->local_f2freport = new stdClass();
}
if (!isset($SESSION->local_f2freport->filters)) {
    $SESSION->local_f2freport->filters = new stdClass();
}

// Réinitialisation explicite.
if (optional_param('resetfilters', 0, PARAM_BOOL)) {
    $SESSION->local_f2freport->filters = new stdClass();
    redirect($url);
}

// Formulaire (POST par défaut).
$mform = new \local_f2freport\form\filter_form();

// Valeurs par défaut récupérées de la session.
$defaults = clone($SESSION->local_f2freport->filters);
$mform->set_data($defaults);

// Si soumis et valide → on enregistre en session.
if ($mform->is_submitted() && $mform->is_validated()) {
    $data = $mform->get_data();
    $filters = (object)[
        'datefrom'   => !empty($data->datefrom) ? (int)$data->datefrom : null,
        'dateto'     => !empty($data->dateto) ? (int)$data->dateto : null,
        'location'   => !empty($data->location) ? trim($data->location) : null,
        'trainerids' => (!empty($data->trainerids) && is_array($data->trainerids)) ? array_map('intval', $data->trainerids) : [],
        'status'     => isset($data->status) ? (string)$data->status : '',
    ];
    $SESSION->local_f2freport->filters = $filters;
} else {
    // Sinon, on lit ce qu’on a en session (ou vide).
    $filters = isset($SESSION->local_f2freport->filters) ? $SESSION->local_f2freport->filters : (object)[
        'datefrom' => null, 'dateto' => null, 'location' => null, 'trainerids' => [], 'status' => ''
    ];
}

// Récupération des fieldids (aliases éventuels) pour city/venue/room (F2F).
$cfg = get_config('local_f2freport') ?: new stdClass();
$aliascity  = !empty($cfg->alias_city)  ? $cfg->alias_city  : 'city';
$aliasvenue = !empty($cfg->alias_venue) ? $cfg->alias_venue : 'venue';
$aliasroom  = !empty($cfg->alias_room)  ? $cfg->alias_room  : 'room';

$fieldids = [
    'city'  => (int)($DB->get_field('facetoface_session_field', 'id', ['shortname' => $aliascity],  IGNORE_MISSING) ?: 0),
    'venue' => (int)($DB->get_field('facetoface_session_field', 'id', ['shortname' => $aliasvenue], IGNORE_MISSING) ?: 0),
    'room'  => (int)($DB->get_field('facetoface_session_field', 'id', ['shortname' => $aliasroom],  IGNORE_MISSING) ?: 0),
];

// Table.
$tableclass = '\\local_f2freport\\table\\sessions_table';
if (!class_exists($tableclass)) {
    throw new moodle_exception('errortablenotfound', 'local_f2freport');
}
$table = new $tableclass('f2f_sessions', (array)$filters, $fieldids);

// Base URL SANS paramètres (comme avant) → tri/pagination restent propres.
// Les filtres viennent de la SESSION, donc pas besoin de query params.
$table->define_baseurl($url);

// Page size depuis réglages (fallback 50).
$pagesize = (int)(get_config('local_f2freport', 'pagesize') ?? 50);
if ($pagesize <= 0) { $pagesize = 50; }

// Rendu.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report_title', 'local_f2freport'), 3);

$mform->display();           // Formulaire (POST)
$table->out($pagesize, true); // Tableau avec pagination/tri
echo $OUTPUT->footer();
