<?php

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

require_once("$CFG->dirroot/enrol/umnauto/lib.php");

class enrol_umnauto_external extends external_api {

    /**
     * Returns the PeopleSoft classes for a given Moodle course.
     */
    public static function get_umn_classes_for_course($courseid) {

        $params = self::validate_parameters(self::get_umn_classes_for_course_parameters(), array(
            'course_id' => $courseid
        ));

        // Not requiring any specific capabiities for this call.

        // prep the output container
        $out = array();

        $classes = enrol_umnauto_get_course_ppsft_classes($params['course_id']);

        $out = array( 'classes' => array() );

        foreach ($classes as $class) {
            $classdata = array(
                'term'        => $class->term,
                'institution' => $class->institution,
                'class_nbr'   => $class->class_nbr,
                'subject'     => $class->subject,
                'catalog_nbr' => $class->catalog_nbr,
                'section'     => $class->section,
                'descr'       => $class->descr
            );
            $out['classes'][] = $classdata;
        }
        return $out;
    }

    public static function get_umn_classes_for_course_parameters() {

        return new external_function_parameters(array(
            'course_id' => new external_value(PARAM_INT, 'Moodle Course ID', VALUE_REQUIRED)
        ));
    }

    public static function get_umn_classes_for_course_returns() {

        return new external_single_structure(array(
            'classes' => new external_multiple_structure(
                new external_single_structure(array(
                    'term'        => new external_value(PARAM_TEXT, 'Term (same as STRM in PeopleSoft)'),
                    'institution' => new external_value(PARAM_TEXT, 'Institution'),
                    'class_nbr'   => new external_value(PARAM_INT,  'Class number'),
                    'subject'     => new external_value(PARAM_TEXT, 'Subject'),
                    'catalog_nbr' => new external_value(PARAM_TEXT, 'Catalog number'),
                    'section'     => new external_value(PARAM_TEXT, 'Class section'),
                    'descr'       => new external_value(PARAM_TEXT, 'Short description')
                )))
        ));
    }
}

