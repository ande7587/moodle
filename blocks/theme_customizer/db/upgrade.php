<?php

function xmldb_block_theme_customizer_upgrade($oldversion = 0) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2013111200) {

        // Define field parent to be added to block_theme
        $table = new xmldb_table('block_theme');
        $field = new xmldb_field('parent', XMLDB_TYPE_CHAR, '250', null, XMLDB_NOTNULL, null, null, 'description');

        // Conditionally launch add field parent
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // theme_customizer savepoint reached
        upgrade_plugin_savepoint(true, 2013111200, 'block', 'theme_customizer');
    }

    if ($oldversion < 2013121700) {

        // Define field "last_content_hash" to be added to block_theme
        $table = new xmldb_table('block_theme');
        $field = new xmldb_field('last_content_hash', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'last_compiled');

        // Conditionally launch add field last_content_hash
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field "notify" to be added to block_theme
        $field = new xmldb_field('notify', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'last_content_hash');

        // Conditionally launch add field notify
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // theme_customizer savepoint reached
        upgrade_plugin_savepoint(true, 2013121700, 'block', 'theme_customizer');
    }

    if ($oldversion < 2014021000) {

        // Define table block_theme_custom_setting to be created.
        $table = new xmldb_table('block_theme_custom_setting');

        // Adding fields to table block_theme_custom_setting.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('theme_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('setting_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('setting_value', XMLDB_TYPE_CHAR, '1000', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_theme_custom_setting.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table block_theme_custom_setting.
        $table->add_index('u-theme_id-setting_name', XMLDB_INDEX_UNIQUE, array('theme_id', 'setting_name'));

        // Conditionally launch create table for block_theme_custom_setting.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // alter table block_theme
        $table = new xmldb_table('block_theme');

        // Define field "category_level" to be added to block_theme
        $field = new xmldb_field('category_level', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'last_compiled');

        // Conditionally launch add field category_level.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // theme_customizer savepoint reached
        upgrade_plugin_savepoint(true, 2014021000, 'block', 'theme_customizer');
    }

    return true;
}