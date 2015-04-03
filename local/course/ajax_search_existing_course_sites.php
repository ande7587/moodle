<?php

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/moodlekiosk/locallib.php');
require_once($CFG->dirroot . '/local/user/lib.php');
require_once($CFG->dirroot . '/local/course/lib.php');

$query = required_param('q', PARAM_TEXT);

// Check permissions.
require_login();
if (isguestuser()) {
    print_error('guestsarenotallowed', '', $returnurl);
}
if (empty($CFG->enablecourserequests)) {
    print_error('courserequestdisabled', '', $returnurl);
}

$context = context_system::instance();
$PAGE->set_context($context);
require_capability('moodle/course:request', $context);

$service = new moodlekiosk_service();
$migration_server_long_names = implode(',', array_keys(local_course_get_migration_server_map()));
$result = $service->search_course(array('course_name' => $query,
                                        'long_names' => $migration_server_long_names));
echo json_encode($result->courses);
