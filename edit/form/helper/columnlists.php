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
 * mod/taskchain/form/columnlists.php
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
require_once(__DIR__.'/record.php');

/**
 * taskchain_form_helper_condition
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_form_helper_columnlists extends taskchain_form_helper_record {

    /** type of record **/
    protected $recordtype = 'columnlists';

    /** sections and fields in this form **/
    protected $sections = array();

    /** default values in a chain record */
    protected $defaultvalues = array();

    /**
     * __construct
     *
     * @param object $mform a MoodleQuickForm
     * @param object $context a context record from the database
     * @param string $type "chains" or "tasks"
     * @param boolean $multiple (optional, default=false)
     * @todo Finish documenting this function
     */
    public function __construct(&$mform, &$context, &$type, $multiple=false) {
        global $CFG, $TC;

        if (empty($TC)) {
            require_once($CFG->dirroot.'/mod/taskchain/locallib.php');
            $TC = new mod_taskchain();
        }

        $this->TC       = &$TC;
        $this->mform    = $mform;
        $this->context  = $context;
        $this->record   = $record;
        $this->multiple = $multiple;
    }
}
