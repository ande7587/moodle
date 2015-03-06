<?php

namespace local_course\task;

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');

class process_migration_responses extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('processmigrationresponses', 'local_course');
    }

    public function execute() {

        $course_request_manager = get_course_request_manager();

        $course_request_manager->check_request_file_statuses();

        $course_request_manager->process_migration_responses();
    }
}
