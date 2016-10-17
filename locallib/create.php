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
 * mod/taskchain/locallib/create.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/**
 * taskchain_create
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_create extends taskchain_base {

    /**
     * chaingrade
     *
     * @uses $DB
     * @param xxx $grade (optional, default=0)
     * @param xxx $status (optional, default=self::STATUS_INPROGRESS)
     * @param xxx $duration (optional, default=0)
     * @param xxx $chainid (optional, default=0)
     * @param xxx $cnumber (optional, default=0)
     * @param xxx $userid (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    function chaingrade($grade=0, $status=self::STATUS_INPROGRESS, $duration=0, $chainid=0, $cnumber=0, $userid=0) {
        global $DB;

        if ($userid==0 && $this->TC->get_chaingrade()) {
            return $this->TC->get_chaingradeid();
        }

        if ($userid) {
            $thisuser = false;
        } else {
            $thisuser = true;
            $userid = $this->TC->userid;
        }
        if ($chainid) {
            $chain = $DB->get_record('taskchain_chains', array('id'=>$chainid), 'id,parenttype,parentid');
        } else {
            $chain = &$this->TC->chain;
        }

        // create new chain grade record
        $chaingrade = new stdClass();
        $chaingrade->parenttype   = $chain->parenttype;
        $chaingrade->parentid     = $chain->parentid;
        $chaingrade->userid       = $userid;
        $chaingrade->grade        = $grade;
        $chaingrade->status       = $status;
        $chaingrade->duration     = $duration;
        $chaingrade->timemodified = $this->TC->time;

        // add new chain grade record
        if (! $chaingrade->id = $DB->insert_record('taskchain_chain_grades', $chaingrade)) {
            print_error('error_insertrecord', 'taskchain', '', 'taskchain_chain_grades');
        }
        $chaingradeid = $chaingrade->id;

        if ($thisuser) {
            // convert $chaingrade to full taskchain_chain_grade object
            $this->TC->chaingrade = new taskchain_chain_grade($chaingrade, array('TC' => &$this->TC));
        }

        return $chaingradeid;
    }

    /**
     * chainattempt
     *
     * @uses $DB
     * @param xxx $grade (optional, default=0)
     * @param xxx $status (optional, default=self::STATUS_INPROGRESS)
     * @param xxx $duration (optional, default=0)
     * @param xxx $chainid (optional, default=0)
     * @param xxx $cnumber (optional, default=0)
     * @param xxx $userid (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    function chainattempt($grade=0, $status=self::STATUS_INPROGRESS, $duration=0, $chainid=0, $cnumber=0, $userid=0) {
        global $DB;

        if ($userid==0 && $this->TC->get_chainattempt()) {
            return $this->TC->get_chainattemptid();
        }

        if ($userid) {
            $thisuser = false;
        } else {
            $thisuser = true;
            $userid = $this->TC->userid;
        }
        if (! $chainid) {
            $chainid = $this->TC->get_chainid();
        }
        if (! $cnumber) {
            $select = 'chainid=? AND userid=?';
            $params = array($chainid, $userid);
            $cnumber = $this->TC->count_records_select('taskchain_chain_attempts', $select, $params, 'MAX(cnumber)') + 1;
        }

        // create new chain attempt record
        $chainattempt = new stdClass();
        $chainattempt->chainid      = $chainid;
        $chainattempt->userid       = $userid;
        $chainattempt->cnumber      = $cnumber;
        $chainattempt->grade        = $grade;
        $chainattempt->status       = $status;
        $chainattempt->duration     = $duration;
        $chainattempt->timemodified = $this->TC->time;

        // add new chain attempt record
        if (! $chainattempt->id = $DB->insert_record('taskchain_chain_attempts', $chainattempt)) {
            print_error('error_insertrecord', 'taskchain', '', 'taskchain_chain_attempts');
        }
        $chainattemptid = $chainattempt->id;

        // sql to select previous chain_attempts and their task_scores and task_attempts
        $taskids = "SELECT id FROM {taskchain_tasks} WHERE chainid=?";
        $select  = 'cnumber<? AND userid=? AND status=?';
        $params  = array($chainid, $cnumber, $userid, self::STATUS_INPROGRESS);

        // set status of previous chain attempts (and their task_attempts and task_scores) to adandoned
        $DB->set_field_select('taskchain_task_attempts',  'status', self::STATUS_ABANDONED, "taskid IN ($taskids) AND $select", $params);
        $DB->set_field_select('taskchain_task_scores',    'status', self::STATUS_ABANDONED, "taskid IN ($taskids) AND $select", $params);
        $DB->set_field_select('taskchain_chain_attempts', 'status', self::STATUS_ABANDONED, "chainid=? AND $select",            $params);

        // set status of previous PENDING chain attempts to COMPLETED
        $select  = 'chainid=? AND cnumber<? AND userid=? AND status=?';
        $params  = array($chainid, $cnumber, $userid, self::STATUS_PENDING);
        $DB->set_field_select('taskchain_chain_attempts', 'status', self::STATUS_COMPLETED, $select, $params);

        // TODO : might be nice to have a setting for "number of concurrent attempts allowed"

        if ($thisuser) {
            // convert $chainattempt to full taskchain_chain_attempt object
            $this->TC->chainattempt = new taskchain_chain_attempt($chainattempt, array('TC' => &$this->TC));
            $this->TC->force_cnumber(0);

            // make sure we have a chaingrade record too
            $this->chaingrade();
        }

        return $chainattemptid;
    }

    /**
     * taskscore
     *
     * @uses $DB
     * @param xxx $score (optional, default=0)
     * @param xxx $status (optional, default=self::STATUS_INPROGRESS)
     * @param xxx $duration (optional, default=0)
     * @param xxx $taskid (optional, default=0)
     * @param xxx $cnumber (optional, default=0)
     * @param xxx $userid (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    function taskscore($score=0, $status=self::STATUS_INPROGRESS, $duration=0, $taskid=0, $cnumber=0, $userid=0) {
        global $DB;

        if ($userid==0 && $this->TC->get_taskscore()) {
            return $this->TC->get_taskscoreid();
        }

        if ($userid) {
            $thisuser = false;
        } else {
            $thisuser = true;
            $userid = $this->TC->userid;
        }
        if (! $taskid) {
            $taskid = $this->TC->get_taskid();
        }
        if (! $cnumber) {
            $cnumber = $this->TC->get_cnumber();
        }

        $taskscore = new stdClass();
        $taskscore->taskid       = $taskid;
        $taskscore->cnumber      = $cnumber;
        $taskscore->userid       = $userid;
        $taskscore->score        = $score;
        $taskscore->status       = $status;
        $taskscore->duration     = $duration;
        $taskscore->timemodified = $this->TC->time;

        if (! $taskscore->id = $DB->insert_record('taskchain_task_scores', $taskscore)) {
            print_error('error_insertrecord', 'taskchain', '', 'taskchain_task_scores');
        }
        $taskscoreid = $taskscore->id;

        if ($thisuser) {
            // convert $taskscore to full taskchain_task_score object
            $this->TC->taskscore = new taskchain_task_score($taskscore, array('TC' => &$this->TC));

            // make sure we have a chainattempt too
            $this->chainattempt();
        }

        return $taskscoreid;
    }

    /**
     * taskattempt
     *
     * @uses $DB
     * @return xxx
     * @todo Finish documenting this function
     */
    function taskattempt() {
        global $DB;
        /*
        Usually this function is called like this:
        line 240 of /mod/taskchain/locallib/create.php: moodle_exception thrown
        line 307 of /mod/taskchain/locallib/create.php: call to taskchain_create->taskattempt()
        line ? of unknownfile: call to taskchain_create->attempt()
        line 555 of /mod/taskchain/locallib.php: call to call_user_func_array()
        line 1722 of /mod/taskchain/attempt/hp/6/renderer.php: call to mod_taskchain->__call()
        line 1722 of /mod/taskchain/attempt/hp/6/renderer.php: call to mod_taskchain->create->attempt()
        line 1231 of /mod/taskchain/attempt/hp/6/renderer.php: call to mod_taskchain_attempt_hp_6_renderer->fix_submissionform()
        line 278 of /mod/taskchain/attempt/renderer.php: call to mod_taskchain_attempt_hp_6_renderer->postprocessing()
        line 89 of /mod/taskchain/attempt.php: call to mod_taskchain_attempt_renderer->render_attempt()
        */
        if ($this->TC->get_taskattempt()) {
            return $this->TC->get_taskattemptid();
        }

        // get secondary key fields
        $userid = $this->TC->userid;
        $taskid = $this->TC->get_taskid();
        $cnumber = $this->TC->get_cnumber();

        if ($cnumber < 0) {
            // create chain grade and attempt
            $this->chainattempt();
            $cnumber = $this->TC->get_cnumber();
        }

        // get maximum tnumber (task attempt number)
        $select = "taskid=? AND cnumber=? AND userid=?";
        $params = array($taskid, $cnumber, $userid);
        $tnumber = $this->TC->count_records_select('taskchain_task_attempts', $select, $params, 'MAX(tnumber)') + 1;

        // create new task attempt record
        $taskattempt = new stdClass();
        $taskattempt->taskid       = $taskid;
        $taskattempt->cnumber      = $cnumber;
        $taskattempt->tnumber      = $tnumber;
        $taskattempt->userid       = $userid;
        $taskattempt->status       = self::STATUS_INPROGRESS;
        $taskattempt->penalties    = 0;
        $taskattempt->score        = 0;
        $taskattempt->duration     = 0;
        $taskattempt->resumestart  = $this->TC->time;
        $taskattempt->resumefinish = 0;
        $taskattempt->timestart    = $this->TC->time;
        $taskattempt->timefinish   = 0;
        //$taskattempt->timemodified = $this->TC->time;

        // add new task attempt record
        if (! $taskattempt->id = $DB->insert_record('taskchain_task_attempts', $taskattempt)) {
            print_error('error_insertrecord', 'taskchain', '', 'taskchain_task_attempts');
        }
        $taskattemptid = $taskattempt->id;

        // convert $taskattempt to full taskchain_task_attempt object
        $this->TC->taskattempt = new taskchain_task_attempt($taskattempt, array('TC' => &$this->TC));
        $this->TC->force_tnumber(0);

        // set previous task attempts to adandoned
        $select = 'taskid=? AND cnumber=? AND tnumber<? AND userid=? AND status=?';
        $params = array($taskid, $cnumber, $tnumber, $userid, self::STATUS_INPROGRESS);
        $DB->set_field_select('taskchain_task_attempts', 'status', self::STATUS_ABANDONED, $select, $params);

        // TODO : might be nice to have a setting for "number of concurrent attempts allowed"

        // make sure we have a taskscore record too
        $this->taskscore();

        return $taskattemptid;
    }

    /**
     * attempt
     *
     * @param xxx $type "chain" or "task"
     * @param xxx $grade (optional, default=0)
     * @param xxx $status (optional, default=self::STATUS_INPROGRESS)
     * @param xxx $duration (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    function attempt($type, $grade=0, $status=self::STATUS_INPROGRESS, $duration=0) {
        $attempt = "{$type}attempt";
        return $this->$attempt($grade, $status, $duration);
    }
}
