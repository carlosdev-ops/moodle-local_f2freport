<?php
namespace local_f2freport\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

class sessions_table extends \table_sql {
    protected $filters = [];
    protected $cityfieldid = null;
    protected $venuefieldid = null;
    protected $roomfieldid = null;

    public function __construct(string $uniqueid, array $filters, array $fieldids) {
        parent::__construct($uniqueid);
        $this->filters = $filters;
        $this->cityfieldid  = $fieldids['city']  ?? null;
        $this->venuefieldid = $fieldids['venue'] ?? null;
        $this->roomfieldid  = $fieldids['room']  ?? null;

        // Colonnes / entêtes depuis la config admin.
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
                foreach ($cfg->columns as $k => $v) {
                    if (!empty($v) && isset($allcols[$k])) { $enabledkeys[] = $k; }
                }
            } else if (is_string($cfg->columns)) {
                foreach (explode(',', $cfg->columns) as $k) {
                    $k = trim($k);
                    if ($k !== '' && isset($allcols[$k])) { $enabledkeys[] = $k; }
                }
            }
        }
        if (empty($enabledkeys)) { $enabledkeys = array_keys($allcols); }

        $columns = $enabledkeys;
        $headers = array_map(fn($k) => $allcols[$k], $enabledkeys);

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->sortable(true, 'timestart', SORT_ASC);
        $this->collapsible(false);
        $this->pageable(true);

        [$fields, $from, $where, $params, $countfrom, $countparams] = $this->build_sql();
        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(1) FROM $countfrom WHERE $where", $countparams);
    }

    protected function build_sql(): array {
        global $DB;

        $sessionscols   = $DB->get_columns('facetoface_sessions');
        $hasdirectdates = isset($sessionscols['timestart']) && isset($sessionscols['timefinish']);

        $timestartcol  = $hasdirectdates ? 's.timestart'  : 'sd.timestart';
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

        // Métadonnées ville/lieu/salle.
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
            'cityfieldid'  => $this->cityfieldid  ?? 0,
            'venuefieldid' => $this->venuefieldid ?? 0,
            'roomfieldid'  => $this->roomfieldid  ?? 0,
        ];

        // --- Filtres ---
        if (!empty($this->filters['datefrom'])) {
            $where .= " AND {$timestartcol} >= :datefrom";
            $params['datefrom'] = (int)$this->filters['datefrom'];
        }
        if (!empty($this->filters['dateto'])) {
            $where .= " AND {$timestartcol} <= :dateto";
            $params['dateto'] = (int)$this->filters['dateto'] + 86399;
        }

        // Lieu (contient) : on cherche dans city OU venue OU room.
        if (!empty($this->filters['location'])) {
            $like1 = $DB->sql_like('dcity.data',  ':loc1', false);
            $like2 = $DB->sql_like('dvenue.data', ':loc2', false);
            $like3 = $DB->sql_like('droom.data',  ':loc3', false);
            $where .= " AND ( ($like1) OR ($like2) OR ($like3) )";
            $needle = '%' . $this->filters['location'] . '%';
            $params['loc1'] = $needle;
            $params['loc2'] = $needle;
            $params['loc3'] = $needle;
        }

        // Réplique FROM pour le count (mêmes jointures si on filtre dessus).
        $countfrom = $from;

        // Params utilisés par le COUNT (pas les placeholders inutiles).
        $countparams = $params;
        unset($countparams['ns_city'], $countparams['ns_venue'], $countparams['ns_room']);

        return [$fields, $from, $where, $params, $countfrom, $countparams];
    }

    // Rendus de colonnes.
    public function col_timestart($row): string {
        return !empty($row->timestart)
            ? userdate((int)$row->timestart, get_string('strftimedatetime', 'langconfig'))
            : '—';
    }
    public function col_timefinish($row): string {
        return !empty($row->timefinish)
            ? userdate((int)$row->timefinish, get_string('strftimedatetime', 'langconfig'))
            : '—';
    }
    public function col_city($row): string   { return format_string($row->city); }
    public function col_venue($row): string  { return format_string($row->venue); }
    public function col_room($row): string   { return format_string($row->room); }
    public function col_coursefullname($row): string { return format_string($row->coursefullname); }

    // "présents / inscrits".
    public function col_totalparticipants($row): string {
        $present = (int)($row->presentcount ?? 0);
        $total   = (int)($row->totalparticipants ?? 0);
        return $present . ' / ' . $total;
    }

    public function get_totalrows(): int {
        return (int)($this->totalrows ?? 0);
    }
}
