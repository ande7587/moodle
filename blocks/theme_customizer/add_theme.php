<?php

require(realpath(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))).'/config.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/forms.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/lib.php');

$site = get_site();
require_login();

$action   = optional_param('action', null, PARAM_TEXT);

$PAGE->set_context(null); // hack - set context to something, by default to system context
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Add a theme");
$PAGE->set_heading($SITE->fullname);

$return_url = new moodle_url('/blocks/theme_customizer/add_theme.php');
$PAGE->set_url($return_url);

$sitecontext = context_system::instance();

require_capability('block/theme_customizer:manage', $sitecontext);

$theme_lib = new theme_customizer();

// setup navigation
$theme_lib->setup_navbar('add_theme');


$add_theme_form = new block_theme_customizer_add_theme_form();

if ($action != null) {
    if ($add_theme_form->is_cancelled()) {
        redirect('admin.php');
    }

    // process form submission
    switch ($action) {
        case 'add_theme':
            $data = $add_theme_form->get_data();

            // verify the theme shortname
            $shortname = $theme_lib->validate_shortname($data->theme_shortname);
            if ($shortname !== $data->theme_shortname) {
                print_error('invalid_shortname', 'block_theme_customizer', $return_url, array('shortname' => $data->theme_shortname));
            }

            $theme_record = new stdClass();
            $theme_record->shortname     = $data->theme_shortname;
            $theme_record->fullname      = $data->theme_fullname;
            $theme_record->description   = $data->theme_description;
            $theme_record->timemodified  = time();
            $theme_record->last_compiled = 0;

            $theme_id = $DB->insert_record('block_theme', $theme_record, true);

            redirect('edit_theme.php?theme_id=' . $theme_id);
            break;


        default:
            // display error
            print_error('unknown_action', 'block_theme_customizer', $return_url);
            break;
    }
}
else {
    // display the forms
    echo $OUTPUT->header();
    echo "<h2 id=\"add_theme_title\">Add a theme</h2>";

    $add_theme_form->display();

    echo $OUTPUT->footer();
}
