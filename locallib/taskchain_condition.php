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
 * mod/taskchain/locallib/taskchain_condition.php
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
 * taskchain_condition
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_condition extends taskchain_base {

    /** db field: id (primary key, default=null) */
    private $id                  = null;

    /** db field: taskid (integer, default=0) */
    private $taskid              = 0;

    /** db field: groupid (integer, default=0) */
    private $groupid             = 0;

    /** db field: conditiontype (integer, default=0) */
    private $conditiontype       = 0;

    /** db field: conditionscore (integer, default=0) */
    private $conditionscore      = 0;

    /** db field: conditiontaskid (integer, default=0) */
    private $conditiontaskid     = 0;

    /** db field: sortorder (integer, default=0) */
    private $sortorder           = 0;

    /** db field: attempttype (integer, default=0) */
    private $attempttype         = 0;

    /** db field: attemptcount (integer, default=0) */
    private $attemptcount        = 0;

    /** db field: attemptduration (integer, default=0) */
    private $attemptduration     = 0;

    /** db field: attemptdelay (integer, default=0) */
    private $attemptdelay        = 0;

    /** db field: nexttaskid (integer, default=0) */
    private $nexttaskid          = 0;

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
     * get the "groupid" property
     *
     * @return integer the current groupid $value
     */
    public function get_groupid() {
        return $this->groupid;
    }

    /**
     * set the "groupid" property
     *
     * @param integer the new groupid $value
     */
    public function set_groupid($value) {
        $this->groupid = $value;
    }

    /**
     * get the "conditiontype" property
     *
     * @return integer the current conditiontype $value
     */
    public function get_conditiontype() {
        return $this->conditiontype;
    }

    /**
     * set the "conditiontype" property
     *
     * @param integer the new conditiontype $value
     */
    public function set_conditiontype($value) {
        $this->conditiontype = $value;
    }

    /**
     * get the "conditionscore" property
     *
     * @return integer the current conditionscore $value
     */
    public function get_conditionscore() {
        return $this->conditionscore;
    }

    /**
     * set the "conditionscore" property
     *
     * @param integer the new conditionscore $value
     */
    public function set_conditionscore($value) {
        $this->conditionscore = $value;
    }

    /**
     * get the "conditiontaskid" property
     *
     * @return integer the current conditiontaskid $value
     */
    public function get_conditiontaskid() {
        return $this->conditiontaskid;
    }

    /**
     * set the "conditiontaskid" property
     *
     * @param integer the new conditiontaskid $value
     */
    public function set_conditiontaskid($value) {
        $this->conditiontaskid = $value;
    }

    /**
     * get the "sortorder" property
     *
     * @return integer the current sortorder $value
     */
    public function get_sortorder() {
        return $this->sortorder;
    }

    /**
     * set the "sortorder" property
     *
     * @param integer the new sortorder $value
     */
    public function set_sortorder($value) {
        $this->sortorder = $value;
    }

    /**
     * get the "attempttype" property
     *
     * @return integer the current attempttype $value
     */
    public function get_attempttype() {
        return $this->attempttype;
    }

    /**
     * set the "attempttype" property
     *
     * @param integer the new attempttype $value
     */
    public function set_attempttype($value) {
        $this->attempttype = $value;
    }

    /**
     * get the "attemptcount" property
     *
     * @return integer the current attemptcount $value
     */
    public function get_attemptcount() {
        return $this->attemptcount;
    }

    /**
     * set the "attemptcount" property
     *
     * @param integer the new attemptcount $value
     */
    public function set_attemptcount($value) {
        $this->attemptcount = $value;
    }

    /**
     * get the "attemptduration" property
     *
     * @return integer the current attemptduration $value
     */
    public function get_attemptduration() {
        return $this->attemptduration;
    }

    /**
     * set the "attemptduration" property
     *
     * @param integer the new attemptduration $value
     */
    public function set_attemptduration($value) {
        $this->attemptduration = $value;
    }

    /**
     * get the "attemptdelay" property
     *
     * @return integer the current attemptdelay $value
     */
    public function get_attemptdelay() {
        return $this->attemptdelay;
    }

    /**
     * set the "attemptdelay" property
     *
     * @param integer the new attemptdelay $value
     */
    public function set_attemptdelay($value) {
        $this->attemptdelay = $value;
    }

    /**
     * get the "nexttaskid" property
     *
     * @return integer the current nexttaskid $value
     */
    public function get_nexttaskid() {
        return $this->nexttaskid;
    }

    /**
     * set the "nexttaskid" property
     *
     * @param integer the new nexttaskid $value
     */
    public function set_nexttaskid($value) {
        $this->nexttaskid = $value;
    }
}

