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
$PAGE->set_title("Restore a theme");
$PAGE->set_heading($SITE->fullname);

$return_url = new moodle_url('/blocks/theme_customizer/restore_theme.php?theme_id=' . $theme_id);
$PAGE->set_url($return_url);

$sitecontext = context_system::instance();

require_capability('block/theme_customizer:use', $sitecontext);
$is_manager = has_capability('block/theme_customizer:manage', $sitecontext);

$theme_lib = new theme_customizer();

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


// setup navigation
$theme_lib->setup_navbar('restore_theme');


if ($action != null) {
    // process form submission
    switch ($action) {
        case 'upload_theme_archive':
            $form = new block_theme_customizer_restore_theme_upload_form(null, array('theme_id' => $theme_id));

            if ($form->is_cancelled()) {
                redirect($is_manager ? 'admin.php' : 'user.php');
            }

            $form->get_data();
            make_temp_directory("theme_customizer/");

            // save the uploaded file to temp dir
            $uploaded_filename = $form->get_new_filename('theme_archive_filemanager');
            $file_info = pathinfo($uploaded_filename);

            $archive_name = basename($uploaded_filename, '.'.$file_info['extension']);
            $file_path    = "{$CFG->tempdir}/theme_customizer/{$archive_name}.zip";

            if (!$form->save_file('theme_archive_filemanager', $file_path, true)) {
                print_error('cannot_save_upload', 'block_theme_customizer', $return_url);
            };

            // uncompress the uploaded file
            $fp = get_file_packer();
            make_temp_directory("theme_customizer/{$archive_name}/");

            if (!$fp->extract_to_pathname($file_path, "{$CFG->tempdir}/theme_customizer/{$archive_name}/")) {
                print_error('cannot_extract_archive', 'block_theme_customizer', $return_url, array('filename' => $file_path));
            };

            // read the theme def
            $file_content = file_get_contents("{$CFG->tempdir}/theme_customizer/{$archive_name}/theme.json");

            if (!$file_content) {
                print_error('invalid_theme_def', 'block_theme_customizer', $return_url, array('filename' => 'theme.json'));
            }

            $theme_def = json_decode($file_content, true);

            if (is_null($theme_def)) {
                print_error('invalid_theme_def', 'block_theme_customizer', $return_url, array('filename' => 'theme.json'));
            }

            // execute
            $result = $theme_lib->restore_theme($theme_id, "{$CFG->tempdir}/theme_customizer/{$archive_name}");

            // clean up temp files
            if ($result) {
                remove_dir("{$CFG->tempdir}/theme_customizer/{$archive_name}");
                unlink("{$CFG->tempdir}/theme_customizer/{$archive_name}.zip");
            }

            // redirect
            redirect('edit_theme.php?theme_id='.$theme_id);
            break;


        default:
            // display error
            print_error('unknown_action', 'block_theme_customizer', $return_url);
            break;
    }
}
else {
    // display the upload form
    echo $OUTPUT->header();
    echo "<h2 id=\"restore_theme_title\">Restore theme {$theme['fullname']}</h2>";

    echo '<div id="restore_theme_explain">'.get_string('restore_theme_explain', 'block_theme_customizer').'</div>';

    $form = new block_theme_customizer_restore_theme_upload_form(null, array('theme_id' => $theme_id));
    $form->display();

    echo $OUTPUT->footer();
}