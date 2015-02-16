<?php

require_once '../../config.php';
require_once('lib.php');

$blockinstanceid = required_param('hacpclientid', PARAM_INT);

# TODO: Any particular reason to use get_selected_user as in admin/roles/check.php?
$userid = optional_param('userid', null, PARAM_INT);
$user = $userid ? $DB->get_record('user', array('id'=>$userid)) : null;

$block = hacpclient_get_block($blockinstanceid);
$coursecontext = $block->context->get_course_context();
$courseid = $coursecontext->instanceid;
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$retryfailed = optional_param('retryfailed', null, PARAM_INT);

require_login($course);

$url = new moodle_url('/blocks/hacpclient/user.php',
                      array('hacpclientid' => $block->instance->id,
                            'userid'       => $userid));

$PAGE->set_url($url);

# TODO: We should use a different capability.
require_capability('moodle/block:edit', $block->context);

if ($post = data_submitted()) {
    if (isset($post->retry)) {
        $hacpclient_session_id = required_param('retry', PARAM_INT);
        $aiccurl = required_param('aiccurl', PARAM_RAW);
        try {
            $block->retry_error($user->id, $aiccurl); 
        } catch (Exception $ex) {
            $params = $url->param('retryfailed', $hacpclient_session_id);
            redirect($url);
        }
    }

    redirect($PAGE->url);
}


$sessionsurl = new moodle_url('/blocks/hacpclient/sessions.php',
                              array('hacpclientid' => $blockinstanceid));

$PAGE->set_pagelayout('admin');
$PAGE->navbar->add($block->title);
$PAGE->navbar->add(get_string('showsessions', 'block_hacpclient'), $sessionsurl);
$PAGE->navbar->add(get_string('showusers', 'block_hacpclient'));

$PAGE->set_title(get_string('sessions', 'block_hacpclient'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// The use of user_selector on this page is modeled somewhat after
// that in admin/roles/check.php.
$userselector = new hacpclient_enrolled_user_selector('userid',
                                                      array('coursecontext'=>$coursecontext));

$userselector->set_multiselect(false);
$userselector->set_rows(10);

echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthnormal');

echo html_writer::start_tag('form',
                            array('method' => 'get',
                                  'action' => $CFG->wwwroot . '/blocks/hacpclient/user.php'));

$userselector->display();

echo html_writer::empty_tag('input',
                            array('type'  => 'hidden',
                                  'name'  => 'hacpclientid',
                                  'value' => $blockinstanceid));

echo html_writer::start_tag('p');

echo html_writer::empty_tag('input',
                            array('type'  => 'submit',
                                  'value' => get_string('showsessionsforuser',
                                                        'block_hacpclient')));

echo html_writer::end_tag('p');

echo html_writer::end_tag('form');
echo $OUTPUT->box_end();

// If no userid specified, close out the page now that the search box is sent.
if (!$userid) {
    echo $OUTPUT->footer();
    exit;
}

$usersessions = $block->get_user_sessions($userid);

$sessiontable = new html_table();
$sessiontable->head = array(get_string('aiccurlheader'     , 'block_hacpclient'),
                            get_string('lessonstatusheader', 'block_hacpclient'),
                            get_string('getparamtimeheader', 'block_hacpclient'),
                            get_string('putparamtimeheader', 'block_hacpclient'),
                            get_string('errorheader'       , 'block_hacpclient'),
                            get_string('aiccsidheader'     , 'block_hacpclient')
                           );

foreach ($usersessions as $session) {
    $row = array();

    if ($session->errorcode == HACPCLIENT_ERROR_NONE) {
        $errortext = get_string('no');
    } elseif ($retryfailed == $session->id) {
        $errortext = get_string('errorretryfailed', 'block_hacpclient');
    } else {
        $errortext = $OUTPUT->render($block->error_retry_button($session));
    }

    // Assumes user attributes are available as part of session object.
    $row[] = $session->aicc_url;

    $row[] = hacpclient_session_manager::convert_status_to_display_string(
                                                   $session->lesson_status);
    $row[] = strftime('%F %R', $session->getparamtime);
    $row[] = $session->putparamtime ? strftime('%F %R', $session->putparamtime) : '';

    $row[] = $errortext;

    $getparamtest = new moodle_url('/blocks/hacpclient/getparam.php',
                                   array('hacpclientid' => $blockinstanceid,
                                         'aiccurl' => hacpclient_base64url_encode($session->aicc_url),
                                         'aiccsid' => hacpclient_base64url_encode($session->aicc_sid)));

    $row[] = $OUTPUT->action_link($getparamtest,
                                  $session->aicc_sid,
                                  new popup_action('click', $getparamtest, 'aiccsidpopup'));

    $sessiontable->data[] = $row;
}

echo html_writer::tag('h3', fullname($user) . ' ('.$user->username.')');

echo html_writer::table($sessiontable);

echo $OUTPUT->footer();
