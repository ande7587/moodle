<?php

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('compareconfig_form.php');
require_once('lib.php');

admin_externalpage_setup('compareplugins');
require_capability('moodle/site:config', context_system::instance());

$uploadform = new configutil_uploadconfig_form();

if ($formdata = $uploadform->get_data()) {

    $content = $uploadform->get_file_content('configfile');

    $lines = explode("\n", $content);

    $otherplugconfig = array();

    foreach ($lines as $line) {
        $line = rtrim($line, "\r");
        $line_array = explode("\t", $line);
        if (count($line_array) == 3) {
            list($plugin, $setting, $value) = $line_array;

            if (! array_key_exists($plugin, $otherplugconfig)) {
                $otherplugconfig[$plugin] = array();
            }

            // Before adding the value to the array, we unescape the
            // backslashes, tabs, and new lines. This is done on the
            // assumption that the escaping in the file is the same
            // as one would get in mysql command line query output.
            $otherplugconfig[$plugin][$setting]
                        = str_replace(array('\\\\', '\\t', '\\n'),
                                            array("\\", "\t", "\n"),
                                            $value);
        }
    }

    $filename = $uploadform->get_new_filename('configfile');
    $filename = pathinfo($filename, PATHINFO_FILENAME);
    if (count($otherplugconfig) > 0) {
        $SESSION->configutil_otherplugconfig = $otherplugconfig;
        $SESSION->configutil_otherplugconfig_filename = $filename;
    }

    redirect($PAGE->url);
    die;
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('compareplugins', 'local_configutil'));


// Display the comparison table if we have something to compare against.
if (isset($SESSION->configutil_otherplugconfig)) {

    $otherplugconfig = $SESSION->configutil_otherplugconfig;

    $currentplugconfiglist = $DB->get_records('config_plugins', null, 'plugin,name');

    $currentplugconfig = array();
    foreach ($currentplugconfiglist as $cfg) {
        if (! array_key_exists($cfg->plugin, $currentplugconfig)) {
            $currentplugconfig[$cfg->plugin] = array();
        }
        $currentplugconfig[$cfg->plugin][$cfg->name] = $cfg->value;
    }

    $merged = array_merge_recursive($currentplugconfig, $otherplugconfig);

    ksort($merged);

    $rowarray = array();

    $noconfigsettingstring = get_string('noconfigsetting', 'local_configutil');

    $plugtable = new html_table();
    $plugtable->head = array(get_string('configsectionname' , 'local_configutil'),
                             get_string('configsettingname' , 'local_configutil'),
                             get_string('currentconfigvalue', 'local_configutil'),
                             $SESSION->configutil_otherplugconfig_filename);

    $plugtable->colclasses = array('sectionname', 'settingname', 'currentvalue', 'othervalue');
    $rowindex=0;

    foreach ($merged as $plugin => $settings) {

        $section = $plugin;

        ksort($settings);

        $currentsettings = array_key_exists($plugin, $currentplugconfig)
                           ? $currentplugconfig[$plugin]
                           : null;

        $othersettings = array_key_exists($plugin, $otherplugconfig)
                         ? $otherplugconfig[$plugin]
                         : null;

        foreach ($settings as $setting => $mergedvaluesgarbage) {
            $sectioncell = new html_table_cell('<span class="filtertarget">&gt;</span>&nbsp;'.$section);
            $namecell = new html_table_cell($setting);

            if ($currentsettings and array_key_exists($setting, $currentsettings)) {
                $currentcell = new html_table_cell($currentsettings[$setting]);
            } else {
                $currentcell = new html_table_cell($noconfigsettingstring);
            }

            if (array_key_exists($setting, $othersettings)) {
                $othercell = new html_table_cell($othersettings[$setting]);
            } else {
                $othercell = new html_table_cell($noconfigsettingstring);
            }

            // Some plugins have '/' instead of '_' between plugin type and plugin name.
            $sectionclass = str_replace('/', '_', $section);
            $sectionclasses[] = $sectionclass;

            if ($othercell->text !== $currentcell->text) {
                $plugtable->rowclasses[$rowindex] = "nosettingmatch $sectionclass";
            } else {
                $plugtable->rowclasses[$rowindex] = "settingmatch $sectionclass";
            }

            if (array_key_exists($plugin, $CFG->forced_plugin_settings)
                and array_key_exists($setting, $CFG->forced_plugin_settings[$plugin]))
            {
                $currentcell->text = $CFG->forced_plugin_settings[$plugin][$setting];
                $plugtable->rowclasses[$rowindex] .= ' forcedsetting';
            }

            $rowarray[] = new html_table_row(array($sectioncell, $namecell, $currentcell, $othercell));

            ++$rowindex;

        }
    }

    $plugtable->data = $rowarray;

    echo html_writer::tag('div', '', array('id'=>'configutil_compareconfig_controls'));

    echo html_writer::start_tag('div', array('class'=>'configtablewrapper'));
    echo html_writer::table($plugtable);
    echo html_writer::end_tag('div');
} else {
    $sectionclasses = array();
}

$PAGE->requires->yui_module('moodle-local_configutil-compareconfig',
                            'M.local_configutil.init_compareConfig',
                            array(array('sectionnames' => $sectionclasses)));

echo $OUTPUT->single_button(new moodle_url('/local/configutil/exportpluginconfig.php'),
                            get_string('exportplugconfig', 'local_configutil'),
                            'get');

echo html_writer::start_tag('div', array('class'=>'uploadconfigformwrapper'));
$uploadform->display();
echo html_writer::end_tag('div');

echo $OUTPUT->footer();

