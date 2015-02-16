<?php

require_once($CFG->dirroot.'/blocks/hacpclient/lib.php');

defined('MOODLE_INTERNAL') || die();

class block_hacpclient extends block_base {

    function init() {
        $this->title   = get_string('pluginname', 'block_hacpclient');
    }

    function specialization() {

        // If this instance has a title configured, display that title.
        if (!empty($this->config->title)) {
            $this->title = $this->config->title;
        }

        $configchanged = false;

        if (empty($this->config)) {
            $this->config = new stdClass;
        }

        // Generate a value for AU_password, if it does not already exist.
        if (!isset($this->config->aupassword)) {
            $this->config->aupassword = generate_password();
            $configchanged = true;
        }

        if (empty($this->config->roleid)) {
            $default_role = hacpclient_default_enrollment_role();
            $this->config->roleid = $default_role->id;
            $configchanged = true;
        }

        if (empty($this->config->completetrigger)) {
            $this->config->completetrigger = hacpclient_default_complete_trigger();
            $configchanged = true;
        }

        if ($configchanged) {
            $this->instance_config_commit();
        }
    }

    /**
     * This method determines which kinds of pages the block can appear on.
     */
    function applicable_formats() {
        return array('all' => true,
                     'my'  => false);
    }

    /**
     * Delete associated sessions from our database when deleting an instance.
     * Does not affect the CMI.
     */
    function instance_delete() {
        global $DB;

        $DB->delete_records('block_hacpclient_sessions',
                            array('hacpclientid' => $this->instance->id));

        return true;
    }

    function get_content() {
        global $USER, $COURSE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;

        $this->content->text = '';

        # TODO: Consider whether this is the correct capability for both links.
        #       Should be kept consistent with targets.
        if (has_capability('moodle/block:edit', $this->context)) {

            $this->content->text .= $this->get_metadata_linkdiv();
            $this->content->text .= $this->get_sessions_linkdiv();
        }

        if ($this->get_complete_trigger() == HACP_COMPLETE_VIEW) {
            $this->send_sessions_complete($USER->id, $COURSE->id);
        } else {
            // Check for and send pending updates.
            $this->send_completes_on_completion($USER->id);
        }

        $statusstring = $this->get_user_session_status_string($USER->id);

        $status_html =<<<HTML
<div class="lessonstatus">
    <span class="message">$statusstring</span>
</div>
HTML;
        $this->content->text .= $status_html;

        return $this->content;
    }

    private function get_metadata_linkdiv() {

        $metadataurl = new moodle_url('/blocks/hacpclient/downloadmetadata.php',
                                      array('hacpclientid' => $this->instance->id));

        $metadatalink = html_writer::link($metadataurl,
                                          get_string('downloadmetadata', 'block_hacpclient'));

        return "<div>$metadatalink</div>";
    }

    private function get_sessions_linkdiv() {

        $sessionsurl = new moodle_url('/blocks/hacpclient/sessions.php',
                                      array('hacpclientid' => $this->instance->id));

        $sessionslink = html_writer::link($sessionsurl,
                                          get_string('showsessions', 'block_hacpclient'));

        return "<div>$sessionslink</div>";
    }

    private function send_sessions_complete($userid, $courseid) {

        $session_manager = hacpclient_get_hacpclient_session_manager();

        $aupassword = $this->config->aupassword;

        $hacpsessions = $this->get_user_sessions($userid);

        $completedstatuses = hacpclient_session_manager::get_completed_statuses();

        // Might be possible to have multiple sessions but is unlikely. Would
        // be the case if user was taking the course through multiple CMIs (i.e.,
        // different aicc_url values).
        foreach ($hacpsessions as $hacpsession) {
            if (! in_array($hacpsession->lesson_status, $completedstatuses)) {
                $score = hacpclient_get_overall_course_grade($userid, $courseid);
                $session_manager->send_complete_no_throw($hacpsession, $score, $aupassword);
            }
        }
    }

    public function get_enrollment_roleid() {

        if (!empty($this->config) and $this->config->roleid) {
            return $this->config->roleid;
        }
        $default_role =  hacpclient_default_enrollment_role();
        return $default_role->id;
    }

    public function get_complete_trigger() {

        if (!empty($this->config) and $this->config->completetrigger) {
            return $this->config->completetrigger;
        }
        $default_trigger =  hacpclient_default_complete_trigger();
        return $default_trigger;
    }


