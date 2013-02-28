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
 * mod/taskchain/locallib/taskchain_chain_attempt.php
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
 * taskchain_chain_attempt
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_chain_attempt extends taskchain_base {

    /** db field: id (primary key, default=null) */
    private $id                  = null;

    /** db field: chainid (integer, default=0) */
    private $chainid             = 0;

    /** db field: cnumber (integer, default=0) */
    private $cnumber             = 0;

    /** db field: userid (integer, default=0) */
    private $userid              = 0;

    /** db field: grade (integer, default=0) */
    private $grade               = 0;

    /** db field: status (integer, default=0) */
    private $status              = 0;

    /** db field: duration (integer, default=0) */
    private $duration            = 0;

    /** db field: timemodified (integer, default=0) */
    private $timemodified        = 0;

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
     * get the "chainid" property
     *
     * @return integer the current chainid $value
     */
    public function get_chainid() {
        return $this->chainid;
    }

    /**
     * set the "chainid" property
     *
     * @param integer the new chainid $value
     */
    public function set_chainid($value) {
        $this->chainid = $value;
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
     * get the "grade" property
     *
     * @return integer the current grade $value
     */
    public function get_grade() {
        return $this->grade;
    }

    /**
     * set the "grade" property
     *
     * @param integer the new grade $value
     */
    public function set_grade($value) {
        $this->grade = $value;
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
     * get the "timemodified" property
     *
     * @return integer the current timemodified $value
     */
    public function get_timemodified() {
        return $this->timemodified;
    }

    /**
     * set the "timemodified" property
     *
     * @param integer the new timemodified $value
     */
    public function set_timemodified($value) {
        $this->timemodified = $value;
    }
}

