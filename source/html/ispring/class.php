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
 * mod/taskchain/source/html/ispring/class.php
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
require_once($CFG->dirroot.'/mod/taskchain/source/html/class.php');

/**
 * taskchain_source_html_ispring
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_source_html_ispring extends taskchain_source_html {
    // properties of the icon for this source file type
    public $icon = 'mod/taskchain/file/html/ispring/icon.gif';

    // returns taskchain_source object if $filename is a task file, or false otherwise

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

        if (! preg_match('/<!--\s*<!DOCTYPE[^>]*>\s*-->/', $this->filecontents)) {
            // no fancy DOCTYPE workarounds for IE6
            return false;
        }

        // detect <object ...>, <embed ...> and self-closing <script ... /> tags
        if (! preg_match('/<object[^>]*id="presentation"[^>]*>/', $this->filecontents)) {
            return false;
        }

        if (! preg_match('/<embed[^>]*name="presentation"[^>]*>/', $this->filecontents)) {
            return false;
        }

        if (! preg_match('/<script[^>]*src="[^">]*fixprompt.js"[^>]*\/>/', $this->filecontents)) {
            return false;
        }

        return true;
    }

    // returns the introduction text for a task

    /**
     * get_entrytext
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_entrytext() {
        return '';
    }
} // end class
