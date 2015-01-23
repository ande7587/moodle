<?php

if ($ADMIN->fulltree) {
    // prefix
    $settings->add(new admin_setting_configtext(
        'block_theme_customizer/prefix',
        get_string('setting_prefix', 'block_theme_customizer'),
        '',
        'umnauto_'));

    // output dir
    $settings->add(new admin_setting_configtext(
        'block_theme_customizer/output_dir',
        get_string('setting_output_dir', 'block_theme_customizer'),
        '',
        $CFG->dataroot.'/theme'));

    // parent
    $settings->add(new admin_setting_configtext(
        'block_theme_customizer/parents',
        get_string('setting_parents', 'block_theme_customizer'),
        '',
        'umn_clean'));

    // environment theme
    $settings->add(new admin_setting_configtext(
        'block_theme_customizer/env_theme',
        get_string('setting_env_theme', 'block_theme_customizer'),
        '',
        ''));

    // recipients of notification email
    $settings->add(new admin_setting_configtext(
        'block_theme_customizer/notification_recipients',
        get_string('setting_notification_recipients', 'block_theme_customizer'),
        '',
        ''));
}
