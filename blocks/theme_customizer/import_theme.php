<?php

require(realpath(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))).'/config.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/forms.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/lib.php');

$site = get_site();
require_login();

$action   = optional_param('action', null, PARAM_TEXT);

$PAGE->set_context(null); // hack - set context to something, by default to system context
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Import a theme");
$PAGE->set_heading($SITE->fullname);

$return_url = new moodle_url('/blocks/theme_customizer/import_theme.php');
$PAGE->set_url($return_url);

$sitecontext = context_system::instance();

require_capability('block/theme_customizer:manage', $sitecontext);

$theme_lib = new theme_customizer();

// setup navigation
$theme_lib->setup_navbar('import_theme');


if ($action != null) {
    // process form submission
    switch ($action) {
        case 'upload_theme_archive':
            $form = new block_theme_customizer_import_theme_upload_form();

            if ($form->is_cancelled()) {
                redirect('admin.php');
            }

            $form->get_data();

            make_temp_directory('theme_customizer');

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

            // check duplicate
            $has_duplicate = true && $DB->get_record('block_theme', array('shortname' => $theme_def['shortname']));

            // display the naming form
            echo $OUTPUT->header();
            echo "<h2 id=\"add_theme_title\">Import a theme</h2>";

            if ($has_duplicate) {
                echo get_string('duplicate_name', 'block_theme_customizer', array('name' => $theme_def['shortname']));
                $theme_def['shortname'] = '';
            }

            $form = new block_theme_customizer_import_theme_name_form();
            $form->set_data(array(
                'theme_shortname'     => $theme_def['shortname'],
                'theme_fullname'      => $theme_def['fullname'],
                'archive_name'        => $archive_name));

            $form->display();

            echo $OUTPUT->footer();
            break;


        case 'naming_import_theme':
            // check name
            $form = new block_theme_customizer_import_theme_name_form();

            if ($form->is_cancelled()) {
                redirect('admin.php');
            }

            $data = $form->get_data();

            if (!is_null($data)) {
                $has_duplicate = true && $DB->get_record('block_theme', array('shortname' => $data->theme_shortname));
            }

            if (is_null($data) || $has_duplicate) {
                // display the naming form
                echo $OUTPUT->header();
                echo "<h2 id=\"add_theme_title\">Import a theme</h2>";

                if ($has_duplicate) {
                    echo get_string('duplicate_name', 'block_theme_customizer', array('name' => $data->theme_shortname));
                }

                $form->display();

                echo $OUTPUT->footer();
                exit();
            }

            // execute
            $result = $theme_lib->import_theme("{$CFG->tempdir}/theme_customizer/{$data->archive_name}",
                                               $data->theme_shortname,
                                               $data->theme_fullname);

            // clean up temp files
            if ($result) {
                remove_dir("{$CFG->tempdir}/theme_customizer/{$data->archive_name}");
                unlink("{$CFG->tempdir}/theme_customizer/{$data->archive_name}.zip");
            }

            // redirect
            redirect('admin.php');
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
    echo "<h2 id=\"add_theme_title\">Import a theme</h2>";

    $form = new block_theme_customizer_import_theme_upload_form();
    $form->display();

    echo $OUTPUT->footer();
}