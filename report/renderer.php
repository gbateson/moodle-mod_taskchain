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
 * mod/taskchain/report/renderer.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/** Include required files */
require_once($CFG->dirroot.'/mod/taskchain/renderer.php');
require_once($CFG->dirroot.'/mod/taskchain/report/tablelib.php');
require_once($CFG->dirroot.'/mod/taskchain/report/userfiltering.php');

/**
 * mod_taskchain_report_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_report_renderer extends mod_taskchain_renderer {

    protected $tablecolumns = array();

    protected $filterfields = array();

    public $mode = '';

    protected $userfilter = '';

    protected $attemptfilter = '';

    public $questions = array();

    public $has_questioncolumns = false;

    /**
     * init
     *
     * @uses $DB
     * @param xxx $taskchain
     * @todo Finish documenting this function
     */
    public function init($taskchain)   {
        global $DB;

        // save a reference to the $taskchain record
        $this->taskchain = $taskchain;

        // add question numbers to $tablecolumns
        if ($this->has_questioncolumns) {
            if ($records = $DB->get_records('taskchain_questions', array('taskchainid' => $this->taskchain->id), '', 'id,name,text')) {
                $this->questions = array_values($records);
            }
        }

    }

    /**
     * render_report
     *
     * @param xxx $taskchain
     * @todo Finish documenting this function
     */
    public function render_report($taskchain)  {
        $this->init($taskchain);
        echo $this->header();
        echo $this->reportcontent();
        echo $this->footer();
    }

    /**
     * reportcontent
     *
     * @uses $DB
     * @uses $USER
     * @return xxx
     * @todo Finish documenting this function
     */
    public function reportcontent()  {
        global $DB, $USER;

        // check capabilities
        if ($this->taskchain->can_reviewallattempts()) {
            $userid = 0; // all users
        } else if ($this->taskchain->can_reviewmyattempts()) {
            // current user can only review their own attempts
            $userid = $USER->id;
        } else {
            // has_capability('mod/taskchain:review', $this->taskchain->context))
            // should already have been checked in "mod/taskchain/report.php"
            return false;
        }

        // set baseurl for this page (used for filters and table)
        $baseurl = $this->taskchain->url->report($this->mode)->out();

        // display user and attempt filters
        $this->display_filters($baseurl);

        // create report table
        $uniqueid = $this->page->pagetype.'-'.$this->mode;
        $table = new taskchain_report_table($uniqueid, $this);

        // set the table columns
        $tablecolumns = $this->tablecolumns;
        if (! $this->taskchain->can_deleteattempts()) {
            // remove the select column from students view
            $i = array_search('selected', $tablecolumns);
            if (is_numeric($i)) {
                array_splice($tablecolumns, $i, 1);
            }
        }
        if ($this->has_questioncolumns) {
            $i = array_search('penalties', $tablecolumns);
            if ($i===false) {
                $i = array_search('score', $tablecolumns);
            }
            if ($i===false) {
                $i = count($tablecolumns);
            }
            array_splice($tablecolumns, $i, 0, $this->get_question_columns());
        }

        // setup the report table
        $table->setup_report_table($tablecolumns, $baseurl);

        // setup sql to COUNT records
        list($select, $from, $where, $params) = $this->count_sql($userid);
        $table->set_count_sql("SELECT $select FROM $from WHERE $where", $params);

        // setup sql to SELECT records
        list($select, $from, $where, $params) = $this->select_sql($userid);
        $table->set_sql($select, $from, $where, $params);

        // extract attempt records
        $table->query_db($table->get_page_size());

        // extract question responses, if required
        if ($this->has_questioncolumns) {
            $this->add_responses_to_rawdata($table);
        }

        // display the table
        $table->build_table();
        $table->finish_html();

        // display the legend
        $table->print_legend();
    }

    /**
     * display_filters
     *
     * @param xxx $baseurl
     * @todo Finish documenting this function
     */
    public function display_filters($baseurl) {
        if (count($this->filterfields) && $this->taskchain->can_reviewattempts()) {

            $user_filtering = new taskchain_user_filtering($this->filterfields, $baseurl);

            $this->userfilter = $user_filtering->get_sql_filter();
            $this->attemptfilter = $user_filtering->get_sql_filter_attempts();

            $user_filtering->display_add();
            $user_filtering->display_active();
        }
    }

    /**
     * count_sql
     *
     * @param xxx $userid (optional, default=0)
     * @param xxx $attemptid (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function count_sql($userid=0, $attemptid=0) {
        $select = 'COUNT(1)';
        $from   = '{taskchain_attempts}';
        $where  = 'taskchainid=?';
        $params = array($this->taskchain->id);

        // restrict to a specific user
        if ($userid) {
            $where .= ' AND userid=?';
            $params[] = $userid;
        }

        // restrict to a specific attempt
        if ($attemptid) {
            $where = ' AND id=?';
            $params[] = $attemptid;
        }

        return array($select, $from, $where, $params);
    }

    /**
     * select_sql
     *
     * @param xxx $userid (optional, default=0)
     * @param xxx $attemptid (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function select_sql($userid=0, $attemptid=0) {

        // the standard way to get Moodle grades is thus:
        // $grades = grade_get_grades($this->taskchain->course->id, 'mod', 'taskchain', $this->taskchain->id, $userid);
        // $grade = $grades->items[0]->grades[$USER->id]->grade;

        // get question fields, if any
        $select_questions = '';
        foreach ($this->get_question_columns() as $column) {
            $select_questions .= "1 AS $column, ";
        }

        // sql to select all attempts at this TaskChain (and Moodle grade)
        $select = 'ha.*, (ha.timemodified - ha.timestart) AS duration, '.$select_questions.
                  'ROUND(gg.rawgrade, 0) AS grade';
        $from   = '{taskchain_attempts} ha, {grade_items} gi, {grade_grades} gg';
        $where  = 'ha.taskchainid=? AND ha.userid=gg.userid AND gg.itemid=gi.id '.
                  'AND gi.courseid=? AND gi.itemtype=? AND gi.itemmodule=? AND gi.iteminstance=?';
        $params = array($this->taskchain->id, $this->taskchain->course->id, 'mod', 'taskchain', $this->taskchain->id);

        // add user fields. if required
        if (in_array('fullname', $this->tablecolumns)) {
            $select .= ', u.id AS userid, u.firstname, u.lastname, u.picture, u.imagealt, u.email';
            $from   .= ', {user} u';
            $where  .= ' AND ha.userid=u.id';
        }

        // restrict sql to a specific user
        if ($userid) {
            $where .= ' AND ha.userid=?';
            $params[] = $userid;
        }

        // restrict sql to a specific attempt
        if ($attemptid) {
            $where = ' AND ha.id=?';
            $params[] = $attemptid;
        }

        return array($select, $from, $where, $params);
    }

    /**
     * add_responses_to_rawdata
     *
     * @uses $DB
     * @param xxx $table (passed by reference)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function add_responses_to_rawdata(&$table)   {
        global $DB;

        if (empty($table->rawdata) || empty($this->questions)) {
            return false;
        }

        // get question column names ($index_by_id = true)
        $question_columns = $this->get_question_columns(true);

        // empty out the response fields in the the rawdata
        $attemptids = array_keys($table->rawdata);
        foreach ($attemptids as $attemptid) {
            foreach ($question_columns as $questionid => $column) {
                $table->rawdata[$attemptid]->$column = '';
            }
        }

        // set sql to select responses for these attempts
        list($a_filter, $a_params) = $DB->get_in_or_equal($attemptids);
        list($q_filter, $q_params) = $DB->get_in_or_equal(array_keys($question_columns));

        $select = '*';
        $from   = '{taskchain_responses}';
        $where  = "attemptid $a_filter AND questionid $q_filter";
        $params = array_merge($a_params, $q_params);

        // get the responses for these attempts
        if (! $responses = $DB->get_records_sql("SELECT $select FROM $from WHERE $where", $params)) {
            return false;
        }

        foreach ($responses as $response) {
            $attemptid = $response->attemptid;
            if (empty($table->rawdata[$attemptid])) {
                return false; // shouldn't happen
            }

            $questionid = $response->questionid;
            if (empty($question_columns[$questionid])) {
                return false; // shouldn't happen
            }

            $column = $question_columns[$questionid];
            $this->add_response_to_rawdata($table, $attemptid, $column, $response);
        }
    }

    /**
     * add_response_to_rawdata
     *
     * @param xxx $table (passed by reference)
     * @param xxx $attemptid
     * @param xxx $column
     * @param xxx $response
     * @todo Finish documenting this function
     */
    public function add_response_to_rawdata(&$table, $attemptid, $column, $response)  {
        // $table->rawdata[$attemptid]->$column = $response->somefield;
    }

    /**
     * get_question_columns
     *
     * @param xxx $index_by_id (optional, default=false)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_question_columns($index_by_id=false)  {
        $question_columns = array();
        foreach ($this->questions as $i => $question) {
            if ($index_by_id) {
                $id = $question->id;
            } else {
                $id = $i;
            }
            $question_columns[$id] = "q_$i";
        }
        return $question_columns;
    }
}
