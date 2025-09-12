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
 * Builder class for the face-to-face report.
 *
 * This class is responsible for fetching and preparing all data
 * required for the report.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_f2freport;

defined('MOODLE_INTERNAL') || die();

class report_builder {
    /**
     * Normalises a date field from a date_selector.
     * - if array [day,month,year] -> timestamp 00:00
     * - if int -> int
     * - else 0
     *
     * @param mixed $v The value to normalize.
     * @return int The normalized timestamp.
     */
    public static function normalize_date($v): int {
        if (is_array($v) && isset($v['day'], $v['month'], $v['year'])) {
            return make_timestamp((int)$v['year'], (int)$v['month'], (int)$v['day'], 0, 0, 0);
        }
        return (int)$v;
    }

    /**
     * Parses a CSV list into an array (lowercase/trim, empty filtered).
     *
     * @param string|null $csv The CSV string to parse.
     * @param array $fallback The fallback array to return if the CSV is empty.
     * @return array The parsed array of aliases.
     */
    public static function parse_aliases(?string $csv, array $fallback): array {
        $csv = trim((string)$csv);
        if ($csv === '') {
            return $fallback;
        }
        $out = [];
        foreach (explode(',', $csv) as $token) {
            $t = \core_text::strtolower(trim($token));
            if ($t !== '') {
                $out[] = $t;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Gets the list of courses having face-to-face activities.
     *
     * @return array An array of course options for a select menu.
     */
    public function get_course_options(): array {
        global $DB;
        $courseoptions = [0 => get_string('allcourses', 'local_f2freport')];
        $facetofaceid = $DB->get_field('modules', 'id', ['name' => 'facetoface'], IGNORE_MISSING);
        if ($facetofaceid) {
            $cms = $DB->get_records('course_modules', ['module' => $facetofaceid], '', 'id, course');
            if ($cms) {
                $courseids = array_values(array_unique(array_map(function ($cm) {
                    return (int)$cm->course;
                }, $cms)));
                if (!empty($courseids)) {
                    [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
                    $courses = $DB->get_records_select_menu('course', "id $insql", $inparams, 'fullname ASC', 'id, fullname');
                    foreach ($courses as $cid => $fullname) {
                        $courseoptions[(int)$cid] = $cid . ' - ' . format_string($fullname);
                    }
                }
            }
        }
        return $courseoptions;
    }

    /**
     * Gets the IDs of session fields (city/venue/room) via aliases.
     *
     * @return array An array of field IDs, keyed by 'city', 'venue', 'room'.
     */
    public function get_field_ids(): array {
        global $DB;

        $cfg = get_config('local_f2freport') ?: new \stdClass();
        $aliases = [
            'city'  => self::parse_aliases($cfg->aliases_city ?? '', ['city', 'ville', 'location']),
            'venue' => self::parse_aliases($cfg->aliases_venue ?? '', ['venue', 'lieu', 'building', 'site', 'centre', 'center', 'campus']),
            'room'  => self::parse_aliases($cfg->aliases_room ?? '', ['room', 'salle', 'classroom', 'roomnumber']),
        ];

        $fieldids = ['city' => null, 'venue' => null, 'room' => null];
        if ($DB->get_manager()->table_exists('facetoface_session_field')) {
            $fields = $DB->get_records('facetoface_session_field', null, '', 'id, shortname, name');
            foreach ($fields as $f) {
                $sn = \core_text::strtolower(trim($f->shortname ?? ''));
                $nm = \core_text::strtolower(trim($f->name ?? ''));
                // City
                if ($fieldids['city'] === null) {
                    if (in_array($sn, $aliases['city'], true) || in_array($nm, $aliases['city'], true)) {
                        $fieldids['city'] = (int)$f->id;
                    } else {
                        foreach ($aliases['city'] as $needle) {
                            if (($sn !== '' && strpos($sn, $needle) !== false) || ($nm !== '' && strpos($nm, $needle) !== false)) {
                                $fieldids['city'] = (int)$f->id;
                                break;
                            }
                        }
                    }
                }
                // Venue
                if ($fieldids['venue'] === null) {
                    if (in_array($sn, $aliases['venue'], true) || in_array($nm, $aliases['venue'], true)) {
                        $fieldids['venue'] = (int)$f->id;
                    } else {
                        foreach ($aliases['venue'] as $needle) {
                            if (($sn !== '' && strpos($sn, $needle) !== false) || ($nm !== '' && strpos($nm, $needle) !== false)) {
                                $fieldids['venue'] = (int)$f->id;
                                break;
                            }
                        }
                    }
                }
                // Room
                if ($fieldids['room'] === null) {
                    if (in_array($sn, $aliases['room'], true) || in_array($nm, $aliases['room'], true)) {
                        $fieldids['room'] = (int)$f->id;
                    } else {
                        foreach ($aliases['room'] as $needle) {
                            if (($sn !== '' && strpos($sn, $needle) !== false) || ($nm !== '' && strpos($nm, $needle) !== false)) {
                                $fieldids['room'] = (int)$f->id;
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $fieldids;
    }

    /**
     * Build the SQL query for the sessions table.
     *
     * @param array $filters The filters to apply.
     * @param array $fieldids The field IDs for city, venue, and room.
     * @return array An array containing [fields, from, where, params, countsql].
     */
    public function build_sql(array $filters, array $fieldids): array {
        global $DB;

        $sessionscols   = $DB->get_columns('facetoface_sessions');
        $hasdirectdates = isset($sessionscols['timestart']) && isset($sessionscols['timefinish']);

        $timestartcol  = $hasdirectdates ? 's.timestart' : 'sd.timestart';
        $timefinishcol = $hasdirectdates ? 's.timefinish' : 'sd.timefinish';

        $fields = "
            s.id AS id,
            c.id AS courseid,
            c.fullname AS coursefullname,
            s.id AS sessionid,
            {$timestartcol} AS timestart,
            {$timefinishcol} AS timefinish,
            COALESCE(dcity.data,  :ns_city)   AS city,
            COALESCE(dvenue.data, :ns_venue)  AS venue,
            COALESCE(droom.data,  :ns_room)   AS room,
            COALESCE(su.participants, 0) AS totalparticipants,
            COALESCE(att.presentcount, 0) AS presentcount
        ";

        $from = "
            {facetoface} f
            JOIN {course} c ON c.id = f.course
            JOIN {facetoface_sessions} s ON s.facetoface = f.id
        ";

        if (!$hasdirectdates) {
            $from .= "
                LEFT JOIN (
                    SELECT sessionid, MIN(timestart) AS timestart, MAX(timefinish) AS timefinish
                      FROM {facetoface_sessions_dates}
                  GROUP BY sessionid
                ) sd ON sd.sessionid = s.id
            ";
        }

        $from .= "
            LEFT JOIN {facetoface_session_data} dcity
                ON dcity.sessionid = s.id AND dcity.fieldid = :cityfieldid
            LEFT JOIN {facetoface_session_data} dvenue
                ON dvenue.sessionid = s.id AND dvenue.fieldid = :venuefieldid
            LEFT JOIN {facetoface_session_data} droom
                ON droom.sessionid = s.id AND droom.fieldid = :roomfieldid

            LEFT JOIN (
                SELECT sessionid, COUNT(1) AS participants
                  FROM {facetoface_signups}
              GROUP BY sessionid
            ) su ON su.sessionid = s.id

            LEFT JOIN (
                SELECT fsu.sessionid, COUNT(1) AS presentcount
                  FROM {facetoface_signups} fsu
                  JOIN (
                        SELECT signupid, MAX(id) AS maxid
                          FROM {facetoface_signups_status}
                      GROUP BY signupid
                  ) last ON last.signupid = fsu.id
                  JOIN {facetoface_signups_status} fss ON fss.id = last.maxid AND fss.statuscode = 100
              GROUP BY fsu.sessionid
            ) att ON att.sessionid = s.id
        ";

        $where  = '1=1';
        $params = [
            'ns_city'   => get_string('notspecified', 'local_f2freport'),
            'ns_venue'  => get_string('notspecified', 'local_f2freport'),
            'ns_room'   => get_string('notspecified', 'local_f2freport'),
            'cityfieldid'  => $fieldids['city'] ?? 0,
            'venuefieldid' => $fieldids['venue'] ?? 0,
            'roomfieldid'  => $fieldids['room'] ?? 0,
        ];

        if (!empty($filters['courseid'])) {
            $where .= ' AND f.course = :courseid';
            $params['courseid'] = (int)$filters['courseid'];
        }

        $now = time();
        if (!empty($filters['futureonly'])) {
            $where .= " AND {$timestartcol} >= :now";
            $params['now'] = $now;
        } else {
            if (!empty($filters['datefrom'])) {
                $where .= " AND {$timestartcol} >= :datefrom";
                $params['datefrom'] = (int)$filters['datefrom'];
            }
            if (!empty($filters['dateto'])) {
                $datetoend = (int)$filters['dateto'] + 86399;
                $where .= " AND {$timestartcol} <= :dateto";
                $params['dateto'] = $datetoend;
            }
        }

        $countfrom = "
            {facetoface} f
            JOIN {course} c ON c.id = f.course
            JOIN {facetoface_sessions} s ON s.facetoface = f.id
        ";
        if (!$hasdirectdates) {
            $countfrom .= "
                LEFT JOIN (
                    SELECT sessionid, MIN(timestart) AS timestart, MAX(timefinish) AS timefinish
                      FROM {facetoface_sessions_dates}
                  GROUP BY sessionid
                ) sd ON sd.sessionid = s.id
            ";
        }
        $countsql = "SELECT COUNT(1) FROM $countfrom WHERE $where";

        return [$fields, $from, $where, $params, $countsql];
    }
}
