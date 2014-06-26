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
 * mod/taskchain/attempt/hp/6/jquiz/xml/v6/intro/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/attempt/hp/6/jquiz/xml/v6/renderer.php');

/**
 * mod_taskchain_attempt_hp_6_jquiz_xml_v6_intro_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_hp_6_jquiz_xml_v6_intro_renderer extends mod_taskchain_attempt_hp_6_jquiz_xml_v6_renderer {

    /**
     * List of source types which this renderer can handle
     *
     * @return array of strings
     */
    static public function sourcetypes()  {
        return array('hp_6_jquiz_xml');
    }

    /**
     * fix_bodycontent
     *
     * @return void, but may update $this->bodycontent
     * @todo Finish documenting this function
     */
    function fix_bodycontent() {
        // remove current text from each answer button
        // and replace it with the text from the answer
        $search = '/(<li id="Q_\d+_\d+">)(<button[^>]*>)(.*?)(<\/button>)(.*?)(<\/li>)/';
        $replace = '$1$2$5$4$6';
        $this->bodycontent = preg_replace($search, $replace, $this->bodycontent);
        parent::fix_bodycontent();
    }

    /**
     * fix_headcontent
     *
     * @return void, but may update $this->headcontent
     * @todo Finish documenting this function
     */
    function fix_headcontent() {
        $search = '/ol.MCAnswers\{.*?\}/s';
        $replace = ''
            ."ol.MCAnswers{\n"
            ."	text-align: left;\n"
            ."	list-style-type: none;\n"
            ."	padding: 1.5em 0em;\n"
            ."}\n"
            ."ol.MCAnswers li button{\n"
            ."	font-size: 1.1em;\n"
            ."}\n"
        ;
        $this->headcontent = preg_replace($search, $replace, $this->headcontent, 1);

        // accept all answers as "correct" answers, so that Finished gets set to true
        $search = "/(I\[\d\]\[3\]\[\d\] = new Array\('.*',\d,)0(\d\);)/";
        $replace = '$1,1,$2';
        //$this->headcontent = preg_replace($search, $replace, $this->headcontent);

        parent::fix_headcontent();
    }

    /**
     * get_js_functionnames
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function get_js_functionnames() {
        // start list of function names
        $names = parent::get_js_functionnames();
        $names .= ($names ? ',' : '').'FuncBtnDown';
        return $names;
    }

    /**
     * fix_js_FuncBtnDown
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @todo Finish documenting this function
     */
    function fix_js_FuncBtnDown(&$str, $start, $length) {
        $substr = substr($str, $start, $length);
        if ($pos = strrchr($substr, '}')) {
            $insert = ''
                ."	FuncBtnOut = function() {}\n"
                ."	FuncBtnOver = function() {}\n"
            ;
            $substr = substr_replace($substr, $insert, $pos-1, 0);
        }
        $str = substr_replace($str, $substr, $start, $length);
    }

    /**
     * fix_js_WriteToInstructions
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @todo Finish documenting this function
     */
    function fix_js_WriteToInstructions(&$str, $start, $length) {
        $str = substr_replace($str, 'function WriteToInstructions(Feedback) {}', $start, $length);
    }

    /**
     * fix_js_ShowMessage
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @todo Finish documenting this function
     */
    function fix_js_ShowMessage(&$str, $start, $length) {
        $str = substr_replace($str, 'function ShowMessage(Feedback) {}', $start, $length);
    }

    /**
     * fix_js_CheckMCAnswer
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @todo Finish documenting this function
     */
    function fix_js_CheckMCAnswer(&$str, $start, $length) {
        $substr = substr($str, $start, $length);

        parent::fix_js_CheckFinished($substr, 0, $length);

        $search = '/\s*'.'Btn.innerHTML = [^;]*;'.'/s';
        $substr = preg_replace($search, '', $substr);

        $str = substr_replace($str, $substr, $start, $length);
    }

    /**
     * fix_js_CheckFinished
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @todo Finish documenting this function
     */
    function fix_js_CheckFinished(&$str, $start, $length) {
        $substr = substr($str, $start, $length);

        parent::fix_js_CheckFinished($substr, 0, $length);

        $search = '/setTimeout'.'[^;]*'.';/';
        $replace = "setTimeout('HP_send_results('+TaskEvent+')', SubmissionTimeout)";
        $substr = preg_replace($search, $replace, $substr, 1);

        $str = substr_replace($str, $substr, $start, $length);
    }
}
