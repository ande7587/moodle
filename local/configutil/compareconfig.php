<?php

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('compareconfig_form.php');
require_once('lib.php');

admin_externalpage_setup('compareconfig');
require_capability('moodle/site:config', context_system::instance());

$uploadform = new configutil_uploadconfig_form();

if ($formdata = $uploadform->get_data()) {

    $content = $uploadform->get_file_content('configfile');

    $lines = explode("\n", $content);

    $other_core = array();

    foreach ($lines as $line) {
        $line = rtrim($line, "\r");
        $line_array = explode("\t", $line);
        if (count($line_array) == 2) {
            // Before adding the value to the array, we unescape the
            // backslashes, tabs, and new lines. This is done on the
            // assumption that the escaping in the file is the same
            // as one would get in mysql command line query output.
            $other_core[$line_array[0]] = str_replace(array('\\\\', '\\t', '\\n'),
                                                      array("\\", "\t", "\n"),
                                                      $line_array[1]);
        }
    }

    $filename = $uploadform->get_new_filename('configfile');
    $filename = pathinfo($filename, PATHINFO_FILENAME);
    if (count($other_core) > 0) {
        $SESSION->configutil_othercore = $other_core;
        $SESSION->configutil_othercore_filename = $filename;
    }

    redirect($PAGE->url);
    #echo $content;
    die;
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('compareconfig', 'local_configutil'));

# TODO: We don't need to get the settings if we are not going to compare.
$adminroot = admin_get_root();
$configsettings = array();
function get_settings($part_of_admin, &$settings) {
    if (property_exists($part_of_admin, 'settings') and isset($part_of_admin->settings)) {

        foreach ($part_of_admin->settings as $key => $setting) {
            // If plugin is set, then the setting is not from mdl_config.
            // A few settings appear in more than one section. This will
            // use the last section found.
            if (! $setting->plugin) {
                $settings[$key] = $part_of_admin->name;
            }
        }
    }
    if (isset($part_of_admin->children)) {
        foreach ($part_of_admin->children as $child) {
            get_settings($child, $settings);
        }
    }
}
get_settings($adminroot, $configsettings);
$sectionnames = array_values(array_unique($configsettings));

$PAGE->requires->yui_module('moodle-local_configutil-compareconfig',
                            'M.local_configutil.init_compareConfig',
                            array(array('sectionnames' => $sectionnames)));


// Display the comparison table if we have something to compare against.
if (isset($SESSION->configutil_othercore)) {

    $other_core = $SESSION->configutil_othercore;

    $current_core = $DB->get_records_menu('config', null, 'name', 'name,value');

    $allkeys = array_keys(array_merge($other_core, $current_core));
    sort($allkeys);

    $rowarray = array();

    $noconfigsettingstring = get_string('noconfigsetting', 'local_configutil');

    $coretable = new html_table();
    $coretable->head = array(get_string('configsectionname' , 'local_configutil'),
                             get_string('configsettingname' , 'local_configutil'),
                             get_string('currentconfigvalue', 'local_configutil'),
                             $SESSION->configutil_othercore_filename);

    $coretable->colclasses = array('sectionname', 'settingname', 'currentvalue', 'othervalue');
    $rowindex=0;

    foreach ($allkeys as $key) {

        if (array_key_exists($key, $configsettings)) {
            $section = $configsettings[$key];
            $sectionlink = html_writer::link(new moodle_url('/admin/settings.php?section='.$section), $section);
            $sectioncell = new html_table_cell('<span class="filtertarget">&gt;</span>&nbsp;'.$sectionlink);
        } else {
            $section = 'unknown';
            $sectioncell = new html_table_cell('<span class="filtertarget">&gt;</span>&nbsp;'.'unknown');
        }
        $namecell = new html_table_cell($key);

        if (array_key_exists($key, $current_core)) {
            $currentcell = new html_table_cell($current_core[$key]);
        } else {
            $currentcell = new html_table_cell($noconfigsettingstring);
        }

        if (array_key_exists($key, $other_core)) {
            $othercell = new html_table_cell($other_core[$key]);
        } else {
            $othercell = new html_table_cell($noconfigsettingstring);
        }

        if ($othercell->text !== $currentcell->text) {
            $coretable->rowclasses[$rowindex] = "nosettingmatch $section";
        } else {
            $coretable->rowclasses[$rowindex] = "settingmatch $section";
        }

        if (array_key_exists($key, $CFG->config_php_settings)) {
            $currentcell->text = $CFG->config_php_settings[$key];
            $coretable->rowclasses[$rowindex] .= ' forcedsetting';
        }

        $rowarray[] = new html_table_row(array($sectioncell, $namecell, $currentcell, $othercell));

        ++$rowindex;
    }

    $coretable->data = $rowarray;

    echo html_writer::tag('div', '', array('id'=>'configutil_compareconfig_controls'));

    echo html_writer::start_tag('div', array('class'=>'configtablewrapper'));
    echo html_writer::table($coretable);
    echo html_writer::end_tag('div');
}

echo $OUTPUT->single_button(new moodle_url('/local/configutil/exportconfig.php'),
                            get_string('exportconfig', 'local_configutil'),
                            'get');

echo html_writer::start_tag('div', array('class'=>'uploadconfigformwrapper'));
$uploadform->display();
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
