<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute and/or modify
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

namespace local_f2freport\table;

use local_f2freport\report_builder;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Table for displaying face-to-face sessions.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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

        // Define columns and headers.
        $columns = [
            'sessionid',
            'courseid',
            'coursename',
            'timestart',
            'timefinish',
            'city',
            'venue',
            'room',
            'totalparticipants',
        ];

        $headers = [
            get_string('sessionid', 'local_f2freport'),
            get_string('courseid', 'local_f2freport'),
            get_string('coursename', 'local_f2freport'),
            get_string('timestart', 'local_f2freport'),
            get_string('timefinish', 'local_f2freport'),
            get_string('city', 'local_f2freport'),
            get_string('venue', 'local_f2freport'),
            get_string('room', 'local_f2freport'),
            get_string('totalparticipants', 'local_f2freport'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        // Set table properties.
        $this->sortable(true, 'timestart', SORT_ASC);
        $this->collapsible(false);
        $this->pageable(true);

        // Set the SQL query.
        [$fields, $from, $where, $params, $countsql] = $this->builder->build_sql($filters, $fieldids);
        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql($countsql, $params);
    }


    /**
     * Format the courseid column.
     *
     * @param \stdClass $row The row data.
     * @return string The formatted column content.
     */
    public function col_courseid($row): string {
        return (string)$row->courseid;
    }

    /**
     * Format the coursename column.
     *
     * @param \stdClass $row The row data.
     * @return string The formatted column content.
     */
    public function col_coursename($row): string {
        // Guard: ensure we have a valid course ID before creating course links.
        if (!empty($row->courseid) && !empty($row->coursename)) {
            $url = new \moodle_url('/course/view.php', ['id' => $row->courseid]);
            return \html_writer::link($url, format_string($row->coursename), ['target' => '_blank']);
        }
        return format_string($row->coursename ?? get_string('notspecified', 'local_f2freport'));
    }

    /**
     * Format the timestart column.
     *
     * @param \stdClass $row The row data.
     * @return string The formatted column content.
     */
    public function col_timestart($row): string {
        if (isset($row->timestart) && $row->timestart !== null && $row->timestart > 0) {
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
        if (isset($row->timefinish) && $row->timefinish !== null && $row->timefinish > 0) {
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
     * Displays "signed up / capacity" as a clickable link.
     *
     * @param \stdClass $row The row data.
     * @return string The formatted column content.
     */
    public function col_totalparticipants($row): string {
        $registered = (int)($row->totalparticipants ?? 0);
        $capacity   = (int)($row->maxcapacity ?? 0);

        // If no capacity is set, show just the registered count
        if ($capacity > 0) {
            $text = $registered . ' / ' . $capacity;
        } else {
            $text = (string)$registered;
        }

        if ($registered > 0) {
            $url = new \moodle_url('/local/f2freport/participants.php', ['sessionid' => $row->sessionid]);
            return \html_writer::link($url, $text, [
                'target' => '_blank',
                'title' => get_string('viewparticipants', 'local_f2freport')
            ]);
        }

        return $text;
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
