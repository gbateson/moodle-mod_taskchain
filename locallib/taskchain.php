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
 * mod/taskchain/locallib/taskchain.php
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
 * taskchain
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain extends taskchain_base {

    /** db field: id (primary key, default=null) */
    private $id                  = null;

    /** db field: course (integer, default=0) */
    private $course              = 0;

    /** db field: name (string (255), default='') */
    private $name                = '';

    /** db field: timecreated (integer, default=0) */
    private $timecreated         = 0;

    /** db field: timemodified (integer, default=0) */
    private $timemodified        = 0;

    /** db field: completionmingrade (decimal, default=0.0) */
    private $completionmingrade  = 0.0;

    /** db field: completionpass (integer, default=0) */
    private $completionpass    = 0;

    /** db field: completioncompleted (integer, default=0) */
    private $completioncompleted = 0;

    public $gradelimit           = 0;
    public $gradeweighting       = 0;

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
     * get the "course" property
     *
     * @return integer the current course $value
     */
    public function get_course() {
        return $this->course;
    }

    /**
     * set the "course" property
     *
     * @param integer the new course $value
     */
    public function set_course($value) {
        $this->course = $value;
    }

    /**
     * get the "name" property
     *
     * @return string (255) the current name $value
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * set the "name" property
     *
     * @param string (255) the new name $value
     */
    public function set_name($value) {
        $this->name = $value;
    }

    /**
     * get the "timecreated" property
     *
     * @return integer the current timecreated $value
     */
    public function get_timecreated() {
        return $this->timecreated;
    }

    /**
     * set the "timecreated" property
     *
     * @param integer the new timecreated $value
     */
    public function set_timecreated($value) {
        $this->timecreated = $value;
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

    /**
     * get the "completionmingrade" property
     *
     * @return integer the current completionmingrade $value
     */
    public function get_completionmingrade() {
        return $this->completionmingrade;
    }

    /**
     * set the "completionmingrade" property
     *
     * @param integer the new completionmingrade $value
     */
    public function set_completionmingrade($value) {
        $this->completionmingrade = $value;
    }

    /**
     * get the "completionpass" property
     *
     * @return integer the current completionpass $value
     */
    public function get_completionpass() {
        return $this->completionpass;
    }

    /**
     * set the "completionpass" property
     *
     * @param integer the new completionpass $value
     */
    public function set_completionpass($value) {
        $this->completionpass = $value;
    }

    /**
     * get the "completioncompleted" property
     *
     * @return integer the current completioncompleted $value
     */
    public function get_completioncompleted() {
        return $this->completioncompleted;
    }

    /**
     * set the "completioncompleted" property
     *
     * @param integer the new completioncompleted $value
     */
    public function set_completioncompleted($value) {
        $this->completioncompleted = $value;
    }
}

