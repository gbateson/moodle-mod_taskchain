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
 * mod/taskchain/attempt/hp/6/jmatch/html/flashcard/renderer.php
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
 * mod_taskchain_attempt_hp_6_jmatch_html_flashcard_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_hp_6_jmatch_html_flashcard_renderer extends mod_taskchain_attempt_hp_6_jmatch_html_renderer {

    public $js_object_type = 'JMatchFlashcard';

    /**
     * constructor function
     *
     * @param xxx $page
     * @param xxx $target
     * @todo Finish documenting this function
     */
    public function __construct(moodle_page $page, $target)  {
        parent::__construct($page, $target);

        // replace standard jmatch.js with flashcard.js
        $this->javascripts = preg_grep('/jmatch.js/', $this->javascripts, PREG_GREP_INVERT);
        array_push($this->javascripts, 'mod/taskchain/attempt/hp/6/jmatch/flashcard.js');
    }

    /**
     * List of source types which this renderer can handle
     *
     * @return array of strings
     */
    static public function sourcetypes()  {
        return array('hp_6_jmatch_html_flashcard');
    }

    /**
     * fix_js_StartUp_DragAndDrop
     *
     * @param xxx $substr (passed by reference)
     * @todo Finish documenting this function
     */
    public function fix_js_StartUp_DragAndDrop(&$substr)  {
        $this->fix_js_StartUp_DragAndDrop_Flashcard($substr);
    }

    /**
     * fix_mediafilter_onload_extra
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function fix_mediafilter_onload_extra()  {
        // automatically show first item
        return $this->fix_mediafilter_onload_extra_Flashcard();
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
        $names .= ($names ? ',' : '').'DeleteItem,ShowItem';
        return $names;
    }

    /**
     * fix_js_DeleteItem
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @todo Finish documenting this function
     */
    public function fix_js_DeleteItem(&$str, $start, $length)  {
        $this->fix_js_DeleteItem_Flashcard($str, $start, $length);
    }

    /**
     * fix_js_ShowItem
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @todo Finish documenting this function
     */
    public function fix_js_ShowItem(&$str, $start, $length)  {
        $this->fix_js_ShowItem_Flashcard($str, $start, $length);
    }

    /**
     * get_stop_function_name
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_stop_function_name()  {
        return 'HP_send_results';
    }

    /**
     * get_stop_function_args
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function get_stop_function_args()  {
        return $this->get_send_results_event();
    }
}
