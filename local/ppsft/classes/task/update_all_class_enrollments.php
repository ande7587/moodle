<?php


/**
 * Updates all class enrollments.  Requires no input.
 * Can take a while to run.
    public function execute() {
 */
namespace local_ppsft\task;

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');
require_once(__DIR__.'/../../ppsft_data_updater.class.php');
require_once(__DIR__.'/../../ppsft_data_adapter.class.php');

class update_all_class_enrollments extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('updateallclassenrollments', 'local_ppsft');
    }

    public function execute(){

        $ppsft_updater = ppsft_get_updater();
        $ppsft_updater->update_all_class_enrollments();

        mtrace('Updated all class enrollments.');

        $ppsft_updater->sync_vanished_enrollments();

        mtrace('Synchronized vanished enrollments.');
    }
}
