<?php

/**
 * WARNING: THIS SCRIPT RESETS CONFIGURATION VALUES TO THEIR DEFAULTS.
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->libdir.'/adminlib.php');

// now get cli options
list($options, $unrecognized) = cli_get_params(array('help'=>false, 'execute'=>false),
                                               array('h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"WARNING: Resets configuration defaults
Options:
--execute

Example:
$ php local/configutil/cli/reset_default_settings.php --execute

";

    echo $help;
    die;
}

cron_setup_user();

if (!$options['execute']) {
    echo "Not actually changing the configuration settings because --execute not set.\n";
}

$prompt = "WARNING: THIS WILL CHANGE CONFIGURATION SETTINGS! Do you want to proceed? (NO/yes)";
$proceed = cli_input($prompt);
if ($proceed !== 'yes') {
    echo "Default configuration reset canceled\n";
    die;
}



echo "Resettings configuration defaults...  DISABLED";

#######admin_apply_default_settings(NULL, true);

