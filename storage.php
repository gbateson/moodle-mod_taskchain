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
 * mod/taskchain/storage.php
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
require_once($CFG->dirroot.'/lib/xmlize.php');

/**
 * mod_taskchain_storage
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_storage {

    /**
     * the name of the $_POST fields holding the score and details
     * and the xml tag within the details that holds the results
     */
    const scorefield = 'mark';
    const detailsfield = 'detail';
    const xmlresultstag = 'hpjsresult';

    /**
     * the two fields that will be used to determine the duration of a task attempt
     *     starttime/endtime are recorded by the client (and may not be trustworthy)
     *     resumestart/resumefinish are recorded by the server (but include transfer time to and from client)
     */
    const durationstartfield = 'timestart';
    const durationfinishfield = 'timefinish';

    // functions to store responses returned from browser

    /**
     * store
     *
     * @uses $CFG
     * @uses $DB
     * @param xxx $TC
     * @todo Finish documenting this function
     */
    static public function store($TC)  {
        global $CFG, $DB;

        if (empty($TC->taskattempt)) {
            return; // no attempt record - shouldn't happen !!
        }

        if ($TC->taskattempt->userid != $TC->userid) {
            return; // wrong userid - shouldn't happen !!
        }

        // update task attempt fields using incoming data
        $TC->taskattempt->score    = max(0, optional_param(self::scorefield, 0, PARAM_INT));
        $TC->taskattempt->status   = max(0, optional_param('status', 0, PARAM_INT));
        $TC->taskattempt->redirect = max(0, optional_param('redirect', 0, PARAM_INT));
        $TC->taskattempt->details  = optional_param(self::detailsfield, '', PARAM_RAW);

        // time values, e.g. "2008-09-12 16:18:18 +0900",
        // need to be converted to numeric date stamps
        $timefields = array('starttime', 'endtime');
        foreach ($timefields as $timefield) {

            $TC->taskattempt->$timefield = 0; // default
            if ($time = optional_param($timefield, '', PARAM_RAW)) {

                // make sure the timezone has a "+" sign
                // Note: sometimes it gets stripped (by optional_param?)
                $time = preg_replace('/(?<= )\d{4}$/', '+$0', trim($time));

                // convert $time to numeric date stamp
                // PHP4 gives -1 on error, whereas PHP5 give false
                $time = strtotime($time);

                if ($time && $time>0) {
                    $TC->taskattempt->$timefield = $time;
                }
            }
        }
        unset($timefields, $timefield, $time);

        // set finish times
        $TC->taskattempt->timefinish = $TC->time;
        $TC->taskattempt->resumefinish = $TC->time;

        // increment task attempt duration
        $startfield = self::durationstartfield; // "starttime" or "resumestart"
        $finishfield = self::durationfinishfield; // "endtime" or "resumefinish"
        $duration = ($TC->taskattempt->$finishfield - $TC->taskattempt->$startfield);
        if ($duration > 0) {
            $TC->taskattempt->duration += $duration;
        }
        unset($duration, $startfield, $finishfield);

        // set clickreportid, (for click reporting)
        $TC->taskattempt->clickreportid = $TC->taskattempt->id;

        // check if there are any previous results stored for this attempt
        // this could happen if ...
        //     - the task has been resumed
        //     - clickreporting is enabled for this task
        if ($DB->get_field('taskchain_task_attempts', 'timefinish', array('id'=>$TC->taskattempt->id))) {
            if (self::can_clickreport($TC)) {
                // add task attempt record for each form submission
                // records are linked via the "clickreportid" field

                // update timemodified and status in previous records in this clickreportid group
                $params = array('clickreportid' => $TC->taskattempt->clickreportid);
                $DB->set_field('taskchain_task_attempts', 'timemodified', $TC->time, $params);
                $DB->set_field('taskchain_task_attempts', 'status', $TC->taskattempt->status, $params);

                // add new attempt record
                unset($TC->taskattempt->id);
                if (! $TC->taskattempt->id = $DB->insert_record('taskchain_task_attempts', $TC->taskattempt->to_stdclass())) {
                    print_error('error_insertrecord', 'taskchain', '', 'taskchain_task_attempts');
                }

            } else {
                // remove previous responses for this attempt, if required
                // (N.B. this does NOT remove the attempt record, just the responses)
                $params = array('attemptid' => $TC->taskattempt->id);
                $DB->delete_records('taskchain_responses', $params);
            }
        }

        // add details of this task attempt, if required
        // "taskchain_storedetails" is set by administrator
        // Site Admin -> Modules -> Activities -> TaskChain
        if ($CFG->taskchain_storedetails) {

            // delete/update/add the details record
            $params = array('attemptid' => $TC->taskattempt->id);
            if ($DB->record_exists('taskchain_details', $params)) {
                $DB->set_field('taskchain_details', 'details', $TC->taskattempt->details, $params);
            } else {
                $details = (object)array(
                    'attemptid' => $TC->taskattempt->id,
                    'details' => $TC->taskattempt->details
                );
                if (! $DB->insert_record('taskchain_details', $details, false)) {
                    print_error('error_insertrecord', 'taskchain', '', 'taskchain_details');
                }
                unset($details);
            }
        }

        // add details of this attempt
        self::store_details($TC->taskattempt);

        // update the attempt record
        if (! $DB->update_record('taskchain_task_attempts', $TC->taskattempt->to_stdclass())) {
            print_error('error_updaterecord', 'taskchain', '', 'taskchain_task_attempts');
        }

        if ($TC->taskattempt->status==mod_taskchain::STATUS_ABANDONED) {
            switch (self::can_continue($TC)) {
                case mod_taskchain::CONTINUE_ABANDONCHAIN:
                    $TC->chaingrade->status==mod_taskchain::STATUS_ABANDONED;
                    if (! $DB->set_field('taskchain_chain_grades', 'status', mod_taskchain::STATUS_ABANDONED, array('id'=>$TC->chaingrade->id))) {
                        print_error('error_updaterecord', 'taskchain', '', 'taskchain_chain_grades');
                    }
                case mod_taskchain::CONTINUE_RESTARTCHAIN:
                    $TC->chainattempt->status==mod_taskchain::STATUS_ABANDONED;
                    if (! $DB->set_field('taskchain_chain_attempts', 'status', mod_taskchain::STATUS_ABANDONED, array('id'=>$TC->chainattempt->id))) {
                        print_error('error_updaterecord', 'taskchain', '', 'taskchain_chain_attempts');
                    }
                case mod_taskchain::CONTINUE_RESTARTTASK:
                    $TC->taskscore->status==mod_taskchain::STATUS_ABANDONED;
                    if (! $DB->set_field('taskchain_task_scores', 'status', mod_taskchain::STATUS_ABANDONED, array('id'=>$TC->taskscore->id))) {
                        print_error('error_updaterecord', 'taskchain', '', 'taskchain_task_scores');
                    }
                case mod_taskchain::CONTINUE_RESUMETASK:
                    // $TC->taskattempt has already been updated
                    // so we don't need to do anything here
            }
        }

        // regrade the task to take account of the latest task attempt score
        $TC->regrade_task();
    }

    /**
     * store_details
     *
     * @param xxx $attempt
     * @todo Finish documenting this function
     */
    static public function store_details($attempt)  {

        // parse the attempt details as xml
        $details = xmlize($attempt->details);
        $question_number; // initially unset
        $question = false;
        $response  = false;

        $i = 0;
        while (isset($details[self::xmlresultstag]['#']['fields']['0']['#']['field'][$i]['#'])) {

            // shortcut to field
            $field = &$details[self::xmlresultstag]['#']['fields']['0']['#']['field'][$i]['#'];

            // extract field name and data
            if (isset($field['fieldname'][0]['#']) && is_string($field['fieldname'][0]['#'])) {
                $name = $field['fieldname'][0]['#'];
            } else {
                $name = '';
            }
            if (isset($field['fielddata'][0]['#']) && is_string($field['fielddata'][0]['#'])) {
                $data = $field['fielddata'][0]['#'];
            } else {
                $data = '';
            }

            // parse the field name into $matches
            //  [1] task type
            //  [2] attempt detail name
            if (preg_match('/^(\w+?)_(\w+)$/', $name, $matches)) {
                $tasktype = strtolower($matches[1]);
                $name = strtolower($matches[2]);

                // parse the attempt detail $name into $matches
                //  [1] question number
                //  [2] question detail name
                if (preg_match('/^q(\d+)_(\w+)$/', $name, $matches)) {
                    $num = $matches[1];
                    $name = strtolower($matches[2]);
                    // not needed Moodle 2.0 and later
                    // $data = addslashes($data);

                    // adjust JCross question numbers
                    if (preg_match('/^(across|down)(.*)$/', $name, $matches)) {
                        $num .= '_'.$matches[1]; // e.g. 01_across, 02_down
                        $name = $matches[2];
                        if (substr($name, 0, 1)=='_') {
                            $name = substr($name, 1); // remove leading '_'
                        }
                    }

                    if (isset($question_number) && $question_number==$num) {
                        // do nothing - this response is for the same question as the previous response
                    } else {
                        // store previous question / response (if any)
                        self::add_response($attempt, $question, $response);

                        // initialize question object
                        $question = new stdClass();
                        $question->name = '';
                        $question->text = '';
                        $question->taskid = $attempt->taskid;

                        // initialize response object
                        $response = new stdClass();
                        $response->attemptid = $attempt->id;

                        // update question number
                        $question_number = $num;
                    }

                    // adjust field name and value, and set question type
                    // (may not be necessary one day)
                    // taskchain_adjust_response_field($tasktype, $question, $num, $name, $data);

                    // add $data to the question/response details
                    switch ($name) {
                        case 'name':
                        case 'type':
                            $question->$name = $data;
                            break;
                        case 'text':
                            $question->$name = mod_taskchain::string_id($data);
                            break;

                        case 'correct':
                        case 'ignored':
                        case 'wrong':
                            $response->$name = mod_taskchain::string_ids($data);
                            break;

                        case 'score':
                        case 'weighting':
                        case 'hints':
                        case 'clues':
                        case 'checks':
                            $response->$name = intval($data);
                            break;
                    }

                } else { // attempt details

                    // adjust field name and value
                    //taskchain_adjust_response_field($tasktype, $question, $num='', $name, $data);

                    // add $data to the attempt details
                    if ($name=='penalties') {
                        $attempt->$name = intval($data);
                    }
                }
            }

            $i++;
        } // end while

        // add the final question and response, if any
        self::add_response($attempt, $question, $response);
    }

    /**
     * add_response
     *
     * @uses $DB
     * @param xxx $attempt (passed by reference)
     * @param xxx $question (passed by reference)
     * @param xxx $response (passed by reference)
     * @todo Finish documenting this function
     */
    static public function add_response(&$attempt, &$question, &$response)  {
        global $DB;

        if (! $question || ! $response || ! isset($question->name)) {
            // nothing to add
            return;
        }

        $loopcount = 1;
        $questionname = $question->name;

        // loop until we are able to add the response record
        $looping = true;
        while ($looping) {

            $question->md5key = md5($question->name);
            if (! $question->id = $DB->get_field('taskchain_questions', 'id', array('taskid'=>$attempt->taskid, 'md5key'=>$question->md5key))) {
                // add question record
                if (! $question->id = $DB->insert_record('taskchain_questions', $question)) {
                    print_error('error_insertrecord', 'taskchain', '', 'taskchain_questions');
                }
            }

            if ($DB->record_exists('taskchain_responses', array('attemptid'=>$attempt->id, 'questionid'=>$question->id))) {
                // there is already a response to this question for this attempt
                // probably because this task has two questions with the same text
                //  e.g. Which one of these answers is correct?

                // To workaround this, we create new question names
                //  e.g. Which one of these answers is correct? (2)
                // until we get a question name for which there is no response yet on this attempt

                $loopcount++;
                $question->name = "$questionname ($loopcount)";

                // This method fails to correctly identify questions in
                // tasks which allow questions to be shuffled or omitted.
                // As yet, there is no workaround for such cases.

            } else {
                // no response found to this question in this attempt
                // so we can proceed
                $response->questionid = $question->id;

                // add response record
                if(! $response->id = $DB->insert_record('taskchain_responses', $response)) {
                    print_error('error_insertrecord', 'taskchain', '', 'taskchain_responses');
                }
                $looping = false;
            }

        } // end while
    }

    /**
     * provide_review
     * does this output format allow task attempts to be reviewed?
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    static public function provide_review() {
        return false;
    }

    /**
     * provide_resume
     * does this output format allow task attempts to be resumed?
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    static public function provide_resume() {
        return false;
    }

    /**
     * provide_clickreport
     * does this output format allow a clickreport
     * show a click trail of what students clicked
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    static public function provide_clickreport() {
        return false;
    }

    /**
     * can_review
     * can the current task attempt be reviewed now?
     *
     * @param xxx $TC
     * @return xxx
     * @todo Finish documenting this function
     */
    static public function can_review($TC) {
        if (self::provide_review() && $TC->task->reviewoptions) {
            if ($attempt = $TC->get_taskattempt()) {
                if ($TC->task->reviewoptions & mod_taskchain::REVIEW_DURINGATTEMPT) {
                    // during attempt
                    if ($attempt->status==mod_taskchain::STATUS_INPROGRESS) {
                        return true;
                    }
                }
                if ($TC->task->reviewoptions & mod_taskchain::REVIEW_AFTERATTEMPT) {
                    // after attempt (but before task closes)
                    if ($attempt->status==mod_taskchain::STATUS_COMPLETED) {
                        return true;
                    }
                    if ($attempt->status==mod_taskchain::STATUS_ABANDONED) {
                        return true;
                    }
                    if ($attempt->status==mod_taskchain::STATUS_TIMEDOUT) {
                        return true;
                    }
                }
                if ($TC->task->reviewoptions & mod_taskchain::REVIEW_AFTERCLOSE) {
                    // after the task closes
                    if ($TC->task->timeclose < $TC->time) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * can_resume
     * can the current chain/task attempt be paused and resumed later?
     *
     * @param xxx $TC
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    static public function can_resume($TC, $type) {
        if ($type=='chain' || ($type=='task' && self::provide_resume())) {
            if (isset($TC->$type) && $TC->$type->allowresume) {
                return true;
            }
        }
        return false;
    }

    /**
     * can_restart
     * can the current chain/task be restarted after the current attempt finishes?
     *
     * @param xxx $TC
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    static public function can_restart($TC, $type) {
        if (isset($TC->$type) && $TC->$type->attemptlimit) {
            if ($attempts = $TC->get_attempts($type)) {
                if (count($attempts) >= $TC->$type->attemptlimit) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * can_continue
     *
     * @param xxx $TC
     * @return xxx
     * @todo Finish documenting this function
     */
    static public function can_continue($TC) {
        if (self::can_resume($TC, 'chain')) {
            if (self::can_resume($TC, 'task')) {
                return mod_taskchain::CONTINUE_RESUMETASK;
            } else if (self::can_restart($TC, 'task')) {
                return mod_taskchain::CONTINUE_RESTARTTASK;
            }
        }
        if (self::can_restart($TC, 'chain')) {
            return mod_taskchain::CONTINUE_RESTARTCHAIN;
        } else {
            return mod_taskchain::CONTINUE_ABANDONCHAIN;
        }
    }

    /**
     * can_clickreport
     *
     * @param xxx $TC
     * @return xxx
     * @todo Finish documenting this function
     */
    static public function can_clickreport($TC) {
        if (self::provide_clickreport() && isset($TC->task) && $TC->task->clickreporting) {
            return true;
        } else {
            return false;
        }
    }
}
