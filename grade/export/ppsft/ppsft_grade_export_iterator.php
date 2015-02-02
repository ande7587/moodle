<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

// Started from graded_users_iterator in grade/lib.php.

/**
 * This class iterates over all users that are graded in a course.
 * Returns detailed info about users and their grades.
 * Started with grade_users_iterator.  Supports filtering by PeopleSoft
 * class.
 */
class ppsft_grade_export_iterator {

    /**
     * The course whose users we are interested in
     */
    protected $course;

    /**
     * An array of grade items or null if only user data was requested
     */
    protected $grade_items;

    /**
     * The ppsft_classes ID we are interested in. 0 means all.
     */
    protected $ppsftclassid;

    /**
     * A recordset of graded users
     */
    protected $users_rs;

    /**
     * A recordset of user grades (grade_grade instances)
     */
    protected $grades_rs;

    /**
     * Array used when moving to next user while iterating through the grades recordset
     */
    protected $gradestack;

    /**
     * The first field of the users table by which the array of users will be sorted
     */
    protected $sortfield1;

    /**
     * Should sortfield1 be ASC or DESC
     */
    protected $sortorder1;

    /**
     * The second field of the users table by which the array of users will be sorted
     */
    protected $sortfield2;

    /**
     * Should sortfield2 be ASC or DESC
     */
    protected $sortorder2;

    /**
     * Constructor
     *
     * @param object $course A course object
     * @param array  $grade_items array of grade items, if not specified only user info returned
     * @param int    $ppsftclassid iterate only ppsftclass users if present
     * @param string $sortfield1 The first field of the users table by which the array of users will be sorted
     * @param string $sortorder1 The order in which the first sorting field will be sorted (ASC or DESC)
     * @param string $sortfield2 The second field of the users table by which the array of users will be sorted
     * @param string $sortorder2 The order in which the second sorting field will be sorted (ASC or DESC)
     */
    public function __construct($course, $grade_items=null, $ppsftclassid=0,
                                          $sortfield1='lastname', $sortorder1='ASC',
                                          $sortfield2='firstname', $sortorder2='ASC') {
        $this->course      = $course;
        $this->grade_items = $grade_items;
        $this->sortfield1  = $sortfield1;
        $this->sortorder1  = $sortorder1;
        $this->sortfield2  = $sortfield2;
        $this->sortorder2  = $sortorder2;
        $this->ppsftclassid = $ppsftclassid;

        $this->gradestack  = array();
    }

    /**
     * Initialise the iterator
     *
     * @return boolean success
     */
    public function init() {
        global $CFG, $DB;

        $this->close();

        export_verify_grades($this->course->id);
        $course_item = grade_item::fetch_course_item($this->course->id);
        if ($course_item->needsupdate) {
            // can not calculate all final grades - sorry
            return false;
        }

        $coursecontext = context_course::instance($this->course->id);
        $parentcontextids = $coursecontext->get_parent_context_ids(true);
        $relatedcontexts = ' IN ('.implode(',', $parentcontextids).')';

        list($gradebookroles_sql, $params) =
            $DB->get_in_or_equal(explode(',', $CFG->gradebookroles), SQL_PARAMS_NAMED, 'grbr');
        list($enrolledsql, $enrolledparams) = get_enrolled_sql($coursecontext);

        $params = array_merge($params, $enrolledparams);

        $ppsftclassfiltersql = '';
        if ($this->ppsftclassid) {
            $ppsftclassfiltersql = "AND pce.ppsftclassid = :ppsftclassid";
            $params['ppsftclassid'] = $this->ppsftclassid;
        }

        if (empty($this->sortfield1)) {
            // we must do some sorting even if not specified
            $ofields = ", u.id AS usrt";
            $order   = "usrt ASC";

        } else {
            $ofields = ", u.$this->sortfield1 AS usrt1";
            $order   = "usrt1 $this->sortorder1";
            if (!empty($this->sortfield2)) {
                $ofields .= ", u.$this->sortfield2 AS usrt2";
                $order   .= ", usrt2 $this->sortorder2";
            }
            if ($this->sortfield1 != 'id' and $this->sortfield2 != 'id') {
                // user order MUST be the same in both queries,
                // must include the only unique user->id if not already present
                $ofields .= ", u.id AS usrt";
                $order   .= ", usrt ASC";
            }
        }

        $userfields = 'u.*';

        // $params contents: gradebookroles and ppsftclassid (for $ppsftclassfiltersql)
        $users_sql = "SELECT $userfields $ofields,
                             ppsft_enrol.section AS ppsft_section,
                             ppsft_enrol.grading_basis AS ppsft_grading_basis,
                             la.timeaccess as last_access
                        FROM {user} u
                        JOIN ($enrolledsql) je ON je.id = u.id
                        JOIN (
                                  SELECT DISTINCT ra.userid
                                    FROM {role_assignments} ra
                                   WHERE ra.roleid $gradebookroles_sql
                                     AND ra.contextid $relatedcontexts
                             ) rainner ON rainner.userid = u.id " .

                       // The following is a left join in graded_user_iterator.  In this case,
                       // we want only ppsft enrollees.
                       "JOIN (
                           SELECT pce.userid AS userid,
                                CONCAT(ppsft_classes.subject,
                                       ppsft_classes.catalog_nbr, '_',
                                       ppsft_classes.section) AS section,
                                pce.grading_basis
                           FROM {ppsft_class_enrol} pce
                                JOIN {ppsft_classes} ppsft_classes
                                    ON ppsft_classes.id = pce.ppsftclassid
                                JOIN {enrol_umnauto_classes} enrol_umnauto_classes
                                    ON ppsft_classes.id = enrol_umnauto_classes.ppsftclassid
                                JOIN {enrol} enrol
                                    ON enrol_umnauto_classes.enrolid = enrol.id
                           WHERE  enrol.courseid = :courseid AND
                                  pce.status = 'E' AND
                                  pce.grading_basis not in ('NON', 'NGA')
                                  $ppsftclassfiltersql
                           GROUP BY userid
                       ) ppsft_enrol ON u.id = ppsft_enrol.userid
                       LEFT JOIN {user_lastaccess} la on la.userid = u.id and
                                                         la.courseid = :courseid2
                       WHERE u.deleted = 0
                       ORDER BY {$order}";

