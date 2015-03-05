<?php

$tasks = array(
    array(
        'classname' => 'local_ppsft\task\update_recent_enrollment_changes',
        'blocking' => 0,
        'minute' => '8,18,28,38,48,58',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'local_ppsft\task\update_all_class_enrollments',
        'blocking' => 0,
        'minute' => '16',
        'hour' => '21',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'local_ppsft\task\update_all_class_instructors',
        'blocking' => 0,
        'minute' => '46',
        'hour' => '21',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'local_ppsft\task\update_ppsft_terms',
        'blocking' => 0,
        'minute' => '53',
        'hour' => '21',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    )
);


