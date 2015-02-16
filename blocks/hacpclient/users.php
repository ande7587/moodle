<?php

require_once '../../config.php';
require_once('lib.php');

$blockinstanceid = required_param('hacpclientid', PARAM_INT);

$aiccurlbase64url = required_param('aiccurl', PARAM_ALPHANUMEXT);
$aiccurl = hacpclient_base64url_decode($aiccurlbase64url);

$filter = optional_param('filter', HACPCLIENT_USERS_ALL, PARAM_INT);

$block = hacpclient_get_block($blockinstanceid);
$coursecontext = $block->context->get_course_context();
$courseid = $coursecontext->instanceid;
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);

if ($oldaccesstime = optional_param('oldaccesstime', 0, PARAM_INT)) {
    hacpclient_old_access_time($oldaccesstime);
}

$url = new moodle_url('/blocks/hacpclient/users.php');
$PAGE->set_url($url);

# TODO: We should use a different capability.
require_capability('moodle/block:edit', $block->context);

$sessionsurl = new moodle_url('/blocks/hacpclient/sessions.php',
                              array('hacpclientid' => $blockinstanceid));

$PAGE->set_pagelayout('report');
$PAGE->navbar->add($block->title);
$PAGE->navbar->add(get_string('showsessions', 'block_hacpclient'), $sessionsurl);
$PAGE->navbar->add(get_string('showusers', 'block_hacpclient'));

$PAGE->set_title(get_string('sessions', 'block_hacpclient'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

$usersessions = $block->get_user_sessions_for_url($aiccurl, $filter);

$usertable = new html_table();
$usertable->head = array(get_string('userheader'        , 'block_hacpclient'),
                         get_string('lessonstatusheader', 'block_hacpclient'),
                         get_string('getparamtimeheader', 'block_hacpclient'),
                         get_string('putparamtimeheader', 'block_hacpclient'),
                         get_string('errorheader'       , 'block_hacpclient'),
                         get_string('aiccsidheader'     , 'block_hacpclient')
                        );

foreach ($usersessions as $session) {
    $row = array();

    // Assumes user attributes are available as part of session object.
    $row[] = fullname($session) . ' ('.$session->username.')';

    $row[] = hacpclient_session_manager::convert_status_to_display_string(
                                                   $session->lesson_status);
    $row[] = strftime('%F %R', $session->getparamtime);
    $row[] = $session->putparamtime ? strftime('%F %R', $session->putparamtime) : '';
    $row[] = $session->errorcode == HACPCLIENT_ERROR_NONE ? get_string('no')
                                                          : get_string('yes');

    $getparamtest = new moodle_url('/blocks/hacpclient/getparam.php',
                                   array('hacpclientid' => $blockinstanceid,
                                         'aiccurl'      => $aiccurlbase64url,
                                         'aiccsid'      => hacpclient_base64url_encode($session->aicc_sid)));

    $row[] = $OUTPUT->action_link($getparamtest,
                                  $session->aicc_sid,
                                  new popup_action('click', $getparamtest, 'aiccsidpopup'));

    $usertable->data[] = $row;
}

switch($filter) {
    case HACPCLIENT_USERS_ALL:
        $heading = get_string('usersessionsall', 'block_hacpclient', $aiccurl);
        break;
    case HACPCLIENT_USERS_ERRORS:
        $heading = get_string('usersessionserrors', 'block_hacpclient', $aiccurl);
        break;
    case HACPCLIENT_USERS_OLD:
        $heading = get_string('usersessionsold', 'block_hacpclient', $aiccurl);
        break;
    default:
}

echo html_writer::tag('h3', $heading);
echo html_writer::table($usertable);

echo $OUTPUT->footer();
