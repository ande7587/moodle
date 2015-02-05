<?php

/**
 * Local enrol external functions and service definitions.
 *
 * @package    local
 * @subpackage enrol
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(

    // === enrol related functions ===
    'local_enrol_get_user_courses' => array(
        'classname'   => 'local_enrol_external',
        'methodname'  => 'get_user_courses',
        'classpath'   => 'local/enrol/externallib.php',
        'description' => 'Get the courses of a particular user',
        'type'        => 'read',
        'capabilities'=> 'moodle/user:viewuseractivitiesreport'
    ),
    'local_enrol_get_course_users' => array(
        'classname'   => 'local_enrol_external',
        'methodname'  => 'get_course_users',
        'classpath'   => 'local/enrol/externallib.php',
        'description' => 'Get the users of a particular course',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:view,moodle/course:viewhiddencourses,moodle/course:viewparticipants'
    ),
    'local_enrol_add_user_to_course' => array(
        'classname'   => 'local_enrol_external',
        'methodname'  => 'add_user_to_course',
        'classpath'   => 'local/enrol/externallib.php',
        'description' => 'add one or more users to a particular course',
        'type'        => 'write',
        'capabilities'=> 'moodle/role:assign'
    ),
    'local_enrol_remove_user_from_course' => array(
        'classname'   => 'local_enrol_external',
        'methodname'  => 'remove_user_from_course',
        'classpath'   => 'local/enrol/externallib.php',
        'description' => 'remove one or more users from a particular course',
        'type'        => 'write',
        'capabilities'=> 'moodle/role:assign'    // @TODO: cannot find role:unassign
    )
);
