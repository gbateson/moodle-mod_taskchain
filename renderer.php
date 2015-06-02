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
 * mod/taskchain/renderer.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/**
 * mod_taskchain_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_renderer extends plugin_renderer_base {

    /** the mod_taskchain object (as defined in "mod/taskchain/locallib.php") representing this TaskChain */
    protected $TC;

    /** empty table cell values (perhaps this should be a function?) */
    var $nonumber = '-';
    var $notext = '&nbsp;';

    /**
     * constructor function
     *
     * @param $page
     * @param $target
     * @todo Finish documenting this function
     */
    function __construct(moodle_page $page, $target) {
        global $TC;

        // standard initiailization
        parent::__construct($page, $target);

        // add reference to global $TC (TaskChain) object
        $this->TC = &$TC;

        // we could also set $PAGE (=$this->page, =$page)
    }

    /**
     * taskmenu
     *
     * @param xxx $TC
     * @return xxx
     * @todo Finish documenting this function
     */
    public function taskmenu() {
        $output = '';

        // show task name and description
        $output .= $this->heading();

        $this->TC->get_tasks();
        $this->TC->get_available_tasks();

        $type = $this->TC->get_taskid();
        switch ($type) {
            case taskchain::CONDITIONTASKID_MENUNEXT:
                // show menu of links to available tasks and their scores
                // (unavailable tasks are not shown)
                $showtaskids = &$this->TC->availabletaskids;
                $linktaskids = &$this->TC->availabletaskids;
                break;

            case taskchain::CONDITIONTASKID_MENUNEXTONE:
                // show menu of available tasks and their scores
                // with a link to the next unattempted task
                // (unavailable tasks are not shown)
                $showtaskids = &$this->TC->availabletaskids;
                $linktaskids = array($this->TC->availabletaskid);
                break;

            case taskchain::CONDITIONTASKID_MENUALL:
                // show menu of links to all tasks and their scores
                // (unavailable tasks are listed too, but with no link)
                $showtaskids = array_keys($this->TC->tasks);
                $linktaskids = &$this->TC->availabletaskids;
                break;

            case taskchain::CONDITIONTASKID_MENUALLONE:
                // show menu of all tasks and their scores
                // with a link to the next unattempted available task
                $showtaskids = array_keys($this->TC->tasks);
                $linktaskids = array($this->TC->availabletaskid);
                break;

            default:
                return false;
        }

        $counttaskscores = 0;
        $score_column = false;
        $resume_column = false;

        // get task scores
        if ($this->TC->get_taskscores()) {
            foreach ($this->TC->taskscores as $id=>$taskscore) {
                $taskid = $taskscore->taskid;
                if (in_array($taskid, $showtaskids)) {
                    $this->TC->tasks[$taskid]->taskscore = &$this->TC->taskscores[$id];
                    if ($this->TC->tasks[$taskid]->scorelimit && $this->TC->tasks[$taskid]->scoreweighting) {
                        $score_column = true;
                    }
                    if ($this->TC->tasks[$taskid]->taskscore->status==taskchain::STATUS_INPROGRESS && $this->TC->tasks[$taskid]->allowresume) {
                    //    $resume_column = true;
                    }
                    $counttaskscores++;
                }
            }
        }

        // cache the TaskChain "manage" capability
        $can_manage = $this->TC->can->manage();

        // cache the date format (strftimedaydatetime, strftimedatetimeshort, strftimerecentfull)
        $dateformat = get_string('strftimerecent');

        // start attempts table
        $table = new html_table();
        $table->attributes['class'] = 'generaltable taskchaintaskssummary';

        // add column headings, if required
        if ($counttaskscores) {
            $table->head = array(get_string('task', 'mod_taskchain'),
                                 get_string('status', 'mod_taskchain'),
                                 get_string('duration', 'mod_taskchain'),
                                 get_string('lastaccess', 'mod_taskchain'));
        }
        $table->align = array('left', 'center', 'center', 'left');
        $table->size  = array('', '', '', '');

        if ($score_column) {
            // insert score column
            array_splice($table->head,  1, 0, get_string('score', 'mod_taskchain'));
            array_splice($table->align, 1, 0, 'center');
            array_splice($table->size,  1, 0, '');
        }
        if ($can_manage) {
            // prepend edit column
            if ($counttaskscores) {
                array_unshift($table->head, $this->notext);
            }
            array_unshift($table->align, 'center');
            array_unshift($table->size, '');
        }
        if ($resume_column) {
            // append resume column
            $table->head[]  = $this->notext;
            $table->align[] = 'center';
            $table->size[]  = '';
        }

        // print rows of tasks and their scores
        $tasklinktitle = get_string('starttaskattempt', 'mod_taskchain');
        foreach ($showtaskids as $taskid) {

            // shortcuts to task and taskscore
            $task = &$this->TC->tasks[$taskid];
            if (isset($task->taskscore)) {
                $taskscore = &$task->taskscore;
            } else {
                $taskscore = false;
            }

            // start the table row for this task
            $row = new html_table_row();

            // edit icons
            if ($can_manage) {
                $cell = $this->commands(
                    // $types, $taskchainscriptname, $id, $params, $popup
                    array('update', 'delete'), 'edit/task.php', 'taskid',
                    array('taskid'=>$taskid, 'cnumber'=>0),
                    false
                );
                $row->cells[] = new html_table_cell($cell);
            }

            // task name
            $cell = format_string($task->name);
            if (in_array($taskid, $linktaskids)) {
                $params = array('taskid'=>$taskid, 'tnumber'=>-1, 'taskattemptid'=>0, 'taskscoreid'=>0);
                $href = $this->format_url('attempt.php', '', $params);
                $cell = html_writer::link($href, $cell, array('title' => $tasklinktitle));
            }
            $row->cells[] = new html_table_cell($cell);

            // add task score columns, if required
            if ($counttaskscores) {

                // score
                if ($score_column) {
                    if ($this->TC->tasks[$taskid]->scorelimit && $taskscore) {
                        $cell = $taskscore->score.'%';
                    } else {
                        $cell = $this->notext;
                        //$cell = $this->nonumber;
                    }
                    $row->cells[] = new html_table_cell($cell);
                }

                // status, duration, timemodified of task score
                if ($taskscore) {
                    array_push($row->cells,
                               new html_table_cell($this->TC->format_status($taskscore->status)),
                               new html_table_cell($this->TC->format_time($taskscore->duration)),
                               new html_table_cell(userdate($taskscore->timemodified, $dateformat)));
                } else {
                    $cell = new html_table_cell($this->notext);
                    array_push($row->cells, $cell, $cell, $cell);
                }


                //resume button
                if ($resume_column) {
                    if ($task->allowresume && $taskscore && $taskscore->status==taskchain::STATUS_INPROGRESS) {
                        $cell = get_string('resume', 'mod_taskchain');
                    } else {
                        $cell = $this->notext;
                    }
                    $row->cells[] = new html_table_cell($cell);
                }
            }

            // append this row to the table
            $table->data[] = $row;
        }

        if (empty($table->data)) {
            $output .= get_string('notasksforyou', 'mod_taskchain');
        } else {
            if (count($linktaskids)==1) {
                $output .= $this->whatnext('clicklinktocontinue');
            } else {
                $output .= $this->whatnext('');
            }
            $output .= html_writer::table($table);
        }

        $output = $this->box($output, 'generalbox centeredboxtable');
        $output .= $this->js_reloadcoursepage();

        return $output;
    }

    /**
     * js_reloadcoursepage
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function js_reloadcoursepage() {
        $output = '';
        $output .= '<script type="text/javascript">'."\n";
        $output .= '//<![CDATA['."\n";
        $output .= "function taskchain_addEventListener(obj, str, fn, bool) {\n";
        $output .= "	if (obj.addEventListener) {\n";
        $output .= "		obj.addEventListener(str, fn, bool);\n";
        $output .= "	} else if (obj.attachEvent) {\n";
        $output .= "		obj.attachEvent('on'+str, fn);\n";
        $output .= "	} else {\n";
        $output .= "		obj['on'+str] = fn;\n";
        $output .= "	}\n";
        $output .= "}\n";
        $output .= "function taskchain_removeEventListener(obj, str, fn, bool) {\n";
        $output .= "	if (obj.removeEventListener) {\n";
        $output .= "		obj.removeEventListener(str, fn, bool);\n";
        $output .= "	} else if (obj.detachEvent) {\n";
        $output .= "		obj.detachEvent('on'+str, fn);\n";
        $output .= "	} else {\n";
        $output .= "		obj['on'+str] = null;\n";
        $output .= "	}\n";
        $output .= "}\n";
        $output .= "function taskchain_onload() {\n";
        $output .= "	// fancy code to allow IE to detect onclose.\n";
        $output .= "	// if any links are clicked they will unset this flag\n";
        $output .= "	window.taskchain_onclose = true;\n";
        $output .= "	var links = document.getElementsByTagName('a');\n";
        $output .= "	if (links) {\n";
        $output .= "		var i_max = links.length;\n";
        $output .= "		for (var i=0; i<i_max; i++) {\n";
        $output .= "			if (links[i].href && links[i].onclick==null) {\n";
        $output .= "				links[i].onclick = function() {\n";
        $output .= "					window.taskchain_onclose = false;\n";
        $output .= "					return true;\n";
        $output .= "				};\n";
        $output .= "			}\n";
        $output .= "		}\n";
        $output .= "		links = null;\n";
        $output .= "	}\n";
        $output .= "	// fancy code to allow IE to detect onblur properly.\n";
        $output .= "	// thanks to Vladimir Kelman for ideas found at:\n";
        $output .= "	// http://www.codingforums.com/showthread.php?t=76312\n";
        $output .= "	if (navigator.appName=='Microsoft Internet Explorer') {\n";
        $output .= "		window.taskchain_activeElement = document.activeElement;\n";
        $output .= "	}\n";
        $output .= "	return true;\n";
        $output .= "}\n";
        $output .= "function taskchain_onunload() {\n";
        $output .= "	if (window.taskchain_onclose) {\n";
        $output .= "		taskchain_refreshcoursepage();\n";
        $output .= "	}\n";
        $output .= "	return true;\n";
        $output .= "}\n";
        $output .= "function taskchain_onblur() {\n";
        $output .= "	if (navigator.appName=='Microsoft Internet Explorer' && (window.taskchain_activeElement != document.activeElement)) {\n";
        $output .= "		window.taskchain_activeElement = document.activeElement;\n";
        $output .= "	} else {\n";
        $output .= "		taskchain_refreshcoursepage();\n";
        $output .= "	}\n";
        $output .= "	return true;\n";
        $output .= "}\n";
        $output .= "function taskchain_refreshcoursepage() {\n";
        $output .= "	var refreshcoursepage = false;\n";
        $output .= "	if (window.opener && ! opener.closed) {\n";
        $output .= "		if (opener.location.href.match('/course/view.php')) {\n";
        $output .= "			refreshcoursepage = true;\n";
        $output .= "		}\n";
        $output .= "	}\n";
        $output .= "	if (refreshcoursepage) {\n";
        $output .= "		var target_src = new RegExp('^(.*)(taskchain/courselinks\\\\.js\\\\.php\\\\?)(.*)(rnd=[0-9]+)(.*)$');\n";
        $output .= "		var obj = opener.document.getElementsByTagName('script');\n";
        $output .= "		if (obj) {\n";
        $output .= "			var i_max = obj.length;\n";
        $output .= "			for (var i=0; i<i_max; i++) {\n";
        $output .= "				if (! obj[i].src) {\n";
        $output .= "					continue;\n";
        $output .= "				}\n";
        $output .= "				var m = obj[i].src.match(target_src);\n";
        $output .= "				if (! m) {\n";
        $output .= "					continue;\n";
        $output .= "				}\n";
        $output .= "				opener.location.reload();\n";
        $output .= "				break;\n";
        $output .= "			}\n";
        $output .= "		}\n";
        $output .= "		obj = null;\n";
        $output .= "	}\n";
        $output .= "	taskchain_removeEventListener(self, 'blur',   taskchain_onblur,   false);\n";
        $output .= "	taskchain_removeEventListener(self, 'unload', taskchain_onunload, false);\n";
        $output .= "}\n";
        $output .= "taskchain_addEventListener(self, 'load',   taskchain_onload,   false);\n";
        $output .= "taskchain_addEventListener(self, 'blur',   taskchain_onblur,   false);\n";
        $output .= "taskchain_addEventListener(self, 'unload', taskchain_onunload, false);\n";
        $output .= '//]]>'."\n";
        $output .= "</script>\n";
        return $output;
    }

    /**
     * entrypage
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function entrypage() {
        $output = '';

        // show task name and description
        $output .= $this->heading();

        // show entry page text, if required
        $output .= $this->description_box('entry');

        // show entry page warnings, if any
        $output .= $this->warnings('chain');

        // show entry page options, if required
        $output .= $this->entryoptions();

        // show view/review/continue button
        $output .= $this->view_attempt_button('chain');

        return $output;
    }

    /**
     * exitpage
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function exitpage() {
        $output = '';

        // show task name and description
        $output .= $this->heading();

        // show exit page
        $output .= $this->exitfeedback();

        // show entry page text, if required
        $output .= $this->description_box('exit');

        // show entry page warnings, if any
        $output .= $this->warnings('chain');

        // show exit links
        $output .= $this->exitlinks();

        // if there is no link back to the course page, show a continue button
        if (! $this->TC->chain->exitoptions & mod_taskchain::EXITOPTIONS_COURSE) {
            $output .= $this->continue_button($this->TC->url->course());
        }

        return $output;
    }

    /////////////////////////////////////////////////////////////////////
    // functions to generate common html snippets                      //
    /////////////////////////////////////////////////////////////////////

    /**
     * form_start
     *
     * @param xxx $taskchainscriptname
     * @param xxx $params
     * @param xxx $attributes (optional, default=array)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function form_start($taskchainscriptname, $params, $attributes=array())  {
        $output = '';

        if (empty($attributes['method'])) {
            $attributes['method'] = 'post';
        }
        if (empty($attributes['action'])) {
            $url = new moodle_url('/mod/taskchain/'.$taskchainscriptname);
            $attributes['action'] = $url->out();
        }
        $output .= html_writer::start_tag('form', $attributes)."\n";

        $params['sesskey'] = sesskey();

        $hiddenfields = '';
        foreach ($params as $name => $value) {
            $hiddenfields .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>$name, 'value'=>$value))."\n";
        }
        if ($hiddenfields) {
            // xhtml strict requires a container for the hidden input elements
            $output .= html_writer::start_tag('fieldset', array('style'=>'display:none'))."\n";
            $output .= $hiddenfields;
            $output .= html_writer::end_tag('fieldset')."\n";
        }

        // xhtml strict requires a container for the contents of the <form>
        $output .= html_writer::start_tag('div')."\n";

        return $output;
    }

    /**
     * form_end
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function form_end()  {
        $output = '';
        $output .= html_writer::end_tag('div')."\n";
        $output .= html_writer::end_tag('form')."\n";
        return $output;
    }

    ////////////////////////////////////////////////////////////////////////////
    // Helper methods                                                         //
    ////////////////////////////////////////////////////////////////////////////

    /**
     * format_url
     *
     * @uses $CFG
     * @param xxx $url
     * @param xxx $id
     * @param xxx $params
     * @param xxx $more_params (optional, default=false)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function format_url($url, $id, $params, $more_params=false)  {
        global $CFG;

        // convert relative URL to absolute URL
        if (! preg_match('/^(https?:\/)?\//', $url)) {
            $url = $CFG->wwwroot.'/mod/taskchain/'.$url;
        }

        // merge parameters into a single array (including $TC ids)
        $all_params = $this->TC->merge_params($params, $more_params, $id);

        $join = '?';
        foreach ($all_params as $name=>$value) {
            if ($value) {
                $url .= $join.$name.'='.$value;
                $join = '&'; // &amp;
            }
        }
        return $url;
    }

    /**
     * commands
     *
     * @param xxx $types
     * @param xxx $scripts taskchain script name(s) e.g. attempt.php
     * @param xxx $id
     * @param xxx $params
     * @param xxx $popup (optional, default=false)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function commands($types, $scripts, $id, $params, $popup=false)  {
        // $types : array('add', 'update', 'delete', 'deleteall', 'preview')
        // $params : array('name' => 'value') for url query string
        // $popup : true, false or array('name' => 'something', 'width' => 999, 'height' => 999)

        $is_array_scripts = is_array($scripts);
        $is_array_params = (is_array($params) && isset($params[0]) && is_array($params[0]));

        $commands = html_writer::start_tag('span', array('class'=>'commands'))."\n";
        foreach ($types as $i => $type) {
            if ($is_array_scripts) {
                $s = $scripts[$i];
            } else {
                $s = $scripts;
            }
            if ($is_array_params) {
                $p = $params[$i];
            } else {
                $p = $params;
            }
            $commands .= $this->command($type, $s, $id, $p, $popup);
        }
        $commands .= html_writer::end_tag('span')."\n";
        return $commands;
    }

    /**
     * command
     *
     * @uses $CFG
     * @param xxx $type
     * @param xxx $taskchainscriptname
     * @param xxx $id
     * @param xxx $params
     * @param xxx $popup (optional, default=false)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function command($type, $taskchainscriptname, $id, $params, $popup=false)  {
        global $CFG;

        static $str;
        if (! isset($str)) {
            $str = new stdClass();
        }
        if (! isset($str->$type)) {
            $str->$type = get_string($type);
        }

        switch ($type) {
            case 'add':
                $icon = ''; // t/add
                break;
            case 'edit':
            case 'update':
                $icon = 't/edit';
                break;
            case 'delete':
                $icon = 't/delete';
                break;
            case 'deleteall':
                $icon = '';
                break;
            case 'preview':
                $icon = 't/preview';
                break;
            default:
                // unknown command type !!
                return '';
        }

        //foreach ($params as $key => $value) {
        //    if (empty($value)) {
        //        unset($params[$key]);
        //    }
        //}

        $params['action'] = $type;
        $params['inpopup'] = 0;
        $url = $this->format_url($taskchainscriptname, '', $params);
        //$url = new moodle_url('/mod/taskchain/'.$taskchainscriptname, $params);

        if ($icon) {
            $linktext = $this->pix_icon($icon, get_string($type));
        } else {
            $linktext = $str->$type;
        }

        if ($popup) {
            if (is_bool($popup)) {
                $popup = array();
            } else if (is_string($popup)) {
                $popup = array('name' => $popup);
            }
            // Note: Moodle >= 2.7 styles treat width <= 767px as a tablet
            $name = (isset($popup['name']) ? $popup['name'] : get_string('popupwindowname'));
            $width = (isset($popup['width']) ? $popup['width'] : 780);
            $height = (isset($popup['height']) ? $popup['height'] : 520);
            $options = "menubar=0,location=0,scrollbars,resizable,width=$width,height=$height";

            $simple_js = true;
            if ($simple_js) {
                if (empty($popup['fullscreen']) && empty($popup['fullwidth'])) {
                    $width  = "Math.min($width,screen.availWidth)";
                } else {
                    $width  = 'screen.availWidth'; // fullscreen OR fullwidth
                }
                if (empty($popup['fullscreen']) && empty($popup['fullheight'])) {
                    $height  = "Math.min($height,screen.availHeight)";
                } else {
                    $height  = 'screen.availHeight'; // fullscreen OR fullheight
                }
                $onclick = "this.target='$name'; var w=window.open(this.href+'&inpopup=1','$name','$options');".
                           "w.moveTo(0,0); w.resizeTo($width,$height); if(w)w.focus(); w=null; return false;";
                $command = html_writer::link($url, $linktext,  array('title' => $str->$type, 'onclick' => $onclick));
            } else {
                $action = new popup_action('click', $url, $name, array('height' => $height, 'width' => $width));
                $command = $this->action_link($url, $linktext, $action, array('title' => $str->$type))."\n";
            }

        } else {
            $command = html_writer::link($url, $linktext, array('title' => $str->$type))."\n";
        }

        if (! $icon) {
            // add white space between text commands
            $command .= ' &nbsp; ';
        }

        return ' '.$command;
    }

    /**
     * heading
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function heading($text='', $level=2, $classes='main', $id=null) {
        if ($text=='') {
            switch (true) {
                case isset($this->TC->condition) : // drop through to add Task name
                case isset($this->TC->task)      : $text = format_string($this->TC->task->name);      break;
                case isset($this->TC->taskchain) : $text = format_string($this->TC->taskchain->name); break;
            }
        }
        if ($this->TC->can->manage()) {
            switch (true) {
                case isset($this->TC->condition) : break; // do nothing
                case isset($this->TC->task)      : $text .= ' '.$this->taskedit_icon(); break;
                case isset($this->TC->taskchain) : $text .= ' '.$this->modedit_icon();  break;
            }
        }
        return parent::heading($text);
    }

    /**
     * modedit_icon
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function modedit_icon() {
        $params = array('update' => $this->TC->coursemodule->id, 'return' => 1, 'sesskey' => sesskey());
        $url = new moodle_url('/course/modedit.php', $params);
        $img = html_writer::empty_tag('img', array('src' => $this->pix_url('t/edit')));
        return ' '.html_writer::link($url, $img);
    }

    /**
     * taskedit_icon
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function taskedit_icon() {
        $img = html_writer::empty_tag('img', array('src' => $this->pix_url('t/edit')));
        return ' '.html_writer::link($this->TC->url->edit('task'), $img);
    }

    /**
     * Formats taskchain entry/exit description text
     *
     * @uses $CFG
     * @param string $type of page, either "entry" or "exit"
     * @return string
     */
    public function description_box($type='') {
        global $CFG;
        require_once($CFG->dirroot.'/lib/filelib.php');

        if ($type) {
            $textfield = $type.'text';
            $formatfield = $type.'format';
        } else {
            $type = 'intro';
            $textfield = 'intro';
            $formatfield = 'introformat';
        }

        $text = '';
        if (trim(strip_tags($this->TC->chain->$textfield))) {
            $options = (object)array('noclean'=>true, 'para'=>false, 'filter'=>true, 'context'=>$this->TC->coursemodule->context);
            $text = file_rewrite_pluginfile_urls($this->TC->chain->$textfield, 'pluginfile.php', $this->TC->coursemodule->context->id, 'mod_taskchain', $type, null);
            $text = trim(format_text($text, $this->TC->chain->$formatfield, $options, null));
        }

        if ($text) {
            return $this->box($text, 'generalbox', 'intro');
        } else {
            return '';
        }
    }

    /**
     * entryoptions
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function entryoptions()  {
        $output = '';
        $table = new html_table();

        // define the date format - can be one of the following:
        // strftimerecentfull, strftimedaydatetime, strftimedatetime
        $dateformat = get_string('strftimedaydatetime');

        // show open / close dates
        if ($this->TC->chain->entryoptions & mod_taskchain::ENTRYOPTIONS_DATES) {

            if ($this->TC->chain->timeopen) {
                $table->data[] = new html_table_row(array(
                    new html_table_cell(get_string('timeopen', 'mod_taskchain').':'),
                    new html_table_cell(userdate($this->TC->chain->timeopen, $dateformat))
                ));
            }

            if ($this->TC->chain->timeclose) {
                $table->data[] = new html_table_row(array(
                    new html_table_cell(get_string('timeclose', 'mod_taskchain').':'),
                    new html_table_cell(userdate($this->TC->chain->timeclose, $dateformat))
                ));
            }
        }

        // show grading info
        if ($this->TC->chain->entryoptions & mod_taskchain::ENTRYOPTIONS_GRADING) {

            if ($this->TC->chain->attemptlimit > 1) {
                $table->data[] = new html_table_row(array(
                    new html_table_cell(get_string('attemptsallowed', 'mod_taskchain').':'),
                    new html_table_cell($this->TC->chain->attemptlimit)
                ));
            }

            if ($this->TC->chain->timelimit > 0) {
                $table->data[] = new html_table_row(array(
                    new html_table_cell(get_string('timelimit', 'mod_taskchain').':'),
                    new html_table_cell(format_time($this->TC->chain->timelimit))
                ));
            }

            if ($this->TC->chain->gradeweighting && $this->TC->chain->attemptlimit != 1) {
                $table->data[] = new html_table_row(array(
                    new html_table_cell(get_string('grademethod', 'mod_taskchain').':'),
                    new html_table_cell($this->TC->format_grademethod())
                ));
            }
        }

        if (count($table->data)) {
            $table->attributes['class'] = 'generaltable taskchainentryoptions';
            $output .= html_writer::table($table);
        }

        // print summary of attempts by this user at this chain
        if ($this->TC->chain->entryoptions & mod_taskchain::ENTRYOPTIONS_ATTEMPTS) {
            $output .= $this->attemptssummary('chain');
        }

        return $output;
    }

    /**
     * warnings
     *
     * @uses $CFG
     * @uses $USER
     * @param xxx $type "chain" or "task"
     * @return xxx
     * @todo Finish documenting this function
     */
    function warnings($type) {
        global $CFG, $USER;
        $warnings = array();

        $canstart = true;
        $canresume = $this->TC->$type->allowresume;

        if (! $this->TC->can->preview()) {
            if ($type=='chain') {
                if ($error = $this->TC->require_chain_tasks()) {
                    // there are no tasks in this chain
                    $warnings[] = $error;
                    $canstart = false;
                    $canresume = false;
                }
            }
            if ($error = $this->TC->require_isopen('chain')) {
                // chain/task is not (yet) open
                $warnings[] = $error;
                $canstart = false;
                $canresume = false;
            }
            if ($error = $this->TC->require_notclosed('chain')) {
                // chain/task is (already) closed
                $warnings[] = $error;
                $canstart = false;
                $canresume = false;
            }
            if ($error = $this->TC->require_entrycm()) {
                // minimum grade for previous activity not satisfied
                $warnings[] = $error;
                $canstart = false;
                $canresume = false;
            }
            if ($error = $this->TC->require_delay('chain', 'delay1')) {
                // delay1 has not expired yet
                $warnings[] = $error;
                $canstart = false;
            }
            if ($error = $this->TC->require_delay('chain', 'delay2')) {
                // delay2 has not expired yet
                $warnings[] = $error;
                $canstart = false;
            }
            if ($error = $this->TC->require_moreattempts('chain', true)) {
                // maximum number of attempts reached
                $warnings[] = $error;
                $canstart = false;
            }
        }

        // cache the $canstart and $canresume values for this $type
        $this->TC->can->attempts('start'.$type, $canstart);
        $this->TC->can->attempts('resume'.$type, $canresume);

        // return formatted warnings
        if (count($warnings)) {
            return $this->box(html_writer::alist($warnings), 'generalbox taskchainwarnings');
        } else {
            return '';
        }
    }

    /**
     * attemptssummary
     *
     * @uses $CFG
     * @param xxx $type "chain" or "task"
     * @return xxx
     * @todo Finish documenting this function
     */
    function attemptssummary($type) {
        global $CFG, $DB;
        $output = '';

        // get the attempts for this $type
        $this->TC->get_attempts($type);

        $attempts = "{$type}attempts";
        $countattempts = "count{$type}attempts";
        $resumeattempts = "resume{$type}attempts";

        $attemptids = array(
            'all' => array(),
            'inprogress' => array(),
            'timedout'   => array(),
            'abandoned'  => array(),
            'completed'  => array(),
            'zeroduration' => array(),
            'zeroscore' => array()
        );

        if ($type=='chain') {
            $number = 'cnumber';
            $grade = 'grade';
        } else {
            $number = 'tnumber';
            $grade = 'score';
        }
        $mode = $type.$grade;
        $gradelimit = $grade.'limit';
        $grademethod = $grade.'method';
        $gradeweighting = $grade.'weighting';

        if ($this->TC->$attempts) {
            // show summary of attempts so far

            // get chaingrades/taskscores
            $graderecords = $mode.'s';
            $this->TC->get->$graderecords();

            $dateformat = get_string('strftimerecentfull');
            $strresume = get_string('resume', 'mod_taskchain');

            // cache showselectcolumn switch
            if ($this->TC->can->deleteattempts()) {
                $showselectcolumn = true;
            } else {
                $showselectcolumn = false;
            }

            $canstart = $this->TC->can->attempts('start'.$type);
            $canresume = $this->TC->can->attempts('resume'.$type);

            if ($this->TC->can->viewreports()) {
                // teacher
                $canreview = true;
                $resumetab = 'preview';
            } else if ($this->TC->can->reviewmyattempts()) {
                // student
                $canreview = $canstart;
                $resumetab = 'info';
            } else {
                // somebody else - guest?
                $canreview = false;
                $resumetab = '';
            }

            // start attempts table (info + resume buttons)
            $table = new html_table();
            $table->attributes['class'] = 'generaltable taskchainattemptssummary';

            $table->head  = array(get_string($number, 'mod_taskchain'),
                                  get_string('status', 'mod_taskchain'),
                                  get_string('duration', 'mod_taskchain'),
                                  get_string('lastaccess', 'mod_taskchain'));
            $table->align = array('center', 'center', 'left', 'left');
            $table->size  = array('', '', '', '');

            if ($this->TC->$type->$gradelimit && $this->TC->$type->$gradeweighting) {
                // insert grade column
                array_splice($table->head, 1, 0, array(get_string($grade, 'mod_taskchain')));
                array_splice($table->align, 1, 0, array('center'));
                array_splice($table->size, 1, 0, array(''));
            }
            if ($showselectcolumn) {
                // prepend select column
                array_splice($table->head, 0, 0, '&nbsp;');
                array_splice($table->align, 0, 0, array('center'));
                array_splice($table->size, 0, 0, array(''));
            }
            if ($canresume) {
                // append resume column
                $table->head[] = '&nbsp;';
                $table->align[] = 'center';
                $table->size[] = '';
            }

            // print rows of attempt info
            foreach ($this->TC->$attempts as $attempt) {
                $row = new html_table_row();

                if ($showselectcolumn) {
                    $id = '['.$attempt->userid.']['.$attempt->chainid.']['.$attempt->cnumber.']';
                    $row->cells[] = html_writer::checkbox('selected'.$id, 1, false);

                    switch ($attempt->status) {
                        case mod_taskchain::STATUS_INPROGRESS: $attemptids['inprogress'][] = $id; break;
                        case mod_taskchain::STATUS_TIMEDOUT: $attemptids['timedout'][] = $id; break;
                        case mod_taskchain::STATUS_ABANDONED: $attemptids['abandoned'][] = $id; break;
                        case mod_taskchain::STATUS_COMPLETED: $attemptids['completed'][] = $id; break;
                    }
                    if ($attempt->$grade==0) {
                        $attemptids['zero'.$grade][] = $id;
                    }
                    if ($attempt->duration==0) {
                        $attemptids['zeroduration'][] = $id;
                    }
                    $attemptids['all'][] = $id;
                }

                $row->cells[] = $attempt->$number;
                if ($this->TC->$type->$gradelimit && $this->TC->$type->$gradeweighting) {
                    if ($canreview && isset($this->TC->$mode)) {
                        $params = array('tab' => 'report', 'mode' => $mode, $mode.'id' => $this->TC->$mode->id);
                        $href = $this->format_url('report.php', '', $params);
                        $row->cells[] = '<a href="'.$href.'">'.$attempt->$grade.'%</a>';
                    } else {
                        $row->cells[] = $attempt->$grade.'%';
                    }
                }
                $row->cells[] = mod_taskchain::format_status($attempt->status);
                $row->cells[] = mod_taskchain::format_time($attempt->duration);
                $row->cells[] = userdate($attempt->timemodified, $dateformat);

                if ($canresume) {
                    $cell = '&nbsp;';
                    if ($attempt->status==mod_taskchain::STATUS_INPROGRESS) {
                        if ($this->TC->$type->timelimit && $attempt->duration > $this->TC->$type->timelimit) {
                            // do nothing, this attempt has timed out
                        } else {
                            $params = array('tab'=>$resumetab, $type.'attemptid'=>$attempt->id);
                            $cell = ''
                                .'<a class="resumeattempt" href="'.$this->format_url('attempt.php', 'coursemoduleid', $params).'">'
                                .$strresume
                                //.'<img src="'.$CFG->pixpath.'/t/preview.gif" class="iconsmall" alt="'.$strresume.'" />'
                                .'</a>'
                            ;
                        }
                    }
                    $row->cells[] = $cell;
                }

                $table->data[] = $row;
            }

            // start form if necessary
            if ($showselectcolumn) {
                $onsubmit = ''
                    ."var x=false;"
                    ."var obj=document.getElementsByTagName('input');"
                    ."if(obj){"
                        ."for(var i in obj){"
                            ."if(obj[i].name && obj[i].name.substr(0,9)=='selected[' && obj[i].checked){"
                                ."x=true;"
                                ."break;"
                            ."}"
                        ."}"
                        ."if(!x){"
                            ."alert('".get_string('checksomeboxes', 'mod_taskchain')."');"
                        ."}"
                    ."}"
                    ."if(x){"
                        ."x=confirm('".get_string('confirmdeleteattempts', 'mod_taskchain')."');"
                    ."}"
                    ."if(this.elements['confirmed']){"
                        ."this.elements['confirmed'].value=(x?1:0);"
                    ."}"
                    ."return x;"
                ;
                $params = array('confirmed' => '0',
                                'action'    => 'deleteselected',
                                'userlist'  => $this->TC->get_userid());
                $params = $this->TC->merge_params($params);
                $output .= $this->form_start('view.php', $params, array('onsubmit' => $onsubmit));
           }

            // print the summary of attempts
            $output .= html_writer::table($table);

            // end form if necessary
            if ($showselectcolumn) {
                $output .= ''
                    .'<script type="text/javascript">'."\n"
                    .'//<!CDATA['."\n"
                    ."function taskchain_set_checked(nameFilter, indexFilter, checkedValue) {\n"
                    ."	var partMatchName = new RegExp(nameFilter);\n"
                    ."	var fullMatchName = new RegExp(nameFilter+indexFilter);\n"
                    ."	var inputs = document.getElementsByTagName('input');\n"
                    ."	if (inputs) {\n"
                    ."		var i_max = inputs.length;\n"
                    ."	} else {\n"
                    ."		var i_max = 0;\n"
                    ."	}\n"
                    ."	for (var i=0; i<i_max; i++) {\n"
                    ."		if (inputs[i].type=='checkbox' && inputs[i].name.match(partMatchName)) {\n"
                    ."			if (inputs[i].name.match(fullMatchName)) {\n"
                    ."				inputs[i].checked = checkedValue;\n"
                    ."			} else {\n"
                    ."				inputs[i].checked = false;\n"
                    ."			}\n"
                    ."		}\n"
                    ."	}\n"
                    ."	return true;\n"
                    ."}\n"
                    ."function taskchain_set_checked_attempts(obj) {\n"
                    ."	var indexFilter = obj.options[obj.selectedIndex].value;\n"
                    ."	if (indexFilter=='none') {\n"
                    ."		checkedValue = 0;\n"
                    ."	} else {\n"
                    ."		checkedValue = 1;\n"
                    ."	}\n"
                    ."	if (indexFilter=='none' || indexFilter=='all') {\n"
                    ."		indexFilter = '\\\\[\\\\d+\\\\]\\\\[\\\\d+\\\\]';\n"
                    ."	} else {\n"
                    ."		indexFilter = indexFilter.replace(new RegExp('^[^:]*:'), '');\n"
                    ."		indexFilter = indexFilter.replace(new RegExp(',', 'g'), '|');\n"
                    ."		indexFilter = indexFilter.replace(new RegExp('\\\\[', 'g'), '\\\\[');\n"
                    ."		indexFilter = indexFilter.replace(new RegExp('\\\\]', 'g'), '\\\\]');\n"
                    ."	}\n"
                    ."	taskchain_set_checked('selected', indexFilter, checkedValue);"
                    ."}\n"
                    .'//]]>'."\n"
                    .'</script>'."\n"
                ;

                // set up attempt status drop down menu
                $options = array(
                    'none' => get_string('none')
                );
                foreach($attemptids as $type=>$ids) {
                    if ($total = count($ids)) {
                        if ($type=='all') {
                            $options['all'] = get_string('all');
                            if ($total > 1) {
                                $options['all'] .= " ($total)";
                            }
                        } else {
                            $options[$type.':'.implode(',', $ids)] = get_string($type, 'mod_taskchain')." ($total)";
                        }
                    }
                }

                // print attempt selection and deletion form
                $table = new html_table();
                $table->attributes['class'] = 'generaltable taskchaindeleteattempts';

                $table->data[] = new html_table_row(array(
                    get_string('selectattempts', 'mod_taskchain').':',
                    html_writer::select($options, 'selectattempts', '', '', array('onchange' => 'return taskchain_set_checked_attempts(this)'))
                ));

                $table->data[] = new html_table_row(array(
                    $this->notext,
                    html_writer::empty_tag('input', array('type'=>'submit', 'value'=>get_string('deleteattempts', 'mod_taskchain')))
                ));

                $output .= html_writer::table($table)."\n";
                $output = $this->box($output, 'generalbox');

                $output .= $this->form_end();
            }
        }

        return $output;
    }

    /**
     * view_attempt_button
     *
     * @uses $CFG
     * @uses $USER
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    function view_attempt_button($type) {
        global $CFG, $USER;
        $output = '';

        if ($this->TC->can->preview()) {
            // teacher
            $canstart = true;
            $button_string = "preview{$type}now";
            $tab = 'preview';
        } else if ($this->TC->can->attempt()) {
            // student
            $canstart = $this->TC->can->attempts('start'.$type);
            $button_string = "start{$type}attempt";
            $tab = 'info';
        } else {
            // somebody else - guest?
            $canstart = false;
            $button_string = '';
            $tab = '';
        }

        if ($canstart) {
            if ($type=='chain') {
                $cnumber = mod_taskchain::FORCE_NEW_ATTEMPT; // new chain attempt
                $tnumber = 0; // undefined
            } else {
                $cnumber = $this->TC->get_cnumber();
                $tnumber = mod_taskchain::FORCE_NEW_ATTEMPT; // new task attempt
            }
            $output .= '<div style="text-align:center">';
            $params = array('cnumber' => $cnumber, 'tnumber' => $tnumber, 'tab' => $tab);
            $url = $this->format_url('attempt.php', 'coursemoduleid', $params);
            $output .= $this->single_button($url, get_string($button_string, 'mod_taskchain'), 'get');
            $output .= '</div>';
        } else {
            //print_heading(get_string('nomoreattempts', 'task'));
            $output .= $this->continue_button($CFG->wwwroot . '/course/view.php?id=' . $this->TC->course->id);
        }
        return $output;
    }

    /**
     * view_attempt_button
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function view_attempt_button_new($type)  {
        $output = '';

        // Initialize button text. This will be set something
        // if as start/continue attempt button should appear.
        $buttontext = '';

        if ($this->TC->can->preview()) {
            $buttontext = get_string('previewtasknow', 'mod_taskchain');
        } else if ($this->TC->can->start()) {
            if ($this->TC->count_distinct_clickreportids()) {
                $buttontext = get_string('reattempttask', 'mod_taskchain');
            } else {
                $buttontext = get_string('attempttasknow', 'mod_taskchain');
            }
        }

        $output .= $this->box_start('taskchainviewbutton');

        if ($buttontext) {
            $url = $this->TC->url->attempt();
            $button = new single_button($url, $buttontext);
            $button->class .= ' taskchainviewbutton';
            $output .= $this->render($button);
        } else {
            $url = new moodle_url('/course/view.php', array('id' => $this->TC->course->id));
            $output .= $this->continue_button($url);
        }

        $output .= $this->box_end();
        return $output;
    }

    /**
     * whatnext
     *
     * @param xxx $str (optional, default='')
     * @return xxx
     * @todo Finish documenting this function
     */
    public function whatnext($str='') {
        switch ($str) {
            case '':
                $whatnext = get_string('exit_whatnext_default', 'mod_taskchain');
                break;

            case 'exit_whatnext':
                switch (mt_rand(0,1)) { // random 0 or 1. You can add more if you like
                    case 0: $whatnext = get_string('exit_whatnext_0', 'mod_taskchain'); break;
                    case 1: $whatnext = get_string('exit_whatnext_1', 'mod_taskchain'); break;
                }
                break;

            default:
                $whatnext = get_string($str, 'mod_taskchain');
        }

        return html_writer::tag('h3', $whatnext, array('class'=>'taskchainwhatnext'));
    }

    /**
     * exitfeedback
     *
     * @uses $CFG
     * @return xxx
     * @todo Finish documenting this function
     */
    public function exitfeedback() {
        global $CFG;

        $percentsign = '%';

        $feedback = array();

        // shortcut to exitoptions
        $exitoptions = $this->TC->chain->exitoptions;

        if ($this->TC->chain->gradeweighting==0) {
            if ($exitoptions & mod_taskchain::EXITOPTIONS_ATTEMPTSCORE || $exitoptions & mod_taskchain::EXITOPTIONS_TASKCHAINGRADE) {
                $text = get_string('exit_nograde', 'mod_taskchain');
                $feedback[] = html_writer::tag('li', $text);
            }
        } else if ($this->TC->get_gradeitem() && $this->TC->get_chainattempt()) {
            if ($exitoptions & mod_taskchain::EXITOPTIONS_ENCOURAGEMENT) {
                switch (true) {
                    case $this->TC->chainattempt->grade >= 90:
                        $text = get_string('exit_excellent', 'mod_taskchain');
                        break;
                    case $this->TC->chainattempt->grade >= 60:
                        $text = get_string('exit_welldone', 'mod_taskchain');
                        break;
                    case $this->TC->chainattempt->grade > 0:
                        $text = get_string('exit_goodtry', 'mod_taskchain');
                        break;
                    default:
                        $text = get_string('exit_areyouok', 'mod_taskchain');
                }
                $feedback[] = html_writer::tag('li', $text, array('class' => 'taskchainexitencouragement'));
            }
            if ($exitoptions & mod_taskchain::EXITOPTIONS_ATTEMPTSCORE) {
                $text = get_string('exit_attemptscore', 'mod_taskchain', $this->TC->chainattempt->grade.$percentsign);
                $feedback[] = html_writer::tag('li', $text);
            }
            if ($exitoptions & mod_taskchain::EXITOPTIONS_TASKCHAINGRADE) {
                switch ($this->TC->chain->grademethod) {
                    case mod_taskchain::GRADEMETHOD_HIGHEST:
                        if ($this->TC->chainattempt->grade < $this->TC->gradeitem->percent) {
                            // current attempt is less than the highest so far
                            $text = get_string('exit_taskchaingrade_highest', 'mod_taskchain', $this->TC->gradeitem->percent.$percentsign);
                            $feedback[] = html_writer::tag('li', $text);
                        } else if ($this->TC->chainattempt->grade==0) {
                            // zero score is best so far
                            $text = get_string('exit_taskchaingrade_highest_zero', 'mod_taskchain', $this->TC->chainattempt->grade.$percentsign);
                            $feedback[] = html_writer::tag('li', $text);
                        } else if ($this->TC->get_chainattempts()) {
                            // current attempt is highest so far
                            $maxgrade = null;
                            foreach ($this->TC->chainattempts as $attempt) {
                                if ($attempt->id==$this->TC->chainattempt->id) {
                                    continue; // skip current attempt
                                }
                                if ($maxgrade===null || $maxgrade < $attempt->grade) {
                                    $maxgrade = $attempt->grade;
                                }
                            }
                            if ($maxgrade===null) {
                                // do nothing (no previous attempt)
                            } else if ($maxgrade==$this->TC->chainattempt->grade) {
                                // attempt grade equals previous best
                                $text = get_string('exit_taskchaingrade_highest_equal', 'mod_taskchain');
                                $feedback[] = html_writer::tag('li', $text);
                            } else {
                                $text = get_string('exit_taskchaingrade_highest_previous', 'mod_taskchain', $maxgrade.$percentsign);
                                $feedback[] = html_writer::tag('li', $text);
                            }
                        } else {
                            die('oops, no attempts');
                        }
                        break;
                    case mod_taskchain::GRADEMETHOD_AVERAGE:
                        $text = get_string('exit_taskchaingrade_average', 'mod_taskchain', $this->TC->gradeitem->percent.$percentsign);
                        $feedback[] = html_writer::tag('li', $text);
                        break;
                    // case mod_taskchain::GRADEMETHOD_TOTAL:
                    // case mod_taskchain::GRADEMETHOD_FIRST:
                    // case mod_taskchain::GRADEMETHOD_LAST:
                    default:
                        $text = get_string('exit_taskchaingrade', 'mod_taskchain', $this->TC->gradeitem->percent.$percentsign);
                        $feedback[] = html_writer::tag('li', $text);
                        break;
                }
            }
        }

        if (count($feedback)) {
            $feedback = html_writer::tag('ul', implode('', $feedback), array('class' => 'taskchainexitfeedback'));
            return $this->box($feedback);
        } else {
            return '';
        }
    }

    /**
     * exitlinks
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function exitlinks()  {
        $table = new html_table();
        $table->attributes['class'] = 'generaltable taskchainexitlinks';

        if ($this->TC->chainattempt->status==mod_taskchain::STATUS_COMPLETED) {
            // next activity, if there is one
            if ($this->TC->require_exitgrade() && $this->TC->chainattempt->grade < $this->TC->chain->exitgrade) {
                // insufficient grade to show link to next activity
                $cm = false;
            } else {
                // get next activity, if there is one
                $cm = $this->TC->get_cm('exit');
            }
            if ($cm) {
                $url = $this->TC->url->view($cm);
                $table->data[] = new html_table_row(array(
                    new html_table_cell(html_writer::link($url, get_string('exit_next', 'mod_taskchain'))),
                    new html_table_cell(html_writer::link($url, format_string(urldecode($cm->name))))
                ));
            }
        }

        if ($this->TC->chain->exitoptions & mod_taskchain::EXITOPTIONS_RETRY) {
            // retry this taskchain, if allowed
            if ($this->TC->chain->attemptlimit==0 || empty($this->TC->chainattempts) || $this->TC->chain->attemptlimit < count($this->TC->chainattempts)) {
                $table->data[] = new html_table_row(array(
                    new html_table_cell(html_writer::link($this->TC->url->view(), get_string('exit_retry', 'mod_taskchain'))),
                    new html_table_cell(html_writer::link($this->TC->url->view(), format_string($this->TC->taskchain->name))),
                ));
            }
        }

        if ($this->TC->chain->exitoptions & mod_taskchain::EXITOPTIONS_INDEX) {
            $table->data[] = new html_table_row(array(
                new html_table_cell(html_writer::link($this->TC->url->index(), get_string('exit_index', 'mod_taskchain'))),
                new html_table_cell(html_writer::link($this->TC->url->index(), get_string('exit_index_text', 'mod_taskchain')))
            ));
        }

        if ($this->TC->chain->exitoptions & mod_taskchain::EXITOPTIONS_COURSE) {
            $table->data[] = new html_table_row(array(
                new html_table_cell(html_writer::link($this->TC->url->course(), get_string('exit_course', 'mod_taskchain'))),
                new html_table_cell(html_writer::link($this->TC->url->course(), get_string('exit_course_text', 'mod_taskchain')))
            ));
        }

        if ($this->TC->chain->exitoptions & mod_taskchain::EXITOPTIONS_GRADES) {
            if ($this->TC->course->showgrades && $this->TC->chain->gradeweighting) {
                $url = new moodle_url($this->TC->url->grades());
                $table->data[] = new html_table_row(array(
                    new html_table_cell(html_writer::link($url, get_string('exit_grades', 'mod_taskchain'))),
                    new html_table_cell(html_writer::link($url, get_string('exit_grades_text', 'mod_taskchain')))
                ));
            }
        }

        $output = '';
        if ($count = count($table->data)) {
            if ($count>1) {
                $output .= $this->whatnext('exit_whatnext');
            }
            $output .= html_writer::table($table);
        }

        return $output;
    }

    /**
     * page_delete
     *
     * @todo Finish documenting this function
     */
    function page_delete($message, $taskchainscriptname, $params, $footercourse='none') {
        $output = '';

        $output .= $this->header();
        $output .= $this->heading();

        $output .= $this->box_start('generalbox', 'notice');
        $output .= $this->form_start($taskchainscriptname, $params);

        $output .= html_writer::start_tag('div', array('class' => 'buttons'));
        $output .= html_writer::tag('p', $message);
        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'deleteconfirmed', 'value' => get_string('yes')));
        $output .= ' ';
        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'deletecancelled', 'value' => get_string('no')));
        $output .= html_writer::end_tag('div');
        $output .= $this->form_end();

        $output .= $this->box_end();
        $output .= $this->footer();

        return $output;
    }

    /**
     * page_quick
     *
     * @todo Finish documenting this function
     */
    function page_quick($text='', $button='', $link='') {
        $output = '';

        $output .= $this->header();
        $output .= $this->heading();

        $output .= $this->box_start('generalbox', 'notice');
        $output .= html_writer::tag('p', $text, array('align' => 'center'));

        switch ($button) {
            case 'continue' : $output .= $this->continue_button($link); break;
            case 'close'    : $output .= $this->close_window_button(); break;
            default:          $output .= $this->single_button($link, $button);
        }

        $output .= $this->box_end();
        $output .= $this->js();
        $output .= $this->footer();

        return $output;
    }

    /**
     * js = subclasses can add their own javascript if they want
     *
     * @todo Finish documenting this function
     */
    function js() {
        global $mform;
        $method = 'get_js';
        if (isset($mform) && method_exists($mform, $method)) {
            return $mform->$method();
        } else {
            return '';
        }
    }

}
