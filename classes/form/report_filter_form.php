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
 * Filter form for the face-to-face report.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_f2freport\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class report_filter_form extends \moodleform {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        // Course options injected by the controller.
        $courseoptions = $this->_customdata['courseoptions'] ?? [];
        if (empty($courseoptions)) {
            $courseoptions = [0 => get_string('allcourses', 'local_f2freport')];
        }

        // Course filter.
        $mform->addElement(
            'select',
            'courseid',
            get_string('filtercourse', 'local_f2freport'),
            $courseoptions
        );
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', 0);

        // Optional date filters.
        $mform->addElement(
            'date_selector',
            'datefrom',
            get_string('datefrom', 'local_f2freport'),
            ['optional' => true]
        );
        $mform->setType('datefrom', PARAM_INT);

        $mform->addElement(
            'date_selector',
            'dateto',
            get_string('dateto', 'local_f2freport'),
            ['optional' => true]
        );
        $mform->setType('dateto', PARAM_INT);

        // "Future only" checkbox.
        $mform->addElement(
            'advcheckbox',
            'futureonly',
            get_string('futureonly', 'local_f2freport')
        );
        $mform->setType('futureonly', PARAM_BOOL);
        $mform->setDefault('futureonly', 0);

        // Action buttons.
        $buttons = [];
        $buttons[] = $mform->createElement('submit', 'submitbutton', get_string('filter', 'local_f2freport'));
        $buttons[] = $mform->createElement('submit', 'resetbutton', get_string('reset', 'local_f2freport'));
        $mform->addGroup($buttons, 'actions', '', ' ', false);
    }
}
