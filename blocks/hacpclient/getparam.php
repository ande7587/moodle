<?php

require_once '../../config.php';
require_once('lib.php');

$blockinstanceid = required_param('hacpclientid', PARAM_INT);

$aiccurlbase64url = required_param('aiccurl', PARAM_ALPHANUMEXT);
$aiccurl = hacpclient_base64url_decode($aiccurlbase64url);

$aiccsidbase64url = required_param('aiccsid', PARAM_ALPHANUMEXT);
$aiccsid = hacpclient_base64url_decode($aiccsidbase64url);

$block = hacpclient_get_block($blockinstanceid);
$coursecontext = $block->context->get_course_context();
$courseid = $coursecontext->instanceid;
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);

$url = new moodle_url('/blocks/hacpclient/getparam.php',
                      array('hacpclientid' => $blockinstanceid,
                            'aiccurl'      => $aiccurlbase64url,
                            'aiccsid'      => $aiccsidbase64url));

$PAGE->set_url($url);

# TODO: We should use a different capability.
require_capability('moodle/block:edit', $block->context);

$PAGE->set_pagelayout('popup');

$PAGE->set_title(get_string('getparamtesttitle', 'block_hacpclient'));
$PAGE->set_heading(get_string('getparamtestheading', 'block_hacpclient'));

echo $OUTPUT->header();
echo "<p>(aicc_url = $aiccurl, aicc_sid = $aiccsid)</p>";

if (! $DB->record_exists('block_hacpclient_sessions',
                         array('hacpclientid' => $blockinstanceid,
                               'aicc_url'     => $aiccurl,
                               'aicc_sid'     => $aiccsid)))
{
    echo 'Not a valid session';
    exit;
}

$session_manager = hacpclient_get_hacpclient_session_manager();

$getparamresponse = $session_manager->getparam($aiccurl, $aiccsid, $block->config->aupassword);

// Printing out the response in a way that should at least be close to as it was received.

echo '<pre>';

foreach ($getparamresponse as $group => $inivals) {
echo "\n[$group]\n";
echo $inivals[HACP_INI_FREEFORM_KEY];
}

echo '</pre>';

echo $OUTPUT->footer();