    /**
     * If $userid is not set, sends for all users that have completed.
     */
    public function send_completes_on_completion($userid=null) {

        if ($this->get_complete_trigger() == HACP_COMPLETE_COMPLETION) {

            $parentcontextid = $this->instance->parentcontextid;
            $parentcontext = context::instance_by_id($parentcontextid);

            # TODO: Could add another branch for activity completion, but not an
            #       actual requirement, yet.
            if ($parentcontext->contextlevel === CONTEXT_COURSE) {
                $this->send_completes_on_course_completion($parentcontext, $userid);
            }
        }
    }

    public function send_completes_on_course_completion($coursecontext, $userid=null) {
        global $DB;

        $completedstatusessql = hacpclient_session_manager::get_completed_statuses_sql();

        $params = array('hacpclientid' => $this->instance->id,
                        'courseid'     => $coursecontext->instanceid);

        if ($userid) {
            $usercondition = ' and hs.userid = :userid ';
            $params['userid'] = $userid;
        } else {
            $usercondition = '';
        }

        // For each user that has completed the course and for which
        // the lesson status indicates that the student has not
        // completed the lesson, send a completion message.
        $sql =<<<SQL
select hs.*
from {block_hacpclient_sessions} hs
  join {course_completions} cc on cc.userid=hs.userid
where hs.lesson_status not in ($completedstatusessql)
  and hs.hacpclientid = :hacpclientid
  and hs.errorcode = 0
  $usercondition
  and cc.timecompleted is not null
  and cc.course = :courseid
SQL;

        $sessions = $DB->get_records_sql($sql, $params);

        $session_manager = hacpclient_get_hacpclient_session_manager();

        foreach ($sessions as $session) {
            $completedstatuses = hacpclient_session_manager::get_completed_statuses();
            if (! in_array($session->lesson_status, $completedstatuses)) {

                $score = hacpclient_get_overall_course_grade($session->userid, $coursecontext->instanceid);

                $session_manager->send_complete_no_throw($session, $score, $this->config->aupassword);
            }
        }
    }

    public function get_status_overview_by_url() {
        global $DB;

        $completedstatusessql = hacpclient_session_manager::get_completed_statuses_sql();

        $sql =<<<SQL
select aicc_url,
       count(*) as sessioncount,
       count(nullif(0, errorcode)) as errors,
       count(nullif(false, lesson_status in ($completedstatusessql))) as completed,
       count(nullif(false, lesson_status not in ($completedstatusessql)
                           and getparamtime <= :oldtime)) as oldincomplete,
       max(getparamtime) as maxgetparamtime
from {block_hacpclient_sessions}
where hacpclientid = :hacpclientid
group by aicc_url order by max(getparamtime) desc
SQL;

        $oldtime = hacpclient_old_access_time();

        $params = array('oldtime' => $oldtime,
                        'hacpclientid' => $this->instance->id);

        return $DB->get_records_sql($sql, $params);
    }

    public function get_participation_summary() {
        global $DB;

        // mysql does not have a full outer join so handling doing the join
        // counting in php with array operations.

        $coursecontext = $this->context->get_course_context();

        // See get_enrolled_sql, get_enrolled_users, and count_enrolled_users in lib/accesslib.php.
        list($esql, $params) = get_enrolled_sql($coursecontext, '', 0, 'id');

        $enrolleduserids = $DB->get_fieldset_sql($esql, $params);

        $sql =<<<SQL
select distinct userid
from {block_hacpclient_sessions}
where hacpclientid = :hacpclientid
SQL;

        $hacpuserids = $DB->get_fieldset_sql($sql,
                                             array('hacpclientid' => $this->instance->id));

        return array('enrolled'    => count($enrolleduserids),
                     'hacpusers'   => count($hacpuserids),
                     'nohacp'      => count(array_diff($enrolleduserids, $hacpuserids)),
                     'notenrolled' => count(array_diff($hacpuserids, $enrolleduserids)));

    }

