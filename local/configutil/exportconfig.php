<?php

require_once '../../config.php';
require_once('lib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$filename_prefix = 'cfg';
$downloadfilename = build_config_export_filename($filename_prefix);

configutil_send_export_headers($downloadfilename);

$current_core_config = $DB->get_records_menu('config', null, 'name', 'name,value');
foreach ($current_core_config as $setting => $value) {
    $value = str_replace(array("\\", "\t", "\n"),
                         array('\\\\', '\\t', '\\n'),
                         $value);

    echo "$setting\t$value\n";
}

exit;

