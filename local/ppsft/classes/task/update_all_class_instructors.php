<?php

/**
 * Updates all class instructors.  Requires no input.
 * Can take a while to run.
 */
namespace local_ppsft\task;

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');

class update_all_class_instructors extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('updateallclassinstructors', 'local_ppsft');
    }

    public function execute() {
        $ppsft_updater = ppsft_get_updater();
        $ppsft_updater->update_all_class_instructors();
    }
}
