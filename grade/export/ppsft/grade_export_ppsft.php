<?php

require_once($CFG->dirroot.'/grade/export/lib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once('ppsft_grade_export_iterator.php');

/**
 * Overriding csv_export_writer so that we can adjust file naming.
 */
class ppsft_csv_export_writer extends csv_export_writer {

    /**
     * Set the filename for the uploaded csv file.  Overridden from base class.
     *
     * @param string $dataname    The name of the module.
     * @param string $extension  File extension for the file.
     */
    public function set_filename($dataname, $extension = '.csv') {
        $filename = clean_filename($dataname);
        $filename .= clean_filename('-' . gmdate("Ymd_Hi"));
        $filename .= $extension;
        $this->filename = $filename;
    }

}

class grade_export_ppsft extends grade_export {

    public $plugin = 'ppsft';

    public $separator = 'comma';

    public $includeheaders;
    public $includename;
    public $includegradingbasis;
    public $ppsftclassid;

    /**
     * Constructor should set up all the private variables ready to be pulled
     * @param object $course
     * @param int $ppsftclassid of selected ppsft section, 0 means all
     * @param string $itemlist comma separated list of item ids, empty means all
     */
    public function __construct($course, $formdata){
        parent::__construct($course, 0, $formdata);
        $this->includeheaders = $formdata->includeheaders;
        $this->includename = $formdata->includename;
        $this->includegradingbasis = $formdata->includegradingbasis;
        $this->ppsftclassid = $formdata->ppsftclassid;
    }

    /**
     * Init object based using data from form
     * @param object $formdata
     */
    function process_form($formdata) {
        parent::process_form($formdata);

        // The following are assumed to be in the form, so not testing with isset.
        $this->includeheaders = $formdata->includeheaders;
        $this->includename = $formdata->includename;
        $this->includegradingbasis = $formdata->includegradingbasis;
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

    /**
     * Returns array of parameters used by dump.php and export.php.
     * Overridden from base.
     * @return array
     */
    public function get_export_params() {
        $itemids = array_keys($this->columns);
        $itemidsparam = implode(',', $itemids);
        if (empty($itemidsparam)) {
            $itemidsparam = '-1';
        }

        # TODO: Which do we no longer need?
        $params = array('id'                =>$this->course->id,
                        'ppsftclassid'      =>$this->ppsftclassid,
                        'itemids'           =>$itemidsparam,
                        'export_letters'    =>$this->export_letters,
                        'includeheaders'    =>$this->includeheaders,
                        'includename'       =>$this->includename,
                        'includegradingbasis' =>$this->includegradingbasis
                        );

        return $params;
    }

    private function get_date_string($time) {
        $datestr = $time ? strftime('%m/%d/%Y', $time) : '';
        return $datestr;
    }
    public function print_grades() {
        global $CFG;

        $downloadfilename = $this->get_download_filename();
        $csvexport = new ppsft_csv_export_writer($this->separator);
        $csvexport->set_filename($downloadfilename);

        if ($this->includeheaders) {

            // Print names of all the fields
            $exporttitle = array();

            $exporttitle[] = get_string('columnidnumber', 'gradeexport_ppsft');

            // Should be just the course total for this report
            // but keeping the loop for now.
            foreach ($this->columns as $grade_item) {
                // Looping over displaytype as other export (e.g., txt) do, but in
                // our case it will likely always be letter displaytype.
                foreach ($this->displaytype as $gradedisplayname => $gradedisplayconst) {
                    $exporttitle[] = $this->format_column_name($grade_item, false, $gradedisplayname);
                }
            }

            $exporttitle[] = get_string('lastaccess', 'gradeexport_ppsft');

            if ($this->includename) {
                $exporttitle[] = get_string('columnname', 'gradeexport_ppsft');
            }

            if ($this->includegradingbasis) {
                $exporttitle[] = get_string('columngradingbasis', 'gradeexport_ppsft');
            }

            $csvexport->add_data($exporttitle);
        }

        // Print all the lines of data.
        $gui = new ppsft_grade_export_iterator($this->course, $this->columns, $this->ppsftclassid);
        $gui->init();
        while ($userdata = $gui->next_user()) {

            $exportdata = array();
            $user = $userdata->user;

            $exportdata[] = $user->idnumber;

            foreach ($userdata->grades as $itemid => $grade) {
                // Looping over displaytype as other export (e.g., txt) do, but in
                // our case it will likely always be letter displaytype.
                foreach ($this->displaytype as $gradedisplayconst) {
                    $exportdata[] = $this->format_grade($grade, $gradedisplayconst);
                }
            }
            $exportdata[] = $this->get_date_string($user->last_access);

            if ($this->includename) {
                $exportdata[] = "{$user->lastname}, {$user->firstname}";
            }

            if ($this->includegradingbasis) {
                $exportdata[] = $user->ppsft_grading_basis;
            }

            $csvexport->add_data($exportdata);
        }
        $gui->close();
        $csvexport->download_file();
        exit;
    }
    /**
     *
     */
    private function get_download_filename() {
        global $DB;

        $sql =<<<SQL
select
    concat(pc.term, pc.institution, pc.class_nbr, '_', pc.subject, '_',
              pc.catalog_nbr, '_', pc.section) as basename
from {ppsft_classes} pc
where pc.id = :ppsftclassid
SQL;

        $basename = $DB->get_field_sql($sql, array('ppsftclassid' => $this->ppsftclassid));

        // The csv_export_writer will add the suffix to the filename.
        return $basename;
    }

    /**
     *
     */
    private function get_graded_ppsft_classes() {
        return static::get_graded_ppsft_classes_for_course($this->course->id);
    }

    public static function get_graded_ppsft_classes_for_course($courseid) {
        global $DB;

        $sql =<<<SQL
select
    pc.id,
    pc.subject,
    pc.catalog_nbr,
    pc.section
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


