<?php

/**
 * The question type class for the matrix question type.
 *
 * @copyright   2012 University of Geneva
 * @author      laurent.opprecht@unige.ch
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package     qtype
 * @subpackage  matrix
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__)) . '/qtype_matrix_grading.class.php';

// renderer for the whole question - needs a matching class
// see matrix_qtype::matrix_renderer_options
//define('QTYPE_MATRIX_RENDERER_MATRIX', 'matrix');

/**
 * The matrix question class
 *
 * Pretty simple concept - a matrix with a number of different grading methods and options.
 */
class qtype_matrix extends question_type
{

    public static function get_string($identifier, $component = 'qtype_matrix', $a = null)
    {
        return get_string($identifier, $component, $a);
    }

    public static function gradings()
    {
        return qtype_matrix_grading::gradings();
    }

    public static function grading($type)
    {
        return qtype_matrix_grading::create($type);
    }

    public static function defaut_grading()
    {
        return qtype_matrix_grading::default_grading();
    }

//    public static function matrix_renderer_options()
//    {
//        return array(
//            QTYPE_MATRIX_RENDERER_MATRIX => get_string('matrix', 'qtype_matrix'),
//        );
//    }

    function name()
    {
        return 'matrix';
    }

    /**
     * Deletes question from the question-type specific tables
     *
     * @param integer $questionid The question being deleted
     * @return boolean to indicate success of failure.
     */
    function delete_question_options($questionid, $contextid = null)
    {
        if (empty($questionid))
        {
            return false;
        }

        global $DB;
        global $CFG;

        $prefix = $CFG->prefix;

        //wheights
        $sql = "DELETE FROM {$prefix}question_matrix_weights
                WHERE {$prefix}question_matrix_weights.rowid IN
                      (
                      SELECT rows.id FROM {$prefix}question_matrix_rows  AS rows
                      INNER JOIN {$prefix}question_matrix      AS matrix ON rows.matrixid = matrix.id
                      WHERE matrix.questionid = $questionid
                      )";
        $DB->execute($sql);

        //rows
        $sql = "DELETE FROM {$prefix}question_matrix_rows
                WHERE {$prefix}question_matrix_rows.matrixid IN
                      (
                      SELECT matrix.id FROM {$prefix}question_matrix AS matrix
                      WHERE matrix.questionid = $questionid
                      )";
        $DB->execute($sql);

        //cols
        $sql = "DELETE FROM {$prefix}question_matrix_cols
                WHERE {$prefix}question_matrix_cols.matrixid IN
                      (
                      SELECT matrix.id FROM {$prefix}question_matrix AS matrix
                      WHERE matrix.questionid = $questionid
                      )";
        $DB->execute($sql);

        //matrix
        $sql = "DELETE FROM {$prefix}question_matrix WHERE questionid = $questionid";
        $DB->execute($sql);

        return true;
    }

    /**
     * Deletes question from the question-type specific tables
     *
     * @param integer $questionid The question being deleted
     * @return boolean to indicate success of failure.
     */
    function delete_question($questionid, $contextid = null)
    {
        if (empty($questionid))
        {
            return false;
        }


        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $this->delete_question_options($questionid);
        parent::delete_question($questionid, $contextid);

        $transaction->allow_commit();

        return true;
    }

    /**
     * @return boolean true if this question type sometimes requires manual grading.
     */
    function is_manual_graded()
    {
        return true;
    }

    /*
     * @return boolean to indicate success of failure.
     */
    function get_question_options($question)
    {
        parent::get_question_options($question);
        $matrix = self::retrieve_matrix($question->id);
        if ($matrix)
        {
            $question->options->rows = $matrix->rows;
            $question->options->cols = $matrix->cols;
            $question->options->weights = $matrix->weights;
            $question->options->grademethod = $matrix->grademethod;
            $question->options->multiple = $matrix->multiple;
            $question->options->renderer = $matrix->renderer;
        }
        else
        {
            $question->options->rows = array();
            $question->options->cols = array();
            $question->options->weights = array(array());
            $question->options->grademethod = self::defaut_grading()->get_name();
            $question->options->multiple = true;
        }
        return true;
    }

