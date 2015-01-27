<?php

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

class configutil_uploadconfig_form extends moodleform {

    function definition() {
        $mform = $this->_form;

        $mform->addElement('filepicker',
                           'configfile',
                           get_string('referenceconfigfile', 'local_configutil'));

        $mform->addElement('submit',
                           'submitbutton',
                           get_string('uploadconfig', 'local_configutil'));
    }

}


