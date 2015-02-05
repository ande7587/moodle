<?php

/**
 * External local/enrol API
 *
 * @package    local
 * @subpackage enrol
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->libdir/accesslib.php");
require_once("$CFG->dirroot/local/ldap/lib.php");


class local_enrol_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_user_courses_parameters() {
        return new external_function_parameters(
            array(
                'user_id_type'  => new external_value(PARAM_TEXT, 'type of user ID (x500, emplid)', VALUE_DEFAULT, 'x500'),
                'user_id'       => new external_value(PARAM_TEXT, 'User ID value', VALUE_REQUIRED)
            )
        );
    }

   /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_user_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(array(
                'course_id'    => new external_value(PARAM_INT, 'course ID'),
                'course_name'  => new external_value(PARAM_TEXT, 'course short name'),
                'lastaccess'   => new external_value(PARAM_TEXT, 'course last-access timestamp'),
                'roles'        => new external_multiple_structure(
                    new external_single_structure(array(
                        'RA_id'             => new external_value(PARAM_INT, 'Role_Assignment ID'),
                        'role_id'           => new external_value(PARAM_INT, 'role ID'),
                        'role_name'         => new external_value(PARAM_TEXT, 'role name'),
                        'role_shortname'    => new external_value(PARAM_TEXT, 'role short name')
                ), 'role of the user within the course', VALUE_OPTIONAL))
        )));
    }


    /**
     * Get all role_assignments, related courses and users for a specific user
     * @param string $x500 username
     * @return array, see get_user_courses_returns()
     */
    public static function get_user_courses($user_id_type, $user_id) {
        global $DB;

        $params = self::validate_parameters(self::get_user_courses_parameters(), array(
                'user_id_type'  => $user_id_type,
                'user_id'       => $user_id));

        // map the corresponding user ID type
        switch ($params['user_id_type']) {
            case 'x500':
                $user = $DB->get_record('user', array(
                        'username' => umn_ldap_person_accessor::uid_to_moodle_username($params['user_id'])));
                break;

            case 'emplid':
                $user = $DB->get_record('user', array('idnumber' => $params['user_id']));
                break;

            default:
                throw new moodle_exception(get_string('errorinvalidparam', 'webservice', 'user_id_type'));
        }

        // check the user record
        if (!$user) {
            throw new moodle_exception(get_string('errorinvalidparam', 'webservice', 'user_id'));
        }

        // now security checks
        $context = context_user::instance($user->id);
        try {
            self::validate_context($context);
        } catch (Exception $e) {
            throw new moodle_exception("Context error: {$e->getMessage()}");
        }

        require_capability('moodle/user:viewuseractivitiesreport', $context);

        if ($params['user_id_type'] == 'emplid') {
            require_capability('local/user:view_idnumber', $context);
        }

        // prep the output
        $out = array();

        // get the enrolled courses
        $courses = enrol_get_users_courses($user->id);

        foreach ($courses as $course_id => $course) {
            $out[$course_id] = array(
                'course_id'     => $course_id,
                'course_name'   => $course->shortname,
                'lastaccess'    => null,
                'roles'         => array()
            );
        }

        // get last-access time
        $rs = $DB->get_records('user_lastaccess', array('userid' => $user->id));

        foreach ($rs as $row) {
            if (isset($out[$row->courseid])) {
                $out[$row->courseid]['lastaccess'] = $row->timeaccess;
            }
        }

        // get the roles
        $query =
              'SELECT
                    role_assignments.id AS role_assignments__id,
                    course.id AS course__id,
                    role.id AS role__id,
                    role.name AS role__name,
                    role.shortname AS role__shortname
               FROM {role_assignments} role_assignments
                    LEFT JOIN {context} context ON context.id = role_assignments.contextid
                    LEFT JOIN {course} course ON context.contextlevel = ? AND
                                                 context.instanceid = course.id
                    LEFT JOIN {role} role ON role.id = role_assignments.roleid
               WHERE
                    role_assignments.userid = ? ';

        $params = array(CONTEXT_COURSE, $user->id);

        if (count($courses) > 0) {
            $query .= 'AND course.id IN ('.implode(',', array_fill(0, count($courses), '?')).')';

            $params = array_merge($params, array_keys($courses));
        }

        $ra_records = $DB->get_records_sql($query, $params);

        foreach ($ra_records as $ra_id => $ra_record) {
            $out[$ra_record->course__id]['roles'][$ra_id] = array(
                'RA_id'            => $ra_record->role_assignments__id,
                'role_id'          => $ra_record->role__id,
                'role_name'        => $ra_record->role__name,
                'role_shortname'   => $ra_record->role__shortname
            );
        }

        // return output
        return $out;
    }



    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_course_users_parameters() {
        return new external_function_parameters(
            array(
                'course_id'     => new external_value(PARAM_TEXT, 'Course ID', VALUE_REQUIRED),
                'user_id_type'  => new external_value(PARAM_TEXT, 'ID type (x500, emplid)', VALUE_DEFAULT, 'x500')
            )
        );
    }


   /**
     * Returns description of method result value
     * same as get_user_courses()
     * @return external_description
     */
    public static function get_course_users_returns() {
        return new external_multiple_structure(
            new external_single_structure(array(
                'user_id'         => new external_value(PARAM_TEXT, 'user x500 or emplid'),
                'user_firstname'  => new external_value(PARAM_TEXT, 'user firstname'),
                'user_lastname'   => new external_value(PARAM_TEXT, 'user lastname'),
                'lastaccess'      => new external_value(PARAM_TEXT, 'course last-access timestamp'),
                'roles'           => new external_multiple_structure(
                    new external_single_structure(array(
                        'RA_id'             => new external_value(PARAM_INT, 'Role_Assignment ID'),
                        'role_id'           => new external_value(PARAM_INT, 'role ID'),
                        'role_name'         => new external_value(PARAM_TEXT, 'role name'),
                        'role_shortname'    => new external_value(PARAM_TEXT, 'role short name')
                ), 'role of the user within the course', VALUE_OPTIONAL))
        )));
    }


    /**
     * Get all role_assignments, related courses and users for a specific course
     * @param int $course_id Course ID
     * @return array, see get_course_users_returns()
     */
    public static function get_course_users($course_id, $user_id_type) {
        global $DB;

        $params = self::validate_parameters(self::get_course_users_parameters(), array(
                'course_id'     => $course_id,
                'user_id_type'  => $user_id_type));

        if (!in_array($params['user_id_type'], array('x500', 'emplid'))) {
            throw new moodle_exception(get_string('errorinvalidparam', 'webservice', 'user_id_type'));
        }

        // get the course record from course_id
        if (!$course = $DB->get_record('course', array('id' => $params['course_id']))) {
            throw new moodle_exception(get_string('errorinvalidparam', 'webservice', 'course_id'));
        }

        // now security checks
        $context = context_course::instance($course->id);
        try {
            self::validate_context($context);
        } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->course_id = $params['course_id'];
                throw new moodle_exception(
                        get_string('errorcoursecontextnotvalid' , 'webservice', $exceptionparam));
        }

        require_capability('moodle/course:view', $context);
        require_capability('moodle/course:viewhiddencourses', $context);
        require_capability('moodle/course:viewparticipants', $context);

        if ($params['user_id_type'] == 'emplid') {
            require_capability('local/user:view_idnumber', $context);
        }

        // prep the output
        $out = array();

        // get the enrolled users
        $users = get_enrolled_users($context);

        $user_id_map = array();
        foreach ($users as $user_id => $user) {
            switch ($params['user_id_type']) {
                case 'x500':
                    try {
                        $user_id_map[$user_id] = umn_ldap_person_accessor::moodle_username_to_uid($user->username);
                    }
                    catch(Exception $e) {
                        $user_id_map[$user_id] = '';    // gracefully recover
                    }
                    break;

                case 'emplid':
                    $user_id_map[$user_id] = $user->idnumber;
                    break;
            }

            $out[$user_id] = array(
                'user_id'          => $user_id_map[$user_id],
                'user_firstname'   => $user->firstname,
                'user_lastname'    => $user->lastname,
                'lastaccess'       => null,
                'roles'            => array()
            );
        }

        // get the last-access time
        $rs = $DB->get_records('user_lastaccess', array('courseid' => $course->id));
        foreach ($rs as $row) {
            if (isset($out[$row->userid])) {
                $out[$row->userid]['lastaccess'] = $row->timeaccess;
            }
        }

        // get the roles
        $query =
              'SELECT
                    role_assignments.id AS role_assignments__id,
                    user.id AS user__id,
                    role.id AS role__id,
                    role.name AS role__name,
                    role.shortname AS role__shortname
               FROM {role_assignments} role_assignments
                    LEFT JOIN {user} user ON user.id = role_assignments.userid
                    LEFT JOIN {role} role ON role.id = role_assignments.roleid
               WHERE
                    role_assignments.contextid = ? ';

        $params = array($context->id);

        if (count($users) > 0) {
            $query .= ' AND user.id IN ('.implode(',', array_fill(0, count($users), '?')).')';

            $params = array_merge($params, array_keys($users));
        }

        $ra_records = $DB->get_records_sql($query, $params);

        foreach ($ra_records as $ra_id => $ra_record) {
            $out[$ra_record->user__id]['roles'][$ra_id] = array(
                'RA_id'            => $ra_record->role_assignments__id,
                'role_id'          => $ra_record->role__id,
                'role_name'        => $ra_record->role__name,
                'role_shortname'   => $ra_record->role__shortname
            );
        }

        // return output
        return $out;
    }




    //========== ADD USER(S) TO A COURSE ==========

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function add_user_to_course_parameters() {
        return new external_function_parameters(
            array(
                'course_id'     => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
                'user_id_type'  => new external_value(PARAM_TEXT,
                                   'type of user ID (x500, emplid, moodle_id)', VALUE_DEFAULT, 'x500'),
                'users'         => new external_multiple_structure(
                    new external_single_structure(array(
                        'user_id'    => new external_value(PARAM_TEXT,
                                       'user ID (x500, emplid, moodle_id, ... depending on user_id_type', VALUE_REQUIRED),
                        'role_id'    => new external_value(PARAM_TEXT, 'ID of the role to be assigned', VALUE_REQUIRED)
                    ))
                )
            )
        );
    }


   /**
     * Returns description of method result value
     * @return external_description
     */
    public static function add_user_to_course_returns() {
        return new external_single_structure(array(
            'role_assignments' => new external_multiple_structure(
                new external_single_structure(array(
                    'user_id'     => new external_value(PARAM_TEXT, 'the provided user ID'),
                    'ra_id'       => new external_value(PARAM_INT, 'ID of the created role_assignment')
                )), '', VALUE_OPTIONAL),
            'errors'    => new external_multiple_structure(
                   new external_single_structure(array(
                       'user_id'    => new external_value(PARAM_TEXT, 'the provided user ID'),
                       'message'    => new external_value(PARAM_TEXT, 'error message')
                   )), '', VALUE_OPTIONAL)
        ));
    }


    /**
     * add user(s) to a course, with custom RA-values (UMN specific)
     * @param int $course_id
     * @param string $user_id_type
     * @param array $users
     */
    public static function add_user_to_course($course_id, $user_id_type, $users) {
        global $DB;

        $params = self::validate_parameters(self::add_user_to_course_parameters(), array(
                'course_id'     => $course_id,
                'user_id_type'  => $user_id_type,
                'users'         => $users));

        // get the course record from course_id
        if (!$course = $DB->get_record('course', array('id' => $params['course_id']))) {
            throw new moodle_exception(get_string('errorinvalidparam', 'webservice', 'course_id'));
        }

        // security checks
        $context = context_course::instance($course->id);
        try {
            self::validate_context($context);
        }
        catch (Exception $e) {
            $exceptionparam = new stdClass();
            $exceptionparam->message = $e->getMessage();
            $exceptionparam->course_id = $params['course_id'];
            throw new moodle_exception(
                        get_string('errorcoursecontextnotvalid' , 'webservice', $exceptionparam));
        }

        require_capability('moodle/role:assign', $context);

        // get the list of roles for validation
        $roles = $DB->get_records('role');

        $allowed_roles = explode(',', get_config('local/enrol', 'allowed_roles'));

        // prep the enroller

        /** @var enrol_manual_plugin */
        $enroler = enrol_get_plugin('manual');

        $enrol_instances = enrol_get_instances($course->id, true);

        $manual_instance = null;
        foreach ($enrol_instances as $instance) {
            if ($instance->enrol == 'manual') {
                $manual_instance = $instance;
                break;
            }
        }

        if (is_null($manual_instance)) // no manual instance available, bail out
            throw new moodle_exception(get_string('invalidenrolinstance', 'enrol'));


        // create empty output container
        $out = array('role_assignments'  => array(),
                     'errors'            => array());

        // loop through the user list
        foreach ($params['users'] as $user) {
            // validate role_id
            if (!isset($roles[$user['role_id']])) {
                $out['errors'][] = array('user_id' => $user['user_id'],
                                         'message' => "invalid role_id value '{$user['role_id']}'");
                continue;    // next user
            }

            // validate that the role is allowed
            if ( !in_array($roles[$user['role_id']]->shortname, $allowed_roles) ) {
                $out['errors'][] = array('user_id' => $user['user_id'],
                                         'message' => "unallowed role '{$user['role_id']}'");
                continue;    // next user
            }


            // get user
            switch (strtolower($params['user_id_type'])) {
                case 'x500':
                    $filter = array('username' => umn_ldap_person_accessor::uid_to_moodle_username($user['user_id']));
                    break;

                case 'emplid':
                    $filter    = array('idnumber' => $user['user_id']);
                    break;

                case 'moodle_id':
                    $filter = array('id' => $user['user_id']);
                    break;

                default:
                    throw new moodle_exception(get_string('errorinvalidparam', 'webservice', 'user_id_type'));
            }

            if (!$user_record = $DB->get_record('user', $filter)) {
                $out['errors'][] = array('user_id' => $user['user_id'],
                                         'message' => "no record found");
                continue;    // proceed to next user
            }

            //=== perform enrolment
            try {
                $enroler->enrol_user($manual_instance, $user_record->id, $user['role_id']);
            }
            catch(Exception $e) {
                $out['errors'][] = array('user_id' => $user['user_id'],
                                         'message' => "error enrolling user: '{$e->getMessage()}'");
                continue;    // proceed to next user
            }


            // retrieve the created role_assignment ID
            try {
                $ra_record = $DB->get_record('role_assignments', array(
                        'userid'    => $user_record->id,
                        'contextid' => $context->id,
                        'roleid'    => $user['role_id']));

                if ($ra_record !== false) {
                    // log the success
                    $out['role_assignments'][] = array('user_id' => $user_record->id,
                                                       'ra_id'   => $ra_record->id);
                }
                else {
                    $out['errors'][] = array('user_id' => $user_record->id,
                                             'message' => 'cannot find inserted role_assignment record');
                    continue; // proceed to next user
                }
            }
            catch(Exception $e) {
                $out['errors'][] = array('user_id' => $user_record->id,
                                         'message' => "error retrieving role_assignment record: {$e->getMessage()}");
                continue; // proceed to next user
            }
        }

        return $out;
    }




    //========== REMOVE USER(S) FROM A COURSE ==========

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function remove_user_from_course_parameters() {
        return new external_function_parameters(
            array(
                'course_id'     => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
                'user_id_type'  => new external_value(PARAM_TEXT,
                                   'type of user ID (x500, emplid, moodle_id)', VALUE_DEFAULT, 'x500'),
                'users'         => new external_multiple_structure(
                    new external_single_structure(array(
                        'user_id'    => new external_value(PARAM_TEXT,
                                       'user ID (x500, emplid, moodle_id, ... depending on user_id_type', VALUE_REQUIRED)
                        // placeholder for future options
                    ))
                )
            )
        );
    }


   /**
     * Returns description of method result value
     * @return external_description
     */
    public static function remove_user_from_course_returns() {
        return new external_single_structure(array(
            'unenrolled' => new external_multiple_structure(
                new external_single_structure(array(
                    'user_id'   => new external_value(PARAM_TEXT, 'the provided user ID')
                    // placeholder for future return values (removed roles?)
                )), '', VALUE_OPTIONAL),
            'errors'    => new external_multiple_structure(
                new external_single_structure(array(
                    'user_id'    => new external_value(PARAM_TEXT, 'the provided user ID'),
                    'message'    => new external_value(PARAM_TEXT, 'error message')
                )), '', VALUE_OPTIONAL)
        ));
    }


    /**
     * remove user(s) from a course, also remove related RA_values (UMN's custom)
     * @param int $course_id
     * @param string $user_id_type
     * @param array $users
     */
    public static function remove_user_from_course($course_id, $user_id_type, $users) {
        global $DB;

        $params = self::validate_parameters(self::remove_user_from_course_parameters(), array(
                'course_id'     => $course_id,
                'user_id_type'  => $user_id_type,
                'users'         => $users));

        // get the course record from course_id
        if (!$course = $DB->get_record('course', array('id' => $params['course_id']))) {
            throw new moodle_exception(get_string('errorinvalidparam', 'webservice', 'course_id'));
        }

        // security checks
        $context = context_course::instance($course->id);
        try {
            self::validate_context($context);
        }
        catch (Exception $e) {
            $exceptionparam = new stdClass();
            $exceptionparam->message = $e->getMessage();
            $exceptionparam->course_id = $params['course_id'];
            throw new moodle_exception(
                        get_string('errorcoursecontextnotvalid' , 'webservice', $exceptionparam));
        }

        require_capability('moodle/role:assign', $context);

        // prep the enrol instance

        /** @var enrol_manual_plugin */
        $enroler = enrol_get_plugin('manual');

        $enrol_instances = enrol_get_instances($course->id, true);

        $manual_instance = null;
        foreach ($enrol_instances as $instance) {
            if ($instance->enrol == 'manual') {
                $manual_instance = $instance;
                break;
            }
        }

        if (is_null($manual_instance)) // no manual instance available, bail out
            throw new moodle_exception(get_string('invalidenrolinstance', 'enrol'));



        // create empty output container
        $out = array('unenrolled'  => array(),
                     'errors'      => array());

        // loop through the user list
        foreach ($users as $user) {
            // get user
            switch (strtolower($user_id_type)) {
                case 'x500':
                    $filter = array('username' => umn_ldap_person_accessor::uid_to_moodle_username($user['user_id']));
                    break;

                case 'emplid':
                    $filter = array('idnumber' => $user['user_id']);
                    break;

                case 'moodle_id':
                    $filter = array('id' => $user['user_id']);
                    break;

                default:
                    throw new moodle_exception(get_string('errorinvalidparam', 'webservice', 'user_id_type'));
            }

            if (!$user_record = $DB->get_record('user', $filter)) {
                $out['errors'][] = array('user_id'    => $user['user_id'],
                                         'message'    => "no user record found");
                continue;    // proceed to next user-role
            }


            //======= perform unenrolment
            try {
                $enroler->unenrol_user($manual_instance, $user_record->id);
                $out['unenrolled'][] = array('user_id' => $user['user_id']);
            }
            catch(Exception $e) {
                $out['errors'][] = array('user_id' => $user['user_id'],
                                         'message' => "error unenrolling user: '{$e->getMessage()}'");
                continue;    // proceed to next user
            }

        }

        return $out;
    }

}