    static function retrieve_matrix($question_id)
    {
        if (empty($question_id))
        {
            return null;
        }

        static $results = array();
        if (isset($results[$question_id]))
        {
            return $results[$question_id];
        }

        global $DB;
        $matrix = $DB->get_record('question_matrix', array('questionid' => $question_id));

        if (empty($matrix))
        {
            return false;
        }
        else
        {
            $matrix->multiple = (bool) $matrix->multiple;
        }
        $matrix->rows = $DB->get_records('question_matrix_rows', array('matrixid' => $matrix->id), 'id ASC');
        $matrix->rows = $matrix->rows ? $matrix->rows : array();

        $matrix->cols = $DB->get_records('question_matrix_cols', array('matrixid' => $matrix->id), 'id ASC');
        $matrix->cols = $matrix->cols ? $matrix->cols : array();

        global $CFG;
        $prefix = $CFG->prefix;
        $sql = "SELECT weights.*
                FROM {$prefix}question_matrix_weights AS weights
                WHERE
                    rowid IN (SELECT rows.id FROM {$prefix}question_matrix_rows     AS rows
                              INNER JOIN {$prefix}question_matrix                   AS matrix ON rows.matrixid = matrix.id
                              WHERE matrix.questionid = $question_id)
                    OR

                    colid IN (SELECT cols.id FROM {$prefix}question_matrix_cols     AS cols
                              INNER JOIN {$prefix}question_matrix                   AS matrix ON cols.matrixid = matrix.id
                              WHERE matrix.questionid = $question_id)
               ";
        $matrix->rawweights = $DB->get_records_sql($sql);

        $matrix->weights = array();

        foreach ($matrix->rows as $r)
        {
            $matrix->fullmatrix[$r->id] = array();
            foreach ($matrix->cols as $c)
            {
                $matrix->weights[$r->id][$c->id] = 0;
            }
        }
        foreach ($matrix->rawweights as $w)
        {
            $matrix->weights[$w->rowid][$w->colid] = (float)$w->weight;
        }
        $results[$question_id] = $matrix;
        return $matrix;
    }

    /**
     * Initialise the common question_definition fields.
     *
     * @param question_definition $question the question_definition we are creating.
     * @param object $questiondata the question data loaded from the database.
     */
    protected function initialise_question_instance(question_definition $question, $questiondata)
    {
        parent::initialise_question_instance($question, $questiondata);
        $question->rows = $questiondata->options->rows;
        $question->cols = $questiondata->options->cols;
        $question->weights = $questiondata->options->weights;
        $question->grademethod = $questiondata->options->grademethod;
        $question->multiple = $questiondata->options->multiple;
    }

