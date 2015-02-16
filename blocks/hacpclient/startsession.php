<?php

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('lib.php');

// Setting false to prevent autologinguest even if site is configured
// to autologinguest.
require_login(null, false);

// aicc_sid is type CMIIdentifier: "A string with no white space or
// unprintable characters in it. Maximum of 255 characters."
// According to Chapter 9 ("Data Types") of CMI001 v4 on aicc.org.
$aicc_sid = required_param('aicc_sid', PARAM_RAW);
if (strlen($aicc_sid) > 255 or ! preg_match('/^[[:graph:]]+$/', $aicc_sid)) {
    throw new Exception("Invalid aicc_sid: $aicc_sid");
}

// aicc_url is type CMIurl: "A fully qualified URL (Uniform resource locator)"
// According to Chapter 9 ("Data Types") of CMI001 v4 on aicc.org.
$aicc_url = required_param('aicc_url', PARAM_URL);

$blockinstanceid = required_param('hacpclientid', PARAM_INT);

$PAGE->set_url('/blocks/hacpclient/startsession.php',
               array('hacpclientid' => $blockinstanceid,
                     'aicc_url'     => $aicc_url,
                     'aicc_sid'     => $aicc_sid));

// Don't let guest users start a session.
if (isguestuser()) {
    require_logout();
    redirect($PAGE->url);
}

$PAGE->set_context(context_system::instance());

$blockcontext = context_block::instance($blockinstanceid);

$block = hacpclient_get_block($blockinstanceid);

$coursecontext = $blockcontext->get_course_context();

$session_manager = hacpclient_get_hacpclient_session_manager();

$getparamresponse = $session_manager->getparam($aicc_url, $aicc_sid, $block->config->aupassword);

// If the block instance has a cmipassword set, check it agains the value in
// the core_vendor cmipassword value in the getparam response.
if (!empty($block->config->cmipassword)) {
    $cmipassword = $block->config->cmipassword;
    if (!array_key_exists('core_vendor', $getparamresponse)
        or !array_key_exists('cmipassword', $getparamresponse['core_vendor'])
        or $cmipassword != $getparamresponse['core_vendor']['cmipassword'])
    {
        throw new Exception(get_string('novalidcmipassword', 'block_hacpclient'));
    }
}

// See function is_enrolled() in lib/accesslib.php.
// $context is an object; not just an id.
// Leaving $onlyactive defaulted to false for now.
if (! is_enrolled($blockcontext, $USER) ) {

    $roleid = $block->get_enrollment_roleid();

    enrol_try_internal_enrol($coursecontext->instanceid, $USER->id, $roleid);
}

$session_manager->create_or_update_session($blockinstanceid,
                                           $USER->id,
                                           $aicc_url,
                                           $aicc_sid,
                                           $getparamresponse);

// We do this in case the session has expired on the CMI or the user has returned
// before the necessary cron actions have occurred.
$block->send_completes_on_completion($USER->id);

redirect(new moodle_url('/course/view.php',
                        array('id' => $coursecontext->instanceid)));

