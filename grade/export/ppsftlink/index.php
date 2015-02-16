<?php

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once $CFG->dirroot.'/local/ppsft/lib.php';  // Plug-in dependency!
require_once 'grade_export_ppsftlink.php';

$id = required_param('id', PARAM_INT); // course id

$errorcode = optional_param('errorCode', null, PARAM_ALPHANUMEXT);

// If ppsftclassid is set to a non-zero value, we render the page
// to submit to PeopleSoft.  Otherwise, we display the links.
$ppsftclassid = optional_param('ppsftclassid', 0, PARAM_INT);

$PAGE->set_url('/grade/export/ppsftlink/index.php', array('id'=>$id));

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/ppsftlink:view', $context);

print_grade_page_head($COURSE->id, 'export', 'ppsftlink', get_string('pluginname', 'gradeexport_ppsftlink'));


// The form does not include grade item selection because we always want just the overall
// course grade for this export. Artificially set that in the form data here.
$courseitem = grade_item::fetch_course_item($COURSE->id);
$data = new stdClass;
$data->ppsftclassid = $ppsftclassid;
$data->itemids = array($courseitem->id => '1');
$data->display = array('letter' => '3');
$export = new grade_export_ppsftlink($COURSE, $data);
$ppsftclasslinks = $export->get_graded_ppsft_class_grading_links();

if (empty($ppsftclasslinks)) {
    echo $OUTPUT->box(get_string('nogradableclasses', 'gradeexport_ppsftlink'));
    echo $OUTPUT->footer();
    exit;
}

export_verify_grades($COURSE->id);

echo '<div class="clearer"></div>';

if (empty($ppsftclassid)) {
    // Display class links.

    echo $OUTPUT->container_start('ppsftlinkgradeexportinstructions');
    echo get_string('firstpageexportinstructions', 'gradeexport_ppsftlink');
    echo $OUTPUT->container_end();

    echo $OUTPUT->container_start('ppsftlinkgradeexportlinks');
    foreach ($ppsftclasslinks as $ppsftclasslink) {
        echo $OUTPUT->container_start('ppsftlinkgradeexportlink');
        echo $ppsftclasslink;
        echo $OUTPUT->container_end();
    }
    echo $OUTPUT->container_end();
} else if (empty($errorcode)) {
    // Display preview for exporting grades to PeopleSoft.
    $export->display_preview();
} else {
    // PeopleSoft replied with an error code, so display the appropriate error message.
    $ppsftclass = $DB->get_record('ppsft_classes', array('id' => $ppsftclassid));
    echo '<h5>'.$ppsftclass->subject.' '.$ppsftclass->catalog_nbr.' '.$ppsftclass->section.' ('
               .ppsft_institution_name($ppsftclass->institution).', '
               .ppsft_term_string_from_number($ppsftclass->term).')</h5>';
    echo $OUTPUT->container_start('ppsftlinkerrormessage');
    switch ($errorcode) {
        case 'notAuthorized':
            echo get_string('notauthorized', 'gradeexport_ppsftlink');
            break;
        case 'noGradeRoster':
            echo get_string('nograderoster', 'gradeexport_ppsftlink');
            break;
        case 'rosterClosed':
            echo get_string('rosterclosed', 'gradeexport_ppsftlink');
            break;
        default:
            echo get_string('unknownerrorcode', 'gradeexport_ppsftlink');
    }
    echo $OUTPUT->container_end();
}

echo $OUTPUT->footer();

