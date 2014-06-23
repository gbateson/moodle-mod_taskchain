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
 * Render an attempt at a TaskChain quiz
 * Output format: hp_6_jmatch_xml_sort
 *
 * @package   mod-taskchain
 * @copyright 2010 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// get parent class
require_once($CFG->dirroot.'/mod/taskchain/attempt/hp/6/jmatch/xml/renderer.php');

/**
 * mod_taskchain_attempt_hp_6_jmatch_xml_sort_renderer
 *
 * @copyright 2010 Gordon Bateson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class mod_taskchain_attempt_hp_6_jmatch_xml_sort_renderer extends mod_taskchain_attempt_hp_6_jmatch_xml_renderer {

    //public $js_object_type = 'JMatchSort';
    public $templatefile = 'djmatch6.ht_';

    /**
     * constructor function
     *
     * @param xxx $page
     * @param xxx $target
     * @todo Finish documenting this function
     */
    public function __construct(moodle_page $page, $target)  {
        parent::__construct($page, $target);

        // prepend templates for this output format
        array_unshift($this->templatesfolders, 'mod/taskchain/attempt/hp/6/jmatch/xml/sort/templates');
    }

    /**
     * List of source types which this renderer can handle
     *
     * @return array of strings
     */
    public static function sourcetypes()  {
        return array('hp_6_jmatch_xml');
    }
}
