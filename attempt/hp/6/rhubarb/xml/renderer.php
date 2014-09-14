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
 * mod/taskchain/attempt/hp/6/rhubarb/xml/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/attempt/hp/6/rhubarb/renderer.php');

/**
 * mod_taskchain_attempt_hp_6_rhubarb_xml_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_hp_6_rhubarb_xml_renderer extends mod_taskchain_attempt_hp_6_rhubarb_renderer {

    /**
     * expand_JSRhubarb6
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_JSRhubarb6()  {
        return $this->expand_template('rhubarb6.js_');
    }

    /**
     * expand_Finished
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_Finished()  {
        return $this->TC->task->source->xml_value_js($this->TC->task->source->hbs_software.'-config-file,global,finished');
    }

    /**
     * expand_GuessHere
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_GuessHere()  {
        return $this->TC->task->source->xml_value_js($this->TC->task->source->hbs_software.'-config-file,global,type-your-guess-here');
    }

    /**
     * expand_IncorrectWords
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_IncorrectWords()  {
        return $this->TC->task->source->xml_value_js($this->TC->task->source->hbs_software.'-config-file,'.$this->TC->task->source->hbs_tasktype.',incorrect-words');
    }

    /**
     * expand_PreparingExercise
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_PreparingExercise()  {
        return $this->TC->task->source->xml_value_js($this->TC->task->source->hbs_software.'-config-file,global,preparing-exercise');
    }

    /**
     * expand_Solution
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_Solution()  {
        return $this->TC->task->source->xml_value_int($this->TC->task->source->hbs_software.'-config-file,'.$this->TC->task->source->hbs_tasktype.',include-solution');
    }

    /**
     * expand_WordsArray
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_WordsArray()  {
        $str = '';

        $text = $this->TC->task->source->xml_value('data,rhubarb-text');
        $text = mod_taskchain::textlib('entities_to_utf8', $text);

        $space = ' \\x09\\x0A\\x0C\\x0D'; // " \t\n\r\l"
        $punc = preg_quote('!"#$%&()*+,-./:;+<=>?@[]\\^_`{|}~', '/'); // not apostrophe \'
        $search = '/([^'.$punc.$space.']+)|(['.$punc.']['.$punc.$space.']*)/s';

        if (preg_match_all($search, $text, $matches)) {
            foreach ($matches[0] as $i => $word) {
                $str .= "Words[$i] = '".$this->TC->task->source->js_value_safe($word, true)."';\n";
            }
        }
        return $str;
    }

    /**
     * expand_FreeWordsArray
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_FreeWordsArray()  {
        $str = '';
        $i =0;
        $tags = 'data,free-words,free-word';
        while ($word = $this->TC->task->source->xml_value($tags, "[$i]['#']")) {
            $str .= "FreeWords[$i] = '".$this->TC->task->source->js_value_safe($word, true)."';\n";
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
