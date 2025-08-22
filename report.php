<?php
// Page de rapport - contrôleur minimal (UI seulement).
require(__DIR__ . '/../../config.php');

$context = context_system::instance();
require_login();
require_capability('local/f2freport:viewreport', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/f2freport/report.php'));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('trainingreporttitle', 'local_f2freport'));
$PAGE->set_heading(get_string('trainingreportheading', 'local_f2freport'));

// 2B : on prépare juste l'UI. La vraie liste de cours (F2F) viendra en 2C.
$courseoptions = [0 => get_string('allcourses', 'local_f2freport')];

$customdata  = ['courseoptions' => $courseoptions];
$form        = new \local_f2freport\form\report_filter_form($PAGE->url, $customdata);

// Gestion du bouton "Réinitialiser" : revenir à l'URL sans paramètres.
if (optional_param('resetbutton', null, PARAM_RAW) !== null) {
    redirect($PAGE->url);
}

// Préremplir avec les valeurs soumises (si présentes).
$form->set_data([
    'courseid'   => optional_param('courseid', 0, PARAM_INT),
    'futureonly' => optional_param('futureonly', 0, PARAM_BOOL),
]);

$filtershtml  = $form->render();

// 2B : pas encore de données -> pas de table. Placeholders.
$count        = 0;
$tablehtml    = ''; // En 2C, ce sera le HTML rendu par table_sql (pagination/tri).
$hasresults   = false;
$countsummary = null;

$templatectx = [
    'filtershtml'  => $filtershtml,
    'tablehtml'    => $tablehtml,
    'hasresults'   => $hasresults,
    'countsummary' => $countsummary,
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_f2freport/report', $templatectx);
echo $OUTPUT->footer();
