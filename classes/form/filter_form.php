<?php
namespace local_f2freport\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class filter_form extends \moodleform {

    public function definition(): void {
        $mform = $this->_form;

        // Période.
        $mform->addElement('header', 'periodhdr', get_string('filter_period_header', 'local_f2freport'));
        $mform->addElement('date_selector', 'datefrom', get_string('filter_datefrom', 'local_f2freport'), ['optional' => true]);
        $mform->addHelpButton('datefrom', 'filter_datefrom', 'local_f2freport');
        $mform->addElement('date_selector', 'dateto', get_string('filter_dateto', 'local_f2freport'), ['optional' => true]);
        $mform->addHelpButton('dateto', 'filter_dateto', 'local_f2freport');

        // Autres filtres.
        $mform->addElement('header', 'otherhdr', get_string('filter_other_header', 'local_f2freport'));

        $mform->addElement('text', 'location', get_string('filter_location', 'local_f2freport'), ['size' => 32]);
        $mform->setType('location', PARAM_TEXT);
        $mform->addHelpButton('location', 'filter_location', 'local_f2freport');

        $mform->addElement(
            'autocomplete', 'trainerids', get_string('filter_trainers', 'local_f2freport'), [],
            ['multiple' => true, 'ajax' => 'core_user/form_user_selector',
             'noselectionstring' => get_string('filter_trainers_noselect', 'local_f2freport')]
        );
        $mform->addHelpButton('trainerids', 'filter_trainers', 'local_f2freport');

        $mform->addElement('select', 'status', get_string('filter_status', 'local_f2freport'), [
            '' => get_string('filter_any', 'local_f2freport'),
            'planned' => get_string('status_planned', 'local_f2freport'),
            'completed' => get_string('status_completed', 'local_f2freport'),
            'cancelled' => get_string('status_cancelled', 'local_f2freport'),
        ]);
        $mform->setDefault('status', '');

        // Actions.
        $buttons = [];
        $buttons[] = $mform->createElement('submit', 'applyfilters', get_string('applyfilters', 'local_f2freport'));
        $buttons[] = $mform->createElement('submit', 'resetfilters', get_string('resetfilters', 'local_f2freport'));
        $mform->addGroup($buttons, 'actionsgrp', '', [' '], false);

        // Règle date de fin >= date de début.
        $mform->registerRule('datedesc', 'callback', 'local_f2freport\\form\\filter_form::validate_dates', 'plugin');
        $mform->addRule('dateto', get_string('error_daterange', 'local_f2freport'), 'datedesc', null, 'server');
    }

    public static function validate_dates($value, $args): bool {
        $datefrom = optional_param('datefrom', 0, PARAM_INT);
        $dateto   = optional_param('dateto', 0, PARAM_INT);
        if (!empty($datefrom) && !empty($dateto) && (int)$dateto < (int)$datefrom) {
            return false;
        }
        return true;
    }

    public function is_cancelled() { return false; }
}
