<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moodle's Clean theme, an example of how to make a Bootstrap theme
 *
 * DO NOT MODIFY THIS THEME!
 * COPY IT FIRST, THEN RENAME THE COPY AND MODIFY IT INSTEAD.
 *
 * For full information about creating Moodle themes, see:
 * http://docs.moodle.org/dev/Themes_2.0
 *
 * @package   theme_umn_clean
 * @copyright 2013 Moodle, moodle.org
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // logo title
    $name = 'theme_umn_clean/logotitle';
    $title = get_string('logotitle', 'theme_umn_clean');
    $description = get_string('logotitledesc', 'theme_umn_clean');
    $default = 'University of Minnesota';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $settings->add($setting);

    //Logo link
    $name = 'theme_umn_clean/logolink';
    $title = get_string('logolink', 'theme_umn_clean');
    $description = get_string('logolinkdesc', 'theme_umn_clean');
    $default = 'https://www.umn.edu';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $settings->add($setting);

    // Course dropdown limit
    $name = 'theme_umn_clean/coursemenulimit';
    $title = get_string('coursemenulimit', 'theme_umn_clean');
    $description = get_string('coursemenulimitdesc', 'theme_umn_clean');
    $setting = new admin_setting_configtext($name, $title, $description, 20, PARAM_INT);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // M dropdown
    $name = 'theme_umn_clean/mmenuitems';
    $title = get_string('mmenuitems', 'theme_umn_clean');
    $description = get_string('mmenuitemsdesc', 'theme_umn_clean');
    $setting = new admin_setting_configtextarea($name, $title, $description, '', PARAM_TEXT, '50', '10');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Help dropdown
    $name = 'theme_umn_clean/helpmenuitems';
    $title = get_string('helpmenuitems', 'theme_umn_clean');
    $description = get_string('helpmenuitemsdesc', 'theme_umn_clean');
    $setting = new admin_setting_configtextarea($name, $title, $description, '', PARAM_TEXT, '50', '10');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Footer settings
    $name = 'theme_umn_clean/footersettings';
    $title = get_string('footersettings', 'theme_umn_clean');
    $description = get_string('footersettingsdesc', 'theme_umn_clean');
    $setting = new admin_setting_configtextarea($name, $title, $description, '', PARAM_TEXT, '50', '10');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);
}
