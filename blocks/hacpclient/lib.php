<?php

require_once('hacpclient_session_manager.php');
require_once('hacp_adapter.php');
require_once('hacpclient_exceptions.php');
require_once($CFG->dirroot.'/user/selector/lib.php');

define('HACP_COMPLETE_NONE'      , 1);
define('HACP_COMPLETE_COMPLETION', 2);
define('HACP_COMPLETE_VIEW'      , 3);

// The following HACPCLIENT_ERROR_* values are used for the
// errorcode values.

// Part of the implementation might assume that 0 indicates no
// error, so be cautious if tempted to change this value here.
define('HACPCLIENT_ERROR_NONE', 0);

// This error code indicates that the user completed the AU
// and that Moodle attempted to send the completion message
// to the CMI, but the attempt failed.
define('HACPCLIENT_ERROR_ON_COMPLETE_MSG', 9);

// This is the key for the artificial name-value pair for ini
// groups that represents the freeform version of the group in
// case that's the kind of group we have. Throwing in an '='
// since no real key can contain that character.
define('HACP_INI_FREEFORM_KEY', 'INI_FREEFORM=');

// This is a constant because we have only one in
// each package and (as far as I know), it only needs
// to be unique within a package.
define('HACP_AU_SYSTEMID', 'A1');

// These constants are used to determine which users to show on
// blocks/hacpclient/users.php.
define('HACPCLIENT_USERS_ALL', 1);
define('HACPCLIENT_USERS_ERRORS', 2);
define('HACPCLIENT_USERS_OLD', 3);


class hacpclient_enrolled_user_selector extends user_selector_base {

    const MAX_USERS_PER_PAGE = 200;

    private $coursecontext;

    public function __construct($name, $options) {
        $this->coursecontext = $options['coursecontext'];
        parent::__construct($name, $options);
    }

    protected function get_options() {
        global $CFG;

        $options = parent::get_options();
        $options['file'] = '/blocks/hacpclient/lib.php';
        $options['coursecontext'] = $this->coursecontext;

        return $options;
    }

    public function find_users($search) {
        global $DB;

        list($enrolsql, $eparams) = get_enrolled_sql($this->coursecontext);

        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params = array_merge($params, $eparams);

        if ($wherecondition) {
            $wherecondition = ' AND ' . $wherecondition;
        }

        $fields = 'select ' . $this->required_fields_sql('u');
        $countfields = 'select count(1) ';

        $sql = " from {user} u where u.id in ($enrolsql) $wherecondition ";

        $order = ' order by u.lastname asc, u.firstname asc ';


        if (!$this->is_validating()) {
            $searchcount = $DB->count_records_sql($countfields . $sql, $params);
            if ($searchcount > hacpclient_enrolled_user_selector::MAX_USERS_PER_PAGE) {
                return $this->too_many_results($search, $searchcount);
            }
        }

        $enrolled = $DB->get_records_sql($fields . $sql . $order, $params);

        if (empty($enrolled)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('enrolledusersmatching', 'block_hacpclient', $search);
        } else {
            $groupname = get_string('enrolledusers', 'block_hacpclient');
        }
        return array($groupname => $enrolled);
    }
}


function hacpclient_get_block($blockinstanceid) {
    global $DB;

    $blockinstance = $DB->get_record('block_instances',
                                     array('id'=>$blockinstanceid));

    if ($blockinstance->blockname !== 'hacpclient') {
        throw new Exception('Invalid name: ' . $blockinstance->blockname);
    }

    return block_instance('hacpclient', $blockinstance);
}

function hacpclient_get_hacp_adapter() {
    return new hacpclient_hacp_adapter();
}

function hacpclient_get_hacpclient_session_manager($hacp_adapter = null) {
    if (! $hacp_adapter) {
        $hacp_adapter = hacpclient_get_hacp_adapter();
    }
    return new hacpclient_session_manager($hacp_adapter);
}

function hacpclient_default_enrollment_role() {
    $student_roles = get_archetype_roles('student');
    return reset($student_roles);
}

function hacpclient_default_complete_trigger() {
    return HACP_COMPLETE_COMPLETION;
}

function hacpclient_get_overall_course_grade($userid, $courseid) {
    global $CFG;

    require_once($CFG->dirroot.'/grade/lib.php');
    require_once($CFG->dirroot.'/grade/querylib.php');

    $grade = grade_get_course_grade($userid, $courseid);
    $score = $grade->grade;
    return $score;
}

/**
 * Base64 encoding the URL to avoid issue with URL encoding of URLs.
 * Using base64url variant as shown in
 * http://php.net/manual/en/function.base64-encode.php.
 */
