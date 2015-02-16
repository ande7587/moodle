<?php

require_once($CFG->dirroot.'/grade/export/lib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once('ppsftlink_grade_export_iterator.php');

// The grade export is atypical in that it does not produce a file for download.
// Instead, its intended use is to allow the user to post grades directly to
// a PeopleSoft grading page.

class grade_export_ppsftlink extends grade_export {

    public $plugin = 'ppsftlink';
    // Selected ppsft section, 0 means all
    public $ppsftclassid;

    /**
     * Constructor should set up all the private variables ready to be pulled
     * @param object $course
     * @param stdClass moodle form datC
     */
    public function __construct($course, $formdata) {

        // For the usual kind of grade_export, $itemlist is a constructor parameter. In
        // this case, we set it to be just the course item.
        $courseitem = grade_item::fetch_course_item($course->id);
        $itemlist = $courseitem->id;

        parent::__construct($course, 0, $formdata);
        $this->ppsftclassid = $formdata->ppsftclassid;
    }

    /**
     * If the grade is null, we want an empty string rather than the hyphen
     * that the parent implementation provides.
     */
    public function format_grade($grade, $gradedisplayconst = null) {
        if (is_null($grade->finalgrade)) {
            return '';
        }
        return parent::format_grade($grade, $gradedisplayconst);
    }

    /** Unused from base. */
    public function get_export_params() {
        throw new Exception("In ppsftlink grade export get_export_params.");
    }

    /**
     * Prints preview of exported grades on screen as a feedback mechanism
     * Overridden from base.
     * @param bool $ignored_paramater  Ignored because idnumber always included.
     */
    public function display_preview($ignored_parameter=true) {
        global $OUTPUT;

        // $this->ppsftclassid must be set at this point because this renders for a
        // specific PeopleSoft class.
        if (empty($this->ppsftclassid)) {
            throw new Exception("ppsftclassid not set in display_preview");
        }

        // TODO: Consider query for specific class if performance is an issue.
        $ppsftclasses = enrol_umnauto_get_course_ppsft_classes($this->course->id);
        $ppsftclass = $ppsftclasses[$this->ppsftclassid];
        if (empty($ppsftclass)) {
            throw new Exception("Invalid ppsftclassid in display_preview");
        }

        echo $OUTPUT->container_start('ppsftlinkgradeexportinstructions');
        echo get_string('previewpageinstructions', 'gradeexport_ppsftlink');
        echo $OUTPUT->container_end();

        echo '<h4>'.$ppsftclass->subject.' '.$ppsftclass->catalog_nbr.' '.$ppsftclass->section.'</h4>';

        $this->render_table_and_grade_post_form($ppsftclass);
    }

    public function render_table_and_grade_post_form($ppsftclass) {

        $gradetable = '<table id="ppsftlinkgradepreviewtable">';

        /// Print all the lines of data and build XML along the way.
        $gradexml = new SimpleXMLElement('<gradeData></gradeData>');

        $i = 0;

        $gui = new ppsftlink_grade_export_iterator($this->course, $this->columns, $this->ppsftclassid, null);
        $gui->init();
        while ($userdata = $gui->next_user()) {

            $studentxml = $gradexml->addChild('studentGrade');

            // number of preview rows
            if ($this->previewrows and $this->previewrows <= $i) {
                break;
            }
            $user = $userdata->user;
            // Always require idnumber.
            if (empty($user->idnumber)) {
                // some exports require user idnumber so we can match up students when importing the data
                continue;
            }

            $studentxml->addChild('emplid', $user->idnumber);

            $rowstr = '';
            ### TODO: No point in iterating here since we should have only one grade, the course total.
            foreach ($userdata->grades as $itemid => $grade) {
                // Looping over displaytype as other export (e.g., txt) do, but in
                // our case it will likely always be letter displaytype.
                foreach ($this->displaytype as $gradedisplayconst) {
                   $gradetxt  = $this->format_grade($grade, $gradedisplayconst);
                   $rowstr .= "<td>$gradetxt</td>";
                }
            }

            $studentxml->addChild('grade', $gradetxt);

            $lastaccess = $user->last_access ? strftime('%m/%d/%Y', $user->last_access) : '';

            $gradetable .= "<tr><td>{$user->idnumber}</td>$rowstr<td>$lastaccess</td></tr>\n";

            $studentxml->addChild('lastParticipationDate',
                                  $user->last_access ? strftime('%Y-%m-%d', $user->last_access) : '');

            $i++; // increment the counter
        }
        $gradetable .= '</table>';
        $gui->close();

        $gradedom = new DOMDocument('1.0');
        $gradedom->preserveWhiteSpace = false;
        $gradedom->formatOutput = true;
        $gradedom->loadXML($gradexml->asXML());
        #echo 'XML: <xmp>'. $gradedom->saveXML().'</xmp>';

        // Note dependency on local/ppsft configuration setting.
        $ppsftgradeentryurl = trim(get_config('local_ppsft', 'gradelinkgradeposturl'));;
        $ppsftform = html_writer::start_tag('form',
                                            array('action' => new moodle_url($ppsftgradeentryurl),
                                                  'id' => 'ppsftlinkgradeform',
                                                  'method' => 'post'));

        $ppsftsubmitbutton = html_writer::tag(
                               'input',
                               '',
                               array('type' => 'submit',
                                     'value' => get_string('submittopeoplesoft', 'gradeexport_ppsftlink')));
        $ppsftform .= $ppsftsubmitbutton;

        $ppsftform .= $gradetable;

        $ppsftform .= html_writer::empty_tag('input',
                                             array('type' => 'hidden',
                                                   'name' => 'institution',
                                                   'value' => $ppsftclass->institution));
        $ppsftform .= html_writer::empty_tag('input',
                                             array('type' => 'hidden',
                                                   'name' => 'strm',
                                                   'value' => $ppsftclass->term));
        $ppsftform .= html_writer::empty_tag('input',
                                             array('type' => 'hidden',
                                                   'name' => 'classNbr',
                                                   'value' => $ppsftclass->class_nbr));
        $ppsftform .= html_writer::empty_tag('input',
                                             array('type' => 'hidden',
                                                   'name' => 'gradeData',
                                                   'value' => $gradedom->saveXML()));
        $ppsftform .= $ppsftsubmitbutton;

        $ppsftform .= html_writer::end_tag('form');
        echo $ppsftform;
    }

    /** Unused from base. */
    public function print_grades() {
        throw new Exception("In ppsftlink grade export print_grades.");
    }

    /** Unused from base. */
    public function print_continue() {
        throw new Exception("In ppsftlink grade export print_continue.");
    }

    public function get_graded_ppsft_class_grading_links() {
        $ppsftclasses = $this->get_graded_ppsft_classes();
        $links = array();

        // Note dependency on local/ppsft configuration setting.
        $linkurl = trim(get_config('local_ppsft', 'gradelinkaccessurl'));

        foreach ($ppsftclasses as $ppsftclass) {
            $callbackparams = array();
            $callbackparams['ppsftclassid'] = $ppsftclass->id;
            $callbackparams['id'] = $this->course->id;
            $linktext = $ppsftclass->subject . ' ' . $ppsftclass->catalog_nbr . ' ' . $ppsftclass->section;
            $callback = new moodle_url('/grade/export/'.$this->plugin.'/index.php', $callbackparams);

            $params = array();
            $params['strm'] = $ppsftclass->term;
            $params['institution'] = $ppsftclass->institution;
            $params['classNbr'] = $ppsftclass->class_nbr;
            $params['callback'] = $callback->out(false);

            $links[] = html_writer::link(new moodle_url($linkurl, $params),
                                         $linktext,
                                         array('target' => '_blank'));
        }
        return $links;
    }

    /**
     *
     */
    private function get_graded_ppsft_classes() {
        return static::get_graded_ppsft_classes_for_course($this->course->id);
    }

    /**
     *
     */
    public static function get_graded_ppsft_classes_for_course($courseid) {
        global $DB;

        $sql =<<<SQL
select
    pc.id,
    pc.subject,
    pc.catalog_nbr,
    pc.section,
    pc.term,
    pc.institution,
    pc.class_nbr
from {enrol} e
  join {enrol_umnauto_classes} uc on e.id=uc.enrolid
  join {ppsft_classes} pc on pc.id=uc.ppsftclassid
where e.courseid = :courseid and
      exists (select id from {ppsft_class_enrol} pce
              where pce.ppsftclassid=pc.id and
                    pce.status='E' and
                    pce.grading_basis not in ('NON', 'NGA'))
order by pc.term, pc.institution, pc.subject, pc.catalog_nbr, pc.section
SQL;

        return $DB->get_records_sql($sql, array('courseid' => $courseid));
    }
}


