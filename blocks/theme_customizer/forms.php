<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * manage ownership of the theme
 */
class block_theme_customizer_ownership_form extends moodleform {
    function definition () {
        $mform = & $this->_form;
        $theme = $this->_customdata;

        $mform->addElement('hidden', 'theme_id', $theme['id']);
        $mform->setType('theme_id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'update_editlevel');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'manage_ownership', get_string('manage_ownership_header', 'block_theme_customizer'));

        foreach ($theme['owners'] as $owner_id => $owner) {
            $mform->addElement('html', '<div class="owner_entry"><span class="owner_name">'.$owner['firstname'].' '.$owner['lastname'].'</span>');
//            $mform->addElement('advcheckbox', "edit_level_{$owner_id}_edit", '', 'can edit', null, array(0,1));
            $mform->addElement('html', '<a href="edit_theme.php?action=remove_owner&theme_id=' . $theme['id'] .
                                               '&user_id=' . $owner_id . '">remove</a>');
            $mform->addElement('html', '</div>');
            $mform->setDefault("edit_level_{$owner_id}_edit", ($owner['edit_level'] & 2) ? 1 : 0);
        }

        $mform->addElement('html', '<a id="block_theme_customizer_add_owner_lnk" ' .
                           'href="add_owner.php?theme_id=' . $theme['id'] .
                           '">'.get_string('add_owner_link', 'block_theme_customizer').'</a>');
//        $this->add_action_buttons(false, get_string('submit_ownership_changes', 'block_theme_customizer'));
    }
}


/**
 * manage the theme details
 */
class block_theme_customizer_theme_detail_form extends moodleform {
    function definition () {
        $mform = & $this->_form;
        $theme = $this->_customdata;

        $mform->addElement('hidden', 'theme_id', $theme['id']);
        $mform->setType('theme_id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'update_theme_detail');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'manage_detail', get_string('manage_detail_header', 'block_theme_customizer'));

        $mform->addElement('text', 'theme_shortname', get_string('theme_shortname', 'block_theme_customizer'));
        $mform->setType('theme_shortname', PARAM_TEXT);
        $mform->setDefault('theme_shortname', $theme['shortname']);

        $mform->addElement('text', 'theme_fullname', get_string('theme_fullname', 'block_theme_customizer'));
        $mform->setType('theme_fullname', PARAM_TEXT);
        $mform->setDefault('theme_fullname', $theme['fullname']);

        $level_options = array(
            theme_customizer::level_none       => get_string('category_level_none', 'block_theme_customizer'),
            theme_customizer::level_campus     => get_string('category_level_campus', 'block_theme_customizer'),
            theme_customizer::level_college    => get_string('category_level_college', 'block_theme_customizer'),
            theme_customizer::level_department => get_string('category_level_department', 'block_theme_customizer'),
            theme_customizer::level_course     => get_string('category_level_course', 'block_theme_customizer')
        );
        $mform->addElement('select', 'theme_category_level', get_string('theme_category_level', 'block_theme_customizer'), $level_options);
        $mform->setDefault('theme_category_level', $theme['category_level']);

        $mform->addElement('text', 'theme_parent', get_string('theme_parent', 'block_theme_customizer'));
        $mform->setType('theme_parent', PARAM_TEXT);
        $mform->setDefault('theme_parent', $theme['parent']);

        $mform->addElement('textarea', 'theme_description', get_string('theme_description', 'block_theme_customizer'), 'class="theme_description"');
        $mform->setDefault('theme_description', $theme['description']);

        // ===== custom settings
        $mform->addElement('header', 'manage_custom_setting_values', get_string('manage_custom_setting_values', 'block_theme_customizer'));
        $mform->setExpanded('manage_custom_setting_values', 1);

        // the elements to be repeated
        $custom_key = $mform->createElement('text', 'custom_setting_name', get_string('custom_setting_name', 'block_theme_customizer'), 'maxlength=200');
        $mform->setType('custom_setting_name', PARAM_TEXT);

        $custom_setting_value = $mform->createElement('text', 'custom_setting_value', get_string('custom_setting_value', 'block_theme_customizer'), 'maxlength=980');
        $mform->setType('custom_setting_value', PARAM_TEXT);

        $repeated = array($custom_key, $custom_setting_value, $mform->createElement('html', '<div class="custom-value-separator"></div>'));
        $initial_count = count($theme['custom_settings']) + 2;
        $initial_count = $initial_count < 5 ? 5 : $initial_count;

        $this->repeat_elements($repeated, $initial_count, array(),'custom_repeat', 'custom_add', 3,
                               get_string('add_custom_setting_value', 'block_theme_customizer'), true);

        $i = 0;
        foreach ($theme['custom_settings'] as $name => $setting) {
            $mform->setDefault("custom_setting_name[{$i}]", $name);
            $mform->setDefault("custom_setting_value[{$i}]", $setting['setting_value']);
            $i++;
        }
        // end custom settings ========

        $this->add_action_buttons(false, get_string('submit_detail_changes', 'block_theme_customizer'));
    }
}



/**
 * manage the theme's custom values
 */
class block_theme_customizer_theme_custom_values_form extends moodleform {
    function definition () {
        $mform = & $this->_form;
        $theme = $this->_customdata;

        $mform->addElement('hidden', 'theme_id', $theme['id']);
        $mform->setType('theme_id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'update_theme_custom_values');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'manage_custom_values', get_string('manage_custom_values', 'block_theme_customizer'));
        $mform->setExpanded('manage_custom_values', 1);

        // the elements to be repeated
        $custom_key = $mform->createElement('text', 'custom_key', get_string('custom_key', 'block_theme_customizer'), 'maxlength=200');
        $mform->setType('custom_key', PARAM_TEXT);

        $custom_value = $mform->createElement('text', 'custom_value', get_string('custom_value', 'block_theme_customizer'), 'maxlength=980');
        $mform->setType('custom_value', PARAM_TEXT);

        $repeated = array($custom_key, $custom_value, $mform->createElement('html', '<div class="custom-value-separator"></div>'));

        $this->repeat_elements($repeated, 5, array(),'custom_repeat', 'custom_add', 3,
                               get_string('add_custom_value', 'block_theme_customizer'), true);
    }
}

/**
 * manage the theme's graphic files
 */
class block_theme_customizer_graphic_files_form extends moodleform {
    function definition () {
        $mform = & $this->_form;
        $theme = $this->_customdata;

        $mform->addElement('hidden', 'theme_id', $theme['id']);
        $mform->setType('theme_id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'delete_graphic');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'manage_graphic_files', get_string('manage_graphic_files', 'block_theme_customizer'));

        $mform->addElement('html', '<div id="graphic_file_usage">'.get_string('graphic_file_usage', 'block_theme_customizer').'</div>');

        foreach ($theme['graphic_files'] as $file_id => $file) {
            $mform->addElement('html', '<div class="graphic_entry">'.$file['name'].'</div>');
        }

        $mform->addElement('html', '<a id="block_theme_customizer_update_graphic_lnk" ' .
                           'href="update_graphic.php?theme_id=' . $theme['id'] .
                           '">'.get_string('update_graphic_link', 'block_theme_customizer').'</a>');
    }
}


/**
 * display the CSS entries from template
 */
class block_theme_customizer_CSS_predefined_entry_form extends moodleform {
    function definition () {
        $mform = & $this->_form;
        $template  = $this->_customdata['template'];
        $theme     = $this->_customdata['theme'];

        $mform->addElement('hidden', 'theme_id', $theme['id']);
        $mform->setType('theme_id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'update_predefined_entry');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'manage_predefined_entry', get_string('manage_predefined_entry', 'block_theme_customizer'));

        // scan to see if we have any template entry to display
        $has_template = false;
        foreach ($template['css_files'] as $filename => $file) {
            foreach ($file['entries'] as $entry_id => $entry) {
                $has_template = true;
                break 2;
            }
        }

        if ($has_template) {
            // enumerate all template entries, filled with value specified from the theme (if there is)
            $identifier_2_id_maps = array();    // to look up template id from CSS identifier

            $table_head = '<table id="predefined_css_entries" class="generaltable"><thead><tr class="list_header"><td>' .
                          get_string('entry_css_identifier', 'block_theme_customizer') . '</td><td>' .
                          get_string('entry_css_value', 'block_theme_customizer') . '</td><td>' .
                          get_string('entry_description', 'block_theme_customizer') . '</td><td>' .
                          get_string('css_file', 'block_theme_customizer') . '</td></tr></thead><tbody>';

            $mform->addElement('html', $table_head);
            $has_data = false;        // detect whether there is data to display

            foreach ($template['css_files'] as $filename => $file) {
                foreach ($file['entries'] as $entry_id => $entry) {
                    $mform->addElement('html', '<tr><td>'.$entry['css_identifier'].'</td><td>');
                    $mform->addElement('textarea', 'te_'.$entry_id, '');
                    $mform->addElement('html',  '</td><td>'.$entry['description'].'</td><td>'.$file['name'].'</td></tr>');

                    $identifier_2_id_maps[$entry['css_identifier']] = $entry_id;
                }
            }

            $mform->addElement('html', '</tbody></table>');

            // look up and set values from theme
            foreach ($theme['css_files'] as $filename => $file) {
                foreach ($file['entries'] as $entry) {
                    if (isset($identifier_2_id_maps[$entry['css_identifier']])) {
                        $mform->setDefault('te_' . $identifier_2_id_maps[$entry['css_identifier']], $entry['css_value']);
                    }
                }
            }

            $this->add_action_buttons(false, get_string('submit_update_predefined_entry', 'block_theme_customizer'));
        }
        else {
            // no template entries
            $mform->addElement('html', get_string('no_template_entry', 'block_theme_customizer'));
        }
    }
}



/**
 * a way to build the theme
 */
class block_theme_customizer_build_theme_form extends moodleform {
    function definition () {
        $mform = & $this->_form;
        $theme = $this->_customdata;

        $mform->addElement('hidden', 'theme_id', $theme['id']);
        $mform->setType('theme_id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'build_theme');
        $mform->setType('action', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('submit_build_theme', 'block_theme_customizer'));
    }
}


/**
 * add graphic files to a theme
 */
class block_theme_customizer_update_graphic_files_form extends moodleform {
    function definition () {
        $mform = & $this->_form;
        $theme = $this->_customdata;

        $mform->addElement('hidden', 'theme_id', $theme['id']);
        $mform->setType('theme_id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'update_graphic');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'update_graphic_files', get_string('update_graphic_files', 'block_theme_customizer'));

        // load and list the existing files
        $mform->addElement('filemanager', 'attachments_filemanager', '', null,
                           array('subdirs'         => 0,
                                 'maxbytes'        => 0,
                                 'maxfiles'        => 50,
                                 'accepted_types'  => array('.jpg', '.jpeg', '.gif', '.png', '.svg') ));

        $this->add_action_buttons(true, get_string('submit_update_graphic', 'block_theme_customizer'));
    }
}


/**
 * add owner to a theme
 */
class block_theme_customizer_add_owner_form extends moodleform {
    function definition () {
        $mform = & $this->_form;
        $theme = $this->_customdata;

        $mform->addElement('hidden', 'theme_id', $theme['id']);
        $mform->setType('theme_id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'add_owner');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'add_owner', get_string('add_owner', 'block_theme_customizer'));

        $mform->addElement('textarea', 'owner_usernames', get_string('owner_username_input', 'block_theme_customizer'));
        $mform->setType('owner_usernames', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('submit_add_owner', 'block_theme_customizer'));
    }
}

/**
 * add a theme
 */
class block_theme_customizer_add_theme_form extends moodleform {
    function definition () {
        $mform = & $this->_form;
        $theme = $this->_customdata;

        $mform->addElement('hidden', 'action', 'add_theme');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'add_theme', get_string('add_theme', 'block_theme_customizer'));

