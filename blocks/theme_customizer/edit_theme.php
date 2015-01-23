<?php

require(realpath(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))).'/config.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/forms.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/lib.php');

$site = get_site();
require_login();

$theme_id = required_param('theme_id', PARAM_INT);
$action   = optional_param('action', null, PARAM_TEXT);
$custom_add = optional_param('custom_add', null, PARAM_TEXT);

$sitecontext = context_system::instance();

$PAGE->set_context(null); // hack - set context to something, by default to system context
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Edit theme");
$PAGE->set_heading($SITE->fullname);

$return_url = new moodle_url('/blocks/theme_customizer/edit_theme.php', array('theme_id' => $theme_id));
$PAGE->set_url($return_url);

require_capability('block/theme_customizer:use', $sitecontext);

// check if the current user can manage themes
$is_manager = has_capability('block/theme_customizer:manage', $sitecontext);

$theme_lib = new theme_customizer();

// setup navigation
$theme_lib->setup_navbar('edit_theme', $theme_id);


$data = $theme_lib->load_theme_data(array('theme_id' => $theme_id));

if ($data == false) {
    print_error('invalidrecord', 'error', '', 'theme', "theme ID {$theme_id}");
}

$theme = $data[$theme_id];

// prevent editing the template theme
if ($theme['shortname'] == theme_customizer::template_shortname) {
    print_error('no_editing_template', 'block_theme_customizer');
}

// check ownership
if (!$is_manager && !$theme_lib->verify_theme_ownership($theme)) {
    print_error('not_owner', 'block_theme_customizer', '');
}


// get the template theme
$data = $theme_lib->load_theme_data(array('theme_shortname' => theme_customizer::template_shortname),
                                    array('theme_css_file', 'theme_css_entry'));
$template = array_shift($data);

