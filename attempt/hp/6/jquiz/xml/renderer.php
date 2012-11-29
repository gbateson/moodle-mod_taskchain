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
 * mod/taskchain/attempt/hp/6/jquiz/xml/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/attempt/hp/6/jquiz/renderer.php');

/**
 * mod_taskchain_attempt_hp_6_jquiz_xml_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_hp_6_jquiz_xml_renderer extends mod_taskchain_attempt_hp_6_jquiz_renderer {

    /**
     * expand_ItemArray
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_ItemArray() {
        $q = 0;
        $qq = 0;
        $str = 'I=new Array();'."\n";
        $tags = 'data,questions,question-record';
        while (($question="[$q]['#']") && $this->TC->task->source->xml_value($tags, $question) && ($answers = $question."['answers'][0]['#']") && $this->TC->task->source->xml_value($tags, $answers)) {

            $question_type = $this->TC->task->source->xml_value_int($tags, $question."['question-type'][0]['#']");
            $weighting = $this->TC->task->source->xml_value_int($tags, $question."['weighting'][0]['#']");
            $clue = $this->TC->task->source->xml_value_js($tags, $question."['clue'][0]['#']");

            $a = 0;
            $aa = 0;
            while (($answer = $answers."['answer'][$a]['#']") && $this->TC->task->source->xml_value($tags, $answer)) {
                $text     = $this->expand_ItemArray_answertext($tags,  $answer, $a);
                $feedback = $this->TC->task->source->xml_value_js($tags,  $answer."['feedback'][0]['#']");
                $correct  = $this->TC->task->source->xml_value_int($tags, $answer."['correct'][0]['#']");
                $percent  = $this->TC->task->source->xml_value_int($tags, $answer."['percent-correct'][0]['#']");
                $include  = $this->TC->task->source->xml_value_int($tags, $answer."['include-in-mc-options'][0]['#']");
                if (strlen($text)) {
                    if ($aa==0) { // first time only
                        $str .= "\n";
                        $str .= "I[$qq] = new Array();\n";
                        $str .= "I[$qq][0] = $weighting;\n";
                        $str .= "I[$qq][1] = '$clue';\n";
                        $str .= "I[$qq][2] = '".($question_type-1)."';\n";
                        $str .= "I[$qq][3] = new Array();\n";
                    }
                    $text = $this->TC->task->source->single_line($text, '');
                    $str .= "I[$qq][3][$aa] = new Array('$text','$feedback',$correct,$percent,$include);\n";
                    $aa++;
                }
                $a++;
            }
            if ($aa) {
                $qq++;
            }
            $q++;
        }
        return $str;
    }

    /**
     * expand_ItemArray_answertext
     *
     * @param xxx $tags
     * @param xxx $answer
     * @param xxx $a
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_ItemArray_answertext($tags,  $answer, $a) {
        return $this->TC->task->source->xml_value_js($tags,  $answer."['text'][0]['#']");
    }
}
