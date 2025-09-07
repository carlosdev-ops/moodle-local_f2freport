<?php
// Capabilities pour le plugin local_f2freport.

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    'local/f2freport:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
