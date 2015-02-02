<?php

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_ppsft.php';
require_once 'ppsft_grade_export_form.php';

$id = required_param('id', PARAM_INT); // course id

$PAGE->set_url('/grade/export/ppsft/index.php', array('id'=>$id));

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/ppsft:view', $context);

print_grade_page_head($COURSE->id, 'export', 'ppsft', get_string('exportto', 'grades') . ' ' . get_string('pluginname', 'gradeexport_ppsft'));

$classes = grade_export_ppsft::get_graded_ppsft_classes_for_course($COURSE->id);
if (empty($classes)) {
    echo $OUTPUT->box(get_string('nogradableclasses', 'gradeexport_ppsft'));
    echo $OUTPUT->footer();
    exit;
}

export_verify_grades($COURSE->id);
$actionurl = new moodle_url('/grade/export/ppsft/export.php');

$mform = new ppsft_grade_export_form($actionurl, array( 'classes'=>$classes ));
echo '<div class="clearer"></div>';

echo $OUTPUT->container_start('ppsftgradeexportinstructions');
echo get_string('firstpageexportinstructions', 'gradeexport_ppsft');
echo $OUTPUT->container_end();

$mform->display();

echo $OUTPUT->footer();