    /**
     * Saves question-type specific options.
     * This is called by {@link save_question()} to save the question-type specific data.
     *
     * @param object $question This holds the information from the editing form, it is not a standard question object.
     * @return object $result->error or $result->noticeyesno or $result->notice
     */
    function save_question_options($question)
    {
        global $DB;
        //parent::save_question_options($question);

        $question_id = isset($question->id) ? $question->id : false;

        // pull existing matrix information
        $matrix = self::retrieve_matrix($question_id);
        $matrix_rows = $matrix && is_array($matrix->rows) ? array_values($matrix->rows) : array();    // strip the key to use the row order
        $matrix_cols = $matrix && is_array($matrix->cols) ? array_values($matrix->cols) : array();

        $row_ids = array();    // map from index to row IDs for updating the weights later
        $col_ids = array();

        $transaction = $DB->start_delegated_transaction();

        // update matrix
        $matrix_obj = (object) array(
                'questionid'  => $question->id,
                'multiple'    => $question->multiple,
                'grademethod' => $question->grademethod,
                'renderer'    => 'matrix'
        );

        if ($matrix) {
            $matrix_id = $matrix_obj->id = $matrix->id;
            $DB->update_record('question_matrix', $matrix_obj);
        }
        else {
            $matrix_id = $DB->insert_record('question_matrix', $matrix_obj, true);
        }

        // update existing rows
        $update_count = min(count($question->rowshort), count($matrix_rows));
        for ($i = 0; $i < $update_count; $i++) {
            $row = $matrix_rows[$i];
 
            // if the row is set to blank, delete it
            if (empty($question->rowshort[$i])) {
                $DB->delete_records('question_matrix_rows', array('id' => $row->id));

                // delete referecing weights too
                $DB->delete_records('question_matrix_weights', array('rowid' => $row->id));
                $row_ids[] = null;
            }
            else {
                $row->shorttext    = $question->rowshort[$i];
                $row->description  = $question->rowlong[$i];
                $row->feedback     = $question->rowfeedback[$i];

                $DB->update_record('question_matrix_rows', $row);
                $row_ids[] = $row->id;
            }
        }
 
        $diff_count = count($question->rowshort) - count($matrix_rows);

        // add new rows as needed
        if ($diff_count > 0) {
            for ($i = $update_count; $i < count($question->rowshort); $i++) {
                if (empty($question->rowshort[$i])) {
                    continue;
                }

                $row = (object) array(
                    'matrixid'     => $matrix_id,
                    'shorttext'    => $question->rowshort[$i],
                    'description'  => $question->rowlong[$i],
                    'feedback'     => $question->rowfeedback[$i]
                );

                $row_ids[] = $DB->insert_record('question_matrix_rows', $row);
            }
        }
 
        // delete old rows as needed
        if ($diff_count < 0) {
            for ($i = $update_count; $i < count($matrix_rows); $i++) {
                $DB->delete_records('question_matrix_rows', array('id' => $matrix_rows[$i]->id));

                // delete referecing weights too
                $DB->delete_records('question_matrix_weights', array('rowid' => $row[$i]->id));
            }
        }

        // update existing columns
        $update_count = min(count($question->colshort), count($matrix_cols));
        for ($i = 0; $i < $update_count; $i++) {
            $col = $matrix_cols[$i];

            // if the col is set to blank, delete it
            if (empty($question->colshort[$i])) {
                $DB->delete_records('question_matrix_cols', array('id' => $col->id));

                // delete referencing weights too
                $DB->delete_records('question_matrix_weights', array('colid' => $col->id));
                $col_ids[] = null;
            }
            else {
                $col->shorttext    = $question->colshort[$i];
                $col->description  = $question->collong[$i];

                $DB->update_record('question_matrix_cols', $col);
                $col_ids[] = $col->id;
            }
        }

        $diff_count = count($question->colshort) - count($matrix_cols);

        // add new cols as needed
        if ($diff_count > 0) {
            for ($i = $update_count; $i < count($question->colshort); $i++) {
                if (empty($question->colshort[$i])) {
                    continue;
                }

                $col = (object) array(
                    'matrixid'     => $matrix_id,
                    'shorttext'    => $question->colshort[$i],
                    'description'  => $question->collong[$i]
                );

                $col_ids[] = $DB->insert_record('question_matrix_cols', $col);
            }
        }

        // delete old cols as needed
        if ($diff_count < 0) {
            for ($i = $update_count; $i < count($matrix_cols); $i++) {
                $DB->delete_records('question_matrix_cols', array('id' => $matrix_cols[$i]->id));

                // delete referencing weights too
                $DB->delete_records('question_matrix_weights', array('colid' => $col[$i]->id));
            }
        }

        // map the existing weights record for easy access
        $existing_weights = array();
        if ($matrix) {
            foreach ($matrix->rawweights as $rec) {
                if (!isset($existing_weights[$rec->rowid])) {
                    $existing_weights[$rec->rowid] = array();
                }

                $existing_weights[$rec->rowid][$rec->colid] = $rec;
            }
        }

        // weights
        if ($question->multiple) {
            foreach ($row_ids as $row_index => $row_id) {
                foreach ($col_ids as $col_index => $col_id) {
                    // skip if the row or column has been deleted
                    if ($row_id == null || $col_id == null) {
                        continue;
                    }

                    $key   = qtype_matrix_grading::cell_name($row_index, $col_index, $question->multiple);
                    $value = isset($question->{$key}) ? $question->{$key} : 0;

                    if (!is_numeric($value)) {
                        $value = empty($value) ? 0 : 1;
                    }

                    // update existing weights
                    if (isset($existing_weights[$row_id][$col_id])) {
                        $weight = $existing_weights[$row_id][$col_id];

                        if ($matrix->weights[$row_id][$col_id] != $value) {
                            $weight->weight = $value;

                            $DB->update_record('question_matrix_weights', $weight);
                        }
                    }
                    else {
                        // insert new weights
                        $weight = (object) array(
                                    'rowid'  => $row_id,
                                    'colid'  => $col_id,
                                    'weight' => $value);

                        $DB->insert_record('question_matrix_weights', $weight);
                    }
                }
            }
        }
        else {
            foreach ($row_ids as $row_index => $row_id) {
                if ($row_id == null) {
                    continue;    // skip deleted rows
                }

                // submitted key only has row index, e.g. cell2
                $key = qtype_matrix_grading::cell_name($row_index, 0, $question->multiple);
                $selected_col_id = $col_ids[$question->{$key}];

                // update existing weights
                foreach ($col_ids as $col_index => $col_id) {
                    $value = $selected_col_id == $col_id ? 1 : 0;

                    if (isset($existing_weights[$row_id][$col_id])) {
                        $weight = $existing_weights[$row_id][$col_id];

                        if ($weight->weight != $value) {
                            $weight->weight = $value;

                            $DB->update_record('question_matrix_weights', $weight);
                        }
                    }
                    else {
                        $weight = (object) array(
                                    'rowid'  => $row_id,
                                    'colid'  => $col_id,
                                    'weight' => $value);

                        $DB->insert_record('question_matrix_weights', $weight);
                    }
                }
            }
        }

        $transaction->allow_commit();
    }

