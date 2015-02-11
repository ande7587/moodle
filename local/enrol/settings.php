<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

$ADMIN->add('accounts', new admin_externalpage(
	'bulk_enrol',
	'Bulk user enrollment',
    $CFG->wwwroot.'/local/enrol/bulk_enrol.php',
    array('local/enrol:usebulk')
));

if ($hassiteconfig) {
    // add a setting page
    $settings = new admin_settingpage('local_enrol', get_string('pluginname', 'local_enrol'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_pickroles(
                    'local_enrol/allowed_bulkenrol_roles',
                    new lang_string('allowedbulkenrolroles', 'local_enrol'),
                    new lang_string('configallowedbulkenrolroles', 'local_enrol'),
                    array('student')));
}

