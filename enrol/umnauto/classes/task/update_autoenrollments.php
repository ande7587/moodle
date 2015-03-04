<?php
/**
 * Updates autoenrollments.  Requires no input.
 * Can take a while to run.
 */
namespace enrol_umnauto\task;

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');

class update_autoenrollments extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('updateautoenrollments', 'enrol_umnauto');
    }

    public function execute() {
        enrol_umnauto_sync();
    }
}
