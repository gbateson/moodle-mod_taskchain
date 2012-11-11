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
 * mod/taskchain/attempt/hp/6/sequitur/xml/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/attempt/hp/6/sequitur/renderer.php');

/**
 * mod_taskchain_attempt_hp_6_sequitur_xml_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_hp_6_sequitur_xml_renderer extends mod_taskchain_attempt_hp_6_sequitur_renderer {

    /**
     * expand_JSSequitur6
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_JSSequitur6() {
        return $this->expand_template('sequitur6.js_');
    }

    /**
     * expand_NumberOfOptions
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_NumberOfOptions()  {
        $tags = $this->TC->task->source->hbs_software.'-config-file,'.$this->TC->task->source->hbs_tasktype.',number-of-options';
        return $this->TC->task->source->xml_value_int($tags);
    }

    /**
     * expand_PartText
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_PartText()  {
        $tags = $this->TC->task->source->hbs_software.'-config-file,'.$this->TC->task->source->hbs_tasktype.',show-part-text';
        return $this->TC->task->source->xml_value($tags);
    }

    /**
     * expand_Solution
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_Solution()  {
        $tags = $this->TC->task->source->hbs_software.'-config-file,'.$this->TC->task->source->hbs_tasktype.',include-solution';
        return $this->TC->task->source->xml_value_int($tags);
    }

    /**
     * expand_SolutionCaption
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_SolutionCaption() {
        $tags = $this->TC->task->source->hbs_software.'-config-file,global,solution-caption';
        return $this->TC->task->source->xml_value($tags);
    }

    /**
     * expand_Score
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_Score()  {
        $tags = $this->TC->task->source->hbs_software.'-config-file,global,your-score-is';
        return $this->TC->task->source->xml_value_js($tags);
    }

    /**
     * expand_WholeText
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_WholeText()  {
        $tags = $this->TC->task->source->hbs_software.'-config-file,'.$this->TC->task->source->hbs_tasktype.',show-whole-text';
        return $this->TC->task->source->xml_value($tags);
    }

    /**
     * expand_SegmentsArray
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_SegmentsArray() {
        // we might have empty segments, so we need to first
        // find out how many segments there are and then go
        // through them all, ignoring the empty ones

        $i_max = 0;
        if ($segments = $this->TC->task->source->xml_value('data,segments')) {
            if (isset($segments['segment'])) {
                $i_max = count($segments['segment']);
            }
        }
        unset($segments);

        $str = '';
        $tags = 'data,segments,segment';

        $i =0 ;
        $ii =0 ;
        while ($i<$i_max) {
            if ($segment = $this->TC->task->source->xml_value_js($tags, "[$i]['#']")) {
                $str .= "Segments[$ii]='$segment';\n";
                $ii++;
            }
            $i++;
        }

        return $str;
    }

    /**
     * expand_StyleSheet
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_StyleSheet()  {
        return $this->expand_template('tt3.cs_');
    }
}
