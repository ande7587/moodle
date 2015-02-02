<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once $CFG->libdir.'/formslib.php';

class ppsft_grade_export_form extends moodleform {
    function definition() {
        global $CFG, $COURSE, $USER, $DB;

        $mform =& $this->_form;
        $classes = $this->_customdata['classes'];

        $mform->addElement('header', 'options', get_string('options', 'grades'));

        // Must use only letters for this report.  The value from this element is not actually used anywhere.
        $options = array(GRADE_DISPLAY_TYPE_LETTER     => get_string('letter', 'grades'));
        $mform->addElement('select', 'display[letter]', get_string('gradeexportdisplaytype', 'grades'), $options);
        $mform->setDefault('display[letter]', $CFG->grade_export_displaytype == GRADE_DISPLAY_TYPE_LETTER);

        $mform->addElement('static',
                           'ppsftexportformat',
                           get_string('ppsftexportformat', 'gradeexport_ppsft'),
                           get_string('csv', 'gradeexport_ppsft'));

        $mform->addElement('advcheckbox', 'includeheaders', get_string('includeheaders', 'gradeexport_ppsft'));

        $radio = array();
        $courseIdentifier = null;
        if(!empty($classes)){
            foreach ($classes as $ppsftclass) {
                $courseIdentifier = $ppsftclass->subject . ' ' . $ppsftclass->catalog_nbr . ' ' . $ppsftclass->section;
                $radio[] =& $mform->createElement('radio', 'ppsftclassid', '', $courseIdentifier, $ppsftclass->id);
            }
            //set the first class as default
            $mform->setDefault('ppsftclassid', array_values($classes)[0]->id);
        }
        $mform->addGroup($radio, 'ppsftclassid','Class', ' ', false);

        $mform->addElement('header', 'columnstoinclude', get_string('columnstoinclude', 'gradeexport_ppsft'));

        $mform->addElement('advcheckbox', 'includeidnumber', get_string('columnidnumber', 'gradeexport_ppsft'));
        $mform->addElement('advcheckbox', 'includegrade', get_string('columngrade', 'gradeexport_ppsft'));
        $mform->addElement('advcheckbox', 'includelastaccess', get_string('columnlastaccess', 'gradeexport_ppsft'));
        $mform->addElement('advcheckbox', 'includename', get_string('columnname', 'gradeexport_ppsft'));
        $mform->addElement('advcheckbox', 'includegradingbasis', get_string('columngradingbasis', 'gradeexport_ppsft'));

        // We do not actually use the value from the idnumber, grade, and lastaccess
        // checkboxes.  The PeopleSoft should always include those columns.  The
        // purpose of the checkboxes is to make that clear to the user.
        $mform->setDefault('includeidnumber', 1);
        $mform->setDefault('includegrade', 1);
        $mform->setDefault('includelastaccess', 1);
        $mform->freeze('includeidnumber');
        $mform->freeze('includegrade');
        $mform->freeze('includelastaccess');

        $mform->addElement('hidden', 'id', $COURSE->id);
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons(false, get_string('download'));

    }
}

