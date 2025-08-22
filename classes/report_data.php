<?php
// This file is part of Moodle - https://moodle.org/.
//
// SPDX-License-Identifier: GPL-3.0-or-later
//
// Business/data layer for local_f2freport.

namespace local_f2freport;

defined('MOODLE_INTERNAL') || die();

/**
 * Data provider for Face-to-face sessions report.
 *
 * Rappels:
 * - Utiliser l'API DB Moodle ($DB->get_records_sql()).
 * - Aucune présentation ici.
 * - Chaînes via get_string().
 */
class report_data {

    /**
     * Retourne les sessions F2F (ville/lieu/salle, trainer, participants, etc.)
     * et un menu de cours pour le filtre.
     *
     * @param int  $courseid   Filtre sur un cours (0 = tous).
     * @param bool $futureonly Si true, sessions dont le début >= maintenant.
     * @return array [array $records, array $coursesmenu]
     * @throws \moodle_exception si les champs F2F requis sont absents.
     */
    public static function get_sessions_with_meta(int $courseid, bool $futureonly): array {
        global $DB;

        // 1) Résoudre les fieldid des champs de session requis.
        $fieldids = self::resolve_session_fieldids(['location', 'venue', 'room']);
        if (in_array(null, $fieldids, true)) {
            // Chaîne fournie par lang/xx/local_f2freport.php -> 'missingfields'
            throw new \moodle_exception('missingfields', 'local_f2freport');
        }
        [$fieldid_city, $fieldid_venue, $fieldid_room] = $fieldids;

        // 2) Obtenir le rôle 'editingteacher' (fallback: 3).
        $editingteacher = (int)($DB->get_field('role', 'id', ['shortname' => 'editingteacher']) ?? 3);

        // 3) WHERE dynamique + params alignés.
        $whereparts = [];
        $params = [
            // Placeholders distincts pour éviter les réutilisations multiples.
            'not_city'  => get_string('notspecified', 'local_f2freport'),
            'not_venue' => get_string('notspecified', 'local_f2freport'),
            'not_room'  => get_string('notspecified', 'local_f2freport'),

            'coursectx' => CONTEXT_COURSE,
            'roleid'    => $editingteacher,

            'cityfid'   => $fieldid_city,
            'venuefid'  => $fieldid_venue,
            'roomfid'   => $fieldid_room,
        ];

        if ($futureonly) {
            $whereparts[]  = 'sd.timestart >= :now';
            $params['now'] = time();
        }
        if ($courseid > 0) {
            $whereparts[]       = 'c.id = :courseid';
            $params['courseid'] = $courseid;
        }

        $whereclause = $whereparts ? (' AND ' . implode(' AND ', $whereparts)) : '';

        // 4) SQL: placeholders uniques 1:1 avec $params.
        $sql = "
            SELECT 
                s.id AS sessionid,
                f.course AS courseid,
                c.fullname AS coursefullname,
                sd.timestart,
                sd.timefinish,
                COALESCE(sfcity.data,  :not_city)  AS city,
                COALESCE(sfvenue.data, :not_venue) AS venue,
                COALESCE(sfroom.data,  :not_room)  AS room,
                (
                    SELECT COUNT(1)
                      FROM {facetoface_signups} su
                     WHERE su.sessionid = s.id
                ) AS totalparticipants,
                (
                    SELECT CONCAT(u.firstname, ' ', u.lastname)
                      FROM {role_assignments} ra
                      JOIN {context} ctx ON ctx.id = ra.contextid
                      JOIN {user} u      ON u.id = ra.userid
                     WHERE ctx.contextlevel = :coursectx
                       AND ctx.instanceid  = c.id
                       AND ra.roleid       = :roleid
                       AND u.deleted = 0
                     LIMIT 1
                ) AS trainer
              FROM {facetoface_sessions} s
              JOIN {facetoface_sessions_dates} sd ON sd.sessionid = s.id
              JOIN {facetoface} f  ON f.id = s.facetoface
              JOIN {course} c      ON c.id = f.course
         LEFT JOIN {facetoface_session_data} sfcity  ON sfcity.sessionid  = s.id AND sfcity.fieldid  = :cityfid
         LEFT JOIN {facetoface_session_data} sfvenue ON sfvenue.sessionid = s.id AND sfvenue.fieldid = :venuefid
         LEFT JOIN {facetoface_session_data} sfroom  ON sfroom.sessionid  = s.id AND sfroom.fieldid  = :roomfid
             WHERE 1=1
                   {$whereclause}
          ORDER BY sd.timestart ASC
        ";

        $records = $DB->get_records_sql($sql, $params);

        // 5) Construire le menu des cours (tri alpha, unique).
        $coursesmenu = [];
        foreach ($records as $r) {
            if (!isset($coursesmenu[$r->courseid])) {
                $coursesmenu[$r->courseid] = $r->coursefullname;
            }
        }
        asort($coursesmenu, SORT_NATURAL | SORT_FLAG_CASE);

        return [$records, $coursesmenu];
    }

    /**
     * Résout les IDs des champs de session F2F par shortname.
     *
     * @param string[] $shortnames Ex: ['location','venue','room'].
     * @return array [int|null, int|null, int|null] IDs dans le même ordre.
     */
    private static function resolve_session_fieldids(array $shortnames): array {
        global $DB;

        // Préparer map {shortname => null}
        $map = [];
        foreach ($shortnames as $sn) {
            $map[$sn] = null;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($shortnames, SQL_PARAMS_NAMED);
        $recs = $DB->get_records_select(
            'facetoface_session_field',
            "shortname {$insql}",
            $inparams,
            '',
            'id, shortname'
        );

        foreach ($recs as $r) {
            $map[$r->shortname] = (int)$r->id;
        }

        // Retourne les valeurs dans l'ordre demandé.
        $out = [];
        foreach ($shortnames as $sn) {
            $out[] = $map[$sn] ?? null;
        }
        return $out;
    }
}
