<?php

function xmldb_block_theme_customizer_uninstall() {
    global $DB;

    // remove all the graphic files
    $site_context = context_system::instance();
    $fs = get_file_storage();

    $fs->delete_area_files($site_context->id, 'block_theme_customizer', 'graphic');

    return true;
}