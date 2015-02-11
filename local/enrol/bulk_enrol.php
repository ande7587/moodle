<?php

require('../../config.php');
require_once($CFG->dirroot.'/local/enrol/localenrol_form.php');
require_once($CFG->dirroot.'/local/ldap/lib.php');

$site = get_site();
require_login();

$PAGE->set_context(null); // hack - set context to something, by default to system context
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Bulk user enrollment");
$PAGE->set_heading($SITE->fullname);


$return_url = new moodle_url('/local/enrol/bulk_enrol.php');
$PAGE->set_url($return_url);

$sitecontext = context_system::instance();

if (!has_capability('moodle/role:assign', $sitecontext)) {
    print_error('nopermissions', 'error', '', 'enrol user');
}

if (!has_capability('local/enrol:usebulk', $sitecontext)) {
    print_error('nopermissions', 'error', '', 'bulk enrol user');
}

$x500_form = new local_enrol_bulk_enrol_form();

if ($form_data = $x500_form->get_data()) {
    // get the bulk limit
    $bulk_limit = get_config('local/enrol', 'bulk_limit');    // how many usernames can be submitted at once

    if (!$bulk_limit)
        $bulk_limit = 1000;    // fall-back default value if no config found

    $result = array('enrolled' => array(),
                    'errors'   => array());


    //========= PROCESS SUBMITTED FORM =========

    // verify course
    $course = $DB->get_record('course', array('id' => $form_data->course_id));

    $enrol_instances = enrol_get_instances($form_data->course_id, true);

    $manual_instance = null;
    foreach ($enrol_instances as $instance) {
        if ($instance->enrol == 'manual') {
            $manual_instance = $instance;
            break;
        }
    }

    $manual_enroler = enrol_get_plugin('manual');

    // parse the x500s
    $x500s = preg_split("/[\s,;]+/",
                        trim(str_replace(array("\t","\r","\n",'"',"'"), ' ', $form_data->x500s)));

    // reduce to unique values
    $x500s = array_unique($x500s);

    // apply the limit
    $allowed_x500s = array_slice($x500s, 0, $bulk_limit, false);


    // map from x500 to Moodle username, suppress errors
    $usernames = array();
    foreach ($allowed_x500s as $x500) {
        try {
            $usernames[$x500] = umn_ldap_person_accessor::uid_to_moodle_username($x500);
        }
        catch(Exception $e) {
            $result['errors'][$x500] = 'Invalid x500';
        }
    }

    $query = 'SELECT user.id AS user__id,
                     user.username AS user__username
              FROM {user} AS user
              WHERE username IN (';

    $query .= implode(',', array_fill(0, count($usernames), '?')) . ')';
    $rs = $DB->get_recordset_sql($query, $usernames);

    $found_usernames = array();
    foreach ($rs as $row) {
        $found_usernames[$row->user__id] = $row->user__username;
    }
    $rs->close();    // free up resource on DBMS

    // error log those username not in DB
    $not_found_usernames = array_diff($usernames, $found_usernames);

    $x500_lookup = array_flip($usernames);
    foreach ($not_found_usernames as $username) {
        $result['errors'][$x500_lookup[$username]] = get_string('e_user_not_found', 'local_enrol');
    }

    // perform enrolment
    foreach ($found_usernames as $user_id => $username) {
        try {
            $manual_enroler->enrol_user($manual_instance, $user_id, $form_data->role_id);
            $result['enrolled'][$x500_lookup[$username]] = 'OK';
        }
        catch(Exception $e) {
            $result['errors'][$x500_lookup[$username]] = $e->getMessage();
        }
    }

    $SESSION->__POST_RESULT_local_enrol = array(
        'submitted_count' => count($x500s),
        'processed_count' => count($allowed_x500s),
        'course_id'       => $form_data->course_id,
        'course_name'     => $course->fullname,
        'role_id'         => $form_data->role_id,
        'result'          => $result,
        'skipped_x500s'   => array_slice($x500s, $bulk_limit)
    );

    // display result
    redirect($return_url);
}
else {
    //========= DISPLAY PAGE =========
    echo $OUTPUT->header();

    if (isset($SESSION->__POST_RESULT_local_enrol)) {
        // cache resulted available, display and clear

        $info = $SESSION->__POST_RESULT_local_enrol;

        echo $OUTPUT->box_start('result summary');
        echo '<h2>', get_string('input_header', 'local_enrol'), '</h2>';
        echo '<h4>', get_string('result_summary', 'local_enrol'), '</h4>';
        echo 'Course ID: ', $info['course_id'], '<br/>';
        echo 'Course name: ', $info['course_name'], '<br/>';
        echo 'Role ID: ', $info['role_id'], '<br/><br/>';
        echo 'Submitted: ', $info['submitted_count'], '<br/>';
        echo 'Processed: ', $info['processed_count'], '<br/>';
        echo 'Enrolled: ', count($info['result']['enrolled']), '<br/>';
        echo 'Error: ', count($info['result']['errors']), '<br/><br/>';
        echo $OUTPUT->box_end();

        // print the list of enrolled users
        if (count($info['result']['enrolled']) > 0) {
            echo $OUTPUT->box_start('result success');
            echo '<h3>', get_string('result_enrolled', 'local_enrol'), '</h3>';

            $ok_str = get_string('result_status_success', 'local_enrol');
            foreach (array_keys($info['result']['enrolled']) as $x500) {
                echo "{$x500}: {$ok_str}<br/>";
            }

            echo $OUTPUT->box_end();
            echo '<br/><br/>';
        }

        // print the list of skipped x500s
        if (count($info['skipped_x500s']) > 0) {
            echo $OUTPUT->box_start('result error');
            echo '<h3>', get_string('result_skipped', 'local_enrol'), '</h3>';
            echo implode(', ', $info['skipped_x500s']);
            echo $OUTPUT->box_end();
            echo '<br/><br/>';
        }



        // print the errors
        if (count($info['result']['errors']) > 0) {
            echo $OUTPUT->box_start('result error');
            echo '<h3>', get_string('result_error', 'local_enrol'), '</h3>';

            foreach ($info['result']['errors'] as $x500 => $msg) {
                echo "{$x500}: {$msg}<br/>";
            }

            echo $OUTPUT->box_end();
        }

        // clear cached result
        unset($SESSION->__POST_RESULT_local_enrol);
    }
    else {
        // no cached result, display the form
        $x500_form->display();
    }

    echo $OUTPUT->footer();

    die;
}