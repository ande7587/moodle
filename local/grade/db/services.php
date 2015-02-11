<?php

/**
 * Local grade external functions and service definitions.
 *
 * @package    local
 * @subpackage grade
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(

    // === grade related functions ===
    'local_grade_get_grades' => array(
        'classname'   => 'local_grade_external',
        'methodname'  => 'get_grades',
        'classpath'   => 'local/grade/externallib.php',
        'description' => 'Get the grades of a course (for specific users, activity)',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:view,moodle/course:viewparticipants,moodle/grade:viewall'
    ),
    // Optimized version of get_grades , but might not be backward compatible,
    // so creating totally new one.
    'local_grade_get_course_grades' => array(
        'classname'   => 'local_grade_external',
        'methodname'  => 'get_course_grades',
        'classpath'   => 'local/grade/externallib.php',
        'description' => 'Get the grades of a course',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:view,moodle/course:viewparticipants,moodle/grade:viewall,moodle/grade:viewhidden'
    ),

    // This returns information on grade items, but not on actual grades.
    'local_grade_get_course_grade_items' => array(
        'classname'   => 'local_grade_external',
        'methodname'  => 'get_course_grade_items',
        'classpath'   => 'local/grade/externallib.php',
        'description' => 'Get the grades of a course',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:view,moodle/grade:viewhidden'
    ),
);
