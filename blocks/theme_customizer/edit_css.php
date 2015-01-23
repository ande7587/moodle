<?php

require(realpath(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))).'/config.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/forms.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/lib.php');

$site = get_site();
require_login();

$theme_id   = required_param('theme_id', PARAM_INT);
$action     = strtolower(optional_param('action', null, PARAM_TEXT));
$show_build = strtolower(optional_param('show_build', null, PARAM_TEXT));

$PAGE->set_context(null); // hack - set context to something, by default to system context
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Edit theme CSS");
$PAGE->set_heading($SITE->fullname);

$return_url = new moodle_url("/blocks/theme_customizer/edit_css.php?theme_id={$theme_id}");
$PAGE->set_url($return_url);

$sitecontext = context_system::instance();

require_capability('block/theme_customizer:use', $sitecontext);

// query DB
$theme_lib = new theme_customizer();

// setup navigation
$theme_lib->setup_navbar('edit_css', $theme_id);

$themes = $theme_lib->load_theme_data(array('theme_id' => $theme_id), array('theme_owner', 'theme_css_file', 'theme_css_entry'));

if ($themes == false || !isset($themes[$theme_id])) {
    print_error('invalidrecord', 'error', '', 'theme', "theme ID {$theme_id}");
}

$theme = $themes[$theme_id];

// prevent editing the template theme
if ($theme['shortname'] == theme_customizer::template_shortname) {
    print_error('no_editing_template', 'block_theme_customizer');
}

// verify theme ownership
if (!$theme_lib->verify_theme_ownership($theme)) {
    print_error('not_owner', 'block_theme_customizer', '');
}

// get the template
$data = $theme_lib->load_theme_data(array('theme_shortname' => theme_customizer::template_shortname),
                                    array('theme_css_file'));
$template = array_shift($data);

// parse the list of CSS files for the select box
$css_files = array();
foreach ($template['css_files'] as $filename => $file) {
    $css_files[$filename] = $filename;
}

// sort the CSS file list alphabetically
asort($css_files);


