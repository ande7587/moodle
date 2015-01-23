<?php

require(realpath(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))).'/config.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/forms.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/lib.php');

$site = get_site();
require_login();

$entry_id = required_param('entry_id', PARAM_INT);
$action   = optional_param('action', null, PARAM_TEXT);


$PAGE->set_context(null); // hack - set context to something, by default to system context
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Edit template entry");
$PAGE->set_heading($SITE->fullname);

$return_url = new moodle_url('/blocks/theme_customizer/edit_template.php', array('entry_id' => $entry_id));
$PAGE->set_url($return_url);

$sitecontext = context_system::instance();


require_capability('block/theme_customizer:manage', $sitecontext);

$theme_lib = new theme_customizer();

// setup navigation
$theme_lib->setup_navbar('edit_template', '', '', $entry_id);

$data = $theme_lib->load_theme_data(array('theme_shortname' => theme_customizer::template_shortname));

$template  = array_shift($data);

$entry     = null;
$css_files = array();

foreach ($template['css_files'] as $file) {
    $css_files[$file['id']] = $file['name'];

    foreach ($file['entries'] as $e) {
        if ($e['id'] == $entry_id) {
            $entry = $e;
        }
    }
}

if ($entry_id == 0) {
    // creating new entry
    $page_title = 'Create a template entry.';
    $entry = array('id' => 0);
}
else {
    $page_title = 'Edit a template entry';
}

$PAGE->set_title($page_title);


// check for submitted form
if ($action != null) {
    $form = new block_theme_customizer_edit_template_entry_form(null, array('entry' => $entry, 'css_files' => $css_files));

    if ($form->is_cancelled()) {
        redirect('admin.php');
    }

    // process form submission
    switch ($action) {
        case 'update_template_entry':
            $form_data = $form->get_data();

            $entry_record = new stdClass();
            $entry_record->css_file_id    = $form_data->entry_file_id;
            $entry_record->css_identifier = $form_data->entry_css_identifier;
            $entry_record->css_value      = '';
            $entry_record->description    = $form_data->entry_description;
            $entry_record->timemodified   = time();

            if ($form_data->entry_id == 0) {
                // creating
                $entry_record->timecreated = time();
                $DB->insert_record('block_theme_css_entry', $entry_record);
            }
            else {
                // updating
                $entry_record->id = $entry['id'];
                $DB->update_record('block_theme_css_entry', $entry_record);
            }

            redirect('admin.php');
            break;

        default:
            // display error
            print_error('unknown_action', 'block_theme_customizer', $return_url);
            break;
    }
}
else {
    // display the form
    echo $OUTPUT->header();

    echo '<h2>', $page_title, '</h2>';

    $template_form = new block_theme_customizer_edit_template_entry_form(null, array('entry' => $entry, 'css_files' => $css_files));
    $template_form->display();

    echo $OUTPUT->footer();
}