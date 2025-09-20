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

namespace local_f2freport;

/**
 * Report builder for F2F local.
 *
 * This class is responsible for fetching and preparing all data required for the local.
 *
 * @package     local_f2freport
 * @copyright   2025 Carlos
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_builder {
    /**
     * Normalises a date field from a date_selector.
     * - If array [day,month,year] -> timestamp 00:00.
     * - If int -> int.
     * - Else 0.
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
     * Note: This method is kept for backward compatibility and tests,
     * but the main UI now uses text search instead of a dropdown.
     *
     * @return array An array of course options for a select menu.
     */
    public function get_course_options(): array {
        global $DB;
        $courseoptions = [0 => get_string('allcourses', 'local_f2freport')];

        try {
            $facetofaceid = $DB->get_field('modules', 'id', ['name' => 'facetoface'], IGNORE_MISSING);
            if (!$facetofaceid) {
                return $courseoptions;
            }

            // Select courses that have at least one face-to-face session.
            $sql = "SELECT DISTINCT c.id, c.fullname
                    FROM {course} c
                    JOIN {facetoface} f ON f.course = c.id
                    WHERE c.visible = 1
                    ORDER BY c.fullname ASC";

            $courses = $DB->get_records_sql($sql);

            foreach ($courses as $course) {
                $context = \context_course::instance((int)$course->id, IGNORE_MISSING);
                if ($context && has_capability('moodle/course:view', $context)) {
                    $label = $course->id . ' - ' . format_string(
                        $course->fullname,
                        true,
                        ['context' => $context]
                    );
                    $courseoptions[(int)$course->id] = $label;
                }
            }
        } catch (\Exception $e) {
            debugging('Error loading course options: ' . $e->getMessage());
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
        $venuealiases = [
            'venue', 'lieu', 'building', 'site', 'centre', 'center', 'campus',
        ];
        $aliases = [
            'city'  => self::parse_aliases($cfg->aliases_city ?? '', ['city', 'ville', 'location']),
            'venue' => self::parse_aliases($cfg->aliases_venue ?? '', $venuealiases),
            'room'  => self::parse_aliases($cfg->aliases_room ?? '', ['room', 'salle', 'classroom', 'roomnumber']),
        ];

        $fieldids = ['city' => null, 'venue' => null, 'room' => null];
        if ($DB->get_manager()->table_exists('facetoface_session_field')) {
            $fields = $DB->get_records('facetoface_session_field', null, '', 'id, shortname, name');
            foreach ($fields as $f) {
                $sn = \core_text::strtolower(trim($f->shortname ?? ''));
                $nm = \core_text::strtolower(trim($f->name ?? ''));
                // City.
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
                // Venue.
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
                // Room.
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
     * Parse course filter text with comma-separated multiple selection.
     *
     * @param string $coursetext The course filter text with comma-separated terms.
     * @return array An array containing SQL conditions and parameters.
     */
    public static function parse_course_filter(string $coursetext): array {
        global $DB;

        $coursetext = trim($coursetext);
        if (empty($coursetext)) {
            return ['conditions' => [], 'params' => []];
        }

        // Split by commas
        $terms = explode(',', $coursetext);

        $conditions = [];
        $params = [];
        $paramCounter = 0;

        foreach ($terms as $term) {
            $term = trim($term);
            if (empty($term)) {
                continue;
            }

            // Remove quotes if present
            $searchTerm = trim($term, '"\'');
            $paramCounter++;

            // Create condition for each term (search in course name or ID)
            if (is_numeric($searchTerm)) {
                $condition = "(c.id = :courseid_{$paramCounter} OR " .
                           $DB->sql_like('c.fullname', ":coursetext_{$paramCounter}", false) . ")";
                $params["courseid_{$paramCounter}"] = (int)$searchTerm;
                $params["coursetext_{$paramCounter}"] = '%' . $DB->sql_like_escape($searchTerm) . '%';
            } else {
                $condition = $DB->sql_like('c.fullname', ":coursetext_{$paramCounter}", false);
                $params["coursetext_{$paramCounter}"] = '%' . $DB->sql_like_escape($searchTerm) . '%';
            }

            // All comma-separated terms are combined with OR logic
            $conditions[] = ['type' => 'OR', 'condition' => $condition];
        }

        return ['conditions' => $conditions, 'params' => $params];
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
            c.fullname AS coursename,
            s.id AS sessionid,
            CASE
                WHEN EXISTS (SELECT 1 FROM {facetoface_sessions_dates} WHERE sessionid = s.id AND timestart IS NOT NULL AND timestart > 0)
                THEN {$timestartcol}
                ELSE NULL
            END AS timestart,
            CASE
                WHEN EXISTS (SELECT 1 FROM {facetoface_sessions_dates} WHERE sessionid = s.id AND timestart IS NOT NULL AND timestart > 0)
                THEN {$timefinishcol}
                ELSE NULL
            END AS timefinish,
            COALESCE(dcity.data,  :ns_city)   AS city,
            COALESCE(dvenue.data, :ns_venue)  AS venue,
            COALESCE(droom.data,  :ns_room)   AS room,
            COALESCE(su.participants, 0) AS totalparticipants,
            COALESCE(att.presentcount, 0) AS presentcount,
            s.capacity AS maxcapacity
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
                SELECT fsu.sessionid, COUNT(DISTINCT fsu.userid) AS participants
                  FROM {facetoface_signups} fsu
                  JOIN (
                    SELECT signupid, MAX(id) AS maxstatusid
                      FROM {facetoface_signups_status}
                     WHERE superceded = 0
                  GROUP BY signupid
                  ) latest ON latest.signupid = fsu.id
                  JOIN {facetoface_signups_status} fss ON fss.id = latest.maxstatusid
                 WHERE fss.statuscode IN (40, 50, 60, 70, 80, 90, 100)
              GROUP BY fsu.sessionid
            ) su ON su.sessionid = s.id

            LEFT JOIN (
                SELECT fsu.sessionid, COUNT(DISTINCT fsu.userid) AS presentcount
                  FROM {facetoface_signups} fsu
                  JOIN (
                    SELECT signupid, MAX(id) AS maxstatusid
                      FROM {facetoface_signups_status}
                     WHERE superceded = 0
                  GROUP BY signupid
                  ) latest ON latest.signupid = fsu.id
                  JOIN {facetoface_signups_status} fss ON fss.id = latest.maxstatusid
                 WHERE fss.statuscode IN (90, 100)
              GROUP BY fsu.sessionid
            ) att ON att.sessionid = s.id
        ";

        $whereclauses = ['1=1'];
        $params = [
            'ns_city'      => get_string('notspecified', 'local_f2freport'),
            'ns_venue'     => get_string('notspecified', 'local_f2freport'),
            'ns_room'      => get_string('notspecified', 'local_f2freport'),
            'cityfieldid'  => $fieldids['city'] ?? 0,
            'venuefieldid' => $fieldids['venue'] ?? 0,
            'roomfieldid'  => $fieldids['room'] ?? 0,
        ];

        // Apply advanced course filter with logical operators.
        if (!empty($filters['coursetext'])) {
            $courseFilterResult = self::parse_course_filter($filters['coursetext']);
            if (!empty($courseFilterResult['conditions'])) {
                $courseConditions = [];
                $notConditions = [];

                foreach ($courseFilterResult['conditions'] as $conditionData) {
                    if ($conditionData['type'] === 'NOT') {
                        $notConditions[] = $conditionData['condition'];
                    } else {
                        $courseConditions[] = [
                            'condition' => $conditionData['condition'],
                            'operator' => $conditionData['type']
                        ];
                    }
                }

                $finalCourseConditions = [];

                // Handle positive conditions (AND/OR)
                if (!empty($courseConditions)) {
                    $andConditions = [];
                    $orConditions = [];

                    foreach ($courseConditions as $condData) {
                        if ($condData['operator'] === 'OR') {
                            $orConditions[] = $condData['condition'];
                        } else {
                            $andConditions[] = $condData['condition'];
                        }
                    }

                    if (!empty($andConditions)) {
                        $finalCourseConditions[] = '(' . implode(' AND ', $andConditions) . ')';
                    }
                    if (!empty($orConditions)) {
                        $finalCourseConditions[] = '(' . implode(' OR ', $orConditions) . ')';
                    }
                }

                // Combine positive conditions
                if (!empty($finalCourseConditions)) {
                    $positiveCondition = implode(' AND ', $finalCourseConditions);
                    $whereclauses[] = "($positiveCondition)";
                }

                // Handle negative conditions (NOT)
                foreach ($notConditions as $notCondition) {
                    $whereclauses[] = $notCondition;
                }

                // Add the filter parameters
                $params = array_merge($params, $courseFilterResult['params']);
            }
        }

        // Apply date filters, taking into account waitlist inclusion.
        if (!empty($filters['includewaitlist'])) {
            // When including waitlists, we include sessions with and without valid scheduled dates
            $dateconditions = [];

            // Always include sessions without valid scheduled dates (waitlists/sessions with NULL dates)
            $dateconditions[] = "(NOT EXISTS (SELECT 1 FROM {facetoface_sessions_dates} WHERE sessionid = s.id AND timestart IS NOT NULL AND timestart > 0))";

            // Add date range conditions for sessions WITH valid scheduled dates
            if (!empty($filters['startts']) && !empty($filters['endts'])) {
                $dateconditions[] = "(EXISTS (SELECT 1 FROM {facetoface_sessions_dates} WHERE sessionid = s.id AND timestart IS NOT NULL AND timestart > 0) AND {$timestartcol} >= :startts AND {$timefinishcol} <= :endts)";
                $params['startts'] = (int)$filters['startts'];
                $params['endts'] = (int)$filters['endts'];
            } else if (!empty($filters['startts'])) {
                $dateconditions[] = "(EXISTS (SELECT 1 FROM {facetoface_sessions_dates} WHERE sessionid = s.id AND timestart IS NOT NULL AND timestart > 0) AND {$timestartcol} >= :startts)";
                $params['startts'] = (int)$filters['startts'];
            } else if (!empty($filters['endts'])) {
                $dateconditions[] = "(EXISTS (SELECT 1 FROM {facetoface_sessions_dates} WHERE sessionid = s.id AND timestart IS NOT NULL AND timestart > 0) AND {$timefinishcol} <= :endts)";
                $params['endts'] = (int)$filters['endts'];
            } else {
                // No date filters, include all sessions with valid scheduled dates too
                $dateconditions[] = "(EXISTS (SELECT 1 FROM {facetoface_sessions_dates} WHERE sessionid = s.id AND timestart IS NOT NULL AND timestart > 0))";
            }

            $whereclauses[] = "(" . implode(' OR ', $dateconditions) . ")";
        } else {
            // Exclude sessions without valid scheduled dates (standard behavior) - only show sessions with valid dates
            $whereclauses[] = "(EXISTS (SELECT 1 FROM {facetoface_sessions_dates} WHERE sessionid = s.id AND timestart IS NOT NULL AND timestart > 0))";

            // Apply start date filter.
            if (!empty($filters['startts'])) {
                $whereclauses[] = "{$timestartcol} >= :startts";
                $params['startts'] = (int)$filters['startts'];
            }

            // Apply end date filter.
            if (!empty($filters['endts'])) {
                $whereclauses[] = "{$timefinishcol} <= :endts";
                $params['endts'] = (int)$filters['endts'];
            }
        }

        $where = implode(' AND ', $whereclauses);

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
