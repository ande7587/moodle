<?php

namespace local_group\task;

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once(__DIR__.'/../../peoplesoft_autogroup.php');

class update_autogroup extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('updateautogroup', 'local_group');
    }

    public function execute() {
        global $DB;

        //============== MAIN ROUTINE =============

        $start_stamp = microtime();

        // create an instance
        $autogrouper = new \peoplesoft_autogroup();


        mtrace('Start updating PeopleSoft-based groups ... ');

        // get the list of course IDs that have auto-update selected
        $auto_courses = $DB->get_records('enrol_umnauto_course', array('auto_group' => '1'));

        $course_ids = array();
        foreach ($auto_courses as $id => $record) {
            $course_ids[] = $record->courseid;
        }

        unset($auto_courses);     // release memory

        mtrace(count($course_ids), ' course(s) to be updated');

        $result = $autogrouper->run($course_ids);
        // redirect errors to STDERR
        if (count($result['errors']) > 0) {
            $stderr = fopen('php://stderr', 'w+');

            foreach ($result['errors'] as $course_id => $msgs) {
                fwrite($stderr, "\nCourse {$course_id}:" . implode("\n", $msgs));
            }

            fclose($stderr);
        }

        // print out summary stats
        mtrace('Run stats: ');
        mtrace(print_r($autogrouper->get_stats(), true));

    }
}

?>
