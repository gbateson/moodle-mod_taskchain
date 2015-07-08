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
 * mod/taskchain/attempt/hp/6/jcloze/xml/v6/autoadvance/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/attempt/hp/6/jcloze/xml/v6/renderer.php');

/**
 * mod_taskchain_attempt_hp_6_jcloze_xml_v6_autoadvance_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_hp_6_jcloze_xml_v6_autoadvance_renderer extends mod_taskchain_attempt_hp_6_jcloze_xml_v6_renderer {

    /**
     * get_js_functionnames
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_js_functionnames() {
        // start list of function names
        $names = parent::get_js_functionnames();
        $names .= ($names ? ',' : '').'CheckAnswer';
        return $names;
    }

    /**
     * fix_js_StartUp
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @return xxx
     * @todo Finish documenting this function
     */
    public function fix_js_StartUp(&$str, $start, $length) {
        $this->jcloze_autoadvance_fix_js_StartUp($str, $start, $length);
    }

    /**
     * jcloze_autoadvance_gapid
     *
     * @todo Finish documenting this function
     */
    function jcloze_autoadvance_gapid() {
        return '^Gap([0-9]+)$';
    }

    /**
     * jcloze_autoadvance_gaptype
     *
     * @todo Finish documenting this function
     */
    function jcloze_autoadvance_gaptype() {
        if ($this->use_DropDownList()) {
            return 'select';
        } else {
            return 'input';
        }
    }

    /**
     * fix_js_CheckAnswer
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @todo Finish documenting this function
     */
    public function fix_js_CheckAnswer(&$str, $start, $length) {
        $substr = substr($str, $start, $length);

        // make sure we trim answer as  well as response when checking for correctness
        $search = '/(?<=TrimString\(UpperGuess\) == )(UpperAnswer)/';
        $substr = preg_replace($search, 'TrimString($1)', $substr);

        if ($this->use_DropDownList()) {
            // only treat 1st possible answer as correct
            $substr = str_replace('I[GapNum][1].length', '1', $substr);
        }

        $str = substr_replace($str, $substr, $start, $length);
    }

    /**
     * fix_js_CheckAnswers
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @todo Finish documenting this function
     */
    public function fix_js_CheckAnswers(&$str, $start, $length) {
        $substr = substr($str, $start, $length);

        $search = '/for \(var i \= 0; i<I\.length; i\+\+\)\{(.*?)(?=var TotalScore = 0;)/s';
        $replace = "\n"
            ."	var clues = new Array();\n"
            ."	var li = ListItems[CurrentListItem];\n"
            ."	if (li && li.AnsweredCorrectly==false) {\n"
            ."\n"
            ."		var gapid = new RegExp('^Gap([0-9]+)\$');\n"
            ."		var ListItemScore = 0;\n"
            ."\n"
            ."		var g_max = li.gaps.length;\n"
            ."		for (var g=0; g<g_max; g++) {\n"
            ."\n"
            ."			var m = li.gaps[g].id.match(gapid);\n"
            ."			if (! m) {\n"
            ."				continue;\n"
            ."			}\n"
            ."\n"
            ."			var i = parseInt(m[1]);\n"
            ."			if (! State[i]) {\n"
            ."				continue;\n"
            ."			}\n"
            ."\n"
            ."			if (State[i].AnsweredCorrectly) {\n"
            ."				ListItemScore += State[i].ItemScore;\n"
            ."			} else {\n"
            ."				var GapValue = GetGapValue(i);\n"
            ."				if (typeof(GapValue)=='string' && GapValue=='') {\n"
            ."					// not answered yet\n"
            ."					AllCorrect = false;\n"
            ."				} else if (CheckAnswer(i, true) > -1) {\n"
            ."					// correct answer\n"
            ."					var TotalChars = GapValue.length;\n"
            ."					State[i].ItemScore = (TotalChars-State[i].HintsAndChecks)/TotalChars;\n"
            ."					if (State[i].ClueGiven){\n"
            ."						State[i].ItemScore /= 2;\n"
            ."					}\n"
            ."					if (State[i].ItemScore < 0){\n"
            ."						State[i].ItemScore = 0;\n"
            ."					}\n"
            ."					State[i].AnsweredCorrectly = true;\n"
            ."					SetCorrectAnswer(i, GapValue);\n"
            ."					ListItemScore += State[i].ItemScore;\n"
            ."				} else {\n"
            ."					// wrong answer\n"
            ."					var clue = I[i][2];\n"
            ."					if (clue) {\n"
            ."						var c_max = clues.length;\n"
            ."						for (var c=0; c<c_max; c++) {\n"
            ."							if (clues[c]==clue) {\n"
            ."								break;\n"
            ."							}\n"
            ."						}\n"
            ."						if (c==c_max) {\n"
            ."							clues[c] = clue;\n"
            ."						}\n"
            ."						State[i].ClueGiven = true;\n"
            ."					}\n"
            ."					AllCorrect = false;\n"
            ."				}\n"
            ."			}\n"
            ."		}\n"
            ."		li.AnsweredCorrectly = AllCorrect;\n"
            ."		if (li.AnsweredCorrectly) {\n"
            ."			li.score = Math.round(100 * (ListItemScore / g_max));\n"
            ."			var next_i = CurrentListItem;\n"
            ."			var i_max = ListItems.length;\n"
            ."			for (var i=0; i<i_max; i++) {\n"
            ."				var next_i = (CurrentListItem + i + 1) % i_max;\n"
            ."				if (ListItems[next_i].AnsweredCorrectly==false) {\n"
            ."					break;\n"
            ."				}\n"
            ."			}\n"
            ."			if (next_i==CurrentListItem) {\n"
            ."				AA_SetProgressBar(next_i);\n"
            ."			} else {\n"
            ."				AA_ChangeListItem(next_i);\n"
            ."			}\n"
            ."		}\n"
            ."	}\n"
            ."	li = null;\n"
            ."	clues = clues.join('\\n\\n');\n"
            .'	'
        ;
        $substr = preg_replace($search, $replace, $substr, 1);

        $search = '		TotalScore += State[i].ItemScore;';
        if ($pos = strpos($substr, $search)) {
            $insert = ''
                ."		if (State[i].AnsweredCorrectly==false) {\n"
                ."			AllCorrect = false;\n"
                ."		}\n"
            ;
            $substr = substr_replace($substr, $insert, $pos, 0);
        }

        $search = 'Output += Incorrect';
        if ($pos = strpos($substr, $search)) {
            $insert = 'Output += (clues ? clues : Incorrect)';
            $substr = substr_replace($substr, $insert, $pos, strlen($search));
        }

        $search = 'ShowMessage(Output)';
        if ($pos = strpos($substr, $search)) {
            $insert = 'if (clues || AllCorrect) ';
            $substr = substr_replace($substr, $insert, $pos, 0);
        }

        $search = "setTimeout('WriteToInstructions(Output)', 50);";
        if ($pos = strpos($substr, $search)) {
            $substr = substr_replace($substr, '', $pos, strlen($search));
        }

        parent::fix_js_CheckAnswers($substr, 0, strlen($substr));
        $str = substr_replace($str, $substr, $start, $length);
    }

    /**
     * expand_ClozeBody
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function expand_ClozeBody() {
        $str = '';

        $wordlist = $this->setup_wordlist();

        // cache clues flag and caption
        $includeclues = $this->expand_Clues();
        $cluecaption = $this->expand_ClueCaption();

        // detect if cloze starts with gap
        if (strpos($this->TC->task->source->filecontents, '<gap-fill><question-record>')) {
            $startwithgap = true;
        } else {
            $startwithgap = false;
        }

        // initialize loop values
        $q = 0;
        $tags = 'data,gap-fill';
        $question_record = "$tags,question-record";

        // initialize loop values
        $q = 0;
        $tags = 'data,gap-fill';
        $question_record = "$tags,question-record";

        // loop through text and gaps
        $looping = true;
        while ($looping) {
            $text = $this->TC->task->source->xml_value($tags, "[0]['#'][$q]");
            $gap = '';
            if (($question="[$q]['#']") && $this->TC->task->source->xml_value($question_record, $question)) {
                $gap .= '<span class="GapSpan" id="GapSpan'.$q.'">';
                if (is_array($wordlist)) {
                    $gap .= '<select id="Gap'.$q.'"><option value=""></option>'.$wordlist[$q].'</select>';
                } else if ($wordlist) {
                    $gap .= '<select id="Gap'.$q.'"><option value=""></option>'.$wordlist.'</select>';
                } else {
                    // minimum gap size
                    if (! $gapsize = $this->TC->task->source->xml_value_int($this->TC->task->source->hbs_software.'-config-file,'.$this->TC->task->source->hbs_tasktype.',minimum-gap-size')) {
                        $gapsize = 6;
                    }

                    // increase gap size to length of longest answer for this gap
                    $a = 0;
                    while (($answer=$question."['answer'][$a]['#']") && $this->TC->task->source->xml_value($question_record, $answer)) {
                        $answertext = $this->TC->task->source->xml_value($question_record,  $answer."['text'][0]['#']");
                        $answertext = preg_replace('/&[#a-zA-Z0-9]+;/', 'x', $answertext);
                        $gapsize = max($gapsize, strlen($answertext));
                        $a++;
                    }

                    $gap .= '<input type="text" id="Gap'.$q.'" onfocus="TrackFocus('.$q.')" onblur="LeaveGap()" class="GapBox" size="'.$gapsize.'"></input>';
                }
                if ($includeclues) {
                    $clue = $this->TC->task->source->xml_value($question_record, $question."['clue'][0]['#']");
                    if (strlen($clue)) {
                        $gap .= '<button style="line-height: 1.0" class="FuncButton" onfocus="FuncBtnOver(this)" onmouseover="FuncBtnOver(this)" onblur="FuncBtnOut(this)" onmouseout="FuncBtnOut(this)" onmousedown="FuncBtnDown(this)" onmouseup="FuncBtnOut(this)" onclick="ShowClue('.$q.')">'.$cluecaption.'</button>';
                    }
                }
                $gap .= '</span>';
            }
            if (strlen($text) || strlen($gap)) {
                if ($startwithgap) {
                    $str .= $gap.$text;
                } else {
                    $str .= $text.$gap;
                }
                $q++;
            } else {
                // no text or gap, so force end of loop
                $looping = false;
            }
        }
        if ($q==0) {
            // oops, no gaps found!
            return $this->TC->task->source->xml_value($tags);
        } else {
            return $str;
        }
    }

    /**
     * setup_wordlist
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function setup_wordlist() {

        // get drop down list of words
        $words = array();
        $wordlists = array();
        $singlewordlist = true;

        if ($this->use_DropDownList()) {
            $q = 0;
            $tags = 'data,gap-fill,question-record';
            while (($question="[$q]['#']") && $this->TC->task->source->xml_value($tags, $question)) {
                $a = 0;
                $aa = 0;
                while (($answer=$question."['answer'][$a]['#']") && $this->TC->task->source->xml_value($tags, $answer)) {
                    $text = $this->TC->task->source->xml_value($tags,  $answer."['text'][0]['#']");
                    if (strlen($text)) {
                        $wordlists[$q][$aa] = $text;
                        $words[] = $text;
                        $aa++;
                    }
                    $a++;
                }
                if ($aa) {
                    $wordlists[$q] = array_unique($wordlists[$q]);
                    sort($wordlists[$q]);

                    $wordlist = '';
                    foreach ($wordlists[$q] as $word) {
                        $wordlist .= '<option value="'.$word.'">'.$word.'</option>';
                    }
                    $wordlists[$q] = $wordlist;

                    if ($aa >= 2) {
                        $singlewordlist = false;
                    }
                }
                $q++;
            }

            $words = array_unique($words);
            sort($words);
        }

        if ($singlewordlist) {
            $wordlist = '';
            foreach ($words as $word) {
                $wordlist .= '<option value="'.$word.'">'.$word.'</option>';
            }
            return $wordlist;
        } else {
            return $wordlists;
        }
    }
}
