<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_f2freport', get_string('pluginname', 'local_f2freport'));

    if ($ADMIN->fulltree) {
        // 1) Colonnes affichées (checkbox multiples).
        $colopts = [
            'courseid'          => get_string('courseid', 'local_f2freport'),
            'sessionid'         => get_string('sessionid', 'local_f2freport'),
            'timestart'         => get_string('timestart', 'local_f2freport'),
            'timefinish'        => get_string('timefinish', 'local_f2freport'),
            'city'              => get_string('city', 'local_f2freport'),
            'venue'             => get_string('venue', 'local_f2freport'),
            'room'              => get_string('room', 'local_f2freport'),
            'totalparticipants' => get_string('totalparticipants', 'local_f2freport'), // affiche "présents / inscrits"
            'coursefullname'    => get_string('coursefullname', 'local_f2freport'),
        ];
        $settings->add(new admin_setting_configmulticheckbox(
            'local_f2freport/columns',
            get_string('settings_columns', 'local_f2freport'),
            get_string('settings_columns_desc', 'local_f2freport'),
            array_fill_keys(array_keys($colopts), 1), // tout coché par défaut
            $colopts
        ));

        // 2) Alias pour détecter Ville/Lieu/Salle (CSV).
        $settings->add(new admin_setting_configtext(
            'local_f2freport/aliases_city',
            get_string('settings_aliases_city', 'local_f2freport'),
            get_string('settings_aliases_city_desc', 'local_f2freport'),
            'city,ville,location', PARAM_TEXT
        ));
        $settings->add(new admin_setting_configtext(
            'local_f2freport/aliases_venue',
            get_string('settings_aliases_venue', 'local_f2freport'),
            get_string('settings_aliases_venue_desc', 'local_f2freport'),
            'venue,lieu,building,site,centre,center,campus', PARAM_TEXT
        ));
        $settings->add(new admin_setting_configtext(
            'local_f2freport/aliases_room',
            get_string('settings_aliases_room', 'local_f2freport'),
            get_string('settings_aliases_room_desc', 'local_f2freport'),
            'room,salle,classroom,roomnumber', PARAM_TEXT
        ));

        // 3) Taille de page.
        $settings->add(new admin_setting_configtext(
            'local_f2freport/pagesize',
            get_string('settings_pagesize', 'local_f2freport'),
            get_string('settings_pagesize_desc', 'local_f2freport'),
            25, PARAM_INT
        ));
    }

    $ADMIN->add('localplugins', $settings);
}
