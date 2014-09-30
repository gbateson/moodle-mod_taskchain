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
 * mod/taskchain/report/taskquestions/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/report/renderer.php');

/**
 * mod_taskchain_report_taskquestions_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_report_taskquestions_renderer extends mod_taskchain_report_renderer {
    public $mode = 'taskquestions';

    public $tablecolumns = array(
        'taskattempttnumber',  'selected',
        'taskattemptscore',    'taskattemptstatus',
        'taskattemptduration', 'taskattempttimemodified'
    );
    public $has_usercolumns = true;

    public $filterfields = array(
        'realname'=>0, // 'lastname'=>1, 'firstname'=>1, 'username'=>1,
        'score'=>1, 'timemodified'=>1, 'status'=>1, 'duration'=>1, 'penalties'=>1, 'score'=>1
    );

    public $has_questioncolumns = true;

    /** id param name and table name */
    public $id_param_name = 'taskid';
    public $id_param_table = 'taskchain_tasks';

    /**
     * select_sql
     *
     * @param xxx $userid (optional, default=0)
     * @param xxx $record (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function select_sql($userid=0, $record=null) {
        $select = 'tc_tsk_att.id AS id, '.
                  'tc_tsk_att.cnumber AS taskattemptcnumber, '.
                  'tc_tsk_att.tnumber AS taskattempttnumber, '.
                  'tc_tsk_att.penalties AS taskattemptpenalties, '.
                  'tc_tsk_att.score AS taskattemptscore, '.
                  'tc_tsk_att.status AS taskattemptstatus, '.
                  'tc_tsk_att.duration AS taskattemptduration, '.
                  'tc_tsk_att.timestart AS taskattempttimemodified, '.
                  'tc_tsk.name AS taskscoretaskname';
        $from   = '';
        $where  = '';
        $params = array();
        $this->select_sql_record($select, $from, $where, $params, $userid, $record);
        $this->select_sql_user($select, $from, 'tc_tsk_att');

        return array($select, $from, $where, $params);
    }

    /**
     * select_sql_record
     *
     * @param  string   $select  (passed by reference)
     * @param  string   $from    (passed by reference)
     * @param  string   $where   (passed by reference)
     * @param  array    $params  (passed by reference)
     * @param  integer  $userid  (optional, default=0)
     * @param  object   $record  (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function select_sql_record(&$select, &$from, &$where, &$params, $userid=0, $record=null) {
        $from = '{taskchain_task_attempts} tc_tsk_att '.
                'JOIN {taskchain_tasks} tc_tsk ON tc_tsk_att.taskid = tc_tsk.id';

        // restrict sql to a specific task / user
        if ($record) {
            $where  = 'tc_tsk.id = ?';
            $params = array($record->id);
        } else {
            $from  .= ' JOIN {taskchain_chains} tc_chn ON tc_chn.id = tc_tsk.chainid';
            $where  = 'tc_chn.parenttype = ? AND tc_chn.parentid = ?';
            $params = array(mod_taskchain::PARENTTYPE_ACTIVITY, $this->TC->get_taskchainid());
            if ($userid) {
                $where .= ' AND tc_tsk_att.userid = ?';
                $params[] = $userid;
            }
        }
    }

    /**
     * add_response_to_rawdata
     *
     * @param xxx $table (passed by reference)
     * @param xxx $attemptid
     * @param xxx $column
     * @param xxx $response
     */
    public function add_response_to_rawdata(&$table, $attemptid, $column, $response)  {
        $table->rawdata[$attemptid]->$column = $response->score;
    }
}
