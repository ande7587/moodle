<?php

require(realpath(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))).'/config.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/forms.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/lib.php');

$site = get_site();
require_login();

$action   = optional_param('action', null, PARAM_TEXT);

$sitecontext = context_system::instance();

$PAGE->set_context($sitecontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Manage customized themes");
$PAGE->set_heading($SITE->fullname);

$return_url = new moodle_url('/blocks/theme_customizer/admin.php');
$PAGE->set_url($return_url);

require_capability('block/theme_customizer:manage', $sitecontext);


// process action
if ($action != null) {
    switch ($action) {
        case 'delete_theme':
            $theme_id = required_param('theme_id', PARAM_INT);

            // verify the record
            $theme_lib = new theme_customizer();
            $data = $theme_lib->load_theme_data(array('theme_id' => $theme_id));

            if (!isset($data[$theme_id])) {
                print_error('invalidrecord', 'error', '', 'theme', "theme ID {$theme_id}");
            }

            $theme = $data[$theme_id];

            // check for confirmation
            $confirm = optional_param('confirm', false, PARAM_BOOL) && confirm_sesskey();

            if ($confirm) {
                $DB->delete_records('block_theme', array('id'   => $theme_id));

                // delete related records
                $css_file_ids = array();
                foreach ($theme['css_files'] as $file) {
                    $css_file_ids[] = $file['id'];
                }

                $DB->delete_records_list('block_theme_css_entry', 'css_file_id', $css_file_ids);
                $DB->delete_records_list('block_theme_css_file', 'id', $css_file_ids);
                $DB->delete_records_list('block_theme_owner', 'theme_id', array($theme_id));
                $DB->delete_records_list('block_theme_custom_setting', 'theme_id', array($theme_id));

                // delete graphic files
                $site_context = context_system::instance();
                $fs = get_file_storage();

                $fs->delete_area_files($site_context->id, 'block_theme_customizer', 'graphic', $theme_id);

                // delete the compiled directory
                fulldelete($theme_lib->get_theme_dir($theme));

                redirect($PAGE->url);
            }
            else {
                // display confirmation page
                $yesurl = new moodle_url($PAGE->url, array(
                        'action'    => 'delete_theme',
                        'theme_id'  => $theme_id,
                        'confirm'   => 1,
                        'sesskey'   => sesskey()));

                $message = get_string('delete_theme_confirm', 'block_theme_customizer', array('name' => $theme['fullname']));
                $pagetitle = get_string('delete_theme', 'block_theme_customizer');

                echo $OUTPUT->header();
                echo $OUTPUT->confirm($message, $yesurl, $PAGE->url);
                echo $OUTPUT->footer();
                die;
            }
            break;

        case 'export_theme':
            $theme_id = required_param('theme_id', PARAM_INT);

            // verify the record
            $theme_lib = new theme_customizer();
            $data = $theme_lib->load_theme_data(array('theme_id' => $theme_id));

            if (!isset($data[$theme_id])) {
                print_error('invalidrecord', 'error', '', 'theme', "theme ID {$theme_id}");
            }

            $theme = $data[$theme_id];

            $zipfile = $theme_lib->export_theme($theme_id);

            send_temp_file($zipfile, 'theme_def_'.$theme['shortname'].'.mtz');
            break;


        case 'delete_template_entry':
            $entry_id = required_param('entry_id', PARAM_INT);

            // verify the record
            $theme_lib = new theme_customizer();
            $data = $theme_lib->load_theme_data(array('theme_shortname' => theme_customizer::template_shortname));
            $template = array_shift($data);

            // verify the entry
            $entry = null;
            foreach ($template['css_files'] as $file) {
                foreach ($file['entries'] as $e) {
                    if ($e['id'] == $entry_id) {
                        $entry = $e;
                        break 2;
                    }
                }
            }

            if (is_null($entry)) {
                print_error('invalidrecord', 'error', '', 'theme_css_entry', "entry ID {$entry_id}");
            }

            // check for confirmation
            $confirm = optional_param('confirm', false, PARAM_BOOL) && confirm_sesskey();

            if ($confirm) {
                $DB->delete_records('block_theme_css_entry', array('id'   => $entry_id));
                redirect($PAGE->url);
            }
            else {
                // display confirmation page
                $yesurl = new moodle_url($PAGE->url, array(
                        'action'    => 'delete_template_entry',
                        'entry_id'  => $entry_id,
                        'confirm'   => 1,
                        'sesskey'   => sesskey()));

                $message = get_string('delete_template_entry_confirm', 'block_theme_customizer', array('name' => $entry['css_identifier']));
                $pagetitle = get_string('delete_template_entry', 'block_theme_customizer');

                echo $OUTPUT->header();
                echo $OUTPUT->confirm($message, $yesurl, $PAGE->url);
                echo $OUTPUT->footer();
                die;
            }
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
$themes = $DB->get_records('block_theme');

echo '<h2>Manage themes</h2>';

$themes_table = new html_table();

// header row
$header_row = new html_table_row();
$header_row->attributes['class'] = 'list_header';
$header_row->cells[] = new html_table_cell('Full name');
$header_row->cells[] = new html_table_cell('Short name');
$header_row->cells[] = new html_table_cell('Description');
$header_row->cells[] = new html_table_cell('Notify');

$themes_table->data[] = $header_row;

$has_data = false;    // to decide whether to print out the table

foreach ($themes as $theme) {
    if ($theme->shortname != theme_customizer::template_shortname) {
        $row = new html_table_row();
        $row->cells[] = new html_table_cell($theme->fullname);
        $row->cells[] = new html_table_cell($theme->shortname);
        $row->cells[] = new html_table_cell($theme->description);
        $row->cells[] = new html_table_cell($theme->notify == 0 ? '' : 'yes');
        $row->cells[] = new html_table_cell('<a href="edit_theme.php?theme_id='.$theme->id.'">edit</a>');
        $row->cells[] = new html_table_cell('<a href="admin.php?action=export_theme&theme_id='.$theme->id.'">export</a>');
        $row->cells[] = new html_table_cell('<a href="restore_theme.php?theme_id='.$theme->id.'">restore</a>');
        $row->cells[] = new html_table_cell('<a href="admin.php?action=delete_theme&theme_id='.$theme->id.'">delete</a>');

        $themes_table->data[] = $row;
        $has_data = true;
    }
}

if ($has_data) {
    echo html_writer::table($themes_table);
}

echo '<a id="add_theme_link" href="add_theme.php">Create a new theme</a>';
echo '<br><a id="import_theme_link" href="import_theme.php">Import a theme</a>';
echo '<br><a id="manage_notification_link" href="manage_notification.php">Manage notifications</a>';


// add the template-list form
$theme_lib = new theme_customizer();
$data      = $theme_lib->load_theme_data(array('theme_shortname' => theme_customizer::template_shortname),
                                         array('theme_css_file', 'theme_css_entry'));
$template  = array_shift($data);

echo '<br><br><h2>Manage template entries</h2>';

$entry_table = new html_table();

// header row
$header_row = new html_table_row();
$header_row->attributes['class'] = 'list_header';
$header_row->cells[] = new html_table_cell('Identifier');
$header_row->cells[] = new html_table_cell('Description');
$header_row->cells[] = new html_table_cell('CSS file');

$entry_table->data[] = $header_row;

$has_data = false;    // to decide whether to print out the table

foreach ($template['css_files'] as $file) {
    foreach ($file['entries'] as $entry) {
        $row = new html_table_row();
        $row->cells[] = new html_table_cell($entry['css_identifier']);
        $row->cells[] = new html_table_cell($entry['description']);
        $row->cells[] = new html_table_cell($file['name']);
        $row->cells[] = new html_table_cell('<a href="edit_template.php?entry_id='.$entry['id'].'">edit</a>');
        $row->cells[] = new html_table_cell('<a href="admin.php?action=delete_template_entry&entry_id='.$entry['id'].'">delete</a>');

        $entry_table->data[] = $row;
        $has_data = true;
    }
}

if ($has_data) {
    echo html_writer::table($entry_table);
}

echo '<a id="add_theme_link" href="edit_template.php?entry_id=0">Create a new template entry</a>';


echo $OUTPUT->footer();

