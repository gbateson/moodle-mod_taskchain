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
 * mod/taskchain/attempt/hp/6/sequitur/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/attempt/hp/6/renderer.php');

/**
 * mod_taskchain_attempt_hp_6_sequitur_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_hp_6_sequitur_renderer extends mod_taskchain_attempt_hp_6_renderer {
    public $js_object_type = 'Sequitur';

    public $templatefile = 'sequitur6.ht_';
    public $templatestrings = 'PreloadImageList|SegmentsArray';

    // Glossary autolinking settings
    public $headcontent_strings = 'CorrectIndicator|IncorrectIndicator|YourScoreIs|strTimesUp';
    public $headcontent_arrays = 'Segments';

    // TexToys do not have a SubmissionTimeout variable
    public $hasSubmissionTimeout = false;

    public $response_text_fields = array(
        'correct', 'wrong' // remove: ignored
    );

    public $response_num_fields = array(
        'checks' // remove: score, weighting, hints, clues
    );

    /**
     * constructor function
     *
     * @param xxx $page
     * @param xxx $target
     * @todo Finish documenting this function
     */
    public function __construct(moodle_page $page, $target)  {
        parent::__construct($page, $target);
        array_push($this->javascripts, 'mod/taskchain/attempt/hp/6/sequitur/sequitur.js');
    }

    /**
     * get_js_functionnames
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_js_functionnames()  {
        // start list of function names
        $names = parent::get_js_functionnames();
        $names .= ($names ? ',' : '').'CheckAnswer,TimesUp';
        return $names;
    }

    /**
     * fix_js_TimesUp
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @todo Finish documenting this function
     */
    public function fix_js_TimesUp(&$str, $start, $length)  {
        $substr = substr($str, $start, $length);

        if ($pos = strpos($substr, '	ShowMessage')) {
            if ($this->TC->task->delay3==mod_taskchain::TIME_AFTEROK) {
                $flag = 1; // set form values only
            } else {
                $flag = 0; // set form values and send form
            }
            $insert = ''
                ."	Finished = true;\n"
                ."	HP.onunload(".mod_taskchain::STATUS_TIMEDOUT.",$flag);\n"
            ;
            $substr = substr_replace($substr, $insert, $pos, 0);
        }

        $str = substr_replace($str, $substr, $start, $length);
    }

    /**
     * fix_js_CalculateScore
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @return xxx
     * @todo Finish documenting this function
     */
    public function fix_js_CalculateScore(&$str, $start, $length) {
        // original function was simply this:
        // return Math.floor(100*ScoredPoints/TotalPoints);
        $substr = ''
            ."function CalculateScore(){\n"
            ."	if (typeof(window.TotalPointsAvailable)=='undefined') {\n"
            ."\n"
            ."		// initialize TotalPointsAvailable\n"
            ."		window.TotalPointsAvailable = 0;\n"
            ."\n"
            ."		// add points for questions with complete number of distractors\n"
            ."		TotalPointsAvailable += (TotalSegments - NumberOfOptions) * (NumberOfOptions - 1);\n"
            ."\n"
            ."		// add points for questions with less than the total number of distractors\n"
            ."		TotalPointsAvailable += (NumberOfOptions - 1) * NumberOfOptions / 2;\n"
            ."	}\n"
            ."\n"
            ."	if (TotalPointsAvailable==0) {\n"
            ."		return 0;\n"
            ."	} else {\n"
            ."		return Math.floor(100*ScoredPoints/TotalPointsAvailable);\n"
            ."	}\n"
            ."}"
        ;
        $str = substr_replace($str, $substr, $start, $length);
    }

    /**
     * fix_js_CheckAnswer
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @todo Finish documenting this function
     */
    public function fix_js_CheckAnswer(&$str, $start, $length)  {
        $substr = substr($str, $start, $length);

        // add extra argument to this function, so it can be called from stop button
        if ($pos = strpos($substr, ')')) {
            $substr = substr_replace($substr, ', ForceTaskEvent', $pos, 0);
        }

        // allow for Btn being null (as it is when called from stop button)
        if ($pos = strpos($substr, 'Btn.innerHTML == IncorrectIndicator')) {
            $substr = substr_replace($substr, 'Btn && ', $pos, 0);
        }
        $search = 'else{';
        if ($pos = strrpos($substr, $search)) {
            $substr = substr_replace($substr, 'else if (Btn){', $pos, strlen($search));
        }

        // intercept checks
        if ($pos = strpos($substr, '{')) {
            $insert = "\n"
                ."	if (CurrentNumber!=TotalSegments && !AllDone && Btn && Btn.innerHTML!=IncorrectIndicator){\n"
                ."		HP.onclickCheck(Chosen);\n"
                ."	}"
            ;
            $substr = substr_replace($substr, $insert, $pos+1, 0);
        }

        // set task status
        if ($pos = strpos($substr, 'if (CurrentCorrect == Chosen)')) {
            $event = $this->get_send_results_event();
            $insert = ''
                ."if (CurrentCorrect==Chosen && CurrentNumber>=(TotalSegments-2)){\n"
                ."		var TaskEvent = $event;\n" // COMPLETED or SETVALUES
                ."	} else if (ForceTaskEvent){\n"
                ."		var TaskEvent = ForceTaskEvent;\n"
                ."	} else if (TimeOver){\n"
                ."		var TaskEvent = HP.EVENT_TIMEDOUT;\n"
                ."	} else {\n"
                ."		var TaskEvent = HP.EVENT_CHECK;\n"
                ."	}\n"
                ."	"
            ;
            $substr = substr_replace($substr, $insert, $pos, 0);
        }

        // send results to Moodle, if necessary
        if ($pos = strrpos($substr, '}')) {
            $insert = "\n"
                ."	if (HP.end_of_quiz(TaskEvent)) {\n"
                ."		TimeOver = true;\n"
                ."		Locked = true;\n"
                ."		Finished = true;\n"
                ."	}\n"
                ."	if (Finished || HP.sendallclicks){\n"
                ."		if (TaskEvent==HP.EVENT_COMPLETED){\n"
                ."			// send results after delay (quiz completed as expected)\n"
                ."			setTimeout('HP_send_results('+TaskEvent+')', SubmissionTimeout);\n"
                ."		} else {\n"
                ."			// send results immediately (quiz finished unexpectedly)\n"
                ."			HP_send_results(TaskEvent);\n"
                ."		}\n"
                ."	}\n"
            ;
            $substr = substr_replace($substr, $insert, $pos, 0);
        }

        $str = substr_replace($str, $substr, $start, $length);
    }

    /**
     * get_stop_function_name
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_stop_function_name()  {
        return 'CheckAnswer';
    }

    /**
     * get_stop_function_args
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_stop_function_args()  {
        return '0,null,HP.EVENT_ABANDONED';
    }
}
