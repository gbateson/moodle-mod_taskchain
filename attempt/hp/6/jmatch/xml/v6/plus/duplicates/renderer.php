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
 * mod/taskchain/attempt/hp/6/jmatch/xml/v6/plus/duplicates/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/attempt/hp/6/jmatch/xml/v6/plus/renderer.php');

/**
 * mod_taskchain_attempt_hp_6_jmatch_xml_v6_plus_duplicates_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_hp_6_jmatch_xml_v6_plus_duplicates_renderer extends mod_taskchain_attempt_hp_6_jmatch_xml_v6_plus_renderer {

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
        array_unshift($this->templatesfolders, 'mod/taskchain/attempt/hp/6/jmatch/xml/v6/plus/duplicates/templates');
    }

    /**
     * expand_DragArray
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_DragArray()  {
        $this->set_jmatch_items();
        $str = '';

        // simple array to map item keys and texts
        $texts = array();
        foreach ($this->l_items as $i=>$item) {
            $key = $item['key'];
            if (empty($this->r_items[$i]['fixed'])) {
                $texts[$key] = $item['text'];
            }
        }

        // array to map drag item keys to fixed items key(s)
        $keys = array();
        foreach ($this->l_items as $i=>$item) {
            $key = $item['key'];
            if (empty($this->r_items[$i]['fixed'])) {
                $texts_keys = array_keys($texts, $item['text']);
                foreach ($texts_keys as $i => $texts_key) {
                    $texts_keys[$i] = $texts_key + 1;
                }
                if (count($texts_keys)==1) {
                    $keys[$key] = $texts_keys[0];
                } else {
                    $keys[$key] = 'new Array('.implode(',', $texts_keys).')';
                }
            } else {
                // drag item is fixed
                $keys[$key] = $key + 1;
            }
        }
        unset($texts);

        foreach ($this->r_items as $i=>$item) {
            $str .= "D[$i] = new Array();\n";
            $str .= "D[$i][0] = '".$this->TC->task->source->js_value_safe($item['text'], true)."';\n";
            $str .= "D[$i][1] = ".$keys[$item['key']].";\n";
            $str .= "D[$i][2] = ".$item['fixed'].";\n";
        }
        return $str;
    }
}
