<?php
namespace local_f2freport\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

class sessions_table extends \table_sql {
    /** @var array */
    protected $filters = [];
    /** @var int|null */
    protected $cityfieldid = null;
    /** @var int|null */
    protected $venuefieldid = null;
    /** @var int|null */
    protected $roomfieldid = null;

    public function __construct(string $uniqueid, array $filters, array $fieldids) {
        parent::__construct($uniqueid);
        $this->filters = $filters;
        $this->cityfieldid  = $fieldids['city']  ?? null;
        $this->venuefieldid = $fieldids['venue'] ?? null;
        $this->roomfieldid  = $fieldids['room']  ?? null;

        // Colonnes & en-têtes.
        $columns = [
            'courseid', 'sessionid', 'timestart', 'timefinish',
            'city', 'venue', 'room', 'totalparticipants', 'coursefullname',
        ];
        $headers = [
            get_string('courseid', 'local_f2freport'),
            get_string('sessionid', 'local_f2freport'),
            get_string('timestart', 'local_f2freport'),
            get_string('timefinish', 'local_f2freport'),
            get_string('city', 'local_f2freport'),
            get_string('venue', 'local_f2freport'),
            get_string('room', 'local_f2freport'),
            get_string('totalparticipants', 'local_f2freport'),
            get_string('coursefullname', 'local_f2freport'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->sortable(true, 'timestart', SORT_ASC);
        $this->collapsible(false);
        $this->pageable(true);

        // Prépare SQL.
        [$fields, $from, $where, $params, $countsql] = $this->build_sql();
        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql($countsql, $params);
    }

    /**
     * Construit SQL compatible avec les deux schémas F2F :
     *  - (A) colonnes timestart/timefinish dans facetoface_sessions
     *  - (B) dates dans facetoface_sessions_dates (agrégation MIN/MAX)
     */
    protected function build_sql(): array {
        global $DB;

        // Détecte si facetoface_sessions a des colonnes timestart/timefinish.
        $sessionscols = $DB->get_columns('facetoface_sessions');
        $hasdirectdates = isset($sessionscols['timestart']) && isset($sessionscols['timefinish']);

        // Sélecteurs de colonnes pour les filtres/tri.
        $timestartcol  = $hasdirectdates ? 's.timestart'  : 'sd.timestart';
        $timefinishcol = $hasdirectdates ? 's.timefinish' : 'sd.timefinish';

        // Champs retournés (on ALIAS toujours en timestart/timefinish).
        
        $fields = "
            s.id AS id,                       -- ← clé unique exigée
            c.id AS courseid,
            c.fullname AS coursefullname,
            s.id AS sessionid,
            {$timestartcol} AS timestart,
            {$timefinishcol} AS timefinish,
            COALESCE(dcity.data,  :ns_city)   AS city,
            COALESCE(dvenue.data, :ns_venue)  AS venue,
            COALESCE(droom.data,  :ns_room)   AS room,
            COALESCE(su.participants, 0) AS totalparticipants
        ";

        // FROM + JOINS.
        $from = "
            {facetoface} f
            JOIN {course} c ON c.id = f.course
            JOIN {facetoface_sessions} s ON s.facetoface = f.id
        ";

        if (!$hasdirectdates) {
            // Agrégation MIN/MAX sur les dates de la session.
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
        ";

        // WHERE dynamique.
        $where  = '1=1';
        $params = [
            'ns_city'   => get_string('notspecified', 'local_f2freport'),
            'ns_venue'  => get_string('notspecified', 'local_f2freport'),
            'ns_room'   => get_string('notspecified', 'local_f2freport'),
            'cityfieldid'  => $this->cityfieldid  ?? 0,
            'venuefieldid' => $this->venuefieldid ?? 0,
            'roomfieldid'  => $this->roomfieldid  ?? 0,
        ];

        if (!empty($this->filters['courseid'])) {
            $where .= ' AND f.course = :courseid';
            $params['courseid'] = (int)$this->filters['courseid'];
        }

        $now = time();
        if (!empty($this->filters['futureonly'])) {
            $where .= " AND {$timestartcol} >= :now";
            $params['now'] = $now;
        } else {
            if (!empty($this->filters['datefrom'])) {
                $where .= " AND {$timestartcol} >= :datefrom";
                $params['datefrom'] = (int)$this->filters['datefrom'];
            }
            if (!empty($this->filters['dateto'])) {
                $datetoend = (int)$this->filters['dateto'] + 86399;
                $where .= " AND {$timestartcol} <= :dateto";
                $params['dateto'] = $datetoend;
            }
        }

        // COUNT SQL (doit refléter les mêmes joins/where).
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

    // ──────────────── Formatages des colonnes ────────────────
    public function col_timestart($row): string {
        if (!empty($row->timestart)) {
            return userdate((int)$row->timestart, get_string('strftimedatetime', 'langconfig'));
        }
        return '—';
        }

    public function col_timefinish($row): string {
        if (!empty($row->timefinish)) {
            return userdate((int)$row->timefinish, get_string('strftimedatetime', 'langconfig'));
        }
        return '—';
    }

    public function col_city($row): string { return format_string($row->city); }
    public function col_venue($row): string { return format_string($row->venue); }
    public function col_room($row): string  { return format_string($row->room); }
    public function col_totalparticipants($row): string { return (string)((int)$row->totalparticipants); }
    public function col_coursefullname($row): string { return format_string($row->coursefullname); }
}
