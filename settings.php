<?php
// Admin settings for local_f2freport.
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Page de réglages du plugin.
    $settings = new admin_settingpage('local_f2freport', get_string('pluginname', 'local_f2freport'));

    // 1) Colonnes par défaut (multicheckbox).
    $columnoptions = [
        'courseid'          => get_string('courseid', 'local_f2freport'),
        'coursefullname'    => get_string('coursefullname', 'local_f2freport'),
        'sessionid'         => get_string('sessionid', 'local_f2freport'),
        'timestart'         => get_string('timestart', 'local_f2freport'),
        'timefinish'        => get_string('timefinish', 'local_f2freport'),
        'city'              => get_string('city', 'local_f2freport'),
        'venue'             => get_string('venue', 'local_f2freport'),
        'room'              => get_string('room', 'local_f2freport'),
        'totalparticipants' => get_string('totalparticipants', 'local_f2freport'),
    ];
    $defaultcolumns = [
        'coursefullname'    => 1,
        'sessionid'         => 1,
        'timestart'         => 1,
        'timefinish'        => 1,
        'city'              => 0,
        'venue'             => 0,
        'room'              => 0,
        'totalparticipants' => 1,
        'courseid'          => 0,
    ];

    $settings->add(new admin_setting_configmulticheckbox(
        'local_f2freport/columns',
        get_string('settings_columns', 'local_f2freport'),
        get_string('settings_columns_desc', 'local_f2freport'),
        $defaultcolumns,
        $columnoptions
    ));

    // 2) Alias des champs (au cas où le schéma F2F/local varie).
    $settings->add(new admin_setting_configtext(
        'local_f2freport/alias_city',
        get_string('settings_aliases_city', 'local_f2freport'),
        get_string('settings_aliases_city_desc', 'local_f2freport'),
        '', PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_f2freport/alias_venue',
        get_string('settings_aliases_venue', 'local_f2freport'),
        get_string('settings_aliases_venue_desc', 'local_f2freport'),
        '', PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_f2freport/alias_room',
        get_string('settings_aliases_room', 'local_f2freport'),
        get_string('settings_aliases_room_desc', 'local_f2freport'),
        '', PARAM_ALPHANUMEXT
    ));

    // 3) Taille de page.
    $settings->add(new admin_setting_configtext(
        'local_f2freport/pagesize',
        get_string('settings_pagesize', 'local_f2freport'),
        get_string('settings_pagesize_desc', 'local_f2freport'),
        50, PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}
