<?php

function xmldb_block_theme_customizer_install() {
    global $DB;

    // create the "template" theme record
    $theme = new stdClass();
    $theme->shortname     = '__template__';
    $theme->fullname      = 'DEFAULT TEMPLATE';
    $theme->description   = 'Internal theme for managing template. Do not touch.';
    $theme->timemodified  = time();

    $theme_id = $DB->insert_record('block_theme', $theme, true);

    // insert the CSS files
    $css_files = array('admin', 'blocks', 'calendar', 'core', 'course', 'dock', 'editor',
                       'grade', 'message', 'pagelayout', 'question', 'user', 'banner');

    foreach ($css_files as $filename) {
        $css = new stdClass();
        $css->theme_id     = $theme_id;
        $css->name         = $filename . '.css';
        $css->path         = 'styles';
        $css->timemodified = time();

        $DB->insert_record('block_theme_css_file', $css);
    }
}