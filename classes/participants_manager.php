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

defined('MOODLE_INTERNAL') || die();

/**
 * Manager class for handling face-to-face session participants.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class participants_manager {

    /**
     * Get participants for a specific session.
     *
     * @param int $sessionid The session ID
     * @return array Array of participant objects
     */
    public static function get_session_participants(int $sessionid): array {
        global $DB;

        $sql = "SELECT u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                       u.middlename, u.alternatename, u.email,
                       fss.statuscode, fss.timecreated
                FROM {facetoface_signups} fs
                JOIN (
                    SELECT signupid, MAX(id) AS maxstatusid
                      FROM {facetoface_signups_status}
                     WHERE superceded = 0
                  GROUP BY signupid
                ) latest ON latest.signupid = fs.id
                JOIN {facetoface_signups_status} fss ON fss.id = latest.maxstatusid
                JOIN {user} u ON u.id = fs.userid
                WHERE fs.sessionid = :sessionid
                  AND u.deleted = 0
                ORDER BY fss.statuscode DESC, u.lastname, u.firstname";

        return $DB->get_records_sql($sql, ['sessionid' => $sessionid]);
    }

    /**
     * Get participants grouped by status for a specific session.
     *
     * @param int $sessionid The session ID
     * @return array Array of participants grouped by status
     */
    public static function get_participants_by_status(int $sessionid): array {
        $participants = self::get_session_participants($sessionid);
        $grouped = [];

        foreach ($participants as $participant) {
            $status = $participant->statuscode;
            if (!isset($grouped[$status])) {
                $grouped[$status] = [];
            }
            $grouped[$status][] = $participant;
        }

        // Sort by status priority (most important first)
        $statusOrder = [100, 90, 80, 70, 60, 50, 40, 30, 20, 10];
        $orderedGroups = [];

        foreach ($statusOrder as $statusCode) {
            if (isset($grouped[$statusCode])) {
                $orderedGroups[$statusCode] = $grouped[$statusCode];
            }
        }

        return $orderedGroups;
    }

    /**
     * Get human-readable status text for a status code.
     *
     * @param int $statuscode The status code
     * @return string The status text
     */
    public static function get_status_text(int $statuscode): string {
        // Face-to-face status codes
        $statuses = [
            10 => get_string('status_user_cancelled', 'local_f2freport'),
            20 => get_string('status_session_cancelled', 'local_f2freport'),
            30 => get_string('status_declined', 'local_f2freport'),
            40 => get_string('status_requested', 'local_f2freport'),
            50 => get_string('status_approved', 'local_f2freport'),
            60 => get_string('status_waitlisted', 'local_f2freport'),
            70 => get_string('status_booked', 'local_f2freport'),
            80 => get_string('status_no_show', 'local_f2freport'),
            90 => get_string('status_partially_attended', 'local_f2freport'),
            100 => get_string('status_fully_attended', 'local_f2freport')
        ];

        return $statuses[$statuscode] ?? get_string('status_unknown', 'local_f2freport');
    }

    /**
     * Get Bootstrap badge class for a status code.
     *
     * @param int $statuscode The status code
     * @return string The Bootstrap badge class
     */
    public static function get_status_badge_class(int $statuscode): string {
        $classes = [
            10 => 'badge-secondary',      // User cancelled
            20 => 'badge-dark',           // Session cancelled
            30 => 'badge-danger',         // Declined
            40 => 'badge-info',           // Requested
            50 => 'badge-primary',        // Approved
            60 => 'badge-warning',        // Waitlisted
            70 => 'badge-success',        // Booked
            80 => 'badge-danger',         // No show
            90 => 'badge-warning',        // Partially attended
            100 => 'badge-success'        // Fully attended
        ];

        return $classes[$statuscode] ?? 'badge-secondary';
    }

    /**
     * Get participant counts for a session.
     *
     * @param int $sessionid The session ID
     * @return array Array with 'total' and 'present' counts
     */
    public static function get_participant_counts(int $sessionid): array {
        global $DB;

        // Get total signups (excluding cancelled) using latest status
        $sql = "SELECT COUNT(DISTINCT fs.userid) as total
                FROM {facetoface_signups} fs
                JOIN (
                    SELECT signupid, MAX(id) AS maxstatusid
                      FROM {facetoface_signups_status}
                     WHERE superceded = 0
                  GROUP BY signupid
                ) latest ON latest.signupid = fs.id
                JOIN {facetoface_signups_status} fss ON fss.id = latest.maxstatusid
                WHERE fs.sessionid = :sessionid
                  AND fss.statuscode IN (40, 50, 60, 70, 80, 90, 100)"; // Include active signups

        $total = $DB->get_field_sql($sql, ['sessionid' => $sessionid]) ?: 0;

        // Get present count (fully or partially attended)
        $sql = "SELECT COUNT(DISTINCT fs.userid) as present
                FROM {facetoface_signups} fs
                JOIN (
                    SELECT signupid, MAX(id) AS maxstatusid
                      FROM {facetoface_signups_status}
                     WHERE superceded = 0
                  GROUP BY signupid
                ) latest ON latest.signupid = fs.id
                JOIN {facetoface_signups_status} fss ON fss.id = latest.maxstatusid
                WHERE fs.sessionid = :sessionid
                  AND fss.statuscode IN (90, 100)"; // Partially or fully attended

        $present = $DB->get_field_sql($sql, ['sessionid' => $sessionid]) ?: 0;

        return [
            'total' => (int)$total,
            'present' => (int)$present
        ];
    }
}