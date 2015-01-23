<?php

$capabilities = array(
    // being able to view/edit theme config
    'block/theme_customizer:use' => array(
        'captype'          => 'write',
        'contextlevel'     => CONTEXT_SYSTEM
    ),
    // being able to manage theme configs
    'block/theme_customizer:manage' => array(
        'captype'          => 'write',
        'contextlevel'     => CONTEXT_SYSTEM
    ),
    'block/theme_customizer:addinstance' => array(
        'riskbitmask'      => RISK_DATALOSS,
        'captype'          => 'write',
        'contextlevel'     => CONTEXT_SYSTEM,
        'archetypes'       => array(
            'manager'         => CAP_ALLOW),
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
    'block/theme_customizer:myaddinstance' => array(
            'riskbitmask'  => RISK_DATALOSS,
            'captype'      => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes'   => array(
                'manager'  => CAP_ALLOW,
            ),
            'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
);
