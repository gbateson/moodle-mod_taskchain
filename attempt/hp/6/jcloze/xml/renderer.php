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
 * mod/taskchain/attempt/hp/6/jcloze/xml/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/attempt/hp/6/jcloze/renderer.php');

/**
 * mod_taskchain_attempt_hp_6_jcloze_xml_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_hp_6_jcloze_xml_renderer extends mod_taskchain_attempt_hp_6_jcloze_renderer {

    /**
     * expand_ItemArray
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_ItemArray() {
        $q = 0;
        $qq = 0;

        $str = '';
        // Note: I = new Array(); is declared in hp6jcloze.js_

        $tags = 'data,gap-fill,question-record';
        while (($question="[$q]['#']") && $this->TC->task->source->xml_value($tags, $question)) {

            $a = 0;
            $aa = 0;

            while (($answer=$question."['answer'][$a]['#']") && $this->TC->task->source->xml_value($tags, $answer)) {
                $text = $this->TC->task->source->xml_value_js($tags,  $answer."['text'][0]['#']");
                if (strlen($text)) {
                    if ($aa==0) { // first time only
                        $str .= "\n";
                        $str .= "I[$qq] = new Array();\n";
                        $str .= "I[$qq][1] = new Array();\n";
                    }
                    $str .= "I[$qq][1][$aa] = new Array();\n";
                    $str .= "I[$qq][1][$aa][0] = '$text';\n";
                    $aa++;
                }
                $a++;
            }
            // add clue, if any answers were found
            if ($aa) {
                $clue = $this->TC->task->source->xml_value_js($tags, $question."['clue'][0]['#']");
                $str .= "I[$qq][2] = '$clue';\n";
                $qq++;
            }
            $q++;
        }

        return $str;
    }
}
