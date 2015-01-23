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
$PAGE->set_title("Add theme owner");
$PAGE->set_heading($SITE->fullname);

$return_url = new moodle_url('/blocks/theme_customizer/add_owner.php?theme_id=' . $theme_id);
$PAGE->set_url($return_url);

$sitecontext = context_system::instance();


if (!has_capability('block/theme_customizer:manage', $sitecontext)) {
    print_error('nopermissions', 'error', '', 'manage theme customizer');
}

$theme_lib = new theme_customizer();

// setup navigation
$theme_lib->setup_navbar('add_owner', $theme_id);

$data = $theme_lib->load_theme_data(array('theme_id' => $theme_id), array('theme_owner'));

if ($data == false) {
    print_error('invalidrecord', 'error', '', 'theme', "theme ID {$theme_id}");
}

$theme = $data[$theme_id];

// prevent editing the template theme
if ($theme['shortname'] == theme_customizer::template_shortname) {
    print_error('no_editing_template', 'block_theme_customizer');
}

$owner_form = new block_theme_customizer_add_owner_form(null, $theme);

if ($action != null) {
    if ($owner_form->is_cancelled()) {
        redirect('edit_theme.php?theme_id='.$theme_id);
    }

    // process form submission
    switch ($action) {
        case 'add_owner':
            // parse the usernames
            $form_data = $owner_form->get_data();

            $usernames = array_unique(preg_split(
                            "/[\s,;]+/",
                            trim(str_replace(array("\t","\r","\n",'"',"'"), ' ', $form_data->owner_usernames)),
                            -1,
                            PREG_SPLIT_NO_EMPTY));

            if (count($usernames) == 0) {
                print_error('no_username', 'block_theme_customizer');
            }

            // retrieve the user records
            $users = $DB->get_records_list('user', 'username', $usernames);

            // check if any of the username is invalid
            $username_map = array();
            foreach ($users as $user) {
                $username_map[$user->username] = $user->id;
            }

            foreach ($usernames as $username) {
                if (!isset($username_map[$username])) {
                    print_error('invalidrecord', 'error', '', "user: {$username}", "username: {$username}");
                }
            }

            // add the owners
            foreach ($username_map as $username => $user_id) {
                // skip existing owners
                if (isset($theme['owners'][$user_id])) {
                    continue;
                }

                $owner = new stdClass();
                $owner->theme_id     = $theme_id;
                $owner->user_id      = $user_id;
                $owner->edit_level   = 3;

                $DB->insert_record('block_theme_owner', $owner);
            }

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
    $PAGE->set_title("Add theme owner: {$theme['fullname']}");

    echo $OUTPUT->header();
    echo "<h2 id=\"add_theme_owner_title\">{$theme['fullname']}: add owner</h2>";

    $owner_form->display();

    echo $OUTPUT->footer();
}
