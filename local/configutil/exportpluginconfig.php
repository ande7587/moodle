<?php

require_once '../../config.php';
require_once('lib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$filename_prefix = 'plugins';
$downloadfilename = build_config_export_filename($filename_prefix);

configutil_send_export_headers($downloadfilename);

$current_plugin_config = $DB->get_records('config_plugins',
                                          null,
                                          'plugin,name');

foreach ($current_plugin_config as $cfg) {
    $plugin  = $cfg->plugin;
    $setting = $cfg->name;
    $value   = str_replace(array("\\", "\t", "\n"),
                         array('\\\\', '\\t', '\\n'),
                         $cfg->value);

    echo "$plugin\t$setting\t$value\n";
}

exit;

