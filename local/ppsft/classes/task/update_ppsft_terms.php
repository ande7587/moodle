<?php

namespace local_ppsft\task;

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');
require_once(__DIR__.'/../../ppsft_data_updater.class.php');
require_once(__DIR__.'/../../ppsft_data_adapter.class.php');

class update_ppsft_terms extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('updateppsftterms', 'local_ppsft');
    }

    public function execute() {
        $ppsft_updater = ppsft_get_updater();
        $ppsft_updater->update_terms();
    }
}