if ($action != null) {
    // process form submission
    switch ($action) {
        case 'add_custom_entry':
            $form = new block_theme_customizer_add_custom_entry_form(null, array('theme' => $theme, 'css_files' => $css_files));
            $form_data = $form->get_data();

            // verify filename
            if (!isset($css_files[$form_data->css_file])) {
                print_error('invalid_css', 'block_theme_customizer', $return_url, array('filename' => $form_data->css_file));
            }

            // add the file if not exists
            $file_id = null;
            foreach ($theme['css_files'] as $file) {
                if ($file['name'] == $form_data->css_file) {
                    $file_id = $file['id'];

                    // check for duplicate identifier
                    foreach ($file['entries'] as $entry) {
                        if ($entry['css_identifier'] == $form_data->entry_identifier) {
                            print_error('duplicate_css_identifier', 'block_theme_customizer', $return_url, array(
                                                    'identifier' => $entry['css_identifier'],
                                                    'filename'   => $form_data->css_file));
                        }
                    }

                    break;
                }
            }

            if (is_null($file_id)) {
                $css_file = new stdClass();
                $css_file->theme_id     = $theme['id'];
                $css_file->name         = $form_data->css_file;
                $css_file->path         = '';
                $css_file->timemodified = time();

                $file_id = $DB->insert_record('block_theme_css_file', $css_file, true);
            }

            // add the entry
            $entry = new stdClass();
            $entry->css_file_id    = $file_id;
            $entry->css_identifier = $form_data->entry_identifier;
            $entry->css_value      = $form_data->entry_value;
            $entry->description    = $form_data->entry_description;
            $entry->timecreated    = time();
            $entry->timemodified   = time();

            $DB->insert_record('block_theme_css_entry', $entry);
            redirect($return_url);
            break;


        case 'update_custom_entry':
            $form = new block_theme_customizer_edit_custom_entry_form(null, array('theme' => $theme, 'css_files' => $css_files));
            $form_data = $form->get_data();

            // parse the form data
            $entries        = array();
            $identifiers    = array();    // to check for duplication
            $valid_prefixes = array('ceselec_'    => true,
                                    'ceident_'    => true,
                                    'cevalue_'    => true,
                                    'cedescr_'    => true,
                                    'cefname_'    => true);

            $selected_ids = array();    // keep track of which entries have been selected

            foreach ($form_data as $field => $value) {
                $value = trim($value);
                $field_prefix = substr($field, 0, 8);

                if (isset($valid_prefixes[$field_prefix])) {
                    $parts     = explode('_', $field);
                    $entry_id  = $parts[1];

                    if (!isset($entries[$entry_id])) {
                        $entries[$entry_id] = array();
                    }

                    switch ($field_prefix) {
                        case 'ceselec_':
                            $selected_ids[] = $entry_id;
                            break;

                        case 'ceident_':
                            $entries[$entry_id]['css_identifier'] = $value;

                            // check for duplication
                            if (isset($identifiers[$value])) {
                                print_error('duplicate_css_identifier', 'block_theme_customizer', $return_url,
                                            array('identifier' => $value,
                                                  'filename'   => $css_filename));
                            }

                            $identifiers[$value] = 1;
                            break;

                        case 'cevalue_':
                            $entries[$entry_id]['css_value'] = $value;
                            break;

                        case 'cedescr_':
                            $entries[$entry_id]['description'] = $value;
                            break;

                        case 'cefname_':
                            if (!isset($css_files[$value])) {
                                print_error('invalid_css', 'block_theme_customizer', $return_url, array('filename' => $value));
                            }
                            $entries[$entry_id]['css_filename'] = $value;
                            break;

                        default:
                            print_error('uknown_fieldprefix', 'block_theme_customizer', $return_url, array('field' => $field));
                    }
                }
            }

            // map the existing entries in theme for verification
            $existing_entries = array();    // id => entry
            $existing_files   = array();    // filename => id

            foreach ($theme['css_files'] as $filename => $file) {
                $existing_files[$filename] = $file['id'];

                foreach ($file['entries'] as $entry_id => $entry) {
                    $existing_entries[$entry_id] = $entry;
                }
            }


            // see if the request is to delete entries, display the confirmation
            if ($form_data->submitbutton == get_string('submit_update_custom_entry_delete', 'block_theme_customizer')) {
                if (count($selected_ids) == 0) {
                    print_error('noentryselected', 'block_theme_customizer');
                }

                // display confirmation page
                $data = array(
                    'action'    => 'delete_custom_entry',
                    'theme_id'  => $theme_id,
                    'confirm'   => 1,
                    'entry_ids' => implode(',', $selected_ids),
                    'sesskey'   => sesskey());

                $yesurl = new moodle_url($PAGE->url, $data);

                $message = get_string('delete_entry_confirm', 'block_theme_customizer',
                                      array('count'        => count($selected_ids),
                                            'theme_name'   => $theme['fullname']));

                $pagetitle = get_string('delete_entry_title', 'block_theme_customizer');

                echo $OUTPUT->header();
                echo $OUTPUT->confirm($message, $yesurl, $PAGE->url);
                echo $OUTPUT->footer();
                die;
            }


            // map from the parsed data to the record
            $updatable_properties = array('css_identifier', 'css_value', 'description', 'css_file_id');

            // update only the changed entries
            foreach ($entries as $entry_id => & $entry) {
                if (!isset($existing_entries[$entry_id])) {
                    continue;
                }

                // check if the file needs to be created
                if (!isset($existing_files[$entry['css_filename']])) {
                    $css_file = new stdClass();
                    $css_file->theme_id       = $theme_id;
                    $css_file->name           = $entry['css_filename'];
                    $css_file->path           = 'styles';
                    $css_file->timemodified   = time();

                    $existing_files[$entry['css_filename']] = $DB->insert_record('block_theme_css_file', $css_file, true);
                }

                $entry['css_file_id'] = $existing_files[$entry['css_filename']];

                $entry_record = new stdClass();
                $entry_record->id = $entry_id;

                $has_change    = false;
                $existing_data = $existing_entries[$entry_id];

                foreach ($updatable_properties as $property) {
                    if ($existing_data[$property] != $entry[$property]) {
                        $has_change = true;
                        $entry_record->$property = $entry[$property];
                    }
                    else {
                        $entry_record->$property = $existing_data[$property];
                    }
                }

                if ($has_change) {
                    $entry_record->timemodified = time();
                    $DB->update_record('block_theme_css_entry', $entry_record);
                }
            }

            // build the theme if requested
            if (strpos(strtolower($form_data->submitbutton), 'build')) {
                $theme_lib->compile_theme($theme_id);
                $theme_lib->clear_theme_cache($theme_id, $theme);
                $return_url .= '&show_build=1';
            }

            redirect($return_url);
            break;


        case 'delete_custom_entry':
            // this branch responds to the delete confirmation form
            $entry_ids = required_param('entry_ids', PARAM_TEXT);
            $entry_ids = explode(',', $entry_ids);

            $not_found_entry_ids = array();

            // loop through and delete the entries
            foreach ($entry_ids as $entry_id) {
                $entry_found = false;
                foreach ($theme['css_files'] as $filename => $file) {
                    if (isset($file['entries'][$entry_id])) {
                        $entry_found = true;
                    }
                }

                if (!$entry_found) {
                    $not_found_entry_ids[] = $entry_id;
                }
                else {
                    $DB->delete_records('block_theme_css_entry', array('id' => $entry_id));
                }
            }

            if (count($not_found_entry_ids) > 0) {
                print_error('invalid_entry_id', 'block_theme_customizer', $return_url,
                            array('entry_id' => explode(',', $not_found_entry_ids)));
            }

            redirect($return_url);
            break;

        default:
            // display error
            print_error('unknown_action', 'block_theme_customizer', $return_url);
            break;
    }
}
else {
    $jsmodule = array(
        'name'         => 'edit_css',
        'fullpath'     => '/blocks/theme_customizer/js/edit_css.js',
        'requires'     => array('base', 'io', 'node', 'json', 'event'),
        'strings'	   => array(
            array('show_all', 'block_theme_customizer'),
            array('hide_all', 'block_theme_customizer')
        )
    );

    // get the list of CSS files, together with the number of entries
    $js_css_files = array();
    foreach ($css_files as $css_file) {
        $js_css_files[$css_file] = isset($theme['css_files'][$css_file]) && (count($theme['css_files'][$css_file]['entries']) > 0);
    }

    $PAGE->requires->js_init_call('M.block_theme_customizer.init',
                                  array(array('css_files' => $js_css_files)), true, $jsmodule);


    // display the forms
    $PAGE->set_title("Edit theme {$theme['fullname']} CSS entries");

    echo $OUTPUT->header();
    echo "<h2>{$theme['fullname']}: CSS entries</h2>";

    if ($show_build == '1') {
        echo '<span id=\"edit_css_last_compiled\">(Last build: ';
        echo (!empty($theme['last_compiled']) ? userdate($theme['last_compiled']) : 'none'), ')</span>';
    }

    // display the custom entry forms
    $edit_form = new block_theme_customizer_edit_custom_entry_form(null, array('theme'     => $theme,
                                                                               'css_files' => $css_files));
    $edit_form->display();

    $add_form = new block_theme_customizer_add_custom_entry_form(null, array('theme'      => $theme,
                                                                             'css_files'  => $css_files));
    $add_form->display();

    echo $OUTPUT->footer();
}


die;
