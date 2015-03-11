<?php

// This file contains implementations of custom web service functions for course data.

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

class local_course_external extends external_api {

    public static function get_recent_timestamps_parameters() {
        return new external_function_parameters(array(
            'category_id' => new external_value(PARAM_INT, 'Category ID', VALUE_REQUIRED) 
        ));
    }

    public static function get_recent_timestamps_returns() {
        return new external_single_structure(array(
            'courses' => new external_multiple_structure(
                new external_single_structure(array(
                    'course_id'        => new external_value(PARAM_INT,  'Course ID'),
                    'maxgradeitemtime' => new external_value(PARAM_TEXT, 'maximum timestamp of grade items that have at least one grade assigned'),
                    'maxgradetime'     => new external_value(PARAM_TEXT, 'maximum timestamp of grades'),
                    'maxenroltime'     => new external_value(PARAM_TEXT, 'maximum timestamp of enrollments'),
                    'maxlastaccess'    => new external_value(PARAM_TEXT, 'maximum timestamp of last course access time'),
                    'maxgradecategorytime' => new external_value(PARAM_TEXT, 'maximum timestamp of grade category modification')
                )))
        ));
    }

    /**
     * Returns the most recent grade item, grade, and enrollment timestamps
     * for all course in the category.  This function does not include courses
     * in subcategories.
     */
    public static function get_recent_timestamps($categoryid) {
        global $DB;

        $params = self::validate_parameters(self::get_recent_timestamps_parameters(), array(
            'category_id' => $categoryid
        ));

        // The user must have access to view courses in the category.
        require_capability('moodle/course:view', context_coursecat::instance($categoryid));

        // prep the output container
        $out = array();

        $categoryparam = array('categoryid' => $params['category_id']);

        // Although we could logically combine the following SQL queries
        // into one, mysql is much slower when they are combined.

        $sql =<<<SQL
select c.id,
       coalesce(greatest(max(gc.timecreated), max(gc.timemodified)),
                max(gc.timecreated),
                max(gc.timemodified)) maxgradecategorytime
from {course} c
left join {grade_categories} gc on gc.courseid=c.id
where c.category=:categoryid
group by c.id;
SQL;

        $gradecattimes = $DB->get_records_sql($sql, $categoryparam);
        // Convert array of objects to array of arrays;
        $gradecattimes = array_map(function($o) {return (array) $o;}, $gradecattimes);

        $sql =<<<SQL
select c.id,
       coalesce(greatest(max(gi.timecreated), max(gi.timemodified)),
                max(gi.timecreated),
                max(gi.timemodified)) maxgradeitemtime,
       coalesce(greatest(max(gg.timecreated), max(gg.timemodified)),
                max(gg.timecreated),
                max(gg.timemodified)) maxgradetime
from {course} c
left join {grade_items} gi on gi.courseid=c.id
left join {grade_grades} gg on gg.itemid=gi.id
where c.category=:categoryid
group by c.id;
SQL;

        $gradetimes = $DB->get_records_sql($sql, $categoryparam);
        // Convert array of objects to array of arrays;
        $gradetimes = array_map(function($o) {return (array) $o;}, $gradetimes);

        $sql =<<<SQL
select c.id,
       greatest(max(ue.timecreated), max(ue.timemodified)) maxenroltime,
       max(la.timeaccess) maxlastaccess
from {course} c
left join {enrol} e on e.courseid=c.id
left join {user_enrolments} ue on ue.enrolid=e.id
left join {user_lastaccess} la on la.userid=ue.userid and la.courseid=c.id
where c.category=:categoryid group by c.id;
SQL;

        $enroltimes = $DB->get_records_sql($sql, $categoryparam);
        $enroltimes = array_map(function($o) {return (array) $o;}, $enroltimes);

        // Not actually intending to replace anything; this is similar to
        // array_merge_recursive but preserves numeric keys.
        $coursetimes = array_replace_recursive($gradecattimes, $gradetimes, $enroltimes);

        $out = array( 'courses' => array() );

        foreach ($coursetimes as $times) {
            $coursedata = array(
                'course_id'        => $times['id'],
                'maxgradeitemtime' => static::iso8601_date_from_unixtime($times['maxgradeitemtime']),
                'maxgradetime'     => static::iso8601_date_from_unixtime($times['maxgradetime']),
                'maxenroltime'     => static::iso8601_date_from_unixtime($times['maxenroltime']),
                'maxlastaccess'    => static::iso8601_date_from_unixtime($times['maxlastaccess']),
                'maxgradecategorytime' => static::iso8601_date_from_unixtime($times['maxgradecategorytime'])
            );
            $out['courses'][] = $coursedata;
        }
        return $out;
    }

    /**
     * Simple helper function to get date in iso format for web service response.
     */
    private static function iso8601_date_from_unixtime($unixtime) {
        if (! $unixtime) return '';

        # TODO: What kind of error handling do we need here?
        return date('c', $unixtime);
    }
}

