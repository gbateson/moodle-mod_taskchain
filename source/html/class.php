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
    public function get_name()  {
        if (! isset($this->name)) {
            $this->name = '';
            $this->title = '';

            if (! $this->get_filecontents()) {
                // empty file - shouldn't happen !!
                return false;
            }

            $search = '/<((?:h[1-6])|p|div|title)[^>]*>(.*?)<\/\1[^>]*>/is';
            if (preg_match_all($search, $this->filecontents, $matches)) {

                // search string to match style and script blocks
                $search = '/<(script|style)[^>]*>.*?<\/\1[^>]*>\s/is';

                $i_max = count($matches[0]);
                for ($i=0; $i<$i_max; $i++) {
                    $match = $matches[2][$i];
                    $match = preg_replace($search, '', $match);
                    if ($this->name = trim(strip_tags($match))) {
                        $this->title = trim($matches[2][$i]);
                        break;
                    }
                }
            }
        }
        return $this->name;
    }

    /**
     * get_title
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_title() {
        $this->get_name(); // set name and title
        return $this->title;

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
