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
 * mod/taskchain/attempt/html/ispring/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/attempt/html/renderer.php');

/**
 * mod_taskchain_attempt_html_ispring_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_html_ispring_renderer extends mod_taskchain_attempt_html_renderer {

    /**
     * List of source types which this renderer can handle
     *
     * @return array of strings
     */
    static public function sourcetypes()  {
        return array('html_ispring');
    }

    /**
     * preprocessing
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function preprocessing()  {
        if ($this->cache_uptodate) {
            return true;
        }

        if (! $this->TC->task->source) {
            $this->TC->task->get_source();
        }

        if (! $this->TC->task->source->get_filecontents()) {
            // empty source file - shouldn't happen !!
            return false;
        }

        // remove doctype
        $search = '/\s*(?:<!--\s*)?<!DOCTYPE[^>]*>\s*(?:-->\s*)?/s';
        $this->TC->task->source->filecontents = preg_replace($search, '', $this->TC->task->source->filecontents);

        // replace <object> with link and force through filters
        $search = '/<object id="presentation"[^>]*>.*?<param name="movie" value="([^">]*)"[^>]*>.*?<\/object>/is';
        $replace = '<a href="$1?d=800x600">$1</a>';
        $this->TC->task->source->filecontents = preg_replace($search, $replace, $this->TC->task->source->filecontents);

        // remove fixprompt.js
        $search = '/<script[^>]*src="[^">]*fixprompt.js"[^>]*(?:(?:\/>)|(?:<\/script>))\s*/s';
        $this->TC->task->source->filecontents = preg_replace($search, '', $this->TC->task->source->filecontents);

        parent::preprocessing();
    }
}
