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
 * Version information for the local_f2freport plugin.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_f2freport';

// Plugin version: YYYYMMDDXX format.
$plugin->version = 2025091405;

// Minimum Moodle version required.
$plugin->requires = 2021051700; // Moodle 3.11.0.

// Plugin maturity level.
$plugin->maturity = MATURITY_STABLE;

// Release version (semantic format).
$plugin->release = 'v1.1.2';

// Dependencies.
$plugin->dependencies = [
    // Only check that the facetoface plugin exists,
    // without forcing a specific version that might be incompatible.
    'mod_facetoface' => ANY_VERSION, // Accepts any version.
];
