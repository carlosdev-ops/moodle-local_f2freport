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
 * Library functions for the Face-to-face report plugin.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Inject the face-to-face report link into the navigation menu.
 *
 * @param global_navigation $navigation The navigation tree instance
 */
function local_f2freport_extend_navigation(global_navigation $navigation) {
    global $PAGE;

    if (has_capability('local/f2freport:viewreport', context_system::instance())) {
        $node = $navigation->add(
            get_string('pluginname', 'local_f2freport'),
            new moodle_url('/local/f2freport/report.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_f2freport'
        );

        if ($PAGE->url->compare(new moodle_url('/local/f2freport/report.php'), URL_MATCH_BASE)) {
            $node->make_active();
        }
    }
}

/**
 * Add the face-to-face report link to the settings menu.
 *
 * @param settings_navigation $settingsnav The settings navigation instance
 * @param context $context The current context
 */
function local_f2freport_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    if ($context->contextlevel == CONTEXT_SYSTEM && has_capability('local/f2freport:viewreport', $context)) {
        $reportnode = $settingsnav->find('reports', navigation_node::TYPE_ROOTNODE);
        if ($reportnode) {
            $reportnode->add(
                get_string('pluginname', 'local_f2freport'),
                new moodle_url('/local/f2freport/report.php'),
                navigation_node::TYPE_SETTING,
                null,
                'local_f2freport_report'
            );
        }
    }
}