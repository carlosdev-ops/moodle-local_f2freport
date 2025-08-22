<?php
namespace local_f2freport\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class report_filter_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        // Options de cours injectées par le contrôleur.
        $courseoptions = $this->_customdata['courseoptions'] ?? [];
        if (empty($courseoptions)) {
            $courseoptions = [0 => get_string('allcourses', 'local_f2freport')];
        }

        // Sélecteur de cours.
        $mform->addElement('select', 'courseid',
            get_string('filtercourse', 'local_f2freport'),
            $courseoptions
        );
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', 0);

        // Case "sessions à venir".
        $mform->addElement('advcheckbox', 'futureonly',
            get_string('futureonly', 'local_f2freport')
        );
        $mform->setType('futureonly', PARAM_BOOL);
        $mform->setDefault('futureonly', 0);

        // Boutons "Filtrer" et "Réinitialiser".
        $buttons = [];
        $buttons[] = $mform->createElement('submit', 'submitbutton', get_string('filter', 'local_f2freport'));
        $buttons[] = $mform->createElement('submit', 'resetbutton',  get_string('reset',  'local_f2freport'));
        $mform->addGroup($buttons, 'actions', '', ' ', false);
    }
}
