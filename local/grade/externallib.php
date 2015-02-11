<?php

/**
 * External grade API
 *
 * @package    local
 * @subpackage grades
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->libdir/gradelib.php");
require_once("$CFG->dirroot/local/ldap/lib.php");
require_once("$CFG->dirroot/grade/lib.php");


class local_grade_external extends external_api {

    public static function get_course_grade_items_parameters() {
        return new external_function_parameters(array(
            'course_id' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED)
        ));
    }

    public static function get_course_grade_items_returns() {
        return new external_single_structure(array(
            'course_id'         => new external_value(PARAM_INT,  'requested course ID'),
            'course_shortname'  => new external_value(PARAM_TEXT, 'requested course short name'),
            'course_fullname'   => new external_value(PARAM_TEXT, 'requested course full name'),
            'items'             => new external_multiple_structure(
                new external_single_structure(array(
                    'id'        => new external_value(PARAM_INT,  'ID of the grade item'),
                    'name'      => new external_value(PARAM_TEXT, 'name of the grade item'),
                    'type'      => new external_value(PARAM_TEXT, 'type of the grade item'),
                    'module'    => new external_value(PARAM_TEXT, 'module that this grade item belongs to'),
                    'instance'  => new external_value(PARAM_TEXT, 'ID of the instance that this item is about'),
                    'number'    => new external_value(PARAM_TEXT, 'to distinguish multiple grades for an activity'),
                    'grademin'  => new external_value(PARAM_TEXT, 'minimum allowable grade'),
                    'grademax'  => new external_value(PARAM_TEXT, 'maximum allowable grade'),
                    'gradepass' => new external_value(PARAM_TEXT, 'grade needed to pass'),
                    'is_hidden' => new external_value(PARAM_BOOL, 'true if item is currently hidden'),
                    'hiddenuntil' => new external_value(PARAM_TEXT, 'datetime of "hidden until" value, if set'),
                    'containeritemid' => new external_value(PARAM_INT,  'ID of the grade item that contains this grade item')
            )))
        ));
    }

    /**
     * Returns the grade items for a course, but not the students' grades.
     */
    public static function get_course_grade_items($courseid) {
        global $DB;

        $params = self::validate_parameters(self::get_course_grades_parameters(), array(
            'course_id' => $courseid
        ));

        if (!$course = $DB->get_record('course', array('id' => $params['course_id']))) {
            throw new moodle_exception(get_string('errorinvalidparam', 'webservice', 'course_id'));
        }

        $context = context_course::instance($course->id);

        // verify capability to view grade
        try {
            self::validate_context($context);
        } catch (Exception $e) {
            $exceptionparam = new stdClass();
            $exceptionparam->message = $e->getMessage();
            $exceptionparam->courseid = $params['course_id'];
            throw new moodle_exception(
                    get_string('errorcoursecontextnotvalid' , 'webservice', $exceptionparam));
        }

        require_capability('moodle/course:view', $context);
        // Need to be able to view hidden items, but capability includes grades, too.
        require_capability('moodle/grade:viewhidden', $context);

        // prep the output container
        $out = array(
            'course_id'        => $course->id,
            'course_shortname' => $course->shortname,
            'course_fullname'  => $course->fullname,
            'items'            => array()
        );

        $grade_items = grade_item::fetch_all(array('courseid'=>$course->id));
        $categories = static::get_item_and_parent_for_categories($course->id);

        foreach ($grade_items as $gi) {

            // Get the grade item for the category (or course) that contains the item.
            $parentgradeitemid = null;
            if ($gi->is_course_item()) {
                // Leave $parentgradeitem null
            } else if ($gi->is_category_item()) {
                if (isset($categories[$gi->iteminstance])) {
                    $categoryparentid = $categories[$gi->iteminstance]->categoryparentid;
                    if (isset($categories[$categoryparentid]))
                        $parentgradeitemid = $categories[$categoryparentid]->categoryitemid;
                }
            } else {
                if (isset($categories[$gi->categoryid]))
                    $parentgradeitemid = $categories[$gi->categoryid]->categoryitemid;
            }

            $item_out = array(
                'id'        => $gi->id,
                'name'      => $gi->get_name(),
                'type'      => $gi->itemtype,
                'module'    => $gi->itemmodule,
                'instance'  => $gi->iteminstance,
                'number'    => $gi->itemnumber,
                'grademin'  => $gi->grademin,
                'grademax'  => $gi->grademax,
                'gradepass' => $gi->gradepass,
                'is_hidden' => $gi->is_hidden(),
                'hiddenuntil' => static::iso8601_date_from_hidden($gi->get_hidden()),
                'containeritemid' => $parentgradeitemid,
                ###'timecreated'  => $gi->timecreated,
                ###'timemodified' => $gi->timemodified
            );

            $out['items'][] = $item_out;
        }
        return $out;
    }

    /**
     * This is a helper function used by get_course_grade_items to get
     * the corresponding grade item id for each grade category in the course
     * as well as each grade category's parent grade category id.
     */
    private static function get_item_and_parent_for_categories($courseid) {
        global $DB;

        $sql =<<<SQL
select gc.id categoryid, gi.id categoryitemid, gc.parent categoryparentid
from mdl_grade_categories gc
join mdl_grade_items gi
  on gi.iteminstance=gc.id and
     gi.courseid=gc.courseid and
     gi.itemtype in ('course','category')
where gc.courseid=:courseid
SQL;
        $categories = $DB->get_records_sql($sql, array('courseid'=>$courseid));
        return $categories;
    }

    public static function get_course_grades_parameters() {
        return new external_function_parameters(array(
            'course_id' => new external_value(PARAM_INT,  'Course ID', VALUE_REQUIRED),
            'since'     => new external_value(PARAM_TEXT, 'Grades with possible changes since time', VALUE_OPTIONAL)
        ));
    }

    private static function iso8601_date_from_hidden($hidden) {
        if (! $hidden or $hidden == 1) return '';

        # TODO: What kind of error handling do we need here?
        return date('c', $hidden);

    }

    public static function get_course_grades_returns() {
        return new external_single_structure(array(
            'course_id'         => new external_value(PARAM_INT,  'requested course ID'),
            'course_shortname'  => new external_value(PARAM_TEXT, 'requested course short name'),
            'users'             => new external_multiple_structure(
                new external_single_structure(array(
                    'user_moodle_id' => new external_value(PARAM_INT, 'Instance-specific Moodle ID of the user'),
                    'user_username'  => new external_value(PARAM_TEXT, 'Username; typically scoped UMN internet ID'),
                    'user_idnumber'  => new external_value(PARAM_TEXT,
                                                           'User\'s idnumber; typically the user\'s emplid',
                                                           VALUE_OPTIONAL),
                    'grades' => new external_multiple_structure(
                        new external_single_structure(array(
                            'item_id' => new external_value(PARAM_TEXT, 'ID of the grade item'),
                            'grade'   => new external_value(PARAM_TEXT, 'calculated grade'),
                            'value'   => new external_value(PARAM_FLOAT, 'numerical grade value'),
                            'is_hidden' => new external_value(PARAM_BOOL, 'true if the grade is currently hidden'),
                    )))
            ))),
        ));
    }

    # TODO: Add more grade fields like timestamps, etc.? Might not be worth the extra baggage.

    /**
     * Returns the grades for a course. This is intended to be faster and more efficient than
     * get grades, but does not have so many parameters.
     * @param int $courseid The id of the course with grades to retrieve.
     * @param string $since A timestamp in ISO 8601 format. Optional. Limits the grade items
     *        with grades returned to those that have a grade item timestamp or grade timestamp
     *        more current than the parameter time. If any grades for a grade item is sent, all
     *        grades for that grade item will be sent.
     */
    public static function get_course_grades($courseid, $since=null) {
        global $DB;

        $params = self::validate_parameters(self::get_course_grades_parameters(), array(
            'course_id' => $courseid,
            'since'     => $since
        ));

        if (!$course = $DB->get_record('course', array('id' => $params['course_id']))) {
            throw new moodle_exception(get_string('errorinvalidparam', 'webservice', 'course_id'));
        }

        $context = context_course::instance($course->id);

        // verify capability to view grade
        try {
            self::validate_context($context);
        } catch (Exception $e) {
            $exceptionparam = new stdClass();
            $exceptionparam->message = $e->getMessage();
            $exceptionparam->courseid = $params['course_id'];
            throw new moodle_exception(
                    get_string('errorcoursecontextnotvalid' , 'webservice', $exceptionparam));
        }

        require_capability('moodle/course:view', $context);
        require_capability('moodle/course:viewparticipants', $context);
        require_capability('moodle/grade:viewall', $context);
        require_capability('moodle/grade:viewhidden', $context);

        // verify capability for accessing emplid
        $send_idnumber = has_capability('local/user:view_idnumber', $context);

        // prep the output container
        $out = array(
            'course_id'        => $course->id,
            'course_shortname' => $course->shortname,
            'users'            => array(),
        );

        $grade_items = grade_item::fetch_all(array('courseid'=>$course->id));

        if ($params['since']) {
            // Only need to call this here because we need most current update times.
            // The graded_users_iterator will call it again.
            export_verify_grades($course->id);

            $sincetime = date("U",strtotime($params['since']));

            $sql =<<<SQL
select distinct gi.id
from mdl_grade_items gi
join mdl_grade_grades gg
         on gg.itemid = gi.id
where gi.courseid=:courseid and
      (gi.timecreated > :time1 or gi.timemodified > :time2 or
       gg.timecreated > :time3 or gg.timemodified > :time4)
SQL;
            $sqlparams = array('courseid' => $course->id,
                               'time1' => $sincetime, 'time2' => $sincetime,
                               'time3' => $sincetime, 'time4' => $sincetime);
            $possible_recent_graded_items = $DB->get_records_sql($sql, $sqlparams);

            $grade_items = array_intersect_key($grade_items, $possible_recent_graded_items);
        }

        if (! empty($grade_items)) {
            $gui = new graded_users_iterator($course, $grade_items);
            $gui->init();
            while ($userdata = $gui->next_user()) {
                $user = $userdata->user;
                $userout = array(
                    'user_moodle_id' => $user->id,
                    'user_username'  => $user->username,
                    'grades'  => array()
                );
                if ($send_idnumber) {
                    $userout['user_idnumber'] = $user->idnumber;
                }

                foreach ($userdata->grades as $itemid => $grade) {

                    // Otherwise, grade_grade will fetch the grade_item anew.
                    $grade->grade_item = $grade_items[$grade->itemid];

                    if (is_null($grade->finalgrade)) {
                        $formattedgrade = null;
                    } else {
                        $formattedgrade = grade_format_gradevalue(
                                                $grade->finalgrade,
                                                $grade_items[$grade->itemid]);
                    }
                    $gradeout = array(
                        'item_id' => $grade->itemid,
                        'grade'   => $formattedgrade,
                        'value'   => $grade->finalgrade,
                        'is_hidden' => $grade->is_hidden(),
                    );
                    $userout['grades'][] = $gradeout;
                }
                $out['users'][] = $userout;
            }
        }

        return $out;
    }


    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_grades_parameters() {
        return new external_function_parameters(array(
            'course_id'     => new external_value(PARAM_TEXT, 'Course ID', VALUE_REQUIRED),
            'user_id_type'  => new external_value(PARAM_TEXT, 'the user ID type to be supplied and returned, '.
                                                  'x500 (default) OR emplid (requires special capability) ' .
                                                  'OR username (to include non-UMN users)',
                                                  VALUE_DEFAULT, 'x500'),
            'users'         => new external_value(PARAM_TEXT, 'comma-separated values of x500 or emplid (depending on ' .
                                                  'user_id_type; if not provided, all users in course will be queried',
                                                  VALUE_DEFAULT, null),
            'item_type'     => new external_value(PARAM_TEXT, 'mod, block, ... (for advanced narrowing)', VALUE_DEFAULT, null),
            'item_module'   => new external_value(PARAM_TEXT, 'forum, quiz, ... (for advanced narrowing)', VALUE_DEFAULT, null),
            'item_instance' => new external_value(PARAM_TEXT, 'id of the item module, ... (for advanced narrowing)', VALUE_DEFAULT, null)
        ));
    }


   /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_grades_returns() {
        return new external_single_structure(array(
            'course_id'         => new external_value(PARAM_INT, 'requested course ID'),
            'course_shortname'  => new external_value(PARAM_TEXT, 'requested course short name'),
            'items'             => new external_multiple_structure(
                new external_single_structure(array(
                    'item_id'        => new external_value(PARAM_INT, 'ID of the grade item'),
                    'item_name'      => new external_value(PARAM_TEXT, 'name of the grade item'),
                    'item_type'      => new external_value(PARAM_TEXT, 'type of the grade item'),
                    'item_module'    => new external_value(PARAM_TEXT, 'module that this grade item belongs to'),
                    'item_instance'  => new external_value(PARAM_TEXT, 'ID of the instance that this item is about'),
                    'item_number'    => new external_value(PARAM_TEXT, 'to distinguish multiple grades for an activity'),
                    'item_grademin'  => new external_value(PARAM_TEXT, 'minimum allowable grade'),
                    'item_grademax'  => new external_value(PARAM_TEXT, 'maximum allowable grade'),
                    'item_gradepass' => new external_value(PARAM_TEXT, 'grade needed to pass'),
                    'grades'         => new external_multiple_structure(
                        new external_single_structure(array(
                            'user_id'    => new external_value(PARAM_TEXT, 'ID of the user, x500 or emplid'),
                            'grade'      => new external_value(PARAM_TEXT, 'calculated grade'),
                    )))
            ))),
            'errors'            => new external_multiple_structure(
                new external_single_structure(array(
                    'message'        => new external_value(PARAM_TEXT, 'error message'),
                    'user_id'        => new external_value(PARAM_TEXT, 'the provided user ID', VALUE_OPTIONAL),
                    'item_id'        => new external_value(PARAM_TEXT, 'related item ID', VALUE_OPTIONAL),
                    'item_type'      => new external_value(PARAM_TEXT, 'related item type', VALUE_OPTIONAL),
                    'item_module'    => new external_value(PARAM_TEXT, 'related item module', VALUE_OPTIONAL),
                    'item_instance'  => new external_value(PARAM_TEXT, 'related item instance', VALUE_OPTIONAL),
                )), '', VALUE_OPTIONAL)

        ));
    }



    /**
     * Get grades for a specific course in Moodle
     * Basically an external wrapper for grade_get_grades()
     *
     * ASSUMPTION: the grade_get_grades() call return values for only ONE item
     * @return array
     */
    public static function get_grades($course_id, $user_id_type, $users, $item_type, $item_module, $item_instance) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::get_grades_parameters(), array(
            'course_id'      => $course_id,
            'user_id_type'   => $user_id_type,
            'item_type'      => $item_type,
            'item_module'    => $item_module,
            'item_instance'  => $item_instance,
            'users'          => $users
        ));

        // validate user_id_type
        if (! in_array($params['user_id_type'], array('x500', 'emplid', 'username'))) {
            throw new moodle_exception(get_string('errorinvalidparam', 'webservice', 'user_id_type'));
        }

        // get the course record from course_id
        if (!$course = $DB->get_record('course', array('id' => $params['course_id']))) {
            throw new moodle_exception(get_string('errorinvalidparam', 'webservice', 'course_id'));
        }

        $context = context_course::instance($course->id);

        // verify capability to view grade
        try {
            self::validate_context($context);
        } catch (Exception $e) {
            $exceptionparam = new stdClass();
            $exceptionparam->message = $e->getMessage();
            $exceptionparam->courseid = $params['course_id'];
            throw new moodle_exception(
                    get_string('errorcoursecontextnotvalid' , 'webservice', $exceptionparam));
        }

        require_capability('moodle/course:view', $context);
        require_capability('moodle/course:viewparticipants', $context);
        require_capability('moodle/grade:viewall', $context);

        // verify capability for accessing emplid
        if ($params['user_id_type'] == 'emplid') {
            require_capability('local/user:view_idnumber', $context);
        }

        // prep the output container
        $out = array(
            'course_id'        => $course->id,
            'course_shortname' => $course->shortname,
            'items'            => array(),
            'errors'           => array()
        );


        //========== PROCESS GRADE ITEM =========

        // get the list of grade items for the course
        $grade_items = $DB->get_records('grade_items', array('courseid' => $course->id));

        // verify the submitted item filters, if any
        if (!is_null($item_type) || !is_null($item_module) || !is_null($item_instance)) {
            $matched_item = null;

            foreach ($grade_items as $item) {
                if ($item->itemtype == $params['item_type'] &&
                    $item->itemmodule == $params['item_module'] &&
                    $item->iteminstance == $params['item_instance']) {
                        $matched_item = $item;
                        break;
                }
            }

            if (is_null($matched_item)) {
                $out['errors'][] = array('message' => 'specified item not found');
                return $out;
            }

            $target_items = array($matched_item);
        }
        else {
            $target_items = $grade_items;
        }

        foreach ($target_items as $ind => $item) {
            $target_items[$ind] = new grade_item($item, false);
        }

        //========== PROCESS USERS ==========
        // verifying against the enrolled users to prevent over-querying in case
        // there are too many user IDs submitted

        $user_id_field_map = array('x500'     => 'username',
                                   'emplid'   => 'idnumber',
                                   'username' => 'username');

        $gradebookroles = explode(',', $CFG->gradebookroles);

        //get all users from the specified course, honor the "gradedroles" setting
        $query = "SELECT DISTINCT role_assignments.userid AS id,
                                  user." . $user_id_field_map[$user_id_type] . " AS mid
                  FROM {role_assignments} role_assignments
                       INNER JOIN {context} context ON context.id = role_assignments.contextid
                       INNER JOIN {user} user ON user.id = role_assignments.userid
                  WHERE context.contextlevel = 50 AND
                        context.instanceid = ? AND
                        role_assignments.roleid IN (".implode(',', array_fill(0, count($gradebookroles), '?')).")";

        $enrolled_uids = $DB->get_records_sql_menu($query, array_merge(array($course->id), $gradebookroles));

        switch ($params['user_id_type'] ) {
            case 'x500':
                //map the enrolled internal username to external x500
                foreach ($enrolled_uids as $id => $mid) {
                    try {
                        $enrolled_uids[$id] = umn_ldap_person_accessor::moodle_username_to_uid($mid);
                    }
                    catch (ldap_accessor_exception $e) {
                        unset($enrolled_uids[$id]);
                        $out['errors'][] = array('message' => 'not a valid UMN x500',
                                                 'user_id' => $mid);
                    }
                }
                break;

            case 'emplid':
                // remove entries that doesn't have idnumber
                foreach ($enrolled_uids as $id => $mid) {
                    if (empty($mid)) {
                        unset($enrolled_uids[$id]);
                        $out['errors'][] = array('message'  => "excluded user record {$id} for having empty emplid",
                                                 'user_id'  => '');
                    }
                }
                break;
        }

        $enrolled_mids = array_flip($enrolled_uids);    // reverse lookup for mapped-ids

        // check the submitted user list, if there is
        if (!is_null($params['users']) && trim($params['users']) != '') {
            $submitted_mids = preg_split("/[\s,;]+/", $params['users']);

            // verify enrollment
            $validated_mids = array_intersect(array_keys($enrolled_mids), $submitted_mids);

            // error log the non-enrolled
            foreach (array_diff($submitted_mids, $validated_mids) as $mid) {
                $out['errors'][] = array('message' => 'no graded role for this user',
                                         'user_id' => $mid);
            }

            // map back to UID for querying
            $validated_uids = array();
            foreach ($validated_mids as $mid) {
                $validated_uids[] = $enrolled_mids[$mid];
            }
        }
        else {
            // no user specified, look up all enroled users
            $validated_uids = array_keys($enrolled_uids);
        }


        //========== PROCESS GRADES =========

        // load the grades for manual items
        $manual_grades = array();

        if (count($validated_uids) > 0) {
            list($usql, $uparams) = $DB->get_in_or_equal($validated_uids, SQL_PARAMS_NAMED, 'uid');
            $params = array_merge(array('courseid'  => $course->id,
                                        'item_type' => 'manual'),
                                  $uparams);

            $sql = "SELECT grade_grades.*
                    FROM {grade_items} grade_items
                         INNER JOIN {grade_grades} grade_grades ON grade_grades.itemid = grade_items.id
                    WHERE grade_items.courseid = :courseid AND
                          grade_items.itemtype = :item_type AND
                          grade_grades.userid {$usql}";

            if ($grade_recs = $DB->get_records_sql($sql, $params)) {
                foreach ($grade_recs as $grade_rec) {
                    if (!array_key_exists($grade_rec->itemid, $manual_grades)) {
                        $manual_grades[$grade_rec->itemid] = array();
                    }

                    $manual_grades[$grade_rec->itemid][$grade_rec->userid] = new grade_grade($grade_rec, false);
                }
            }
        }


        // loop through the item list and get grades
        foreach ($target_items as $item_record) {

            // handle manual items differently
            if ($item_record->itemtype == 'manual') {
                // add the item details
                $item_out = array(
                        'item_id'        => $item_record->id,
                        'item_name'      => $item_record->itemname,
                        'item_type'      => $item_record->itemtype,
                        'item_module'    => $item_record->itemmodule,
                        'item_instance'  => $item_record->iteminstance,
                        'item_number'    => $item_record->itemnumber,
                        'item_grademin'  => $item_record->grademin,
                        'item_grademax'  => $item_record->grademax,
                        'item_gradepass' => $item_record->gradepass,
                        'grades'         => array()
                );

                // fill in the user grades
                if (array_key_exists($item_record->id, $manual_grades)) {
                    foreach ($manual_grades[$item_record->id] as $user_id => $user_grade) {
                        $item_out['grades'][] = array(
                            'user_id'    => $enrolled_uids[$user_id],
                            'grade'      => grade_format_gradevalue($user_grade->finalgrade, $item_record)
                        );
                    }
                }
            }
            else {
                // get the grades
                $grades = grade_get_grades(
                    $course->id,
                    $item_record->itemtype,
                    $item_record->itemmodule,
                    $item_record->iteminstance,
                    $validated_uids);

                // no item found, skip
                if (count($grades->items) == 0)
                    continue;

                $item = array_shift($grades->items);

                // add the item details
                $item_out = array(
                    'item_id'        => $item_record->id,
                    'item_name'      => $item->name,
                    'item_type'      => $item_record->itemtype,
                    'item_module'    => $item_record->itemmodule,
                    'item_instance'  => $item_record->iteminstance,
                    'item_number'    => $item_record->itemnumber,
                    'item_grademin'  => $item->grademin,
                    'item_grademax'  => $item->grademax,
                    'item_gradepass' => $item->gradepass,
                    'grades'         => array()
                );

                // add the grades in this item
                foreach ($item->grades as $user_id => $user_grade) {
                    $item_out['grades'][] = array(
                        'user_id'    => $enrolled_uids[$user_id],
                        'grade'      => $user_grade->str_grade
                    );
                }
            }

            $out['items'][] = $item_out;
        }

        return $out;
    }

}
