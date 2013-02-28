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
 * mod/taskchain/locallib/taskchain_string.php
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
 * taskchain_string
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_string extends taskchain_base {

    /** db field: id (primary key, default=null) */
    private $id                  = null;

    /** db field: string (string, default='') */
    private $string              = '';

    /** db field: md5key (string (32), default='') */
    private $md5key              = '';

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
     * get the "string" property
     *
     * @return string the current string $value
     */
    public function get_string() {
        return $this->string;
    }

    /**
     * set the "string" property
     *
     * @param string the new string $value
     */
    public function set_string($value) {
        $this->string = $value;
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
}