    /**
     * Note that the first field in the data set (and so the resulting array key)
     * is the id in mdl_block_hacpclient_sessions.
     */
    public function get_user_sessions_not_enrolled() {
        global $DB;

        $coursecontext = $this->context->get_course_context();

        // See get_enrolled_sql, get_enrolled_users, and count_enrolled_users in lib/accesslib.php.
        list($esql, $params) = get_enrolled_sql($coursecontext, '', 0, 'id');

        $sql =<<<SQL
select hs.id as hacpsessionid, u.*
from {user} u
  join {block_hacpclient_sessions} hs on hs.userid = u.id
  left outer join ($esql) je on je.id = u.id
where hs.hacpclientid = :hacpclientid and je.id is null
SQL;

        $params['hacpclientid'] = $this->instance->id;

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Deletes sessions last accessed before some time in the past and not completed.
     */
    public function delete_old_sessions($aiccurls) {
        global $DB;

        $completedstatusessql = hacpclient_session_manager::get_completed_statuses_sql();

        foreach ($aiccurls as $aiccurl) {
            $DB->delete_records_select(
                'block_hacpclient_sessions',
                "hacpclientid = :hacpclientid
                 and aicc_url = :aiccurl
                 and getparamtime <= :oldaccesstime
                 and lesson_status not in ($completedstatusessql)",
                array('hacpclientid'  => $this->instance->id,
                      'aiccurl'       => $aiccurl,
                      'oldaccesstime' => hacpclient_old_access_time()));
        }
    }

    public function delete_errors($aiccurls) {
        global $DB;

        foreach ($aiccurls as $aiccurl) {
            $DB->delete_records_select(
                'block_hacpclient_sessions',
                "hacpclientid = :hacpclientid
                 and aicc_url = :aiccurl
                 and errorcode > 0",
                array('hacpclientid' => $this->instance->id,
                      'aiccurl'      => $aiccurl));
        }
    }

    public function delete_sessions_for_users_not_enrolled() {
        global $DB;

        $notenrolled = $this->get_user_sessions_not_enrolled();

        $DB->delete_records_list('block_hacpclient_sessions',
                                 'id',
                                 array_keys($notenrolled));
    }

    public function retry_errors($aiccurls) {

        $session_manager = hacpclient_get_hacpclient_session_manager();
        $coursecontext = $this->context->get_course_context();
        $aupassword = $this->config->aupassword;

        foreach ($aiccurls as $aiccurl) {
            $errorsessions = $this->get_user_sessions_for_url($aiccurl,
                                                              HACPCLIENT_USERS_ERRORS);

            foreach ($errorsessions as $session) {
                $score = hacpclient_get_overall_course_grade($session->userid,
                                                             $coursecontext->instanceid);

                if ($session->errorcode == HACPCLIENT_ERROR_ON_COMPLETE_MSG) {
                    $session_manager->send_complete_no_throw($session, $score, $aupassword);
                }
            }
        }
    }

    public function retry_error($userid, $aiccurl) {

        $session_manager = hacpclient_get_hacpclient_session_manager();
        $coursecontext = $this->context->get_course_context();
        $aupassword = $this->config->aupassword;

        $session = $this->get_user_session($userid, $aiccurl);

        if ($session->errorcode == HACPCLIENT_ERROR_ON_COMPLETE_MSG) {

            $score = hacpclient_get_overall_course_grade($userid,
                                                         $coursecontext->instanceid);

            $session_manager->send_complete($session, $score, $aupassword);
        }
    }

    /**
     * Returns a link to the user session page for a given aiccurl.
     */
    public function users_link($base64aiccurl, $filter, $linktext) {

        $link = html_writer::link(new moodle_url(
                                      '/blocks/hacpclient/users.php',
                                      array('hacpclientid' => $this->instance->id,
                                            'aiccurl'      => $base64aiccurl,
                                            'filter'       => $filter)),
                                  $linktext);

        return $link;
    }

    /**
     * If $userid is null, the user search page displays without a user displayed.
     */
    public function user_link($userid, $linktext) {
        $link = html_writer::link(new moodle_url(
                                        '/blocks/hacpclient/user.php',
                                        array('hacpclientid' => $this->instance->id,
                                              'userid'       => $userid)),
                                  $linktext);
        return $link;
    }

    /**
     *
     */
    public function error_retry_button($session) {
        $url = new moodle_url('/blocks/hacpclient/user.php',
                              array('hacpclientid' => $this->instance->id,
                                    'userid'       => $session->userid,
                                    'aiccurl'      => $session->aicc_url,
                                    'retry'        => $session->id));

        return new single_button($url,
                                 get_string('errorretrybutton', 'block_hacpclient'));

    }

    /**
     *
     */
    public function get_user_sessions_for_url($aiccurl, $filter=HACPCLIENT_USERS_ALL) {
        global $DB;

        $notcompletedsql = 'hs.lesson_status not in ('
                           . hacpclient_session_manager::get_completed_statuses_sql()
                           . ')';

        $oldgetparamsql = 'hs.getparamtime <= ' . hacpclient_old_access_time();

        switch($filter) {
            case HACPCLIENT_USERS_OLD:
                $additionalconditions = " and $notcompletedsql and $oldgetparamsql ";
                break;
            case HACPCLIENT_USERS_ERRORS:
                $additionalconditions = " and hs.errorcode <> 0 ";
                break;
            case HACPCLIENT_USERS_ALL:
            default:
                $additionalconditions = '';
        }

        $sql =<<<SQL
select hs.*, u.firstname, u.lastname, u.username
from {block_hacpclient_sessions} hs
  join {user} u on u.id = hs.userid
where hs.hacpclientid = :hacpclientid
  and hs.aicc_url = :aiccurl
  $additionalconditions
order by u.lastname, u.firstname, u.username
SQL;

        $params = array('hacpclientid' => $this->instance->id,
                        'aiccurl'      => $aiccurl);

        $sessions = $DB->get_records_sql($sql, $params);
        return $sessions;
    }

    public function get_user_session($userid, $aiccurl) {
        global $DB;

        $session = $DB->get_record('block_hacpclient_sessions',
                                    array('hacpclientid' => $this->instance->id,
                                          'userid'       => $userid,
                                          'aicc_url'      => $aiccurl),
                                    '*',
                                    MUST_EXIST);

        return $session;
    }

    public function get_user_sessions($userid) {
        global $DB;
        ####error_log("block_hacpclient_user_sessions($hacpclientid, $userid)");
        $sessions = $DB->get_records('block_hacpclient_sessions',
                                     array('hacpclientid' => $this->instance->id,
                                           'userid'       => $userid));
        return $sessions;
    }

    public function get_user_session_status_string($userid) {
        global $DB;

        // Session status string depends on getparamtime, lesson_status, errorcode.

        // If just one session, show error message if error. Show completed, passed,
        // or failed for completed. Show no session if no session. Show session
        // start time for ongoing session.

        // If multiple sessions, state multiple.

        $sessions = $DB->get_records('block_hacpclient_sessions',
                                 array('hacpclientid' => $this->instance->id,
                                       'userid'       => $userid));

        if (count($sessions) == 0)
            return get_string('statusnosession', 'block_hacpclient');

        // A user can have multiple sessions if multiple CMIs are involved.
        if (count($sessions) > 1)
            return get_string('statusmultiplesessions', 'block_hacpclient');

        $session = array_shift($sessions);

        if ($session->errorcode != HACPCLIENT_ERROR_NONE)
            return get_string('statuserror', 'block_hacpclient');

        switch($session->lesson_status) {
            case HACP_LESSON_STATUS_PASSED:
                return get_string('statuspassed'   , 'block_hacpclient');
            case HACP_LESSON_STATUS_COMPLETED:
                return get_string('statuscompleted', 'block_hacpclient');
            case HACP_LESSON_STATUS_FAILED:
                return get_string('statusfailed'   , 'block_hacpclient');
            case HACP_LESSON_STATUS_INCOMPLETE:
            case HACP_LESSON_STATUS_BROWSED:
            case HACP_LESSON_STATUS_NOTATTEMPTED:
                $timestring = strftime('%l:%M %P %F', $session->getparamtime);
                return get_string('statusnotcompleted', 'block_hacpclient', $timestring);
            default:
                return get_string('statusinvalid'     , 'block_hacpclient');
        }
    }

    ////////////////////////////////////////
    // cron-related functions follow

    function cron() {

        try {
            self::send_all_completes_on_completion();

            // The following is a cleanup operation that need not run frequently.
            // The number on the right side of '<' is the percentage of time that
            // cleanup operation runs.  This is modeled after similar random logic
            // in lib/cronlib.php.
            $random100 = rand(0,100);
            if ($random100 < 5) {
                mtrace(" Deleting hacpclient sessions for users not enrolled. ");
                self::delete_all_sessions_for_users_not_enrolled();
            }
        } catch (Exception $ex) {
            hacpclient_log_exception($ex, 'block/hacpclient cron terminated with exception');
            mtrace('WARNING: See error log for exception in block/hacpclient cron');
        }
    }

    /**
     *
     */
    function delete_all_sessions_for_users_not_enrolled() {
        global $DB;

        $instances = $DB->get_records('block_instances', array('blockname'=>'hacpclient'));

        foreach ($instances as $instance) {
            $block = block_instance('hacpclient', $instance);
            $block->delete_sessions_for_users_not_enrolled();
        }
    }


    /**
     * Used by cron to send updates for all completions not yet sent.
     */
    static private function send_all_completes_on_completion() {
        global $DB;

        $completedstatusessql = hacpclient_session_manager::get_completed_statuses_sql();

        // Get hacpclient block instances that have sessions without a lesson_status
        // indicating completion.
        $instances = $DB->get_records_select('block_instances',
                      "id in (select hacpclientid from {block_hacpclient_sessions} where lesson_status not in ($completedstatusessql))");

        foreach ($instances as $instance) {
            $block = block_instance('hacpclient', $instance);
            $block->send_completes_on_completion();
        }


    }

}

