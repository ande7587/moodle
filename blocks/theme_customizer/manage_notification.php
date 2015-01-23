<?php

require(realpath(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))).'/config.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/forms.php');
require_once($CFG->dirroot.'/blocks/theme_customizer/lib.php');

$site = get_site();
require_login();

$action   = optional_param('action', null, PARAM_TEXT);

$PAGE->set_context(null); // hack - set context to something, by default to system context
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Manage Notifications");
$PAGE->set_heading($SITE->fullname);

$return_url = new moodle_url('/blocks/theme_customizer/manage_notification.php');
$PAGE->set_url($return_url);

$sitecontext = context_system::instance();
require_capability('block/theme_customizer:manage', $sitecontext);

$theme_lib = new theme_customizer();

// setup navigation
$theme_lib->setup_navbar('manage_notification');

// process action
if ($action != null) {
    switch ($action) {
        case 'update_notification':
            // parse the submitted data
            $themes = $DB->get_records('block_theme');

            $form = new block_theme_customizer_edit_notification_form(null, $themes);
            $form_data = $form->get_data();


            // update the themes accordingly
            foreach ($themes as $theme) {
                if ($theme->shortname == theme_customizer::template_shortname) {
                    continue;    // ignore template
                }

                $field_name = 'theme_notify__' . $theme->id;

                if (isset($form_data->$field_name) && $form_data->$field_name == theme_customizer::notify_yes) {
                    $theme->notify = theme_customizer::notify_yes;
                }
                else {
                    $theme->notify = theme_customizer::notify_no;
                }

                $DB->update_record('block_theme', $theme);
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

$themes = $DB->get_records('block_theme');

echo '<h2>Manage theme notification</h2>';
echo 'To edit the lis of recipients, go to the <a target="_blank" href="',
     $CFG->wwwroot,'/admin/settings.php?section=blocksettingtheme_customizer">Theme Customizer block setting</a>';

$form = new block_theme_customizer_edit_notification_form(null, $themes);
$form->display();

echo $OUTPUT->footer();

