<?php
/**
 * Settings that allow configuration of the list of font-families.
 *
 * @package    atto_fontfamily
 * @copyright  2015 Joseph Inhofer <jinhofer@umn.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('editoratto', new admin_category('atto_fontfamily', new lang_string('pluginname', 'atto_fontfamily')));

$settings = new admin_settingpage('atto_fontfamily_settings', new lang_string('settings', 'atto_fontfamily'));
if ($ADMIN->fulltree) {
    // List of font-family to be supported
    $name = new lang_string('options', 'atto_fontfamily');
    $desc = new lang_string('options_desc', 'atto_fontfamily');
    $default = '';

    $setting = new admin_setting_configtextarea('atto_fontfamily/options',
                                              $name,
                                              $desc,
                                              $default,
                                              PARAM_TEXT,
                                              '50',
                                              '10');
    $settings->add($setting);
}
