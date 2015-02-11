<?php

$string['pluginname'] = 'Custom enrol plugin';
$string['enrol:usebulk'] =  'use the bulk enrolment tool';

// bulk_enrol forms
$string['input_header'] = 'Bulk user enrollment';
$string['instruction'] = 'Enter a list of up to {$a->limit} Internet IDs (x500s) separated by commas, spaces, or newlines. Enter a specific course ID to add them to, then select a role.';
$string['x500_input'] = 'x500s';
$string['submit_bulk_enrol'] = 'Enroll users';
$string['course_id_input'] = 'Course ID';
$string['role_id_input'] = 'Select a role';
$string['result_summary'] = 'Result summary:';
$string['result_enrolled'] = 'The following users have been enrolled:';
$string['result_skipped'] = 'The following users have been skipped (limit exceeded):';
$string['result_error'] = 'The following errors have occurred:';
$string['result_status_success'] = 'enrolled successfully';

$string['allowedbulkenrolroles'] = 'Allowed bulk enrollment roles';
$string['configallowedbulkenrolroles'] = 'Select roles to be available for bulk enrollment.';

// form validation errors
$string['e_course_not_found'] = 'no course found for the specified course ID';
$string['e_course_no_manual_instance'] = 'manual enrollment is not enabled/allowed for this course';
$string['e_role_not_found'] = 'no role found for the specified role ID';
$string['e_role_not_allowed'] = 'the submitted role is not allowed';
$string['e_user_not_found'] = 'user record not found';
