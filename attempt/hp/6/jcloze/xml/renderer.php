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

    /**
     * jcloze_autoadvance_gapid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function jcloze_autoadvance_gapid() {
        return ''; // must be overridden by child class
    }

    /**
     * jcloze_autoadvance_gaptype
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function jcloze_autoadvance_gaptype() {
        return ''; // must be overridden by child class
    }

    /**
     * jcloze_autoadvance_fix_js_StartUp
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     * @return xxx
     * @todo Finish documenting this function
     */
    function jcloze_autoadvance_fix_js_StartUp(&$str, $start, $length) {
        global $CFG;

        $substr = substr($str, $start, $length);

        $append = '';
        if ($pos = strrpos($substr, '}')) {

            $gapid = $this->jcloze_autoadvance_gapid();
            $gaptype = $this->jcloze_autoadvance_gaptype();

            $insert = "\n"
                ."	var ClozeBody = null;\n"
                ."	window.CurrentListItem = 0;\n"
                ."	window.ListItems = new Array();\n"
                ."	var div = document.getElementsByTagName('div');\n"
                ."	if (div) {\n"
                ."		var d_max = div.length;\n"
                ."		for (var d=0; d<d_max; d++) {\n"
                ."			if (div[d].className=='ClozeBody') {\n"
                ."				ListItems = div[d].getElementsByTagName('li');\n"
                ."				ClozeBody = div[d];\n"
                ."				break;\n"
                ."			}\n"
                ."		}\n"
                ."	}\n"
                ."	div = null;\n"
                ."	var i_max = ListItems.length;\n"
                ."	var gapid = new RegExp('$gapid');\n"
                ."	for (var i=0; i<i_max; i++) {\n"
                ."		ListItems[i].id = 'Q_' + i;\n"
                ."		if (i==CurrentListItem) {\n"
                ."			ListItems[i].style.display = '';\n"
                ."		} else {\n"
                ."			ListItems[i].style.display = 'none';\n"
                ."		}\n"
                ."		ListItems[i].gaps = new Array();\n"
                ."		var gap = ListItems[i].getElementsByTagName('$gaptype');\n"
                ."		if (gap) {\n"
                ."			var g_max = gap.length;\n"
                ."			for (var g=0; g<g_max; g++) {\n"
                ."				if (gapid.test(gap[g].id)) {\n"
                ."					ListItems[i].gaps.push(gap[g]);\n"
                ."				}\n"
                ."			}\n"
                ."			ListItems[i].score = -1;\n"
                ."			ListItems[i].AnsweredCorrectly = false;\n"
                ."		} else {\n"
                ."			ListItems[i].score = 0;\n"
                ."			ListItems[i].AnsweredCorrectly = true;\n"
                ."		}\n"
                ."		gap = null;\n"
                ."	}\n"
                ."	gapid = null;\n"
            ;

            $dots = 'squares'; // default
            if ($param = clean_param($this->expand_UserDefined1(), PARAM_ALPHANUM)) {
                if (is_dir($CFG->dirroot."/mod/taskchain/pix/autoadvance/$param")) {
                    $dots = $param;
                }
            }

            if ($dots) {
                $insert .= ''
                    ."	if (ClozeBody) {\n"
                    ."		var ProgressBar = document.createElement('div');\n"
                    ."		ProgressBar.setAttribute('id', 'ProgressBar');\n"
                    ."		ProgressBar.setAttribute(AA_className(), 'ProgressBar');\n"
                    ."\n"
                    ."		// add feedback boxes and progess dots for each question\n"
                    ."		for (var i=0; i<ListItems.length; i++){\n"
                    ."\n"
                    ."			if (ProgressBar.childNodes.length) {\n"
                    ."				// add arrow between progress dots\n"
                    ."				ProgressBar.appendChild(document.createTextNode(' '));\n"
                    ."				ProgressBar.appendChild(AA_ProgressArrow());\n"
                    ."				ProgressBar.appendChild(document.createTextNode(' '));\n"
                    ."			}\n"
                    ."			ProgressBar.appendChild(AA_ProgressDot(i));\n"
                    ."\n"
                    ."			// AA_Add_FeedbackBox(i);\n"
                    ."		}\n"
                    ."		ClozeBody.parentNode.insertBefore(ProgressBar, ClozeBody);\n"
                    ."		AA_SetProgressBar();\n"
                    ."	}\n"
                ;
                $append = "\n"
                    ."function AA_isNonStandardIE() {\n"
                    ."	if (typeof(window.isNonStandardIE)=='undefined') {\n"
                    ."		if (navigator.appName=='Microsoft Internet Explorer' && (document.documentMode==null || document.documentMode<8)) {\n"
                    ."			// either IE8+ (in compatability mode) or IE7, IE6, IE5 ...\n"
                    ."			window.isNonStandardIE = true;\n"
                    ."		} else {\n"
                    ."			// Firefox, Safari, Opera, IE8+\n"
                    ."			window.isNonStandardIE = false;\n"
                    ."		}\n"
                    ."	}\n"
                    ."	return window.isNonStandardIE;\n"
                    ."}\n"
                    ."function AA_className() {\n"
                    ."	if (AA_isNonStandardIE()){\n"
                    ."		return 'className';\n"
                    ."	} else {\n"
                    ."		return 'class';\n"
                    ."	}\n"
                    ."}\n"
                    ."function AA_onclickAttribute(fn) {\n"
                    ."	if (AA_isNonStandardIE()){\n"
                    ."		return new Function(fn);\n"
                    ."	} else {\n"
                    ."		return fn; // just return the string\n"
                    ."	}\n"
                    ."}\n"
                    ."function AA_images() {\n"
                    ."	return 'pix/autoadvance/$dots';\n"
                    ."}\n"
                    ."function AA_ProgressArrow() {\n"
                    ."	var img = document.createElement('img');\n"
                    ."	var src = 'ProgressDotArrow.gif';\n"
                    ."	img.setAttribute('src', AA_images() + '/' + src);\n"
                    ."	img.setAttribute('alt', src);\n"
                    ."	img.setAttribute('title', src);\n"
                    ."	//img.setAttribute('height', 18);\n"
                    ."	//img.setAttribute('width', 18);\n"
                    ."	img.setAttribute(AA_className(), 'ProgressDotArrow');\n"
                    ."	return img;\n"
                    ."}\n"
                    ."function AA_ProgressDot(i) {\n"
                    ."	// i is either an index on ListItems \n"
                    ."	// or a string to be used as an id for an HTML element\n"
                    ."	if (typeof(i)=='string') {\n"
                    ."		var id = i;\n"
                    ."		var add_link = false;\n"
                    ."	} else if (ListItems[i]) {\n"
                    ."		var id = ListItems[i].id;\n"
                    ."		var add_link = true;\n"
                    ."	} else {\n"
                    ."		return false;\n"
                    ."	}\n"
                    ."	// id should now be: 'Q_' + q ...\n"
                    ."	// where q is an index on the State array\n"
                    ."	var src = 'ProgressDotEmpty.gif';\n"
                    ."	var img = document.createElement('img');\n"
                    ."	img.setAttribute('id', id + '_ProgressDotImg');\n"
                    ."	img.setAttribute('src', AA_images() + '/' + src);\n"
                    ."	img.setAttribute('alt', src);\n"
                    ."	img.setAttribute('title', src);\n"
                    ."	//img.setAttribute('height', 18);\n"
                    ."	//img.setAttribute('width', 18);\n"
                    ."	img.setAttribute(AA_className(), 'ProgressDotEmpty');\n"
                    ."	if (add_link) {\n"
                    ."		var link = document.createElement('a');\n"
                    ."		link.setAttribute('id', id + '_ProgressDotLink');\n"
                    ."		link.setAttribute(AA_className(), 'ProgressDotLink');\n"
                    ."		link.setAttribute('title', 'go to question '+(i+1));\n"
                    ."		var fn = 'AA_ChangeListItem('+i+');return false;';\n"
                    ."		link.setAttribute('onclick', AA_onclickAttribute(fn));\n"
                    ."		link.appendChild(img);\n"
                    ."	}\n"
                    ."	var span = document.createElement('span');\n"
                    ."	span.setAttribute('id', id + '_ProgressDot');\n"
                    ."	span.setAttribute(AA_className(), 'ProgressDot');\n"
                    ."	if (add_link) {\n"
                    ."		span.appendChild(link);\n"
                    ."	} else {\n"
                    ."		span.appendChild(img);\n"
                    ."	}\n"
                    ."	return span;\n"
                    ."}\n"
                    ."function AA_SetProgressDot(i, next_i) {\n"
                    ."	var img = document.getElementById('Q_'+i+'_ProgressDotImg');\n"
                    ."	if (! img) {\n"
                    ."		return;\n"
                    ."	}\n"
                    ."	var src = '';\n"
                    ."	if (ListItems[i].score >= 0) {\n"
                    ."		var score = Math.max(0, ListItems[i].score);\n"
                    ."		if (score >= 99) {\n"
                    ."			src = 'ProgressDotCorrect99Plus'+'.gif';\n"
                    ."		} else if (score >= 80) {\n"
                    ."			src = 'ProgressDotCorrect80Plus'+'.gif';\n"
                    ."		} else if (score >= 60) {\n"
                    ."			src = 'ProgressDotCorrect60Plus'+'.gif';\n"
                    ."		} else if (score >= 40) {\n"
                    ."			src = 'ProgressDotCorrect40Plus'+'.gif';\n"
                    ."		} else if (score >= 20) {\n"
                    ."			src = 'ProgressDotCorrect20Plus'+'.gif';\n"
                    ."		} else if (score >= 0) {\n"
                    ."			src = 'ProgressDotCorrect00Plus'+'.gif';\n"
                    ."		} else {\n"
                    ."			// this question has negative score, which means it has not yet been correctly answered\n"
                    ."			src = 'ProgressDotWrong'+'.gif';\n"
                    ."		}\n"
                    ."	} else {\n"
                    ."		// this question has not been completed\n"
                    ."		if (typeof(next_i)=='number' && i==next_i) {\n"
                    ."			// this question will be attempted next\n"
                    ."			src = 'ProgressDotCurrent'+'.gif';\n"
                    ."		} else {\n"
                    ."			src = 'ProgressDotEmpty'+'.gif';\n"
                    ."		}\n"
                    ."	}\n"
                    ."	var full_src = AA_images() + '/' + src;\n"
                    ."	if (img.src != full_src) {\n"
                    ."		img.setAttribute('src', full_src);\n"
                    ."	}\n"
                    ."}\n"
                    ."function AA_ChangeListItem(i) {\n"
                    ."	ListItems[CurrentListItem].style.display = 'none';\n"
                    ."	var obj = ListItems[i].parentNode;\n"
                    ."	while (obj) {\n"
                    ."		if (obj.tagName=='OL') {\n"
                    ."			obj.start = (i+1);\n"
                    ."			obj = null;\n"
                    ."		} else {\n"
                    ."			// workaround for IE7\n"
                    ."			obj = obj.parentNode;\n"
                    ."		}\n"
                    ."	}\n"
                    ."	ListItems[i].style.display = '';\n"
                    ."	StretchCanvasToCoverContent(true);\n"
                    ."	AA_SetProgressBar(i);\n"
                    ."	CurrentListItem = i;\n"
                    ."}\n"
                    ."function AA_SetProgressBar(next_i) {\n"
                    ."	if (typeof(next_i)=='undefined') {\n"
                    ."		next_i = CurrentListItem;\n"
                    ."	}\n"
                    ."	for (var i=0; i<ListItems.length; i++) {\n"
                    ."		AA_SetProgressDot(i, next_i);\n"
                    ."	}\n"
                    ."}\n"
                ;
            }

            $insert .= "	ClozeBody = null;\n";
            $substr = substr_replace($substr, $insert, $pos, 0);
        }

        parent::fix_js_StartUp($substr, 0, strlen($substr));
        $substr .= $append;

        $str = substr_replace($str, $substr, $start, $length);
    }
}