        $mform->addElement('text', 'theme_shortname', get_string('theme_shortname', 'block_theme_customizer'));
        $mform->setType('theme_shortname', PARAM_TEXT);

        $mform->addElement('text', 'theme_fullname', get_string('theme_fullname', 'block_theme_customizer'));
        $mform->setType('theme_fullname', PARAM_TEXT);

        $mform->addElement('textarea', 'theme_description', get_string('theme_description', 'block_theme_customizer'));

        $this->add_action_buttons(true, get_string('submit_add_theme', 'block_theme_customizer'));
    }
}



/**
 * add or edit a template entry
 */
class block_theme_customizer_edit_template_entry_form extends moodleform {
    function definition () {
        $mform     = & $this->_form;
        $entry     = $this->_customdata['entry'];
        $css_files = $this->_customdata['css_files'];

        $mform->addElement('hidden', 'entry_id', $entry['id']);
        $mform->setType('entry_id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'update_template_entry');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('text', 'entry_css_identifier', get_string('entry_css_identifier', 'block_theme_customizer'));
        $mform->addElement('text', 'entry_description', get_string('entry_description', 'block_theme_customizer'));
        $mform->addElement('select', 'entry_file_id', get_string('entry_file_id', 'block_theme_customizer'), $css_files);

        // fill the form if editing an existing entry
        if ($entry['id'] > 0) {
            $mform->setDefault('entry_css_identifier', $entry['css_identifier']);
            $mform->setDefault('entry_description', $entry['description']);
            $mform->setDefault('entry_file_id', $entry['css_file_id']);
        }


        $this->add_action_buttons(true, get_string('submit_update_template_entry', 'block_theme_customizer'));
    }
}



/**
 * edit existing custom CSS entries of a theme
 */
class block_theme_customizer_edit_custom_entry_form extends moodleform {
    function definition () {
        $mform     = & $this->_form;
        $theme     = $this->_customdata['theme'];
        $css_files = $this->_customdata['css_files'];

        $mform->addElement('hidden', 'theme_id', $theme['id']);
        $mform->setType('theme_id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'update_custom_entry');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'edit_custom_entry', get_string('edit_custom_entry', 'block_theme_customizer'));

        // scan to see if we have any template entry to display
        $has_entry = false;
        foreach ($theme['css_files'] as $filename => $file) {
            if (count($file['entries']) > 0) {
                $has_entry = true;
                break;
            }
        }

        if (!$has_entry) {
            $mform->addElement('html', get_string('no_custom_entry', 'block_theme_customizer'));
            return true;
        }

        $mform->addElement('html', '<div id="block_theme_customizer_css_filter_ctn"></div>');

        // display the existing entries
        $table_head = '<table id="custom_css_entries" class="generaltable"><thead><tr class="list_header"><td></td><td>' .
                      get_string('entry_css_identifier', 'block_theme_customizer') . '</td><td>' .
                      get_string('entry_css_value', 'block_theme_customizer') . '</td><td>' .
                      get_string('entry_description', 'block_theme_customizer') . '</td><td>' .
                      get_string('css_file', 'block_theme_customizer') . '</td></tr></thead><tbody>';

        $mform->addElement('html', $table_head);

        foreach ($css_files as $filename) {
            if (!isset($theme['css_files'][$filename])) {
                continue;
            }

            $file = $theme['css_files'][$filename];

            // skip if no entry for this file
            if (count($file['entries']) == 0) {
                continue;
            }

            $file_basename = basename($filename, '.css');

            foreach ($file['entries'] as $entry_id => $entry) {
                $delete_link = "edit_css.php?action=delete_entry&theme_id={$theme['id']}&entry_id={$entry_id}";

                // select box
                $mform->addElement('html', "<tr class=\"theme_customizer__{$file_basename}\"><td>");
                $mform->addElement('checkbox', 'ceselec_'.$entry['id'], '');

                // CSS identifier
                $mform->addElement('html', '</td><td>');
                $mform->addElement('textarea', 'ceident_'.$entry['id'], '');

                // CSS value
                $mform->addElement('html', '</td><td>');
                $mform->addElement('textarea', 'cevalue_'.$entry['id'], '');

                // description
                $mform->addElement('html', '</td><td>');
                $mform->addElement('textarea', 'cedescr_'.$entry['id'], '');

                // CSS file
                $mform->addElement('html', '</td><td>');
                $mform->addElement('select', 'cefname_'.$entry['id'], '', $css_files);


                // set default values
                $mform->setDefault('ceident_'.$entry['id'], $entry['css_identifier']);
                $mform->setDefault('cevalue_'.$entry['id'], $entry['css_value']);
                $mform->setDefault('cedescr_'.$entry['id'], $entry['description']);
                $mform->setDefault('cefname_'.$entry['id'], $filename);
            }

            // add an empty row to indicate switching to new file
            $mform->addElement('html', '<tr class="separator"><td></td><td></td><td></td><td></td><td></td></tr>');
        }

        $mform->addElement('html', '</tbody></table>');
        $this->add_action_buttons(false, get_string('submit_update_custom_entry', 'block_theme_customizer'));
        $this->add_action_buttons(false, get_string('submit_update_custom_entry_build', 'block_theme_customizer'));
        $this->add_action_buttons(false, get_string('submit_update_custom_entry_delete', 'block_theme_customizer'));
    }
}


/**
 * add a new custom CSS entry for a theme
 */
class block_theme_customizer_add_custom_entry_form extends moodleform {
    function definition () {
        $mform     = & $this->_form;
        $theme     = $this->_customdata['theme'];
        $css_files = $this->_customdata['css_files'];

        $mform->addElement('hidden', 'theme_id', $theme['id']);
        $mform->setType('theme_id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'add_custom_entry');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'add_custom_entry', get_string('add_custom_entry', 'block_theme_customizer'));

        $mform->addElement('textarea', 'entry_identifier', get_string('entry_css_identifier', 'block_theme_customizer'));
        $mform->addElement('textarea', 'entry_value', get_string('entry_css_value', 'block_theme_customizer'));
        $mform->addElement('textarea', 'entry_description', get_string('entry_description', 'block_theme_customizer'));
        $mform->addElement('select', 'css_file', get_string('css_file', 'block_theme_customizer'), $css_files);

        $mform->addRule('entry_identifier', '', 'required');

        $this->add_action_buttons(false, get_string('submit_add_custom_entry', 'block_theme_customizer'));
    }
}


/**
 * upload a theme archive for importing
 */
class block_theme_customizer_import_theme_upload_form extends moodleform {
    function definition () {
        $mform = & $this->_form;

        $mform->addElement('hidden', 'action', 'upload_theme_archive');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'upload_theme_archive', get_string('upload_theme_archive_import', 'block_theme_customizer'));

