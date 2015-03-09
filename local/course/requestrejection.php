<?php

require_once(dirname(__FILE__) . '/../../config.php');

$PAGE->set_url('/local/course/requestrejection.php');

require_login();
if (isguestuser()) {
    print_error('guestsarenotallowed', '');
}
$context = context_system::instance();
$PAGE->set_context($context);
require_capability('moodle/course:request', $context);

$strtitle = get_string('courserequest', 'local_course');
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);

$PAGE->navbar->add($strtitle);
echo $OUTPUT->header();

echo $OUTPUT->box_start('requestrejectioncontainer');

$messagediv = html_writer::tag('div',
                               get_string('courserequestrejection', 'local_course'),
                               array('class'=>'message'));
$buttons  = html_writer::tag('button',
                             get_string('requestdevornonacacoursesite', 'local_course'),
                             array('onclick'=>"window.location.href='"
                                                .new moodle_url('request.php?crtype=nonacad')
                                                ."'"));

$buttons .= html_writer::tag('button',
                             get_string('closewindow', 'local_course'),
                             array('onclick'=>"window.close()"));


echo $OUTPUT->box($messagediv.$buttons, 'requestrejection generalbox');

// Intentionally nesting one box inside the other to allow for centering the div as inline-block.

echo $OUTPUT->box_end();  // requestrejectioncontainer

echo $OUTPUT->footer();
