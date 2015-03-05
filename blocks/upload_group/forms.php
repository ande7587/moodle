<?php

defined('MOODLE_INTERNAL') || exit();

require_once($CFG->libdir.'/formslib.php');

/**
 * upload a CSV file for importing
 */
class block_upload_group_upload_form extends moodleform {

    function definition () {

        $mform = & $this->_form;
        $data = $this->_customdata;

        $mform->addElement('hidden', 'action', 'upload_group_data');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'id', $data['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('html', get_string('upload_help', 'block_upload_group'));
        $mform->addElement('header', 'upload_group_data', get_string('upload_group_data', 'block_upload_group'));

        // add a file manager
        $mform->addElement('filepicker', 'group_data', '');

        // add the encoding option
        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'block_upload_group'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        // add the delimiter option
        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter', get_string('delimiter', 'block_upload_group'), $choices);

        if (array_key_exists('cfg', $choices)) {

            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {

            $mform->setDefault('delimiter_name', 'semicolon');
        } else {

            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $mform->addElement('select', 'preview_num', get_string('row_preview_num', 'block_upload_group'), $choices);
        $mform->setType('preview_num', PARAM_INT);

        $this->add_action_buttons(true, get_string('submit_group_data', 'block_upload_group'));
    }
}

/**
 * display list of roles to choose from
 */
class block_upload_group_confirm_form extends moodleform {

    function definition () {

        global $DB;

        $mform = & $this->_form;
        $data = $this->_customdata;

        $mform->addElement('hidden', 'action', 'process_group_data');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'id', $data['id']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'iid', $data['iid']);
        $mform->setType('iid', PARAM_INT);

        // get the available bulk enrolment roles
        $roleids = get_config('local_enrol', 'allowed_bulkenrol_roles');
        $roles = $DB->get_records_select('role', "id in ($roleids)");
        $rolemenu = role_fix_names($roles, null, ROLENAME_ALIAS, true);

        // Set student role as default.
        $default_role_id = 0;
        foreach ($roles as $role) {

            if ($role->shortname == 'student') {

                $default_role_id = $role->id;

            }
        }

        // add the role option
        $mform->addElement('select', 'role', get_string('role_desc', 'block_upload_group'), $rolemenu);
        $mform->setType('role', PARAM_INT);
        $mform->setDefault('role', $default_role_id);

        $this->add_action_buttons(true, get_string('process_group_data', 'block_upload_group'));
    }
}