        $params['courseid'] = $this->course->id;
        $params['courseid2'] = $this->course->id;

        $this->users_rs = $DB->get_recordset_sql($users_sql, $params);

        if (!empty($this->grade_items)) {
            $itemids = array_keys($this->grade_items);
            list($itemidsql, $grades_params) = $DB->get_in_or_equal($itemids, SQL_PARAMS_NAMED, 'items');
            $params = array_merge($params, $grades_params);
            // $params contents: gradebookroles, enrolledparams, ppsftclassid (for $ppsftclassfiltersql) and itemids

            $grades_sql = "SELECT g.* $ofields
                             FROM {grade_grades} g
                             JOIN {user} u ON g.userid = u.id
                             JOIN ($enrolledsql) je ON je.id = u.id
                             JOIN (
                                      SELECT DISTINCT ra.userid
                                        FROM {role_assignments} ra
                                       WHERE ra.roleid $gradebookroles_sql
                                         AND ra.contextid $relatedcontexts
                                  ) rainner ON rainner.userid = u.id
                             JOIN (
                                 SELECT pce.userid AS userid
                                 FROM {ppsft_class_enrol} pce
                                      JOIN {enrol_umnauto_classes} euc
                                          ON euc.ppsftclassid = pce.ppsftclassid
                                      JOIN {enrol} e
                                          ON e.id = euc.enrolid
                                 WHERE  e.courseid = :courseid AND
                                        pce.status = 'E'
                                        $ppsftclassfiltersql
                                 GROUP BY userid
                             ) ppsft_enrol ON u.id = ppsft_enrol.userid
                             WHERE u.deleted = 0
                               AND g.itemid $itemidsql
                         ORDER BY $order, g.itemid ASC";
            $this->grades_rs = $DB->get_recordset_sql($grades_sql, $params);
        } else {
            $this->grades_rs = false;
        }

        return true;
    }

    /**
     * Returns information about the next user
     * @return mixed array of user info, all grades or null when no more users found
     */
    public function next_user() {
        if (!$this->users_rs) {
            return false; // no users present
        }

        if (!$this->users_rs->valid()) {
            if ($current = $this->_pop()) {
                // this is not good - user or grades updated between the two reads above :-(
            }

            return false; // no more users
        } else {
            $user = $this->users_rs->current();
            $this->users_rs->next();
        }

        // find grades of this user
        $grade_records = array();
        while (true) {
            if (!$current = $this->_pop()) {
                break; // no more grades
            }

            if (empty($current->userid)) {
                break;
            }

            if ($current->userid != $user->id) {
                // grade of the next user, we have all for this user
                $this->_push($current);
                break;
            }

            $grade_records[$current->itemid] = $current;
        }

        $grades = array();

        if (!empty($this->grade_items)) {
            foreach ($this->grade_items as $grade_item) {
                if (array_key_exists($grade_item->id, $grade_records)) {
                    $grades[$grade_item->id] = new grade_grade($grade_records[$grade_item->id], false);
                } else {
                    $grades[$grade_item->id] =
                        new grade_grade(array('userid'=>$user->id, 'itemid'=>$grade_item->id), false);
                }
            }
        }

        $result = new stdClass();
        $result->user      = $user;
        $result->grades    = $grades;
        return $result;
    }

    /**
     * Close the iterator, do not forget to call this function
     */
    public function close() {
        if ($this->users_rs) {
            $this->users_rs->close();
            $this->users_rs = null;
        }
        if ($this->grades_rs) {
            $this->grades_rs->close();
            $this->grades_rs = null;
        }
        $this->gradestack = array();
    }

    /**
     * Add a grade_grade instance to the grade stack
     *
     * @param grade_grade $grade Grade object
     *
     * @return void
     */
    private function _push($grade) {
        array_push($this->gradestack, $grade);
    }


    /**
     * Remove a grade_grade instance from the grade stack
     *
     * @return grade_grade current grade object
     */
    private function _pop() {
        global $DB;
        if (empty($this->gradestack)) {
            if (empty($this->grades_rs) || !$this->grades_rs->valid()) {
                return null; // no grades present
            }

            $current = $this->grades_rs->current();

            $this->grades_rs->next();

            return $current;
        } else {
            return array_pop($this->gradestack);
        }
    }
}