if ($action != null && $custom_add == null) {
    // process form submission
    switch ($action) {
        case 'build_theme':
            $theme_lib->compile_theme($theme_id);
            $theme_lib->clear_theme_cache($theme_id, $theme);
            redirect($return_url);
            break;

        case 'remove_owner':
            // check authorization
            if (!$is_manager) {
                print_error('nopermissions', 'error', '', 'manage theme customizer');
            }

            $user_id = required_param('user_id', PARAM_INT);

            // verify the owner
            if (!isset($theme['owners'][$user_id])) {
                print_error('invalidrecord', 'error', '', 'user', "id: {$user_id}");
            }

            // check for confirmation
            $confirm = optional_param('confirm', false, PARAM_BOOL) && confirm_sesskey();

            if ($confirm) {
                $DB->delete_records('block_theme_owner', array('theme_id'   => $theme_id,
                                                               'user_id'    => $user_id));
                redirect($PAGE->url);
            }
            else {
                // display confirmation page
                $yesurl = new moodle_url($PAGE->url, array(
                        'action'    => 'remove_owner',
                        'theme_id'  => $theme_id,
                        'user_id'   => $user_id,
                        'confirm'   => 1,
                        'sesskey'   => sesskey()));

                $user = $theme['owners'][$user_id];
                $message = get_string('remove_owner_confirm', 'block_theme_customizer', array('name' => $user['firstname'].' '.$user['lastname']));
                $pagetitle = get_string('remove_owner', 'block_theme_customizer');

                echo $OUTPUT->header();
                echo $OUTPUT->confirm($message, $yesurl, $return_url);
                echo $OUTPUT->footer();
                die;
            }
            break;

        case 'update_theme_detail':
            // check authorization
            if (!$is_manager) {
                print_error('nopermissions', 'error', '', 'manage theme customizer');
            }

            $form = new block_theme_customizer_theme_detail_form(null, $theme);
            $data = $form->get_data();

            // verify the theme shortname
            $shortname = $theme_lib->validate_shortname($data->theme_shortname);
            if ($shortname !== $data->theme_shortname) {
                print_error('invalid_shortname', 'block_theme_customizer', $return_url, array('shortname' => $data->theme_shortname));
            }

            $theme_record = new stdClass();
            $theme_record->id = $theme['id'];
            $theme_record->shortname      = $data->theme_shortname;
            $theme_record->fullname       = $data->theme_fullname;
            $theme_record->parent         = $data->theme_parent;
            $theme_record->description    = $data->theme_description;
            $theme_record->category_level = $data->theme_category_level;
            $theme_record->timemodified   = time();

            // check custom values
            if (isset($data->custom_repeat) && $data->custom_repeat > 0) {
                for ($i = 0; $i < $data->custom_repeat; $i++) {
                    $key = trim($data->custom_setting_name[$i]);

                    // insert new record
                    if (!isset($theme['custom_settings'][$key])) {
                        if (!empty($data->custom_setting_value[$i])) {
                            $custom_setting = new stdClass();
                            $custom_setting->theme_id      = $theme['id'];
                            $custom_setting->setting_name  = $key;
                            $custom_setting->setting_value = trim($data->custom_setting_value[$i]);

                            $DB->insert_record('block_theme_custom_setting', $custom_setting);
                        }
                    }
                    else { // update or delete exiting custom_setting record
                        if (!empty($data->custom_setting_value[$i])) {
                            $custom_setting = new stdClass();
                            $custom_setting->id            = $theme['custom_settings'][$key]['id'];
                            $custom_setting->theme_id      = $theme['id'];
                            $custom_setting->setting_name  = $key;
                            $custom_setting->setting_value = trim($data->custom_setting_value[$i]);

                            $DB->update_record('block_theme_custom_setting', $custom_setting);
                        }
                        else {
                            $DB->delete_records('block_theme_custom_setting', array('id' => $theme['custom_settings'][$key]['id']));
                        }
                    }
                }
            }

            $DB->update_record('block_theme', $theme_record);
            redirect($return_url);
            break;

        case 'update_predefined_entry':
            // parse the submitted data
            $form = new block_theme_customizer_CSS_predefined_entry_form(null, array('template' => $template,
                                                                                     'theme'    => $theme));
            $data     = $form->get_data();
            $entries  = array();

            foreach ($data as $param => $value) {
                if (substr($param, 0, 3) == 'te_') {
                    $comps = explode('_', $param);
                    $entries[$comps[1]] = $value;
                }
            }

            // update the entries
            $theme_lib->update_predefined_entries($theme_id, $entries, $theme, $template);    // exception bubble up

            // redirect
            redirect($return_url);
            break;

        default:
            // display error
            print_error('unknown_action', 'block_theme_customizer', $return_url);
            break;
    }
}
else {
    // display the forms
    $PAGE->set_title("Edit theme: {$theme['fullname']}");

    $jsmodule = array(
            'name'         => 'edit_theme',
            'fullpath'     => '/blocks/theme_customizer/js/edit_theme.js',
            'requires'     => array('base', 'io', 'node', 'json', 'event'),
            'strings'	   => array());

    $js_params = array(
            'category_level_none'        => theme_customizer::level_none,
            'category_level_campus'      => theme_customizer::level_campus,
            'category_level_college'     => theme_customizer::level_college,
            'category_level_department'  => theme_customizer::level_department,
            'category_level_course'      => theme_customizer::level_course);

    $PAGE->requires->js_init_call('M.block_theme_customizer.init', array($js_params), true, $jsmodule);

    echo $OUTPUT->header();
    echo "<h2 id=\"edit_theme_title\">{$theme['fullname']}</h2>";
    echo '<span id=\"edit_theme_last_compiled\">(last build: ';
    echo (!empty($theme['last_compiled']) ? userdate($theme['last_compiled']) : 'none'), ')</span>';

    // put a button to build the theme
    $build_form = new block_theme_customizer_build_theme_form(null, $theme);
    $build_form->display();

    // add the managing forms if applicable
    if ($is_manager) {
        $ownership_form = new block_theme_customizer_ownership_form(null, $theme);
        $ownership_form->display();

        $detail_form = new block_theme_customizer_theme_detail_form(null, $theme);
        $detail_form->display();

//         $custom_value_form = new block_theme_customizer_theme_custom_values_form(null, $theme);
//         $custom_value_form->display();
    }

    $css_predefined_form  = new block_theme_customizer_CSS_predefined_entry_form(null, array('template' => $template,
                                                                                             'theme'    => $theme));
    $css_predefined_form->display();

    echo '<a id="edit_theme_css_entries_link" href="edit_css.php?theme_id='.$theme_id.'">Edit all CSS entries</a>';

    $graphic_files_form = new block_theme_customizer_graphic_files_form(null, $theme);
    $graphic_files_form->display();

    echo $OUTPUT->footer();
}
