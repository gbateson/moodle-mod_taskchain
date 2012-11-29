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
 * mod/taskchain/source/hp/6/jmatch/html/intro/class.php
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
require_once($CFG->dirroot.'/mod/taskchain/source/hp/6/jmatch/html/class.php');

/**
 * taskchain_source_hp_6_jmatch_html_intro
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_source_hp_6_jmatch_html_intro extends taskchain_source_hp_6_jmatch_html {

    /**
     * is_taskfile
     *
     * @param xxx $sourcefile
     * @return xxx
     * @todo Finish documenting this function
     */
    public function is_taskfile() {
        if (! preg_match('/\.html?$/', $this->file->get_filename())) {
            // wrong file type
            return false;
        }

        if (! $this->get_filecontents()) {
            // empty or non-existant file
            return false;
        }

        if (! strpos($this->filecontents, '<div class="Feedback" id="DivIntroPage">')) {
            // not a jmatch-intro file
            return false;
        }

        if (strpos($this->filecontents, '<div id="MainDiv" class="StdDiv">')) {
            if (strpos($this->filecontents, '<div id="MatchDiv" align="center">')) {
                // jmatch-intro v6
                return true;
            }
        }

        if (strpos($this->filecontents, '<div class="StdDiv" id="CheckButtonDiv">')) {
            if (strpos($this->filecontents, 'F = new Array();')) {
                if (strpos($this->filecontents, 'D = new Array();')) { // overkill?
                    // jmatch-intro v6+ (drag and drop)
                    return true;
                }
            }
        }

        // not a jmatch-intro file
        return false;
    }
}
