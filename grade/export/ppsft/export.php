<?php

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_ppsft.php';
require_once 'ppsft_grade_export_form.php';
$id = required_param('id', PARAM_INT); // course id

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/ppsft:view', $context);

// print all the exported data here
$classes = grade_export_ppsft::get_graded_ppsft_classes_for_course($COURSE->id);

//pass list of peoplesoft classes to be selected  by user
$mform = new ppsft_grade_export_form(null, array( 'classes'=>$classes ));

// The form does not include grade item selection because we always want just the overall
// course grade for this export. Artificially set that in the form data here.
$courseitem = grade_item::fetch_course_item($COURSE->id);

$data = $mform->get_data();
$data->itemids = array($courseitem->id => '1');

//pass list of peoplesoft classes to be selected  by user
$export = new grade_export_ppsft($course, $data);
$export->print_grades();