        // add a file manager
        $mform->addElement('filepicker', 'theme_archive_filemanager', '');

        $this->add_action_buttons(true, get_string('submit_theme_archive', 'block_theme_customizer'));
    }
}


/**
 * select a name for the theme to be imported
 */
class block_theme_customizer_import_theme_name_form extends moodleform {
    function definition () {
        $mform = & $this->_form;

        $mform->addElement('hidden', 'action', 'naming_import_theme');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('hidden', 'archive_name');
        $mform->setType('archive_name', PARAM_TEXT);

        $mform->addElement('header', 'naming_import_theme', get_string('naming_import_theme', 'block_theme_customizer'));

        $mform->addElement('text', 'theme_shortname', get_string('theme_shortname', 'block_theme_customizer'));
        $mform->setType('theme_shortname', PARAM_TEXT);

        $mform->addElement('text', 'theme_fullname', get_string('theme_fullname', 'block_theme_customizer'));
        $mform->setType('theme_fullname', PARAM_TEXT);

        $mform->addRule('theme_shortname', '', 'required');
        $mform->addRule('theme_fullname', '', 'required');
        $mform->addRule('archive_name', '', 'required');

        $this->add_action_buttons(true, get_string('submit_theme_archive', 'block_theme_customizer'));
    }
}


/**
 * upload a theme archive for restoring
 */
class block_theme_customizer_restore_theme_upload_form extends moodleform {
    function definition () {
        $mform = & $this->_form;

        $mform->addElement('hidden', 'theme_id', $this->_customdata['theme_id']);
        $mform->setType('theme_id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'upload_theme_archive');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'upload_theme_archive', get_string('upload_theme_archive_restore', 'block_theme_customizer'));

        // add a file manager
        $mform->addElement('filepicker', 'theme_archive_filemanager', '');

        $this->add_action_buttons(true, get_string('submit_theme_restore', 'block_theme_customizer'));
    }
}


/**
 * edit themes notification
 */
class block_theme_customizer_edit_notification_form extends moodleform {
    function definition () {
        $mform     = & $this->_form;
        $themes    = $this->_customdata;

        $mform->addElement('hidden', 'action', 'update_notification');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'edit_notification', get_string('edit_notification', 'block_theme_customizer'));

