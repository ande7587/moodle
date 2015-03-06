<?php

namespace local_course\task;

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');
require_once($CFG->libdir.'/clilib.php');

class process_migration_requests extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('processmigrationrequests', 'local_course');
    }

    public function execute() {
        $migration_responder = get_migration_responder();

        mtrace('Processing new requests...');
        $migration_responder->process_migration_requests();

        mtrace('Deleting unmatched responses...');
        $migration_responder->delete_unmatched_responses();
    }
}
