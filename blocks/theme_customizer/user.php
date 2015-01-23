<?php

require(realpath(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))).'/config.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/forms.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/lib.php');

$site = get_site();
require_login();

$action = optional_param('action', null, PARAM_TEXT);

$PAGE->set_context(null); // hack - set context to something, by default to system context
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Customize themes");
$PAGE->set_heading($SITE->fullname);

$return_url = new moodle_url('/blocks/theme_customizer/user.php');
$PAGE->set_url($return_url);

$sitecontext = context_system::instance();

require_capability('block/theme_customizer:use', $sitecontext);

// setup navigation
$theme_lib = new theme_customizer();
$theme_lib->setup_navbar('user');

// process action
if ($action != null) {
    switch ($action) {
        case 'export_theme':
            $theme_id = required_param('theme_id', PARAM_INT);

            // verify the record
            $data = $theme_lib->load_theme_data(array('theme_id' => $theme_id));

            if (!isset($data[$theme_id])) {
                print_error('invalidrecord', 'error', '', 'theme', "theme ID {$theme_id}");
            }

            $theme = $data[$theme_id];

            // check ownership
            if (!$theme_lib->verify_theme_ownership($theme)) {
                print_error('not_owner', 'block_theme_customizer', '');
            }

            $zipfile = $theme_lib->export_theme($theme_id);

            send_temp_file($zipfile, 'theme_def_'.$theme['shortname'].'.mtz');

            // clean up
            unlink($zipfile);
            break;

        default:
            // display error
            print_error('unknown_action', 'block_theme_customizer', $return_url);
            break;
    }
}


// display the forms
echo $OUTPUT->header();

// add the theme-list table
$records = $DB->get_records('block_theme_owner', array('user_id' => $USER->id));

$theme_ids = array();
foreach ($records as $record) {
    $theme_ids[] = $record->theme_id;
}

$themes = $DB->get_records_list('block_theme', 'id', $theme_ids);


echo '<h2>Manage themes</h2>';

if (count($themes) == 0) {
    echo 'You have no theme.';
}
else {
    $themes_table = new html_table();

    // header row
    $header_row = new html_table_row();
    $header_row->attributes['class'] = 'list_header';
    $header_row->cells[] = new html_table_cell('Full name');
    $header_row->cells[] = new html_table_cell('Short name');
    $header_row->cells[] = new html_table_cell('Description');

    $themes_table->data[] = $header_row;

    foreach ($themes as $theme) {
        if ($theme->shortname != theme_customizer::template_shortname) {
            $row = new html_table_row();
            $row->cells[] = new html_table_cell($theme->fullname);
            $row->cells[] = new html_table_cell($theme->shortname);
            $row->cells[] = new html_table_cell($theme->description);
            $row->cells[] = new html_table_cell('<a href="edit_theme.php?theme_id='.$theme->id.'">edit</a>');
            $row->cells[] = new html_table_cell('<a href="user.php?action=export_theme&theme_id='.$theme->id.'">export</a>');
            $row->cells[] = new html_table_cell('<a href="restore_theme.php?theme_id='.$theme->id.'">restore</a>');

            $themes_table->data[] = $row;
        }
    }

    echo html_writer::table($themes_table);
}

echo $OUTPUT->footer();

