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
 * mod/taskchain/locallib/taskchain_task_attempt.php
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
 * taskchain_task_attempt
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_task_attempt extends taskchain_base {

    /** db field: id (primary key, default=null) */
    private $id                  = null;

    /** db field: taskid (integer, default=0) */
    private $taskid              = 0;

    /** db field: userid (integer, default=0) */
    private $userid              = 0;

    /** db field: cnumber (integer, default=0) */
    private $cnumber             = 0;

    /** db field: tnumber (integer, default=0) */
    private $tnumber             = 0;

    /** db field: status (integer, default=1) */
    private $status              = 1;

    /** db field: penalties (integer, default=0) */
    private $penalties           = 0;

    /** db field: score (integer, default=0) */
    private $score               = 0;

    /** db field: duration (integer, default=0) */
    private $duration            = 0;

    /** db field: starttime (integer, default=0) */
    private $starttime           = 0;

    /** db field: endtime (integer, default=0) */
    private $endtime             = 0;

    /** db field: resumestart (integer, default=0) */
    private $resumestart         = 0;

    /** db field: resumefinish (integer, default=0) */
    private $resumefinish        = 0;

    /** db field: timestart (integer, default=0) */
    private $timestart           = 0;

    /** db field: timefinish (integer, default=0) */
    private $timefinish          = 0;

    /** db field: clickreportid (integer, default=0) */
    private $clickreportid       = 0;

    /** integer flag signifying whether or not to redirect after storing results */
    public $redirect             = 0;

    /** string to temporarily hold xml details returned from browser */
    public $details              = null;

    /**
     * get the "id" property
     *
     * @return primary key the current id $value
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * set the "id" property
     *
     * @param primary key the new id $value
     */
    public function set_id($value) {
        $this->id = $value;
    }

    /**
     * get the "taskid" property
     *
     * @return integer the current taskid $value
     */
    public function get_taskid() {
        return $this->taskid;
    }

    /**
     * set the "taskid" property
     *
     * @param integer the new taskid $value
     */
    public function set_taskid($value) {
        $this->taskid = $value;
    }

    /**
     * get the "userid" property
     *
     * @return integer the current userid $value
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * set the "userid" property
     *
     * @param integer the new userid $value
     */
    public function set_userid($value) {
        $this->userid = $value;
    }

    /**
     * get the "cnumber" property
     *
     * @return integer the current cnumber $value
     */
    public function get_cnumber() {
        return $this->cnumber;
    }

    /**
     * set the "cnumber" property
     *
     * @param integer the new cnumber $value
     */
    public function set_cnumber($value) {
        $this->cnumber = $value;
    }

    /**
     * get the "tnumber" property
     *
     * @return integer the current tnumber $value
     */
    public function get_tnumber() {
        return $this->tnumber;
    }

    /**
     * set the "tnumber" property
     *
     * @param integer the new tnumber $value
     */
    public function set_tnumber($value) {
        $this->tnumber = $value;
    }

    /**
     * get the "status" property
     *
     * @return integer the current status $value
     */
    public function get_status() {
        return $this->status;
    }

    /**
     * set the "status" property
     *
     * @param integer the new status $value
     */
    public function set_status($value) {
        $this->status = $value;
    }

    /**
     * get the "penalties" property
     *
     * @return integer the current penalties $value
     */
    public function get_penalties() {
        return $this->penalties;
    }

    /**
     * set the "penalties" property
     *
     * @param integer the new penalties $value
     */
    public function set_penalties($value) {
        $this->penalties = $value;
    }

    /**
     * get the "score" property
     *
     * @return integer the current score $value
     */
    public function get_score() {
        return $this->score;
    }

    /**
     * set the "score" property
     *
     * @param integer the new score $value
     */
    public function set_score($value) {
        $this->score = $value;
    }

    /**
     * get the "duration" property
     *
     * @return integer the current duration $value
     */
    public function get_duration() {
        return $this->duration;
    }

    /**
     * set the "duration" property
     *
     * @param integer the new duration $value
     */
    public function set_duration($value) {
        $this->duration = $value;
    }

    /**
     * get the "starttime" property
     *
     * @return integer the current starttime $value
     */
    public function get_starttime() {
        return $this->starttime;
    }

    /**
     * set the "starttime" property
     *
     * @param integer the new starttime $value
     */
    public function set_starttime($value) {
        $this->starttime = $value;
    }

    /**
     * get the "endtime" property
     *
     * @return integer the current endtime $value
     */
    public function get_endtime() {
        return $this->endtime;
    }

    /**
     * set the "endtime" property
     *
     * @param integer the new endtime $value
     */
    public function set_endtime($value) {
        $this->endtime = $value;
    }

    /**
     * get the "resumestart" property
     *
     * @return integer the current resumestart $value
     */
    public function get_resumestart() {
        return $this->resumestart;
    }

    /**
     * set the "resumestart" property
     *
     * @param integer the new resumestart $value
     */
    public function set_resumestart($value) {
        $this->resumestart = $value;
    }

    /**
     * get the "resumefinish" property
     *
     * @return integer the current resumefinish $value
     */
    public function get_resumefinish() {
        return $this->resumefinish;
    }

    /**
     * set the "resumefinish" property
     *
     * @param integer the new resumefinish $value
     */
    public function set_resumefinish($value) {
        $this->resumefinish = $value;
    }

    /**
     * get the "timestart" property
     *
     * @return integer the current timestart $value
     */
    public function get_timestart() {
        return $this->timestart;
    }

    /**
     * set the "timestart" property
     *
     * @param integer the new timestart $value
     */
    public function set_timestart($value) {
        $this->timestart = $value;
    }

    /**
     * get the "timefinish" property
     *
     * @return integer the current timefinish $value
     */
    public function get_timefinish() {
        return $this->timefinish;
    }

    /**
     * set the "timefinish" property
     *
     * @param integer the new timefinish $value
     */
    public function set_timefinish($value) {
        $this->timefinish = $value;
    }

    /**
     * get the "clickreportid" property
     *
     * @return integer the current clickreportid $value
     */
    public function get_clickreportid() {
        return $this->clickreportid;
    }

    /**
     * set the "clickreportid" property
     *
     * @param integer the new clickreportid $value
     */
    public function set_clickreportid($value) {
        $this->clickreportid = $value;
    }
}

