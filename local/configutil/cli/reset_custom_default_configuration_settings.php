<?php

/**
 * WARNING: THIS SCRIPT RESETS CONFIGURATION VALUES TO THEIR CUSTOM DEFAULT, IF ANY.
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
"WARNING: Resets configuration settings to custom default values.
Options:
--execute

Example:
$ php local/configutil/cli/reset_custom_default_configuration_settings.php --execute

";

    echo $help;
    die;
}

cron_setup_user();

if ($options['execute']) {
    $execute = true;

    $prompt = "WARNING: THIS WILL CHANGE CONFIGURATION SETTINGS! Do you want to proceed? (NO/yes)";
    $proceed = cli_input($prompt);
    if ($proceed !== 'yes') {
        echo "Default configuration reset canceled\n";
        die;
    }

    echo "Resettings configuration custom defaults...\n";

} else {
    $execute = false;
    echo "Not actually changing the configuration settings because --execute not set.\n";
}

// Much of the logic comes from admin_apply_default_settings, which resets
// much more than we want to reset.

$adminroot = admin_get_root(true, true);
$customdefaults = $adminroot->custom_defaults;


if (empty($customdefaults)) {
    echo "Nothing to do. No custom defaults.\n";
    exit;
}

function reset_custom_default_settings($node=NULL) {
    global $customdefaults, $adminroot, $execute;

    if (is_null($node)) {
        $node = $adminroot;
    }

    if ($node instanceof admin_category) {
        $entries = array_keys($node->children);
        foreach ($entries as $entry) {
            reset_custom_default_settings($node->children[$entry]);
        }

    } else if ($node instanceof admin_settingpage) {
        foreach ($node->settings as $setting) {
            $plugin = is_null($setting->plugin) ? 'moodle' : $setting->plugin;
            if (isset($customdefaults[$plugin])) {

                // We use array_key_exists, in part, because null is a valid value here.
                if (array_key_exists($setting->name, $customdefaults[$plugin])) {
                    $customdefaultvalue = $customdefaults[$plugin][$setting->name];

                    if ($execute) {
                        $setting->write_setting($customdefaultvalue);
                        echo "Updated [$plugin][$setting->name]\n";
                    } else {
                        echo "Would have updated [$plugin][$setting->name]\n";
                    }
                }
            }
        }
    }
}
reset_custom_default_settings();

