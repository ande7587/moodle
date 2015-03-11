<?php

/**
 * Local enrol external functions and service definitions.
 *
 * @package local
 * @subpackage webservice
 */

$functions = array(

    // Intended for use by the UMN A+ system to determine which courses have new
    // grade or enrollment data. Takes category id as parameter.
    'local_course_get_recent_timestamps' => array(
        'classname'    => 'local_course_external',
        'methodname'   => 'get_recent_timestamps',
        'classpath'    => 'local/course/externallib.php',
        'description'  => 'Get the most recent timestamps for grades and enrollments',
        'type'         => 'read',
        'capabilities' => 'moodle/course:view'
    )
);

