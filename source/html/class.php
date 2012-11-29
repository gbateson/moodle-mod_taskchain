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
 * mod/taskchain/source/html/class.php
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
 * taskchain_source_html
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_source_html extends taskchain_source {
    // returns name of task that is displayed to user

    /**
     * get_name
     *
     * @param xxx $textonly (optional, default=true)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function html_get_name($textonly=true)  {
        if (! isset($this->name)) {
            $this->name = '';
            $this->title = '';

            if (! $this->get_filecontents()) {
                // empty file - shouldn't happen !!
                return false;
            }
            if (preg_match('/<h(\d)[^>]>(.*?)<\/h$1>/is', $this->filecontents, $matches)) {
                $this->name = trim(strip_tags($this->title));
                $this->title = trim($matches[1]);
            }
            if (! $this->name) {
                if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $this->filecontents, $matches)) {
                    $this->name = trim(strip_tags($matches[1]));
                    if (! $this->title) {
                        $this->title = trim($matches[1]);
                    }
                }
            }
        }
        if ($textonly) {
            return $this->name;
        } else {
            return $this->title;
        }
    }

    /**
     * get_title
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_title() {
        return $this->html_get_name(false);
    }

    // returns the introduction text for a task

    /**
     * get_entrytext
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_entrytext() {
        if (! isset($this->entrytext)) {
            $this->entrytext = '';

            if (! $this->get_filecontents()) {
                // empty file - shouldn't happen !!
                return false;
            }
            if (preg_match('/<(div|p)[^>]*>\s*(.*?)\s*<\/$1>/is', $this->filecontents, $matches)) {
                $this->entrytext .= '<'.$matches[1].'>'.$matches[2].'</'.$matches[1].'>';
            }
        }
        return $this->entrytext;
    }
} // end class
