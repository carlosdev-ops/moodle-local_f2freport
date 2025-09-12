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
 * Table for displaying face-to-face sessions.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_f2freport\table;

use local_f2freport\report_builder;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

class sessions_table extends \table_sql {
    /** @var report_builder $builder The report builder instance. */
    protected $builder;

    /**
     * Constructor.
     *
     * @param string $uniqueid The unique ID for the table.
     * @param report_builder $builder The report builder instance.
     * @param array $filters The filters to apply.
     * @param array $fieldids The field IDs for city, venue, and room.
     */
    public function __construct(string $uniqueid, report_builder $builder, array $filters, array $fieldids) {
        parent::__construct($uniqueid);
        $this->builder = $builder;

        // Determine columns/headers from admin settings.
        $cfg = get_config('local_f2freport') ?: new \stdClass();
        $allcols = [
            'courseid'          => get_string('courseid', 'local_f2freport'),
            'sessionid'         => get_string('sessionid', 'local_f2freport'),
            'timestart'         => get_string('timestart', 'local_f2freport'),
            'timefinish'        => get_string('timefinish', 'local_f2freport'),
            'city'              => get_string('city', 'local_f2freport'),
            'venue'             => get_string('venue', 'local_f2freport'),
            'room'              => get_string('room', 'local_f2freport'),
            'totalparticipants' => get_string('totalparticipants', 'local_f2freport'),
            'coursefullname'    => get_string('coursefullname', 'local_f2freport'),
        ];

        $enabledkeys = [];
        if (!empty($cfg->columns)) {
            if (is_array($cfg->columns)) {
                // Admin_setting_configmulticheckbox → associative array col => 1/0.
                foreach ($cfg->columns as $k => $v) {
                    if (!empty($v) && isset($allcols[$k])) {
                        $enabledkeys[] = $k;
                    }
                }
            } else if (is_string($cfg->columns)) {
                // Fallback if stored as CSV.
                foreach (explode(',', $cfg->columns) as $k) {
                    $k = trim($k);
                    if ($k !== '' && isset($allcols[$k])) {
                        $enabledkeys[] = $k;
                    }
                }
            }
        }
        if (empty($enabledkeys)) {
            $enabledkeys = array_keys($allcols); // Default to all.
        }

        $columns = $enabledkeys;
        $headers = array_map(function ($k) use ($allcols) {
            return $allcols[$k];
        }, $enabledkeys);

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->sortable(true, 'timestart', SORT_ASC);
        $this->collapsible(false);
        $this->pageable(true);

        [$fields, $from, $where, $params, $countsql] = $this->builder->build_sql($filters, $fieldids);
        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql($countsql, $params);
    }

    /**
     * Format the timestart column.
     *
     * @param \stdClass $row The row data.
     * @return string The formatted column content.
     */
    public function col_timestart($row): string {
        if (!empty($row->timestart)) {
            return userdate((int)$row->timestart, get_string('strftimedatetime', 'langconfig'));
        }
        return get_string('notapplicable', 'local_f2freport');
    }

    /**
     * Format the timefinish column.
     *
     * @param \stdClass $row The row data.
     * @return string The formatted column content.
     */
    public function col_timefinish($row): string {
        if (!empty($row->timefinish)) {
            return userdate((int)$row->timefinish, get_string('strftimedatetime', 'langconfig'));
        }
        return get_string('notapplicable', 'local_f2freport');
    }

    /**
     * Format the city column.
     *
     * @param \stdClass $row The row data.
     * @return string The formatted column content.
     */
    public function col_city($row): string {
        return format_string($row->city);
    }

    /**
     * Format the venue column.
     *
     * @param \stdClass $row The row data.
     * @return string The formatted column content.
     */
    public function col_venue($row): string {
        return format_string($row->venue);
    }

    /**
     * Format the room column.
     *
     * @param \stdClass $row The row data.
     * @return string The formatted column content.
     */
    public function col_room($row): string {
        return format_string($row->room);
    }

    /**
     * Format the totalparticipants column.
     * Displays "present / signed up".
     *
     * @param \stdClass $row The row data.
     * @return string The formatted column content.
     */
    public function col_totalparticipants($row): string {
        $present = (int)($row->presentcount ?? 0);
        $total   = (int)($row->totalparticipants ?? 0);
        return $present . ' / ' . $total;
    }

    /**
     * Format the coursefullname column.
     *
     * @param \stdClass $row The row data.
     * @return string The formatted column content.
     */
    public function col_coursefullname($row): string {
        return format_string($row->coursefullname);
    }

    /**
     * Get the total number of rows.
     *
     * @return int The total number of rows.
     */
    public function get_totalrows(): int {
        return (int)($this->totalrows ?? 0);
    }
}
