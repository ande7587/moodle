<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * simple form to enrol list of x500s into a course
 */
class local_enrol_bulk_enrol_form extends moodleform {
    /**
     * form definition
     * @see moodleform::definition()
     */
    function definition () {
        global $DB;

        // retrieve the bulk limit
        $bulk_limit = get_config('local/user', 'bulk_limit');    // how many usernames can be submitted at once

        if (!$bulk_limit)
            $bulk_limit = 1000;    // fall-back default value if no config found

        // build the form
        $mform = & $this->_form;

        $mform->addElement('html', '<h2>' . get_string('input_header', 'local_enrol') . '</h2>');
        $mform->addElement('html', get_string('instruction',
                                              'local_enrol',
                                              array('limit' => number_format($bulk_limit))));

        // x500s input
        $mform->addElement('textarea', 'x500s', get_string('x500_input', 'local_enrol'),
                           array('rows' => '20', 'cols' => '60', 'wrap' => 'virtual'));
        $mform->setType('x500s', PARAM_TEXT);
        $mform->addRule('x500s', null, 'required', null, 'client');

        // course ID input
        $mform->addElement('text', 'course_id', get_string('course_id_input', 'local_enrol'));
        $mform->setType('course_id', PARAM_TEXT);
        $mform->addRule('course_id', null, 'required', null, 'client');

        // get the list of allowed roles from config
        $roleids = get_config('local_enrol', 'allowed_bulkenrol_roles');
        $roles = $DB->get_records_select('role', "id in ($roleids)"); 
        $rolemenu = role_fix_names($roles, null, ROLENAME_ALIAS, true); 

        // Set student role as default.
        $default_role_id = 0;
        foreach ($roles as $role) {
            if ($role->shortname == 'student')
                $default_role_id = $role->id;
        }

        $mform->addElement('select', 'role_id', get_string('role_id_input', 'local_enrol'), $rolemenu);
        $mform->setType('role_id', PARAM_INT);
        $mform->setDefault('role_id', $default_role_id);

        // submit button
        $this->add_action_buttons(false, get_string('submit_bulk_enrol', 'local_enrol'));
    }


    /**
     * form validation
     * @see moodleform::validation()
     */
    function validation($data, $files) {
        global $DB;

        $errors= array();

        if (!$DB->get_record('course', array('id' => $data['course_id']))) {
            $errors['course_id'] = get_string('e_course_not_found', 'local_enrol');
            return $errors;
        }

        // verify enrolment instance for the course
        $enrol_instances = enrol_get_instances($data['course_id'], true);

        $manual_instance_found = false;
        foreach ($enrol_instances as $instance) {
            if ($instance->enrol == 'manual') {
                $manual_instance_found = true;
                break;
            }
        }

        if ($manual_instance_found == false) {
            $errors['course_id'] = get_string('e_course_no_manual_instance', 'local_enrol');
            return $errors;
        }

        // get the list of allowed roles from config
        $allowed_roles = explode(',', get_config('local_enrol', 'allowed_bulkenrol_roles'));

        // verify the role
        if (!in_array($data['role_id'], $allowed_roles)) {
            $errors['role_id'] = get_string('e_role_not_allowed', 'local_enrol');
        }

        return $errors;
    }
}
