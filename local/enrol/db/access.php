<?php

$capabilities = array(
    // being able to use the bulk enrollment page
    'local/enrol:usebulk' => array(
        'captype'          => 'write',
        'contextlevel'     => CONTEXT_SYSTEM,
        'riskbitmask'      => RISK_PERSONAL,
    )
);