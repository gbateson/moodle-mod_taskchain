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
 * mod/taskchain/report/chainattempt/renderer.php
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
 * mod_taskchain_report_chainattempt_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_report_chainattempt_renderer extends mod_taskchain_report_renderer {
    public $mode = 'chainattempt';

    protected $headerfields = array('user', 'chaingrade', 'chainattempt');

    public $tablecolumns = array(
        'taskscoretaskname',    'selected',
        'taskscorescore',       'taskscorestatus',
        'taskscoreduration',    'taskscoretimemodified'
    );

    /** columns with this prefix will be suppressed (i.e. only shown once per user) */
    protected $suppressprefix = 'chainattempt';

    public $filterfields = array(
        'grade'=>1, 'timemodified'=>1, 'status'=>1, 'duration'=>1, 'score'=>1
    );

    /** id param name and table name */
    public $id_param_name = 'chainattemptid';
    public $id_param_table = 'taskchain_chain_attempts';

    /**
     * count_sql
     *
     * @param xxx $userid (optional, default=0)
     * @param xxx $record (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function count_sql($userid=0, $record=null) {
        $select = 'COUNT(1)';
        $from   = '';
        $where  = '';
        $params = array();

        // restrict to a specific chaingrade / user
        $this->select_sql_record($select, $from, $where, $params, $userid, $record);

        return array($select, $from, $where, $params);
    }

    /**
     * select_sql
     *
     * @param xxx $userid (optional, default=0)
     * @param xxx $record (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function select_sql($userid=0, $record=null) {
        $select = 'tc_tsk_scr.id AS id, '.
                  'tc_tsk_scr.userid AS userid, '.
                  'tc_tsk.name AS taskscoretaskname, '.
                  'tc_tsk_scr.score AS taskscorescore, '.
                  'tc_tsk_scr.status AS taskscorestatus, '.
                  'tc_tsk_scr.duration AS taskscoreduration, '.
                  'tc_tsk_scr.timemodified AS taskscoretimemodified, '.
                  'tc_chn_att.id AS chainattemptid, '.
                  'tc_chn_att.chainid AS chainattemptchainid, '.
                  'tc_chn_att.cnumber AS chainattemptcnumber, '.
                  'tc_chn_att.grade AS chainattemptgrade, '.
                  'tc_chn_att.status AS chainattemptstatus, '.
                  'tc_chn_att.duration AS chainattemptduration, '.
                  'tc_chn_att.timemodified AS chainattempttimemodified';
        $from   = '';
        $where  = '';
        $params = array();

        // restrict to a specific chaingrade / user
        $this->select_sql_record($select, $from, $where, $params, $userid, $record);

        // add sql to select user fields
        $this->select_sql_user($select, $from, 'tc_chn_att');

        return array($select, $from, $where, $params);
    }

    /**
     * select_sql_record
     *
     * @param string   $select  (passed by reference)
     * @param string   $from    (passed by reference)
     * @param string   $where   (passed by reference)
     * @param array    $params  (passed by reference)
     * @param integer  $userid  (optional, default=0)
     * @param object   $record  (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function select_sql_record(&$select, &$from, &$where, &$params, $userid=0, $record=null) {
        $from   = '{taskchain_task_scores} tc_tsk_scr '.
                  ' JOIN {taskchain_tasks} tc_tsk ON tc_tsk.id = tc_tsk_scr.taskid'.
                  ' JOIN {taskchain_chains} tc_chn ON tc_chn.id = tc_tsk.chainid'.
                  ' JOIN {taskchain_chain_attempts} tc_chn_att ON (tc_chn_att.chainid = tc_chn.id AND '.
                                                                  'tc_chn_att.cnumber = tc_tsk_scr.cnumber AND '.
                                                                  'tc_chn_att.userid  = tc_tsk_scr.userid)';

        // restrict sql to a specific chainattempt /user
        if ($record) {
            $where  = 'tc_chn_att.id = ?';
            $params = array($record->id);
        } else {
            $where  = 'tc_chn.parenttype = ? AND tc_chn.parentid = ?';
            $params = array(mod_taskchain::PARENTTYPE_ACTIVITY, $this->TC->get_taskchainid());
            if ($userid) {
                $where .= ' AND tc_chn_att.userid = ?';
                $params[] = $userid;
            }
        }

        return array($select, $from, $where, $params);
    }
}
