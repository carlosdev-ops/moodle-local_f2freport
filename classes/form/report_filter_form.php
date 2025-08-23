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

        // ── Cours
        $mform->addElement('select', 'courseid',
            get_string('filtercourse', 'local_f2freport'),
            $courseoptions
        );
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', 0);

        // ── Dates (optionnelles)
        // Pour 3.11, on reste simple: date (sans heure). On appliquera 00:00 / 23:59 en 2C.
        $mform->addElement('date_selector', 'datefrom', get_string('datefrom', 'local_f2freport'),
            ['optional' => true]
        );
        $mform->setType('datefrom', PARAM_INT);

        $mform->addElement('date_selector', 'dateto', get_string('dateto', 'local_f2freport'),
            ['optional' => true]
        );
        $mform->setType('dateto', PARAM_INT);

        // ── Sessions à venir (aura priorité sur datefrom en 2C)
        $mform->addElement('advcheckbox', 'futureonly',
            get_string('futureonly', 'local_f2freport')
        );
        $mform->setType('futureonly', PARAM_BOOL);
        $mform->setDefault('futureonly', 0);

        // ── Actions
        $buttons = [];
        $buttons[] = $mform->createElement('submit', 'submitbutton', get_string('filter', 'local_f2freport'));
        $buttons[] = $mform->createElement('submit', 'resetbutton',  get_string('reset',  'local_f2freport'));
        $mform->addGroup($buttons, 'actions', '', ' ', false);
    }
}
