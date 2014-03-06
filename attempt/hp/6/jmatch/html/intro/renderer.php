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
 * mod/taskchain/attempt/hp/6/jmatch/html/intro/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/attempt/hp/6/jmatch/html/renderer.php');

/**
 * mod_taskchain_attempt_hp_6_jmatch_html_intro_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_hp_6_jmatch_html_intro_renderer extends mod_taskchain_attempt_hp_6_jmatch_html_renderer {

    /**
     * List of source types which this renderer can handle
     *
     * @return array of strings
     */
    static public function sourcetypes()  {
        return array('hp_6_jmatch_html_intro');
    }

    /**
     * fix_headcontent
     *
     * @todo Finish documenting this function
     */
    public function fix_headcontent()  {
        $this->fix_headcontent_DragAndDrop();
        $this->fix_headcontent_rottmeier('jintro');
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
        $names .= ($names ? ',' : '').'CheckAnswer,ShowDescription';
        return $names;
    }

    /**
     * fix_js_StartUp
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @todo Finish documenting this function
     */
    public function fix_js_StartUp(&$str, $start, $length)  {
        $substr = substr($str, $start, $length);
        parent::fix_js_StartUp($substr, 0, $length);

        // remove code that assigns event keypress/keydown handler
        list($pos1, $pos2) = $this->locate_js_block('if', 'is.ie', $substr);
        if ($pos2) {
            $substr = substr_replace($substr, '', $pos1, $pos2 - $pos1);
        }

        $str = substr_replace($str, $substr, $start, $length);
    }

    /**
     * fix_js_ShowDescription
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @todo Finish documenting this function
     */
    public function fix_js_ShowDescription(&$str, $start, $length)  {
        $replace = ''
            ."function ShowDescription(evt, ElmNum){\n"
            ."	if (evt==null) {\n"
            ."		evt = window.event; // IE\n"
            ."	}\n"

            ."	var obj = document.getElementById('DivIntroPage');\n"
            ."	if (obj) {\n"

            // get max X and Y for this page
            ."		var pg = new PageDim();\n"
            ."		var maxX = (pg.Left + pg.W);\n"
            ."		var maxY = (pg.Top  + pg.H);\n"

            // get mouse position
            ."		if (evt.pageX || evt.pageY) {\n"
            ."			var posX = evt.pageX;\n"
            ."			var posY = evt.pageY;\n"
            ."		} else if (evt.clientX || evt.clientY) {\n"
            ."			var posX = evt.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;\n"
            ."			var posY = evt.clientY + document.body.scrollTop + document.documentElement.scrollTop;\n"
            ."		} else {\n"
            ."			var posX = 0;\n"
            ."			var posY = 0;\n"
            ."		}\n"

            // insert new description and make div visible
            ."		obj.innerHTML = D[ElmNum][0];\n"
            ."		obj.style.display = 'block';\n"

            // make sure posX and posY are within the display area
            ."		posX = Math.max(0, Math.min(posX + 12, maxX - getOffset(obj, 'Width')));\n"
            ."		posY = Math.max(0, Math.min(posY + 12, maxY - getOffset(obj, 'Height')));\n"

            // move the description div to (posX, posY)
            ."		setOffset(obj, 'Left', posX);\n"
            ."		setOffset(obj, 'Top', posY);\n"
            ."		obj.style.zIndex = ++topZ;\n"
            ."	}\n"
            ."}\n"
        ;
        $str = substr_replace($str, $replace, $start, $length);
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
            $substr = substr_replace($substr, 'ForceTaskEvent', $pos, 0);
        }

        // intercept checks
        if ($pos = strpos($substr, '{')) {
            $insert = "\n"
                ."	HP.onclickCheck();\n"
            ;
            $substr = substr_replace($substr, $insert, $pos+1, 0);
        }

        // set task status
        if ($pos = strpos($substr, 'if (TotalCorrect == F.length) {')) {
            $insert = ''
                ."if (TotalCorrect == F.length) {\n"
                ."		var TaskEvent = HP.EVENT_COMPLETED;\n"
                ."	} else if (ForceTaskEvent){\n"
                ."		var TaskEvent = ForceTaskEvent;\n" // TIMEDOUT or ABANDONED
                ."	} else if (TimeOver){\n"
                ."		var TaskEvent = HP.EVENT_TIMEDOUT;\n"
                ."	} else {\n"
                ."		var TaskEvent = HP.EVENT_CHECK;\n"
                ."	}\n"
                ."	"
            ;
            $substr = substr_replace($substr, $insert, $pos, 0);
        }

        // remove call to Finish() function
        $substr = preg_replace('/\s*'.'setTimeout\(.*?\);/s', '', $substr);

        // remove call to WriteToInstructions() function
        $search = '/\s*'.'WriteToInstructions\(.*?\);/s';
        $substr = preg_replace($search, '', $substr);

        // remove superfluous if-block that contained WriteToInstructions()
        $search = '/\s*if \(\(is\.ie\)\&\&\(\!is\.mac\)\)\{\s*\}/s';
        $substr = preg_replace($search, '', $substr);

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
                ."			setTimeout('HP_send_results('+TaskEvent+')', SubmissionTimeout);\n"
                ."		} else {\n"
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
        return mod_taskchain::STATUS_ABANDONED;
    }

    /* ================================================ **
    HP6:
        GetViewportHeight,PageDim,TrimString,StartUp,GetUserName,
        ShowMessage,HideFeedback,SendResults,Finish,WriteToInstructions,
        ShowSpecialReadingForQuestion,
    JMatch:
        CheckAnswers,beginDrag
    JMatch-intro:
        StartUpInfo(?),DisplayIntroPage(?),BuildIntroPage(?)
    ** ================================================ */
}
