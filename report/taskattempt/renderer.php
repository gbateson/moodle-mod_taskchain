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
 * mod/taskchain/report/taskattempt/renderer.php
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
 * mod_taskchain_report_taskattempt_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_report_taskattempt_renderer extends mod_taskchain_report_renderer {
    public $mode = 'taskattempt';

    public $tablecolumns = array(
        'taskscorecnumber',  'taskscoretaskname',
        'taskattempttnumber',  'selected',
        'taskattemptscore',    'taskattemptstatus',
        'taskattemptduration', 'taskattempttimemodified'
    );
    public $has_usercolumns = true;

    public $filterfields = array(
        'grade'=>1, 'timemodified'=>1, 'status'=>1, 'duration'=>1, 'score'=>1
    );

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
        $from   = '{taskchain_task_attempts} tc_tsk_att '.
                  ' JOIN {taskchain_tasks} tc_tsk ON tc_tsk.id = tc_tsk_att.taskid'.
                  ' JOIN {taskchain_task_scores} tc_tsk_scr ON tc_tsk_scr.taskid = tc_tsk.id';
        $where  = 'tc_tsk_scr.id = ?';
        $params = array($this->TC->taskscore->id);

        // restrict to a specific user
        if ($userid) {
            $where .= ' AND tc_tsk_att.userid = ?';
            $params[] = $userid;
        }

        // restrict to a specific attempt
        if ($attemptid) {
            $where = ' AND tc_tsk_att.id = ?';
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
        // $grades = grade_get_grades($this->TC->course->id, 'mod', 'taskchain', $this->TC->id, $userid);
        // $grade = $grades->items[0]->grades[$USER->id]->grade;

        // sql to select all grades for this TaskChain
        $select = 'tc_tsk_att.id AS id, '.
                  'tc_tsk_att.userid AS userid, '.
                  'tc_tsk_att.cnumber AS taskattemptcnumber, '.
                  'tc_tsk_att.tnumber AS taskattempttnumber, '.
                  'tc_tsk_att.penalties AS taskattemptpenalties, '.
                  'tc_tsk_att.score AS taskattemptscore, '.
                  'tc_tsk_att.status AS taskattemptstatus, '.
                  'tc_tsk_att.duration AS taskattemptduration, '.
                  'tc_tsk_att.timestart AS taskattempttimemodified, '.
                  'tc_tsk.name AS taskscoretaskname';
        $from   = '{taskchain_task_attempts} tc_tsk_att '.
                  ' JOIN {taskchain_tasks} tc_tsk ON tc_tsk.id = tc_tsk_att.taskid';
        $where  = 'tc_tsk_att.id = ?';
        $params = array($this->TC->taskattempt->id);

        // add user fields. if required
        if (in_array('fullname', $this->tablecolumns)) {
            $select .= ', '.$this->get_userfields('u', null, 'userid');
            $from   .= ' JOIN {user} u ON tc_tsk_att.userid=u.id';
        }

        // restrict sql to a specific user
        if ($userid) {
            $where .= ' AND tc_tsk_att.userid = ?';
            $params[] = $userid;
        }

        // restrict sql to a specific attempt
        if ($attemptid) {
            $where = ' AND tc_tsk_scr.id = ?';
            $params[] = $attemptid;
        }

        return array($select, $from, $where, $params);
    }
}