function hacpclient_base64url_encode($data) {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * See comment for hacpclient_base64url_encode.
 */
function hacpclient_base64url_decode($data) {
  return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function hacpclient_old_access_time($oldaccesstime=0) {
    global $SESSION;

    if ($oldaccesstime) {
        $SESSION->hacpclient_oldaccesstime = $oldaccesstime;
    }

    if (empty($SESSION->hacpclient_oldaccesstime)) {
        $SESSION->hacpclient_oldaccesstime = time() - 24*60*60;
    }

    return $SESSION->hacpclient_oldaccesstime;
}

function hacpclient_send_metadata_download_headers($downloadfilename) {
    global $CFG;

    // Download header setting taken from grade/export/txt/grade_export_txt.php.
    /// Print header to force download
    if (strpos($CFG->wwwroot, 'https://') === 0) { //https sites - watch out for IE! KB812935 and KB316431
        @header('Cache-Control: max-age=10');
        @header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
        @header('Pragma: ');
    } else { //normal http - prevent caching at all cost
        @header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
        @header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
        @header('Pragma: no-cache');
    }
    header("Content-Type: application/download\n");

    header("Content-Disposition: attachment; filename=\"$downloadfilename\"");
}

function hacpclient_get_content_for_crs_file($hacpclientblock, $course) {

    # TODO: Improve the following values.

    $crscoursecreator     = 'University of Minnesota';
    $crscourseid          = $course->id;
    $crscoursetitle       = preg_replace('/[\r\n]/', '_', $course->shortname);
    $crscoursedescription = 'A University of Minnesota Moodle course';

    $crs =<<<CRS
[Course]
Course_Creator=$crscoursecreator
Course_ID=$crscourseid
Course_System=Moodle
Course_Title=$crscoursetitle
Level=1
Total_AUs=1
Total_Blocks=0
Max_Fields_CST=1
Version=4.0

[Course_Behavior]
Max_Normal=99

[Course_Description]
$crscoursedescription
CRS;

    // Ensure that lines end with CRLF before returning. Using PCRE lookbehind.
    return preg_replace('/(?<!\r)\n/', "\r\n", $crs);
}

function hacpclient_get_content_for_au_file($hacpclientblock) {
    global $CFG;

    // Guard against double quotes getting into value strings.

    // According to the spec, values are required for
    // system_id, command_line, file_name, core_vendor,
    // web_launch, and au_password (for Course Level 1).

    // The value we set for core_vendor will come back in [Core_Vendor]
    // in the getparam response. We can use this to validate that the
    // LMS (or "CMI") is authorized to pass students in.
    if (empty($hacpclientblock->config->cmipassword)) {
        $aucorevendorlaunchdata = '';
    } else {
        $aucorevendorlaunchdata = 'cmipassword='.$hacpclientblock->config->cmipassword;
    }

    // This is the password that should be required by the AU when sending
    // HACP requests. Not all CMIs support the password.
    $aupassword = $hacpclientblock->config->aupassword;

    $aufilename = "$CFG->wwwroot/blocks/hacpclient/startsession.php";
    $auweblaunch = "hacpclientid=".$hacpclientblock->instance->id;

    $ausystemid = HACP_AU_SYSTEMID;

    $namevalues = array(
        'system_id' => $ausystemid,
        'type' => '',
        'command_line' => '',
        'max_time_allowed' => '',
        'time_limit_action' => '',
        'file_name' => $aufilename,
        'max_score' => '',
        'mastery_score' => '',
        'system_vendor' => '',
        'core_vendor' => $aucorevendorlaunchdata,
        'web_launch' => $auweblaunch,
        'au_password' => $aupassword);

    $names = '"' . implode('","', array_keys($namevalues)) . '"';
    $values = '"' . implode('","', array_values($namevalues)). '"';

    return "$names\r\n$values";
}

function hacpclient_get_content_for_cst_file() {
    // Be sure to prevent double quotes in values.

    $ausystemid = HACP_AU_SYSTEMID;

    $cst =<<<CST
"block","member"
"root","$ausystemid"
CST;

    return preg_replace('/(?<!\r)\n/', "\r\n", $cst);
}

function hacpclient_get_content_for_des_file() {
    // TODO: What values do we want?
    // Be sure to prevent double quotes in values.

    $ausystemid = HACP_AU_SYSTEMID;

    $des =<<<DES
"system_id","developer_id","title","description"
"$ausystemid","D1","des title","des description"
DES;

    return preg_replace('/(?<!\r)\n/', "\r\n", $des);
}
