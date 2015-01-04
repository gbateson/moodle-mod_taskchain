<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * mod/taskchain/report/tablelib.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/** Include parent classes (table_sql and flexible_table) */
require_once($CFG->dirroot.'/lib//tablelib.php');

/**
 * taskchain_report_table
 *
 * @copyright 2010 Gordon Bateson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class taskchain_report_table extends table_sql {

    /** @var reference to global $TC object */
    protected $TC = null;

    /** @var mod_taskchain_report_renderer for the current page */
    protected $output;

    /** @var string field in the attempt records that refers to the user id */
    public $useridfield = 'userid';

    /** @var date_strings */
    protected $date_strings = null;

    /** @var string time format used for the "timemodified" column */
    protected $timeformat = 'strftimerecentfull';

    /** @var string localized format used for the "timemodified" column */
    protected $strtimeformat;

    /** @var array list of distinct values stored in response columns */
    protected $legend = array();

    /** @var array names of table columns which are to be suppressed */
    protected $suppress_columns = array();

    /**
     * Constructor
     *
     * @param int $uniqueid
     * @param xxx $output (passed by reference)
     * @param xxx $TC (passed by reference)
     */
    function __construct($uniqueid, &$output, &$TC) {
        parent::__construct($uniqueid);
        $this->TC = &$TC;
        $this->output = &$output;
        $this->strtimeformat = get_string($this->timeformat);
    }

    /**
     * setup_report_table
     *
     * @param xxx $tablecolumns
     * @param xxx $baseurl
     * @param xxx $usercount (optional, default value = 10)
     */
    function setup_report_table($tablecolumns, $suppresscolumns, $baseurl, $usercount=10)  {

        // generate headers (using "header_xxx()" methods below)
        $tableheaders = array();
        foreach ($tablecolumns as $tablecolumn) {
            $tableheaders[] = $this->format_header($tablecolumn);
        }

        $this->define_columns($tablecolumns);
        $this->define_headers($tableheaders);
        $this->define_baseurl($baseurl);

        if ($this->has_column('fullname')) {
            $this->pageable(true);
            $this->sortable(true);
            $this->initialbars($usercount > 20);
            if ($this->has_column('selected')) {
                $this->no_sorting('selected');
            }
        } else {
            $this->pageable(false);
            $this->sortable(false);
        }

        // css class for each column is column name without leading
        // "chaingrade", "chainattempt", "taskscore", or "taskattempt"
        $search = '/^(?:chain|task)(?:grade|score|attempt)/';
        foreach ($tablecolumns as $tablecolumn) {
            $class = preg_replace($search, '', $tablecolumn);
            $this->column_class($tablecolumn, $class);
        }

        // if necessary, suppress columns so that some
        // information is only printed once per attempt
        foreach ($suppresscolumns as $suppresscolumn) {
            $this->column_suppress($suppresscolumn);
        }

        // attributes in the table tag
        $this->set_attribute('id', 'attempts');
        $this->set_attribute('class', $this->output->mode);

        parent::setup();
    }

    /**
     * wrap_html_start
     */
    function wrap_html_start() {

        // check this table has a "selected" column
        if (! $this->has_column('selected')) {
            return false;
        }

        // check user can delete attempts
        if (! $this->TC->can_deleteattempts()) {
            return false;
        }

        // start form
        $url = $this->TC->url->report($this->TC->mode);
        $params = array('id'=>'attemptsform', 'method'=>'post', 'action'=>$url->out_omit_querystring());
        echo html_writer::start_tag('form', $params);

        // create hidden fields
        $params = array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey());
        $hidden_fields = html_writer::input_hidden_params($url).
                         html_writer::empty_tag('input', $params)."\n";

        // put hidden fields in a containiner (for strict XHTML compatability)
        $params = array('style'=>'display: none;');
        echo html_writer::tag('div', $hidden_fields, $params);
    }

    /**
     * wrap_html_finish
     */
    function wrap_html_finish() {

        // check this table has a "selected" column
        if (! $this->has_column('selected')) {
            return false;
        }

        // check user can delete attempts
        if (! $this->TC->can_deleteattempts()) {
            return false;
        }

        // start "commands" div
        $params = array('id' => 'commands');
        echo html_writer::start_tag('div', $params);

        // add "select all" link
        $text = get_string('selectall', 'quiz');
        $href = "javascript:select_all_in('TABLE',null,'attempts');";
        echo html_writer::tag('a', $text, array('href' => $href));

        echo ' / ';

        // add "deselect all" link
        $text = get_string('selectnone', 'quiz');
        $href = "javascript:deselect_all_in('TABLE',null,'attempts');";
        echo html_writer::tag('a', $text, array('href' => $href));

        echo ' &nbsp; ';

        // add button to delete attempts
        $confirm = addslashes_js(get_string('confirmdeleteattempts', 'mod_taskchain'));
        $onclick = ''
            ."if(confirm('$confirm') && this.form && this.form.elements['confirmed']) {"
                ."this.form.elements['confirmed'].value = '1';"
                ."return true;"
            ."} else {"
                ."return false;"
            ."}"
        ;
        echo html_writer::empty_tag('input', array('type'=>'submit', 'onclick'=>"$onclick", 'name'=>'delete', 'value'=>get_string('deleteattempts', 'mod_taskchain')));
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'confirmed', 'value'=>'0'))."\n";
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'action', 'value'=>'deleteselected'))."\n";

        // finish "commands" div
        echo html_writer::end_tag('div');

        // finish the "attemptsform" form
        echo html_writer::end_tag('form');
    }

    ////////////////////////////////////////////////////////////////////////////////
    // functions to format header cells                                           //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * format_header
     *
     * @param xxx $tablecolumn
     * @return xxx
     */
    function format_header($tablecolumn)  {
        $method = 'header_'.$tablecolumn;
        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            return $this->header_other($tablecolumn);
        }
    }

    /**
     * header_picture
     *
     * @return xxx
     */
    function header_picture()  {
        return '';
    }

    /**
     * header_fullname
     *
     * @return xxx
     */
    function header_fullname()  {
        return get_string('name');
    }

    /**
     * header_selected
     *
     * @return xxx
     */
    function header_selected()  {
        return get_string('select');
    }

    ////////////////////////////////////////////////////////////////////////////////
    // functions to format CHAINGRADE header cells                                //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * header_chaingradegrade
     *
     * @return xxx
     */
    function header_chaingradegrade()  {
        return $this->header_grade('grade', 'chain');
    }

    /**
     * header_chaingradestatus
     *
     * @return xxx
     */
    function header_chaingradestatus()  {
        return $this->header_status();
    }

    /**
     * header_chaingradeduration
     *
     * @return xxx
     */
    function header_chaingradeduration()  {
        return $this->header_duration();
    }

    /**
     * header_chaingradetimemodified
     *
     * @return xxx
     */
    function header_chaingradetimemodified()  {
        return $this->header_timemodified();
    }

    ////////////////////////////////////////////////////////////////////////////////
    // functions to format CHAINATTEMPT header cells                              //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * header_chainattemptcnumber
     *
     * @return xxx
     */
    function header_chainattemptcnumber()  {
        return $this->header_cnumber();
    }

    /**
     * header_chainattemptgrade
     *
     * @return xxx
     */
    function header_chainattemptgrade()  {
        return $this->header_grade('attemptgrade', 'chain', 'attemptgrademethod', 'gradeweighting');
    }

    /**
     * header_chainattemptstatus
     *
     * @return xxx
     */
    function header_chainattemptstatus()  {
        return $this->header_status();
    }

    /**
     * header_chainattemptduration
     *
     * @return xxx
     */
    function header_chainattemptduration()  {
        return $this->header_duration();
    }

    /**
     * header_chainattempttimemodified
     *
     * @return xxx
     */
    function header_chainattempttimemodified()  {
        return $this->header_timemodified();
    }

    ////////////////////////////////////////////////////////////////////////////////
    // functions to format TASKSCORE header cells                              //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * header_taskscoretaskname
     *
     * @return xxx
     */
    function header_taskscoretaskname()  {
        return get_string('taskname', 'mod_taskchain');
    }

    /**
     * header_taskscorecnumber
     *
     * @return xxx
     */
    function header_taskscorecnumber()  {
        return $this->header_cnumber();
    }

    /**
     * header_taskscorescore
     *
     * @return xxx
     */
    function header_taskscorescore()  {
        return $this->header_grade('score', 'task');
    }

    /**
     * header_taskscorestatus
     *
     * @return xxx
     */
    function header_taskscorestatus()  {
        return $this->header_status();
    }

    /**
     * header_taskscoreduration
     *
     * @return xxx
     */
    function header_taskscoreduration()  {
        return $this->header_duration();
    }

    /**
     * header_taskscoretimemodified
     *
     * @return xxx
     */
    function header_taskscoretimemodified()  {
        return $this->header_timemodified();
    }

    ////////////////////////////////////////////////////////////////////////////////
    // functions to format TASKATTEMPT header cells                               //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * header_taskattempttnumber
     *
     * @return xxx
     */
    function header_taskattemptcnumber()  {
        return $this->header_cnumber();
    }

    /**
     * header_taskattempttnumber
     *
     * @return xxx
     */
    function header_taskattempttnumber()  {
        return $this->header_tnumber();
    }

    /**
     * header_taskattemptscore
     *
     * @return xxx
     */
    function header_taskattemptscore()  {
        return $this->header_score();
    }

    /**
     * header_taskattemptpenalties
     *
     * @return xxx
     */
    function header_taskattemptpenalties()  {
        return $this->header_penalties();
    }

    /**
     * header_taskattemptstatus
     *
     * @return xxx
     */
    function header_taskattemptstatus()  {
        return $this->header_status();
    }

    /**
     * header_taskattemptduration
     *
     * @return xxx
     */
    function header_taskattemptduration()  {
        return $this->header_duration();
    }

    /**
     * header_taskattempttimemodified
     *
     * @return xxx
     */
    function header_taskattempttimemodified()  {
        return $this->header_timemodified();
    }

    ////////////////////////////////////////////////////////////////////////////////
    // utility functions to format header cells                                   //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * header_attemptcnumber
     *
     * @return xxx
     */
    function header_cnumber()  {
        return get_string('cnumber', 'mod_taskchain');
    }

    /**
     * header_attempttnumber
     *
     * @return xxx
     */
    function header_tnumber()  {
        return get_string('tnumber', 'mod_taskchain');
    }

    /**
     * header_grade
     *
     * @param xxx $type ("score", "grade" or "attemptgrade")
     * @param xxx $record ("chain" or "task")
     * @param xxx $grademethod (optional, default="")
     * @param xxx $gradeweighting (optional, default="")
     * @return xxx
     */
    function header_grade($gradetype, $recordtype, $grademethod='', $gradeweighting='')  {
        $grade = get_string($gradetype, 'mod_taskchain');

        if (isset($this->TC->$recordtype)) {
            if ($grademethod=='') {
                $grademethod = $gradetype.'method';
            }
            $grademethod = $this->TC->$recordtype->$grademethod;
            $grademethod = $this->TC->format_grademethod($gradetype, $grademethod);

            if ($gradeweighting=='') {
                $gradeweighting = $gradetype.'weighting';
            }
            $gradeweighting = $this->TC->$recordtype->$gradeweighting;
            if ($gradeweighting != 100) {
                $grademethod = $gradeweighting." x $grademethod/100";
            }
            $grade .= html_writer::empty_tag('br').html_writer::tag('span', '('.$grademethod.')', array('class' => 'grademethod'));
        }

        return $grade;
    }

    /**
     * header_penalties
     *
     * @return xxx
     */
    function header_penalties()  {
        return get_string('penalties', 'mod_taskchain');
    }

    /**
     * header_status
     *
     * @return xxx
     */
    function header_status()  {
        return get_string('status', 'mod_taskchain');
    }

    /**
     * header_duration
     *
     * @return xxx
     */
    function header_duration()  {
        return get_string('duration', 'mod_taskchain');
    }

    /**
     * header_timemodified
     *
     * @return xxx
     */
    function header_timemodified()  {
        return get_string('time', 'quiz');
    }

    /**
     * header_score
     *
     * @return xxx
     */
    function header_score()  {
        return get_string('score', 'quiz');
    }

    /**
     * header_responsefield
     *
     * @return xxx
     */
    function header_responsefield()  {
        return '';
    }

    /**
     * header_other
     *
     * @return xxx
     */
    function header_other($column)  {
        if (substr($column, 0, 2)=='q_') {
            $a = intval(substr($column, 2)) + 1;
            return get_string('questionshort', 'mod_taskchain', $a);
        } else {
            return $column;
        }
    }

    ////////////////////////////////////////////////////////////////////////////////
    // functions to format data cells                                             //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * col_selected
     *
     * @param xxx $row
     * @return xxx
     */
    function col_selected($row)  {
        $id = $this->col_selected_id($row);
        return html_writer::checkbox('selected'.$id, 1, false);
    }

    /**
     * col_selected_id
     *
     * @param xxx $row
     * @return xxx
     */
    function col_selected_id($row)  {
        // $selected[$userid][chainid][cnumber][taskid][tnumber][taskattemptid]
        switch ($this->TC->mode) {

            case 'chaingrade':
            case 'chaingrades':
            case 'chainattempt':
            case 'chainattempts':
                return '['.$row->userid.']['.$row->chainattemptchainid.']['.$row->chainattemptcnumber.']';

            case 'taskscore':
            case 'taskscores':
            case 'taskattempt':
            case 'taskattempts':
                return '['.$row->userid.']['.$row->taskscorechainid.']['.$row->taskattemptcnumber.']['.$row->taskattempttaskid.']['.$row->taskattempttnumber.']['.$row->id.']';

            default:
                return ''; // shouldn't happen !!
        }
    }

    /**
     * col_picture
     *
     * @param xxx $row
     * @return xxx
     */
    function col_picture($row)  {
        $user = new stdClass();
        $fields = explode(',', $this->output->get_userfields());
        foreach ($fields as $field) {
            if ($field=='id') {
                $user->$field = $row->userid;
            } else {
                $user->$field = $row->$field;
            }
        }
        return $this->output->user_picture($user, array('courseid'=>$this->TC->course->id));
    }

    /**
     * col_cnumber
     *
     * @param xxx $row
     * @param xxx $type (optional, default="")
     * @return xxx
     */
    function col_cnumber($row, $type='')  {
        return $this->col_attemptnumber($row, $type, 'cnumber');
    }

    /**
     * col_cnumber
     *
     * @param xxx $row
     * @param xxx $type (optional, default="")
     * @return xxx
     */
    function col_tnumber($row, $type='')  {
        return $this->col_attemptnumber($row, $type, 'tnumber');
    }

    /**
     * col_attemptnumber
     *
     * @param xxx $row
     * @param xxx $recordtype "chaingrade", "chainattempt", "taskscore" or "taskattempt"
     * @param xxx $numbertype "cnumber" or "tnumber"
     * @return xxx
     */
    function col_attemptnumber($row, $recordtype, $numbertype)  {
        $field = $recordtype.$numbertype;
        $value = $row->$field;
        list($prefix, $value) = $this->split_prefix($value);
        $value = $row->$field;
        $value = $this->format_review_link($recordtype, $row, $value);
        return $prefix.$value;
    }

    /**
     * col_grade
     *
     * @param xxx $row
     * @param xxx $type "chaingrade", "chainattempt", "tastscore", or "taskattempt"
     * @return xxx
     */
    function col_grade($row, $type, $grade='grade')  {
        $field = $type.$grade;
        $value = $row->$field;
        list($prefix, $value) = $this->split_prefix($value);
        if ($value===null || $value==='') {
            $value = '&nbsp;';
        } else {
            $value = $value.'%';
        }
        $value = $this->format_review_link($type, $row, $value);
        return $prefix.$value;
    }

    /**
     * col_penalties
     *
     * @param xxx $row
     * @param xxx $type "taskattempt"
     * @return xxx
     */
    function col_penalties($row, $type)  {
        $field = $type.'penalties';
        $value = $row->$field;
        list($prefix, $value) = $this->split_prefix($value);
        $value = $this->format_review_link($type, $row, $value);
        return $prefix.$value;
    }

    /**
     * col_status
     *
     * @param xxx $row
     * @param xxx $type "chaingrade", "chainattempt", "tastscore", or "taskattempt"
     * @return xxx
     */
    function col_status($row, $type)  {
        $field = $type.'status';
        $value = $row->$field;
        list($prefix, $value) = $this->split_prefix($value);
        $value = mod_taskchain::format_status($value);
        $value = $this->format_review_link($type, $row, $value);
        return $prefix.$value;
    }

    /**
     * col_duration
     *
     * @param xxx $row
     * @param xxx $type "chaingrade", "chainattempt", "tastscore", or "taskattempt"
     * @return xxx
     */
    function col_duration($row, $type)  {
        $field = $type.'duration';
        $value = $row->$field;
        list($prefix, $value) = $this->split_prefix($value);

        // prevent warnings on Moodle 2.0
        // and speed up later versions too
        if ($this->date_strings===null) {
            $this->date_strings = (object)array(
                'day'   => get_string('day'),
                'days'  => get_string('days'),
                'hour'  => get_string('hour'),
                'hours' => get_string('hours'),
                'min'   => get_string('min'),
                'mins'  => get_string('mins'),
                'sec'   => get_string('sec'),
                'secs'  => get_string('secs'),
                'year'  => get_string('year'),
                'years' => get_string('years'),
            );
        }

        if ($value) {
            $value = format_time($value, $this->date_strings);
            $value = $this->format_review_link($type, $row, $value);
        } else {
            $value = ''; // format_text(0) returns "now"
        }

        return $prefix.$value;
    }

    /**
     * col_timemodified
     *
     * @param xxx $row
     * @param xxx $type "chaingrade", "chainattempt", "tastscore", or "taskattempt"
     * @return xxx
     */
    function col_timemodified($row, $type)  {
        $field = $type.'timemodified';
        $value = $row->$field;
        list($prefix, $value) = $this->split_prefix($value);
        $value = trim(userdate($value, $this->strtimeformat));
        $value = $this->format_review_link($type, $row, $value);
        return $prefix.$value;
    }

    /**
     * split_prefix
     *
     * @param xxx $value
     * @return xxx
     */
    function split_prefix($value) {
        static $prefix = null;
        static $prefixlen = 0;

        if ($prefix===null) {
            $prefix = html_writer::tag('span', '');
            $prefixlen = strlen($prefix);
        }

        if (strpos($value, $prefix)===0) {
            return array($prefix, substr($value, $prefixlen));
        } else {
            return array('', $value);
        }
    }

    /**
     * other_cols
     *
     * @param xxx $column
     * @param xxx $row
     * @return xxx
     */
    function other_cols($column, $row) {

        if (! property_exists($row, $column)) {
            return $column;
        }

        if ($column=='responsefield') {
            return get_string($row->$column, 'mod_taskchain');
        }

        // format columns Q-1 .. Q-99
        return $this->format_text($row->$column);
    }

    /**
     * format_review_link
     *
     * @param xxx $type "chaingrade", "chainattempt", "taskscore", or "taskattempt"
     * @param xxx $row
     * @param xxx $text
     * @return xxx
     */
    function format_review_link($type, $row, $text)  {
        if (strlen($text) && $this->TC->can_reviewattempts()) {
            $id = $type.'id';
            if (empty($row->$id)) {
                $params = array($id => $row->id);
            } else {
                $params = array($id => $row->$id);
            }
            if ($type=='taskattempt') {
                $url = $this->TC->url->review($row);
            } else {
                $url = $this->TC->url->report($type, $params);
            }
            $text = html_writer::link($url, $text);
        }
        return $text;
    }

    ////////////////////////////////////////////////////////////////////////////////
    // functions to format CHAINGRADE data cells                                  //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * col_chaingradegrade
     *
     * @param xxx $row
     * @return xxx
     */
    function col_chaingradegrade($row)  {
        return $this->col_grade($row, 'chaingrade');
    }

    /**
     * col_chaingradestatus
     *
     * @param xxx $row
     * @return xxx
     */
    function col_chaingradestatus($row)  {
        return $this->col_status($row, 'chaingrade');
    }

    /**
     * col_chaingradeduration
     *
     * @param xxx $row
     * @return xxx
     */
    function col_chaingradeduration($row)  {
        return $this->col_duration($row, 'chaingrade');
    }

    /**
     * col_chaingradetimemodified
     *
     * @param xxx $row
     * @return xxx
     */
    function col_chaingradetimemodified($row)  {
        return $this->col_timemodified($row, 'chaingrade');
    }

    ////////////////////////////////////////////////////////////////////////////////
    // functions to format CHAINATTEMPT data cells                                //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * col_chainattemptcnumber
     *
     * @param xxx $row
     * @return xxx
     */
    function col_chainattemptcnumber($row)  {
        return $this->col_cnumber($row, 'chainattempt');
    }

    /**
     * col_chainattemptgrade
     *
     * @param xxx $row
     * @return xxx
     */
    function col_chainattemptgrade($row)  {
        return $this->col_grade($row, 'chainattempt');
    }

    /**
     * col_chainattemptstatus
     *
     * @param xxx $row
     * @return xxx
     */
    function col_chainattemptstatus($row)  {
        return $this->col_status($row, 'chainattempt');
    }

    /**
     * col_chainattemptduration
     *
     * @param xxx $row
     * @return xxx
     */
    function col_chainattemptduration($row)  {
        return $this->col_duration($row, 'chainattempt');
    }

    /**
     * col_chainattempttimemodified
     *
     * @param xxx $row
     * @return xxx
     */
    function col_chainattempttimemodified($row)  {
        return $this->col_timemodified($row, 'chainattempt');
    }

    ////////////////////////////////////////////////////////////////////////////////
    // functions to format TASKSCORE data cells                                   //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * col_taskscorecnumber
     *
     * @param xxx $row
     * @return xxx
     */
    function col_taskscorecnumber($row)  {
        return $this->col_cnumber($row, 'taskscore');
    }

    /**
     * col_taskscorescore
     *
     * @param xxx $row
     * @return xxx
     */
    function col_taskscorescore($row)  {
        return $this->col_grade($row, 'taskscore', 'score');
    }

    /**
     * col_taskscorestatus
     *
     * @param xxx $row
     * @return xxx
     */
    function col_taskscorestatus($row)  {
        return $this->col_status($row, 'taskscore');
    }

    /**
     * col_taskscoreduration
     *
     * @param xxx $row
     * @return xxx
     */
    function col_taskscoreduration($row)  {
        return $this->col_duration($row, 'taskscore');
    }

    /**
     * col_taskscoretimemodified
     *
     * @param xxx $row
     * @return xxx
     */
    function col_taskscoretimemodified($row)  {
        return $this->col_timemodified($row, 'taskscore');
    }

    ////////////////////////////////////////////////////////////////////////////////
    // functions to format TASKATTEMPT data cells                                   //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * col_taskattemptcnumber
     *
     * @param xxx $row
     * @return xxx
     */
    function col_taskattemptcnumber($row)  {
        return $this->col_cnumber($row, 'taskattempt');
    }

    /**
     * col_taskscorecnumber
     *
     * @param xxx $row
     * @return xxx
     */
    function col_taskattempttnumber($row)  {
        return $this->col_tnumber($row, 'taskattempt');
    }

    /**
     * col_taskattemptpenalties
     *
     * @param xxx $row
     * @return xxx
     */
    function col_taskattemptpenalties($row)  {
        return $this->col_penalties($row, 'taskattempt');
    }

    /**
     * col_taskattemptscore
     *
     * @param xxx $row
     * @return xxx
     */
    function col_taskattemptscore($row)  {
        return $this->col_grade($row, 'taskattempt', 'score');
    }

    /**
     * col_taskattemptstatus
     *
     * @param xxx $row
     * @return xxx
     */
    function col_taskattemptstatus($row)  {
        return $this->col_status($row, 'taskattempt');
    }

    /**
     * col_taskattemptduration
     *
     * @param xxx $row
     * @return xxx
     */
    function col_taskattemptduration($row)  {
        return $this->col_duration($row, 'taskattempt');
    }

    /**
     * col_taskattempttimemodified
     *
     * @param xxx $row
     * @return xxx
     */
    function col_taskattempttimemodified($row)  {
        return $this->col_timemodified($row, 'taskattempt');
    }

    /**
     * override parent class method, because we may want to specify a default sort
     *
     * @return xxx
     */
    function get_sql_sort()  {

        // if user has specified a sort column, use that
        if ($sort = parent::get_sql_sort()) {
            return $sort;
        }

        $sort = array();
        // if there is a "fullname" column, sort by first/last name
        if ($this->has_column('fullname')) {
            $sort[] = 'u.firstname';
            $sort[] = 'u.lastname';
        }
        // sort by "cunumber" and/or "tnumber" column, if they exist
        if ($this->has_column('chaingradecnumber')) {
            $sort[] = 'tc_chn_grd.cnumber ASC';
        }
        if ($this->has_column('taskscorecnumber')) {
            $sort[] = 'tc_tsk_scr.cnumber ASC';
        }
        if ($this->has_column('taskattemptcnumber')) {
            $sort[] = 'tc_tsk_att.cnumber ASC';
        }
        if ($this->has_column('taskattempttnumber')) {
            $sort[] = 'tc_tsk_att.tnumber ASC';
        }
        return implode(',', $sort);
    }

    /**
     * has_column
     *
     * @param xxx $column
     * @return xxx
     */
    public function has_column($column)  {
        return array_key_exists($column, $this->columns);
    }

    /**
     * delete_rows
     *
     * @param xxx $delete_rows
     */
    function delete_rows($delete_rows)  {
        foreach ($delete_rows as $id => $delete_flag) {
            if ($delete_flag) {
                unset($this->rawdata[$id]);
            }
        }
    }

    /**
     * delete_columns
     *
     * @param xxx $delete_columns
     */
    function delete_columns($delete_columns)  {
        $newcolnum = 0;
        foreach($this->columns as $column => $oldcolnum) {
            if (empty($delete_columns[$column])) {
                $this->columns[$column] = $newcolnum++;
            } else {
                unset($this->columns[$column]);
                unset($this->headers[$oldcolnum]);
                foreach (array_keys($this->rawdata) as $id) {
                    unset($this->rawdata[$id]->$column);
                }
            }
        }
        // reset indexes on headers
        $this->headers = array_values($this->headers);
    }

    /**
     * set_legend
     *
     * @param xxx $column
     * @param xxx $value
     * @return xxx
     */
    function set_legend($column, $value) {
        if (empty($column) || empty($value)) {
            return '';
        }

        // if necessary, append this $column to the legend
        if (empty($this->legend[$column])) {
            $this->legend[$column] = array();
        }

        // get the $i(ndex) of this $value in this $column
        $i = array_search($value, $this->legend[$column]);
        if ($i===false) {
            $i = count($this->legend[$column]);
            $this->legend[$column][$i] = $value;
        }

        // return the $value's index (as A, B, C)
        return $this->format_legend_index($i);
    }

    /**
     * print_legend
     */
    function print_legend()  {
        if (empty($this->legend)) {
            return false;
        }

        $stringids = array();
        foreach ($this->legend as $column => $responses) {
            foreach ($responses as $i => $stringid) {
                $stringids[$stringid] = true;
            }
        }
        $strings = mod_taskchain::get_strings(array_keys($stringids));
        unset($stringids, $column, $responses, $i, $stringid);

        foreach ($this->legend as $column => $responses) {
            echo html_writer::start_tag('table');
            echo html_writer::start_tag('tbody');
            foreach ($responses as $i => $response) {
                if (isset($strings[$response])) {
                    $response_string = $strings[$response]->string;
                } else {
                    $response_string = 'Unrecognized string id: '.$response;
                }
                echo html_writer::tag('tr',
                    html_writer::tag('td', $this->format_header($column)).
                    html_writer::tag('td', $this->format_legend_index($i)).
                    html_writer::tag('td', $response_string)
                );
                $column = '&nbsp;';
            }
            echo html_writer::end_tag('tbody');
            echo html_writer::end_tag('table');
        }
    }

    /**
     * format_legend_index
     *
     * @param xxx $i
     * @return xxx
     */
    function format_legend_index($i)  {
        // convert numeric index to A, B, ... Z, AA, AB, ...
        if ($i < 26) {
            return chr(ord('A') + $i);
        } else {
            return $this->format_legend_index(intval($i/26)-1).$this->format_legend_index($i % 26);
        }
    }
}