    /**
     * This method should be overriden if you want to include a special heading or some other
     * html on a question editing page besides the question editing form.
     *
     * @param question_edit_form $mform a child of question_edit_form
     * @param object $question
     * @param string $wizardnow is '' for first page.
     */
    public function display_question_editing_page($mform, $question, $wizardnow) {
        global $OUTPUT;
        $heading = $this->get_heading(empty($question->id));

        if (get_string_manager()->string_exists('pluginname_help', $this->plugin_name())) {
            echo $OUTPUT->heading_with_help($heading, 'pluginname', $this->plugin_name());
        } else {
            echo $OUTPUT->heading_with_help($heading, $this->name(), $this->plugin_name());
        }

        //Who cares about that:

//        $permissionstrs = array();
//        if (!empty($question->id)) {
//            if ($question->formoptions->canedit) {
//                $permissionstrs[] = get_string('permissionedit', 'question');
//            }
//            if ($question->formoptions->canmove) {
//                $permissionstrs[] = get_string('permissionmove', 'question');
//            }
//            if ($question->formoptions->cansaveasnew) {
//                $permissionstrs[] = get_string('permissionsaveasnew', 'question');
//            }
//        }
//        if (!$question->formoptions->movecontext  && count($permissionstrs)) {
//            echo $OUTPUT->heading(get_string('permissionto', 'question'), 3);
//            $html = '<ul>';
//            foreach ($permissionstrs as $permissionstr) {
//                $html .= '<li>'.$permissionstr.'</li>';
//            }
//            $html .= '</ul>';
//            echo $OUTPUT->box($html, 'boxwidthnarrow boxaligncenter generalbox');
//        }
        $mform->display();
    }
}
