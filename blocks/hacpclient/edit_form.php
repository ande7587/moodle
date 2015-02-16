<?php

require_once($CFG->dirroot.'/blocks/hacpclient/lib.php');

class block_hacpclient_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text',
                           'config_title',
                           get_string('blocktitle', 'block_hacpclient'));
        $mform->setDefault('config_title', '');
        $mform->setType('config_title', PARAM_MULTILANG);
        $mform->addHelpButton('config_title', 'blocktitle', 'block_hacpclient');

        $roles = get_default_enrol_roles(context_system::instance());

        $mform->addElement('select', 'config_roleid', get_string('selectrole', 'block_hacpclient'), $roles);
        $default_enrollment_role = hacpclient_default_enrollment_role();
        $mform->setDefault('config_roleid', $default_enrollment_role->id);
        $mform->addHelpButton('config_roleid', 'selectrole', 'block_hacpclient');

        $mform->addElement('select',
                           'config_completetrigger',
                           get_string('completetrigger', 'block_hacpclient'),
                           array(HACP_COMPLETE_NONE=>get_string('completetriggernone', 'block_hacpclient'),
                                 HACP_COMPLETE_COMPLETION=>get_string('completetriggercompletion', 'block_hacpclient'),
                                 HACP_COMPLETE_VIEW=>get_string('completetriggerview', 'block_hacpclient')));
        $mform->setDefault('config_completetrigger', hacpclient_default_complete_trigger());
        $mform->addHelpButton('config_completetrigger', 'completetrigger', 'block_hacpclient');

        // For the aupassword and cmipassword, we don't allow double quotes because they complicate passing
        // the value in a CSV format, which is the format of the .au file.  We also do not allow square
        // brackets because we can't use them in free-form fields and that's where the cmipassword will end up.
        $mform->registerRule('nospacesdblquotessquares', 'regex', '/^[^\s\[\]\"]*$/');
        $passwordmaxlength = 255;

        // AU password.  This is the password that the AU provides passes to the CMI for authorization.  It
        //               is called for in the specifications.
        $mform->addElement('passwordunmask', 'config_aupassword', get_string('aupassword', 'block_hacpclient'));
        $mform->addRule('config_aupassword',
                        get_string('nospacesdblquotessquares', 'block_hacpclient'),
                        'nospacesdblquotessquares');
        $mform->addRule('config_aupassword',
                        get_string('maxlength', 'block_hacpclient', $passwordmaxlength),
                        'maxlength',
                        $passwordmaxlength);
        $mform->addHelpButton('config_aupassword', 'aupassword', 'block_hacpclient');

        // CMI password.  This is the password that the CMI passes to the Moodle AU for authorization.  It is not
        //                part of the AICC specification.
        $mform->addElement('passwordunmask', 'config_cmipassword', get_string('cmipassword', 'block_hacpclient'));
        $mform->addRule('config_cmipassword',
                        get_string('nospacesdblquotessquares', 'block_hacpclient'),
                        'nospacesdblquotessquares');
        $mform->addRule('config_cmipassword',
                        get_string('maxlength', 'block_hacpclient', $passwordmaxlength),
                        'maxlength',
                        $passwordmaxlength);
        $mform->addHelpButton('config_cmipassword', 'cmipassword', 'block_hacpclient');

    }

}

