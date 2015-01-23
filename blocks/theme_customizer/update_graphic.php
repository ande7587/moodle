<?php

require(realpath(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))).'/config.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/forms.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/lib.php');

$site = get_site();
require_login();

$theme_id = required_param('theme_id', PARAM_INT);
$action   = optional_param('action', null, PARAM_TEXT);


$PAGE->set_context(null); // hack - set context to something, by default to system context
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Add graphics files");
$PAGE->set_heading($SITE->fullname);

$return_url = new moodle_url("/blocks/theme_customizer/update_graphic.php?theme_id={$theme_id}");
$PAGE->set_url($return_url);

$sitecontext = context_system::instance();

require_capability('block/theme_customizer:use', $sitecontext);

// query DB
$theme_lib = new theme_customizer();

// setup navigation
$theme_lib->setup_navbar('update_graphic', $theme_id);

$themes = $theme_lib->load_theme_data(array('theme_id' => $theme_id), array('theme_graphic_files'));

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

$form = new block_theme_customizer_update_graphic_files_form(null, $theme);

if ($action != null) {
    if ($form->is_cancelled()) {
        redirect('edit_theme.php?theme_id='.$theme_id);
    }

    // process form submission
    switch ($action) {
        case 'update_graphic':
            $data = $form->get_data();

            file_save_draft_area_files($data->attachments_filemanager, $sitecontext->id, 'block_theme_customizer', 'graphic', $theme_id);
            redirect('edit_theme.php?theme_id='.$theme_id);
            break;

        default:
            // display error
            print_error('unknown_action', 'block_theme_customizer', $return_url);
            break;
    }
}
else {
    // display the forms
    $PAGE->set_title("Update graphic files for {$theme['fullname']}");

    echo $OUTPUT->header();
    echo "<h2>{$theme['fullname']}: graphic files</h2>";

    // move submission files to user draft area
    $data = new stdClass();

    try {
        $data = file_prepare_standard_filemanager($data, 'attachments', array('return_types'=>FILE_INTERNAL), $sitecontext, 'block_theme_customizer', 'graphic', $theme_id);

        // set file manager itemid, so it will find the files in draft area
        $form->set_data($data);
    }
    catch(file_exception $e) {
        echo '<span class="error">'.get_String('missing_files', 'block_theme_customizer').'</span>';
    }

    $form->display();

    echo $OUTPUT->footer();
}


die;