        $mform->addElement('html', '<div id="block_theme_customizer_css_filter_ctn"></div>');

        // display the themes
        $table_head = '<table id="theme_list" class="generaltable"><thead><tr class="list_header">'.
                '<td>'.get_string('theme_fullname', 'block_theme_customizer').'</td>'.
                '<td>'.get_string('theme_shortname', 'block_theme_customizer').'</td>'.
                '<td>'.get_string('theme_description', 'block_theme_customizer').'</td>'.
                '<td>'.get_string('theme_notify', 'block_theme_customizer').'</td>'.
                '</tr></thead><tbody>';

        $mform->addElement('html', $table_head);

        foreach ($themes as $theme) {
            if ($theme->shortname == theme_customizer::template_shortname) {
                continue;    // skip the template
            }

            // CSS identifier
            $mform->addElement('html', "<tr><td>{$theme->fullname}</td><td>{$theme->shortname}</td>");
            $mform->addElement('html', "<td>{$theme->description}</td><td>");
            $mform->addElement('checkbox', 'theme_notify__'.$theme->id, '');
            $mform->addElement('html', '</td></tr>');

            // set default values
            $mform->setDefault('theme_notify__'.$theme->id, $theme->notify == theme_customizer::notify_no ? '' : 'checked');
        }

        $mform->addElement('html', '</tbody></table>');
        $this->add_action_buttons(false, get_string('submit_update_notification', 'block_theme_customizer'));
    }
}
