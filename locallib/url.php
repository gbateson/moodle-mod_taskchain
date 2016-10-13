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
 * mod/taskchain/locallib/url.php
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
 * taskchain_url
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_url extends taskchain_base {

    /**
     * attempt_frame
     *
     * @param xxx $framename (optional, default='')
     * @return moodle_url of this taskchain's attempt page
     * @todo Finish documenting this function
     */
    public function attempt_frame($framename) {
        $params = array('framename' => $framename);
        return $this->attempt($params);
    }

    /**
     * attempt
     *
     * @param xxx $framename (optional, default='')
     * @param xxx $cm (optional, default=null)
     * @return moodle_url of this taskchain's attempt page
     * @todo Finish documenting this function
     */
    public function attempt($params=false) {
        $params = $this->TC->merge_params($params, null, 'coursemoduleid');
        return new moodle_url('/mod/taskchain/attempt.php', $params);
    }

    /**
     * course
     *
     * @param xxx $course (optional, default=null)
     * @return moodle_url of this taskchain's course page
     * @todo Finish documenting this function
     */
    public function course($course=null) {
        if (is_null($course)) {
            $course = $this->TC->course;
        }
        return new moodle_url('/course/view.php', array('id' => $course->id));
    }

    /**
     * edit
     *
     * @param string $type "chain", "chains", "columnlists", "condition", "task", "tasks"
     * @param array $params (optional, default=array())
     * @return moodle_url of one of this taskchain's edit pages
     * @todo Finish documenting this function
     */
    public function edit($type, $params=array()) {
        switch ($type) {
            case 'columnlists': $id = 'courseid';       break;
            case 'chains'     : $id = 'courseid';       break;
            case 'chain'      : $id = 'coursemoduleid'; break;
            case 'tasks'      : $id = 'coursemoduleid'; break;
            case 'task'       : $id = 'taskid';         break;
            case 'condition'  : $id = 'conditionid';    break;
            default           : $id = ''; // shouldn't happen !!
        }
        $params = $this->TC->merge_params($params, null, $id);
        return new moodle_url('/mod/taskchain/edit/'.$type.'.php', $params);
    }

    /**
     * grades
     *
     * @param xxx $course (optional, default=null)
     * @return moodle_url of this taskchain's course grade page
     * @todo Finish documenting this function
     */
    public function grades($course=null) {
        if (is_null($course)) {
            $course = $this->TC->course;
        }
        return new moodle_url('/grade/index.php', array('id' => $course->id));
    }

    /**
     * index
     *
     * @param xxx $course (optional, default=null)
     * @return moodle_url of this course's taskchain index page
     * @todo Finish documenting this function
     */
    public function index($course=null) {
        if (is_null($course)) {
            $course = $this->TC->course;
        }
        return new moodle_url('/mod/taskchain/index.php', array('id' => $course->id));
    }

    /**
     * report
     *
     * @param xxx $mode (optional, default='')
     * @param xxx $params (optional, default=null)
     * @return moodle_url of this taskchain's view page
     * @todo Finish documenting this function
     */
    public function report($mode='', $params=null) {
        if ($params===null) {
            $params = array();
            $params['id'] = $this->TC->coursemodule->id;
        }
        if ($mode) {
            $params['mode'] = $mode;
        }
        return new moodle_url('/mod/taskchain/report.php', $params);
    }

    /**
     * review
     *
     * @param xxx $taskattempt (optional, default=null)
     * @return moodle_url of the review page for an attempt at a task
     * @todo Finish documenting this function
     */
    public function review($taskattempt=null) {
        if ($taskattempt===null) {
            $taskattempt = $this->TC->taskattempt;
        }
        return new moodle_url('/mod/taskchain/review.php', array('id' => $taskattempt->id));
    }

    /**
     * submit
     *
     * @param xxx $taskattempt (optional, default=null)
     * @return moodle_url of this taskchain's attempt page
     * @todo Finish documenting this function
     */
    public function submit($taskattempt=null) {
        if ($taskattempt===null) {
            $taskattempt = $this->TC->taskattempt;
        }
        return new moodle_url('/mod/taskchain/submit.php', array('id' => $taskattempt->id));
    }

    /**
     * view
     *
     * @param xxx $cm (optional, default=null)
     * @return moodle_url of this taskchain's view page
     * @todo Finish documenting this function
     */
    public function view($cm=null, $params=null) {
        if ($params===null) {
            $params = array();
        }
        if ($cm===null) {
            $cm = $this->TC->coursemodule;
        }
        $params['id'] = $cm->id;
        return new moodle_url('/mod/'.$cm->modname.'/view.php', $params);
    }
}
