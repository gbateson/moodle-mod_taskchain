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
 * mod/taskchain/locallib/taskchain_detail.php
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
 * taskchain_detail
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_detail extends taskchain_base {

    /** db field: id (primary key, default=null) */
    private $id                  = null;

    /** db field: attemptid (integer, default=0) */
    private $attemptid           = 0;

    /** db field: details (string, default='') */
    private $details             = '';

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
     * get the "attemptid" property
     *
     * @return integer the current attemptid $value
     */
    public function get_attemptid() {
        return $this->attemptid;
    }

    /**
     * set the "attemptid" property
     *
     * @param integer the new attemptid $value
     */
    public function set_attemptid($value) {
        $this->attemptid = $value;
    }

    /**
     * get the "details" property
     *
     * @return string the current details $value
     */
    public function get_details() {
        return $this->details;
    }

    /**
     * set the "details" property
     *
     * @param string the new details $value
     */
    public function set_details($value) {
        $this->details = $value;
    }
}

