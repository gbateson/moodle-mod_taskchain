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
 * mod/taskchain/attempt/hp/6/jmix/xml/v6/plus/deluxe/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/attempt/hp/6/jmix/xml/v6/plus/renderer.php');

/**
 * mod_taskchain_attempt_hp_6_jmix_xml_v6_plus_deluxe_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_hp_6_jmix_xml_v6_plus_deluxe_renderer extends mod_taskchain_attempt_hp_6_jmix_xml_v6_plus_renderer {

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
        array_unshift($this->templatesfolders, 'mod/taskchain/attempt/hp/6/jmix/xml/v6/plus/deluxe/templates');
    }

    /**
     * fix_bodycontent_DragAndDrop
     *
     * @todo Finish documenting this function
     */
    public function fix_bodycontent_DragAndDrop($prefix='', $suffix='') {
        // user-string-1: prefix (optional)
        // user-string-2: suffix (optional)
        $prefix = trim($this->expand_UserDefined1());
        $suffix = trim($this->expand_UserDefined2());
        parent::fix_bodycontent_DragAndDrop($prefix, $suffix);
    }

     /**
      * fix_js_StartUp_DragAndDrop_DragArea
      *
      * @param xxx $substr (passed by reference)
      * @todo Finish documenting this function
      */
     public function fix_js_StartUp_DragAndDrop_DragArea(&$substr) {
        // fix LeftCol (=left side of drag area)
        $search = '/LeftColPos = [^;]+;/';
        $replace = "LeftColPos = getOffset(document.getElementById('CheckButtonDiv'),'Left') + 20;";
        $substr = preg_replace($search, $replace, $substr, 1);

        // fix DivWidth (=width of drag area)
        $search = '/DivWidth = [^;]+;/';
        $replace = "DivWidth = getOffset(document.getElementById('CheckButtonDiv'),'Width') - 40;";
        $substr = preg_replace($search, $replace, $substr, 1);

        // fix DragTop (=top side of drag area)
        $search = '/DragTop = [^;]+;/';
        $replace = "DragTop = getOffset(document.getElementById('CheckButtonDiv'),'Bottom') + 10;";
        $substr = preg_replace($search, $replace, $substr, 1);
    }

    /**
     * expand_SegmentArray
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_SegmentArray($more_values=array()) {
        // user-string-3: (optional)
        //   distractor words: words, delimited, by, commas, like, this
        //   phrases: (one phrase) [another phrase] {yet another phrase}
        if ($value = $this->expand_UserDefined3()) {
            if (preg_match('/^(\()|(\[)|(\{).*(?(1)\)|(?(2)\]|(?(3)\})))$/', $value)) {
                $search = '/\s*\\'.substr($value, -1).'\s*\\'.substr($value, 0, 1).'\s*/';
                $more_values = preg_split($search, substr($value, 1, -1));
            } else {
                $more_values = preg_split('/\s*,\s*/', trim($value));
            }
        } else {
            $more_values = array();
        }
        return parent::expand_SegmentArray($more_values);
    }
}
