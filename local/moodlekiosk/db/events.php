<?php

$handlers = array (
    'role_assigned ' => array (
        'handlerfile'      => '/local/moodlekiosk/locallib.php',
        'handlerfunction'  => 'moodlekiosk_role_assigned',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),

    'role_unassigned ' => array (
        'handlerfile'      => '/local/moodlekiosk/locallib.php',
        'handlerfunction'  => 'moodlekiosk_role_unassigned',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
);

$observers = array(
    array(
        'eventname'   => '\core\event\course_category_created',
        'includefile' => '/local/moodlekiosk/locallib.php',
        'callback'    => 'moodlekiosk_category_created_updated',
        'internal'    => true
    ),

    array(
        'eventname'   => '\core\event\course_category_updated',
        'includefile' => '/local/moodlekiosk/locallib.php',
        'callback'    => 'moodlekiosk_category_created_updated',
        'internal'    => true
    ),

    array(
        'eventname'   => '\core\event\course_updated',
        'includefile' => '/local/moodlekiosk/locallib.php',
        'callback'    => 'moodlekiosk_course_updated',
        'internal'    => true
    )
);
