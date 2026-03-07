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
 * Upgrade script for the local_f2freport plugin.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function for the local_f2freport plugin.
 *
 * @param int $oldversion The old version number.
 * @return bool Always returns true.
 */
function xmldb_local_f2freport_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Add upgrade steps here as needed.
    // Example:
    // if ($oldversion < 2025091402) {
    //     // Upgrade step for version 2025091402.
    //     upgrade_plugin_savepoint(true, 2025091402, 'local', 'f2freport');
    // }

    return true;
}
