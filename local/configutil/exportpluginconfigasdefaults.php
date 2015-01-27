<?php

/**
 * Query parameters:
 *   defaults
 *   ifnotdefault
 *   plugin
 */

require_once '../../config.php';
require_once($CFG->libdir.'/adminlib.php');
require_once('lib.php');

$showdefaultvalues = optional_param('defaults', false, PARAM_BOOL);
$showonlyifnotdefault = optional_param('ifnotdefault', false, PARAM_BOOL);
// PARAM_SAFEPATH allows the following: a-zA-Z0-9/_-
$showplugin = optional_param('plugin', null, PARAM_SAFEPATH);

require_login(SITEID);
require_capability('moodle/site:config', context_system::instance());

$filename_prefix = 'plugins_as_defaults';
$downloadfilename = build_config_export_filename($filename_prefix);


header("Content-Type: text/plain");
//configutil_send_export_headers($downloadfilename);

$adminroot = admin_get_root();
$plugins = array();
function get_setting_objects($part_of_admin, &$pluginsettings) {
    global $showplugin;

    if (property_exists($part_of_admin, 'settings') and isset($part_of_admin->settings)) {
        foreach ($part_of_admin->settings as $key => $setting) {
            $plugin = empty($setting->plugin) ? 'moodle' : $setting->plugin;

            if (empty($showplugin) or $showplugin===$plugin) {

                if (! array_key_exists($plugin, $pluginsettings)) {
                    $pluginsettings[$plugin] = array();
                }
                $pluginsettings[$plugin][$key] = $setting;
            }
        }
    }
    if (isset($part_of_admin->children)) {
        foreach ($part_of_admin->children as $child) {
            get_setting_objects($child, $pluginsettings);
        }
    }
}

get_setting_objects($adminroot, $plugins);

ksort($plugins);

function comp_func_stringnum($a, $b) {
    if ($a === $b) { return 0; }
    if (is_numeric($a) and is_numeric($b)) {
        return $a == $b ? 0 : ($a > $b ? 1 : -1);
    } 
    // 1 == true and 0 == false
    else if ( ( (is_numeric($a) and is_bool($b)) or (is_bool($a) or is_numeric($b)) ) 
         and ($a==$b) )
    {
        return 0;
    }
    // null == false
    else if ( ( (is_null($a) and ($b === false) ) or ($a === false) and is_null($b) ) )
    {
        return 0;
    }
    return $a > $b ? 1 : -1;
}


echo "// Possible query parameters: ?defaults=true&ifnotdefault=true&plugin=moodle\n\n";

foreach ($plugins as $plugin => $pluginsettings) {
    ksort($pluginsettings);

    foreach ($pluginsettings as $key => $setting) {
        $matchesdefault = false;

        $value = $setting->get_setting();
        $valuestring = var_export($value, true);
        $default = $setting->get_defaultsetting();
        $defaultstring = str_replace("\n", '', var_export($default, true));

        if ($value === $default
            or (comp_func_stringnum($value, $default) == 0))
        {
            $defaultstring = 'MATCHES';
            $matchesdefault = true;

        } else if (is_array($value) and is_array($default)
                   and count($value) === count($default))
        {
            $diff = array_udiff_uassoc($value,
                                       $default,
                                       'comp_func_stringnum',
                                       'comp_func_stringnum');

            if (empty($diff)) {
                $defaultstring = 'MATCHES ARRAY';
                $matchesdefault = true;
            }
        }


        $defaultdisplay = $showdefaultvalues ? " /* DEFAULT: $defaultstring */" : '';

        if (!($matchesdefault and $showonlyifnotdefault)) {
            echo "\$defaults['$plugin']['$key'] = $valuestring;$defaultdisplay\n";
        }
        //echo "/* ACTUAL CURRENT DEFAULT = $defaultstring */\n";
    }
}

exit;

