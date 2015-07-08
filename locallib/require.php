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
 * mod/taskchain/locallib/require.php
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
 * taskchain_require
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_require extends taskchain_base {

    /**
     * access
     *
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    function access($type) {
        if (! $error = $this->subnet($type)) {
            if (! $error = $this->password($type)) {
                $error = false;
            }
        }
        return $error;
    }

    /**
     * subnet
     *
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    function subnet($type) {
        $record = $this->TC->$type;
        if (! $record->subnet) {
            return false;
        }
        if (isset($_SERVER['REMOTE_ADDR']) && address_in_subnet($_SERVER['REMOTE_ADDR'], $record->subnet)) {
            return false;
        }
        // user's IP address is missing or does not match required subnet mask
        return get_string($type.'subneterror', 'mod_taskchain');
    }

    /**
     * password
     *
     * @uses $SESSION
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    function password($type) {
        global $SESSION;
        $error = '';

        // does this chain /task require a password?
        $record = $this->TC->$type;
        if ($record->password) {

            $taskchain_passwordchecked = 'taskchain_'.$type.'_passwordchecked';

            // has password not already been given?
            if (empty($SESSION->$taskchain_passwordchecked[$record->id])) {

                // get password, if any, that was entered
                $password = optional_param('taskchainpassword', '', PARAM_TEXT);
                if (strcmp($record->password, $password)) {

                    // password is missing or invalid
                    $error = html_writer::start_tag('form', array('id'=>'taskchainpasswordform', 'method'=>'post', 'action'=>$this->TC->url->attempt()))."\n";
                    $error .= html_writer::tag('p', get_string($type.'requirepasswordmessage', 'mod_taskchain'));
                    $error .= html_writer::tag('b', get_string('password')).': ';
                    $error .= html_writer::empty_tag('input', array('name'=>'taskchainpassword', 'type'=>'password', 'value'=>''));
                    $error .= html_writer::empty_tag('input', array('type'=>'submit', 'value'=>get_string('ok')));
                    $error .= html_writer::end_tag('form')."\n";
                    if ($password) {
                        // previously entered password was invalid
                        $error .= html_writer::tag('p', get_string('passworderror', 'mod_taskchain'), array('class'=>'red'));
                    }
                } else {
                    // newly entered password was correct
                    if (empty($SESSION->$taskchain_passwordchecked)) {
                        $SESSION->$taskchain_passwordchecked = array();
                    }
                    $SESSION->$taskchain_passwordchecked[$record->id] = true;
                }
            }
        }
        if ($error) {
            return $error;
        } else {
            return false;
        }
    }

    /**
     * availability
     * check availability of the chain / task
     *
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    function availability($type) {
        if (! $error = $this->isopen($type)) {
            if (! $error = $this->notclosed($type)) {
                $error = false;
            }
        }
        return $error;
    }

    /**
     * isopen
     *
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    function isopen($type) {
        $record = $this->TC->$type;
        if ($record->timeopen && $record->timeopen > $this->TC->time) {
            // chain/task is not yet open
            return get_string($type.'notavailable', 'mod_taskchain', userdate($record->timeopen));
        }
        return false;
    }

    /**
     * notclosed
     *
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    function notclosed($type) {
        $record = $this->TC->$type;
        if ($record->timeclose && $record->timeclose < $this->TC->time) {
            // chain/task is already closed
            return get_string($type.'closed', 'mod_taskchain', userdate($record->timeclose));
        }
        return false;
    }

    /**
     * previous_chainattempt
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function previous_chainattempt() {

        // if possible, find a previous chain attempt that can be resumed
        if ($this->TC->chain->allowresume && $this->TC->get_chainattempts()) {

            if ($this->TC->chain->allowresume==self::ALLOWRESUME_FORCE) {
                $force_resume = true;
            } else if ($error = $this->moreattempts('chain')) {
                $force_resume = true;
            } else if ($this->TC->show_entrypage()==false) {
                $force_resume = true;
            } else {
                $force_resume = false;
            }

            foreach (array_keys($this->TC->chainattempts) as $id) {
                $attempt = &$this->TC->chainattempts[$id];
                if ($error = $this->inprogress('chain', $attempt)) {
                    continue; // not "in progress"
                }
                if ($error = $this->moretime('chain', $attempt)) {
                    continue; // no more time
                }
                // $attempt can be resumed
                if ($force_resume) {
                    // set chain attempt details
                    $this->TC->chainattempt = new taskchain_chain_attempt($attempt, array('TC' => &$this->TC));
                    $this->TC->force_cnumber(0);

                    // unset task attempt
                    $this->TC->taskattempt = null;
                    $this->TC->force_tnumber(0);

                    // unset task details
                    $this->TC->task = null;
                } else {
                    // adjust setting to show entry page with a list of attempts
                    $this->TC->chain->set_entrypage(self::YES);
                    $this->TC->chain->set_entryoptions($this->TC->chain->entryoptions | self::ENTRYOPTIONS_ATTEMPTS);
                }
                // at least one attempt can be resumed, so we can stop
                // (if necessary, cnumber has been set to something valid)
                return false;
            }
        }

        // no "in progress" previous chain attempt was found
        // return "true" signifying, "new chain attempt is required"
        return true;
    }

    /**
     * valid_cnumber
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function valid_cnumber() {
        $cnumber = $this->TC->get_cnumber();

        if ($cnumber>0) {
            if ($error = $this->attempt('chain')) {
                // cnumber is not valid - either this chain attempt was deleted
                // or someone is making up cnumbers for the fun of it
                return $error;
            }
            if ($error = $this->canresume('chain')) {
                // chain attempt is valid but cannot be resumed - probably it has just
                // been completed, but it may also have timed out or been abandoned
                $this->TC->force_taskid(self::CONDITIONTASKID_ENDOFCHAIN);
                $this->TC->force_tnumber(0);
                $this->TC->task = null;
            }
            return false;
        }

        if ($cnumber==0) {
            if (($this->TC->tab=='info' || $this->TC->tab=='preview' || $this->TC->tab=='attempt') && $this->TC->can->preview()) {
                // teacher can always view the entry page
                return false;
            }

            // look for a previous chain attempt that is "in progress"
            if (! $this->previous_chainattempt()) {
                return false;
            }

            // no previous chain attempts could be resumed
            // so force the creation of a new chain attempt
            $this->TC->force_cnumber(self::FORCE_NEW_ATTEMPT);
        }

        // create a new chain attempt
        if ($error = $this->canstart('chain')) {
            return $error;
        }

        // at this point, $this->TC->cnumber and $this->TC->chainatempt
        // have been set up by create_chainattempt()

        if ($this->TC->can->preview()) {
            // let a teacher start at any task they like
        } else {
            // a student has to start at the begninning of the chain
            $this->TC->task = null;
        }

        return false;
    }

    /**
     * valid_tnumber
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function valid_tnumber() {
        $tnumber = $this->TC->get_tnumber();
        if ($tnumber >= 0) {
            if (! $this->canresume('task')) {
                // no error (i.e. we can resume this chain/task attempt)
                return false;
            }
            // we cannot resume this chain/task attempt, so try and start a new one
            $this->TC->force_tnumber(self::FORCE_NEW_ATTEMPT);
        }
        return $this->canstart('task');
    }

    /**
     * canstart
     * check user can start a new chain/task attempt
     *
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    function canstart($type) {
        if ($this->TC->can->preview()) {
            // teacher can always start a new attempt
            return false;
        }
        if (! $error = $this->delay($type, 'delay1')) {
            if (! $error = $this->delay($type, 'delay2')) {
                if (! $error = $this->moreattempts($type)) {
                    if (! $error = $this->newattempt($type)) {
                        // new chain/task attempt was successfully created
                        // $this->TC->cnumber/tnumber has now been set
                        $error = false;
                    }
                }
            }
        }
        return $error;
    }

    /**
     * delay
     *
     * @param xxx $type
     * @param xxx $delay
     * @return xxx
     * @todo Finish documenting this function
     */
    function delay($type, $delay) {
        $error = false;

        $record = $this->TC->$type;
        if ($record->$delay && $this->TC->get_lastattempt($type)) {
            // attempts and lastattempt have been retrieved from the database

            $attempts = "{$type}attempts";
            switch ($delay) {
                case 'delay1': $require_delay = (count($this->TC->$attempts)==1); break;
                case 'delay2': $require_delay = (count($this->TC->$attempts)>=2); break;
                default:       $require_delay = false; // shouldn't happen !!
            }

            if ($require_delay) {
                $lastattempttime = "get_last{$type}attempttime";
                $lastattempttime = $this->TC->$lastattempttime();
                $nextattempttime = $lastattempttime + ($record->$delay);
                if ($this->TC->time < $nextattempttime) {
                    // $delay has not expired yet
                    $time = html_writer::tag('strong', userdate($nextattempttime));
                    $error = get_string('temporaryblocked', 'quiz').' '.$time;
                }
            }
        }

        return $error;
    }

    /**
     * moreattempts
     *
     * @param string $type ("chain" or "task")
     * @param boolean $shorterror (optional, default=false)
     * @return mixed, false if moreattempts are available, otherwise error message
     * @todo Finish documenting this function
     */
    function moreattempts($type, $shorterror=false) {
        $error = false;
        $record = $this->TC->$type;
        if ($this->TC->get_attempts($type)) {
            $attempts = "{$type}attempts";
            if ($record->attemptlimit && $record->attemptlimit <= count($this->TC->$attempts)) {
                // maximum number of chain/task attempts reached
                $error = get_string('nomore'.$type.'attempts', 'mod_taskchain');
                if ($shorterror==false) {
                    if ($type=='chain') {
                        $name = $this->TC->taskchain->get_name();
                    } else {
                        $name = $record->get_name(); // task
                    }
                    $attemptlimitstr = mod_taskchain::textlib('strtolower', get_string('attemptlimit', 'mod_taskchain'));
                    $msg = html_writer::tag('b', format_string($name))." ($attemptlimitstr = $record->attemptlimit)";
                    $error = html_writer::tag('p', $error).html_writer::tag('p', $msg);
                }
            }
        }
        return $error;
    }

    /**
     * newattempt
     *
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    function newattempt($type) {
        // create_chain_attempt will get/create taskchain_chain_grades record
        // create_task_attempt will get/create taskchain_task_scores record
        if ($this->TC->create_attempt($type)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * lastattempt
     *
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    function lastattempt($type) {
        if ($this->TC->get_lastattempt($type)) {
            return false;
        }
        // no last attempt
        return get_string("nolast{$type}attempt", 'mod_taskchain');
    }

    /**
     * canresume
     * check user can resume this chain/task attempt
     *
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    function canresume($type) {
        // check whether user can resume this chain/task attempt
        if (! $error = $this->attempt($type)) {
            if (! $error = $this->inprogress($type)) {
                if (! $error = $this->moretime($type)) {
                    $error = false;
                }
            }
        }
        return $error;
    }

    /**
     * preconditions
     * this function may be useful for checking that a "lasttaskattempt" still satisfies preconditions
     * if attempts at earlier tasks have been deleted, then the user may no longer be allowed to take this task
     *
     * @param xxx $attempt (optional, default='')
     * @return xxx
     * @todo Finish documenting this function
     */
    function preconditions($attempt='') {
        $ok = false;
        if ($attempt) {
            $taskid = $this->$attempt->id;
        } else {
            $taskid = $this->taskattempt->id;
        }
        $previoustaskid = 0;
        $this->TC->get_tasks();
        foreach ($this->TC->tasks as $task) {
            // check only the preconditions of the task we are interested in (skip other tasks)
            if ($task->id==$taskid) {
                $ok = $this->TC->check_conditions(self::CONDITIONTYPE_PRE, $taskid, $previoustaskid);
                break;
            }
            // update $previoustaskid, because some preconditions may require it
            $previoustaskid = $task->id;
        }

        if ($ok) {
            return false; // pre-conditions were satisfied
        } else {
            return get_string('preconditionsnotsatisfied', 'mod_taskchain');
        }
    }

    /**
     * attempt
     *
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    function attempt($type) {
        $get_attempt = "get_{$type}attempt";
        $create_attempt = "create_{$type}attempt";
        if (! $this->TC->$get_attempt() && ! $this->TC->$create_attempt()) {
            if ($type=='chain') {
                $a = "chainid=$this->TC->chainid AND cnumber=$this->TC->cnumber userid=$this->userid";
            } else {
                $a = "taskid=$this->taskid AND cnumber=$this->TC->cnumber AND tnumber=$this->tnumber AND userid=$this->userid";
            }
            return get_string($type.'attemptnotfound', 'mod_taskchain', $a);
        }
        return false;
    }

    /**
     * inprogress
     *
     * @param xxx $type
     * @param xxx $attempt (optional, default='')
     * @return xxx
     * @todo Finish documenting this function
     */
    function inprogress($type, $attempt='') {
        if (is_string($attempt)) {
            if ($attempt=='') {
                $attempt = "{$type}attempt";
            }
            $attempt = &$this->TC->$attempt;
        }
        if ($attempt->status > self::STATUS_INPROGRESS) { // allow status==0
            return get_string($type.'attemptnotinprogress', 'mod_taskchain')." ($attempt->status)";
        }
        return false;
    }

    /**
     * moretime
     *
     * @param xxx $type
     * @param xxx $attempt (optional, default='')
     * @return xxx
     * @todo Finish documenting this function
     */
    function moretime($type, $attempt='') {
        $record = $this->TC->$type;
        if (is_string($attempt)) {
            if ($attempt=='') {
                $attempt = "{$type}attempt";
            }
            $attempt = &$this->TC->$attempt;
        }
        if ($record->timelimit && $record->timelimit < $attempt->duration) {
            return get_string('timelimitexpired', 'mod_taskchain');
        }
        return false;
    }

    // check user can submit this chain/task attempt

    /**
     * cansubmit
     *
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    function cansubmit($type) {
        // check whether user can submit results for this chain/task attempt
        if (! $error = $this->attempt($type)) {
            if (! $error = $this->inprogress($type)) {
                $error = false;
            }
        }
        return $error;
    }

    /**
     * chain_cansubmit
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function chain_cansubmit() {
        return $this->cansubmit('chain');
    }

    /**
     * task_cansubmit
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function task_cansubmit() {
        return $this->cansubmit('task');
    }

    // check access to a chain

    /**
     * chain_access
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function chain_access() {
        if ($this->TC->tab=='info' && $this->TC->can->preview()) {
            // teacher can always view the entry page
            return false;
        }
        if (! $error = $this->chain_visibility()) {
            if (! $error = $this->chain_grouping()) {
                if (! $error = $this->access('chain')) {
                    if (! $error = $this->chain_inpopup()) {
                        $error = false;
                    }
                }
            }
        }
        return $error;
    }

    /**
     * chain_visibility
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function chain_visibility() {
        if ($this->TC->coursemodule->visible) {
            // activity is visible to everyone
            return false;
        }
        if (has_capability('moodle/course:viewhiddenactivities', $this->TC->coursemodule->context)) {
            // user can view hidden activities
            return false;
        }
        // activity is currently hidden
        return get_string('activityiscurrentlyhidden');
    }

    /**
     * chain_grouping
     *
     * @uses $CFG
     * @return xxx
     * @todo Finish documenting this function
     */
    function chain_grouping() {
        global $CFG;
        if (empty($CFG->enablegroupings)) {
            // this site doesn't use groupings
            return false;
        }
        if (empty($this->TC->coursemodule->groupmembersonly) || empty($this->TC->coursemodule->groupingid)) {
            // this TaskChain activity doesn't use groupings
            return false;
        }
        if (has_capability('mod/taskchain:manage', $this->TC->coursemodule->context)) {
            // user is a teacher/coursecreator (or admin)
            return false;
        }
        if (has_capability('moodle/site:accessallgroups', $this->TC->coursemodule->context)) {
            // user has access to activities for all groupings
            return false;
        }
        if (groups_has_membership($this->TC->coursemodule)) {
            // user has membership of one of the groups in the required grouping for this activity
            return false;
        }
        // user has no special capabilities and is not a member of the required grouping
        return get_string('groupmembersonlyerror', 'group');
    }

    /**
     * chain_inpopup
     *
     * @uses $CFG
     * @return xxx
     * @todo Finish documenting this function
     */
    function chain_inpopup() {
        global $CFG;

        $error = '';
        if ($this->TC->chain->showpopup) {

            if (! $this->TC->inpopup) {

                $target = "taskchain{$this->TC->chain->parentid}";
                $params = $this->TC->merge_params(array('inpopup'=>1), null, 'coursemoduleid');
                $popupurl = new moodle_url('/mod/taskchain/view.php', $params);
                $openpopupurl = substr($popupurl->out(true), strlen($CFG->wwwroot));

                $popupoptions = implode(',', preg_grep('/^moodle/i', explode(',', $this->TC->chain->get_popupoptions()), PREG_GREP_INVERT));
                $openpopup = "openpopup('$openpopupurl','$target','{$popupoptions}')";
                $error .= "\n".'<script type="text/javascript">'."\n"."//<![CDATA[\n"."$openpopup;\n"."//]]>\n"."</script>\n";

                $onclick = "this.target='$target'; return $openpopup;";
                $link = "\n".'<a href="'.$popupurl.'" onclick="'.$onclick.'">'.format_string($this->TC->taskchain->name).'</a>'."\n";
                $error .= get_string('popupresource', 'resource').'<br />'.get_string('popupresourcelink', 'resource', $link);
            }
        }
        if ($error) {
            return $error;
        } else {
            return false;
        }
    }

    /**
     * chain_availability
     * check availability of chain
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function chain_availability() {
        if (($this->TC->tab=='info' || $this->TC->tab=='preview' || $this->TC->tab=='attempt') && $this->TC->can->preview()) {
            // teacher can always view the entry page
            return false;
        }
        if (! $error = $this->chain_tasks()) {
            if (! $error = $this->availability('chain')) {
                if (! $error = $this->entrycm()) {
                    $error = false;
                }
            }
        }
        return $error;
    }

    /**
     * chain_tasks
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function chain_tasks() {
        if (! $this->TC->get_tasks()) {
            // there are no tasks in this chain
            return get_string('notasksinchain', 'mod_taskchain');
        }
        return false;
    }

    /**
     * entrycm
     *
     * @uses $CFG
     * @uses $DB
     * @uses $USER
     * @return xxx
     * @todo Finish documenting this function
     */
    public function entrycm() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/lib/gradelib.php');

        if (! $cm = $this->TC->get_cm('entry')) {
            return false;
        }

        // set up url to view this activity
        $params = array('id' => $cm->id);
        if ($cm->modname=='taskchain') {
            $params['tab'] = $this->TC->tab;
            if ($this->TC->inpopup) {
                $params['inpopup'] = $this->TC->inpopup;
            }
        }
        $href = new moodle_url('/mod/'.$cm->modname.'/view.php', $params);

        if ($this->TC->chain->entrygrade) {
            if ($grades = grade_get_grades($cm->course, 'mod', $cm->modname, $cm->instance, $USER->id)) {
                $grade = 0;
                if (isset($grades->items[0]) && $grades->items[0]->grademax > 0) {
                    // this activity has a grade item
                    if (isset($grades->items[0]->grades[$USER->id])) {
                        $grade = $grades->items[0]->grades[$USER->id]->grade;
                    } else {
                        $grade = 0;
                    }
                    if ($grade < $this->TC->chain->entrygrade) {
                        // either this user has not attempted the entry activity
                        // or their grade so far on the entry activity is too low
                        $a = (object)array(
                            'usergrade' => intval($grade),
                            'entrygrade' => $this->TC->chain->entrygrade,
                            'entryactivity' => html_writer::tag('a', format_string(urldecode($cm->name)), array('href' => $href))
                        );
                        return get_string('entrygradewarning', 'mod_taskchain', $a);
                    }
                }
            }
        } else {
            // no grade, so test for "completion"
            switch ($cm->modname) {
                case 'resource':
                    $table = 'log';
                    $select = 'cmid=? AND userid=? AND action=?';
                    $params = array($cm->id, $USER->id, 'view');
                    break;
                case 'lesson':
                    $table = 'lesson_grades';
                    $select = 'userid=? AND lessonid==? AND completed>?';
                    $params = array($USER->id, $cm->instance, 0);
                    break;
                default:
                    $table = '';
                    $select = '';
                    $params = array();
            }
            if ($table && $select && ! $DB->record_exists_select($table, $select, $params)) {
                // user has not viewed or completed this activity yet
                $a = html_writer::tag('a', format_string(urldecode($cm->name)), array('href' => $href->out()));
                return get_string('entrycompletionwarning', 'mod_taskchain', $a);
            }
        }

        return false;
    }

    /**
     * exitgrade
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function exitgrade() {
        if ($this->TC->chain->get_exitcm()==0 || $this->TC->chain->get_exitgrade()==0 || empty($this->TC->chainattempt)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * task_access
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function task_access() {
        return $this->access('task');
    }

    /**
     * task_availability
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function task_availability() {
        return $this->availability('task');
    }

    /**
     * next_task
     *
     * @todo Finish documenting this function
     */
    function next_task() {
        // if chain attempt number is >=0, we can
        // try to restart from the last task attempt
        $cnumber = $this->TC->get_cnumber();
        $trylasttaskattempt = ($cnumber >= 0);

        // check the chain attempt number, if any
        if ($error = $this->valid_cnumber()) {
            $this->TC->print_error($error);
        }

        // this should be somewhere else, shouldn't it?
        //switch ($this->TC->action) {
        //    case 'regrade':
        //        $this->TC->regrade_selected_attempts();
        //        break;
        //    case 'deleteall':
        //    case 'deleteselected':
        //        $this->TC->delete_selected_attempts();
        //}

        if ($this->TC->get_cnumber()) {

            if ($this->TC->get_taskid()==0) {
                // no task specified, so try and decide what to do by looking at the last task attempt, if any

                // try to get the last task attempt (within this chain attempt)
                if ($trylasttaskattempt && ! $error = $this->lastattempt('task')) {

                    // check whether the last task attempt is in progress
                    if (! $error = $this->inprogress('task', 'lasttaskattempt')) {

                        // previous task attempt is in progress, so resume from there
                        $this->TC->force_taskid($this->TC->lasttaskattempt->taskid);
                        $this->TC->force_tnumber($this->TC->lasttaskattempt->tnumber);
                        $this->TC->taskattempt = &$this->TC->lasttaskattempt;

                    } else {

                        // previous task attempt is completed (or timedout or abandoned)
                        // get id of next task that is available for this user (using post conditions)
                        // Note : $taskid may be 0, if the postconditions did not specify what to do next
                        $taskid = $this->TC->get_available_task($this->TC->lasttaskattempt->taskid);
                        $this->TC->force_taskid($taskid);
                        $this->TC->force_tnumber(self::FORCE_NEW_ATTEMPT);
                    }
                }

                if ($this->TC->get_taskid()==0) {
                    // either there is no last task attempt, i.e. this is the start of a new chain attempt
                    // or the post-conditions for the task of the last attempt do not specify a next task
                    // get ids of tasks that are available to this user (using pre-conditions)
                    // ids of available tasks are put into $this->TC->availabletaskids
                    switch ($this->TC->get_available_tasks()) {
                        case 0:
                            // no tasks are available at this time :-(
                            break;

                        case 1:
                            // just one task is available, so use that
                            $taskid = reset($this->TC->availabletaskids);
                            $this->TC->force_taskid($taskid);
                            $this->TC->force_tnumber(self::FORCE_NEW_ATTEMPT);
                            break;

                        default:
                            // several tasks are available, so let the user choose what to do
                            $this->TC->force_taskid(self::CONDITIONTASKID_MENUNEXT);
                    }
                }
            }

            if ($this->TC->get_taskid() > 0) {
                // make sure we have read in the task record
                if (! $this->TC->get_task()) {
                    $this->TC->print_error('Invalid task id');
                }

                // check task network address, password
                if ($error = $this->task_access()) {
                    $this->TC->print_error($error);
                }

                // check task is set up and is currently available (=open and not closed)
                if ($error = $this->task_availability()) {
                    $this->TC->print_error($error);
                }

                // check tnumber is valid
                if ($error = $this->valid_tnumber()) {
                    $this->TC->print_error($error);
                }
            }
        }
    }
}
