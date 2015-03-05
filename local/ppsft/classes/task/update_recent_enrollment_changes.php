<?php

namespace local_ppsft\task;

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');

class update_recent_enrollment_changes extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('updaterecentenrollmentchanges', 'local_ppsft');
    }

    public function execute() {

        $ppsft_updater = ppsft_get_updater();

        $ppsft_updater->update_recent_ppsft_enrollment_changes();

        #$emplids_to_update = $ppsft_updater->get_recent_ppsft_enrollment_change_emplids();

        #foreach ($emplids_to_update as $emplid) {
        #    $ppsft_updater->update_student_enrollment($emplid);
        #    echo "Updated ppsft enrollment for $emplid\n";
        #}
    }
}
