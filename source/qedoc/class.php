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
 * mod/taskchain/source/qedoc/class.php
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
require_once($CFG->dirroot.'/mod/taskchain/source/class.php');

/**
 * taskchain_source_qedoc
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_source_qedoc extends taskchain_source {

    /**
     * is_taskfile
     *
     * @param xxx $sourcefile
     * @return xxx
     * @todo Finish documenting this function
     */
    public function is_taskfile() {
        // e.g. http://www.qedoc.net/library/PLJUB_019.zip
        $search = '/http:\/\/www\.qedoc.(?:com|net)\/library\/\w+\.zip/i';
        if (preg_match($search, $this->file->get_source())) { // url ?
            return true;
        } else {
            return false;
        }

        // Note: we may want to detect the following as well:
        // http://www.qedoc.net/qqp/jnlp/PLJUB_019.jnlp
    }

    /**
     * get_name
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_name() {
        return $this->file->get_filename();
    }

    /**
     * get_title
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_title() {
        return $this->file->get_filename();
    }
}
