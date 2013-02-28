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
 * mod/taskchain/locallib/taskchain_question.php
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
 * taskchain_question
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_question extends taskchain_base {

    /** db field: id (primary key, default=null) */
    private $id                  = null;

    /** db field: taskid (integer, default=0) */
    private $taskid              = 0;

    /** db field: name (string, default='') */
    private $name                = '';

    /** db field: md5key (string (32), default='') */
    private $md5key              = '';

    /** db field: type (integer, default=0) */
    private $type                = 0;

    /** db field: text (integer, default=0) */
    private $text                = 0;

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
     * get the "name" property
     *
     * @return string the current name $value
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * set the "name" property
     *
     * @param string the new name $value
     */
    public function set_name($value) {
        $this->name = $value;
    }

    /**
     * get the "md5key" property
     *
     * @return string (32) the current md5key $value
     */
    public function get_md5key() {
        return $this->md5key;
    }

    /**
     * set the "md5key" property
     *
     * @param string (32) the new md5key $value
     */
    public function set_md5key($value) {
        $this->md5key = $value;
    }

    /**
     * get the "type" property
     *
     * @return integer the current type $value
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * set the "type" property
     *
     * @param integer the new type $value
     */
    public function set_type($value) {
        $this->type = $value;
    }

    /**
     * get the "text" property
     *
     * @return integer the current text $value
     */
    public function get_text() {
        return $this->text;
    }

    /**
     * set the "text" property
     *
     * @param integer the new text $value
     */
    public function set_text($value) {
        $this->text = $value;
    }
}

