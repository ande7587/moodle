<?php

require_once '../../config.php';
require_once('lib.php');

$blockinstanceid = required_param('hacpclientid', PARAM_INT);
$block = hacpclient_get_block($blockinstanceid);
$coursecontext = $block->context->get_course_context();
$courseid = $coursecontext->instanceid;
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);

if ($oldaccesstime = optional_param('oldaccesstime', 0, PARAM_INT)) {
    hacpclient_old_access_time($oldaccesstime);
}

$url = new moodle_url('/blocks/hacpclient/sessions.php',
                      array('hacpclientid' => $blockinstanceid));
$PAGE->set_url($url);

# TODO: We should use a different capability.
require_capability('moodle/block:edit', $block->context);

if ($post = data_submitted()) {
    debugging('post data: '.print_r($post, true), DEBUG_DEVELOPER);

    if (! empty($post->aiccurls)) {
        // Apply hacpclient_base64url_decode since we encoded on the form.
        $aiccurls = array_map('hacpclient_base64url_decode', array_keys($post->aiccurls));

        switch ($post->aiccurlaction) {
            case 'deleteoldsessions':
                $block->delete_old_sessions($aiccurls);
                break;
            case 'deleteerrors':
                $block->delete_errors($aiccurls);
                break;
            case 'retryerrors':
                $block->retry_errors($aiccurls);
                break;
        }
    } elseif (! empty($post->action) and $post->action == 'deletenonenrollees') {
        $block->delete_sessions_for_users_not_enrolled();
    }

    redirect($PAGE->url);
}

$PAGE->set_pagelayout('report');
$PAGE->navbar->add($block->title);
$PAGE->navbar->add(get_string('showsessions', 'block_hacpclient'));


$PAGE->set_title(get_string('sessions', 'block_hacpclient'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

$participation = $block->get_participation_summary();

$enrolledcount = $participation['enrolled'];
#$hacpcount     = $participation['hacpusers'];
$nohacpcount   = $participation['nohacp'];
$notenrolled   = $participation['notenrolled'];
$enrolledhacpcount = $enrolledcount - $nohacpcount;

$sumtable = new html_table();
$sumtable->head = array(get_string('sumuserstatusheader', 'block_hacpclient'),
                        get_string('sumusercountheader' , 'block_hacpclient'));

$sumtable->data = array(array(get_string('courseenrollees', 'block_hacpclient'),
                              $block->user_link(null, $enrolledcount)),
                        array(get_string('enrolleeswsession', 'block_hacpclient'),
                              $enrolledhacpcount),
                        array(get_string('enrolleeswosession', 'block_hacpclient'),
                              $nohacpcount),
                        array(get_string('nonenrolleeswsession', 'block_hacpclient'),
                              $notenrolled)
                        );

$aiccurls = $block->get_status_overview_by_url();

$urltable = new html_table();

$oldtime = strftime('%F %R', hacpclient_old_access_time()+1);

$urltable->head = array(get_string('aiccurlheader'        , 'block_hacpclient'),
                        get_string('sessioncountheader'   , 'block_hacpclient'),
                        get_string('completedheader'      , 'block_hacpclient'),
                        get_string('oldincompleteheader'  , 'block_hacpclient', $oldtime),
                        get_string('errorsheader'         , 'block_hacpclient'),
                        get_string('maxgetparamtimeheader', 'block_hacpclient'),
                        get_string('selectheader'         , 'block_hacpclient')
                       );

foreach ($aiccurls as $aiccurl) {
    unset($oldincompletelink, $errorslink);

    $base64url = hacpclient_base64url_encode($aiccurl->aicc_url);

    $sessionslink = $block->users_link($base64url,
                                       HACPCLIENT_USERS_ALL,
                                       $aiccurl->sessioncount);

    if ($aiccurl->oldincomplete > 0) {
        $oldincompletelink = $block->users_link($base64url,
                                                HACPCLIENT_USERS_OLD,
                                                $aiccurl->oldincomplete);
    }

    if ($aiccurl->errors > 0) {
        $errorslink = $block->users_link($base64url,
                                         HACPCLIENT_USERS_ERRORS,
                                         $aiccurl->errors);
    }

    $row = array();
    $row[] = $aiccurl->aicc_url;
    $row[] = $sessionslink;
    $row[] = $aiccurl->completed;
    $row[] = isset($oldincompletelink) ? $oldincompletelink : 0;
    $row[] = isset($errorslink) ? $errorslink : 0;
    $row[] = strftime('%F %R', $aiccurl->maxgetparamtime);
    $row[] = html_writer::checkbox("aiccurls[$base64url]", 1, 0);

    $urltable->data[] = $row;
}

echo html_writer::start_tag('div', array('class' => 'sessiontables'));
echo html_writer::start_tag('div');
echo html_writer::tag('h3', get_string('overallsessions', 'block_hacpclient'));
echo html_writer::table($sumtable);
echo html_writer::end_tag('div');

$urltodeletenonenrollees = new moodle_url('/blocks/hacpclient/sessions.php',
                                          array('hacpclientid' => $blockinstanceid,
                                                'action' => 'deletenonenrollees'));

if ($notenrolled > 0) {
    echo $OUTPUT->single_button($urltodeletenonenrollees,
                                get_string('deletenonenrollees', 'block_hacpclient'));
}

echo html_writer::empty_tag('hr');

echo html_writer::start_tag('form', array('method' => 'post')); // aiccurl action form

echo html_writer::start_tag('div');
echo html_writer::tag('h3', get_string('sessionsbyaiccurl', 'block_hacpclient'));
echo html_writer::table($urltable);
echo html_writer::tag('p', get_string('oldincompletemessage', 'block_hacpclient', $oldtime));
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', array('class' => 'urlcontrols'));

$actions = array('deleteoldsessions' => get_string('deleteoldsessions', 'block_hacpclient'),
                 'deleteerrors'      => get_string('deleteerrors'     , 'block_hacpclient'),
                 'retryerrors'       => get_string('retryerrors'      , 'block_hacpclient')
                );

echo html_writer::select($actions,
                         'aiccurlaction',
                         '',
                         array('' => get_string('selectactionlabel', 'block_hacpclient')));

echo html_writer::empty_tag('input',
                            array('type' => 'submit',
                                  'value' => get_string('executeurlaction', 'block_hacpclient')));

echo html_writer::end_tag('div'); // class urlcontrols

echo html_writer::end_tag('form'); // aiccurl action form

echo html_writer::empty_tag('hr');

echo $block->user_link(null, get_string('searchforuser', 'block_hacpclient'));

echo html_writer::end_tag('div'); // class sessiontables

# Actions by aicc url
#  Delete all old, incomplete.
#  Delete all with error code.
#  Retry errors.

# Maybe provide control to change old date for current Moodle session.

echo $OUTPUT->footer();

