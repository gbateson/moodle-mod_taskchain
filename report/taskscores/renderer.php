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
 * mod/taskchain/report/taskscores/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/report/taskscore/renderer.php');

/**
 * mod_taskchain_report_taskscores_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_report_taskscores_renderer extends mod_taskchain_report_taskscore_renderer {
    public $mode = 'taskscores';

    /** id param name and table name */
    public $id_param_name = 'taskid';
    public $id_param_table = 'taskchain_tasks';

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
                ' JOIN {taskchain_tasks} tc_tsk ON tc_tsk.id = tc_tsk_att.taskid'.
                ' JOIN {taskchain_task_scores} tc_tsk_scr ON (tc_tsk_scr.taskid = tc_tsk.id AND '.
                                                             'tc_tsk_scr.userid = tc_tsk_att.userid)';

        // restrict sql to a specific taskscore /user
        if ($record) {
            $where  = 'tc_tsk.id = ?';
            $params = array($record->id);
        } else {
            $from  .= ' JOIN {taskchain_chains} tc_chn ON tc_chn.id = tc_tsk.chainid';
            $where  = 'tc_chn.parenttype = ? AND tc_chn.parentid = ?';
            $params = array(mod_taskchain::PARENTTYPE_ACTIVITY, $this->TC->get_taskchainid());
            if ($userid) {
                $where .= ' AND tc_tsk_scr.userid = ?';
                $params[] = $userid;
            }
        }
    }
}
