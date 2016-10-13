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
 * mod/taskchain/locallib/regrade.php
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
 * taskchain_regrade
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_regrade extends taskchain_base {

    /**
     * selected_attempts
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function selected_attempts() {
        if (! $userfilter = $this->TC->get_userfilter('')) {
            return false; // no users selected
        }

        if (! $selected = optional_param('selected', 0, PARAM_INT)) {
            return false; // no attempts select
        }

        if (! $confirmed = optional_param('confirmed', 0, PARAM_INT)) {
            return false; // regrade is not confirmed
        }

        // clean the array of selected records (i.e. only alow chains and tasks that this user is allowed to regrade)
        list($taskchains, $chains, $tasks, $taskattempts) = $this->TC->clean_selected($selected, 'regrade');

        // regrade tasks, chains and taskchains
        $this->selected_tasks($selected, $taskchains, $chains, $tasks, $userfilter);
    }

    /**
     * selected_tasks
     *
     * @param xxx $selected (passed by reference)
     * @param xxx $taskchains (passed by reference)
     * @param xxx $chains (passed by reference)
     * @param xxx $tasks (passed by reference)
     * @param xxx $userfilter (passed by reference)
     * @todo Finish documenting this function
     */
    public function selected_tasks(&$selected, &$taskchains, &$chains, &$tasks, &$userfilter) {
        $get_cnumbers = true;
        foreach ($selected as $userid => $chainids) {
            foreach ($chainids as $chainid => $cnumbers) {
                if (is_array($cnumbers)) {
                    foreach ($cnumbers as $cnumber => $taskids) {
                        if (is_array($taskids)) {
                            foreach ($taskids as $taskid => $tnumbers) {
                                if ($cnumber) {
                                    $this->attempts('task', $tasks[$taskid], $cnumber, $userid);
                                } else {
                                    if ($get_cnumbers) {
                                        $this->TC->get_cnumbers($chains, $tasks, $userfilter);
                                        $get_cnumbers = false;
                                    }
                                    foreach(array_keys($tasks[$taskid]->userids[$userid]->cnumbers) as $cnumber) {
                                        $this->attempts('task', $tasks[$taskid], $cnumber, $userid);
                                    }
                                    $cnumber = 0;
                                }
                            }
                        }
                        if ($cnumber) {
                            $this->chainattempt($chains[$chainid], $cnumber, $userid, $tasks);
                        } else {
                            if ($get_cnumbers) {
                                $this->get_cnumbers($chains, $tasks, $userfilter);
                                $get_cnumbers = false;
                            }
                            foreach(array_keys($chains[$chainid]->userids[$userid]->cnumbers) as $cnumber) {
                                $this->chainattempt($chains[$chainid], $cnumber, $userid, $tasks);
                            }
                            $cnumber = 0;
                        } // end if $cnumber
                    } // end foreach $cnumber
                } // end if is_array($cnumbers)
                $this->attempts('chain', $chains[$chainid], 0, $userid);
                taskchain_update_grades($taskchains[$chains[$chainid]->parentid], $userid);
            } // end foreach ($chainids)
        } // end foreach ($selected)
    }

    /**
     * get_cnumbers
     *
     * @uses $CFG
     * @uses $DB
     * @param xxx $chains (passed by reference)
     * @param xxx $tasks (passed by reference)
     * @param xxx $userfilter (passed by reference)
     * @todo Finish documenting this function
     */
    public function get_cnumbers(&$chains, &$tasks, &$userfilter) {
        global $CFG, $DB;
        list($select, $params) = $DB->get_in_or_equal(array_keys($chains));
        $select = 'taskid IN (SELECT id FROM {taskchain_tasks} WHERE chainid '.$select;
        if ($userfilter) {
            $select .= " AND $userfilter";
        }
        $sort = 'userid,cnumber,taskid,tnumber';
        $fields = 'id,userid,cnumber,taskid,tnumber';
        if ($taskattempts = $DB->get_records_select('taskchain_task_attempts', $select, null, $sort, $fields)) {
            foreach ($taskattempts as $id=>$taskattempt) {
                if (empty($tasks[$taskattempt->taskid])) {
                    continue; // shouldn't happen !!
                }
                $taskid = $taskattempt->taskid;
                $userid = $taskattempt->userid;
                $cnumber = $taskattempt->cnumber;
                $chainid = $tasks[$taskid]->chainid;

                if (empty($tasks[$taskid]->userids[$userid])) {
                    $tasks[$taskid]->userids[$userid] = new stdClass;
                    $tasks[$taskid]->userids[$userid]->cnumbers = array();
                }
                $tasks[$taskid]->userids[$userid]->cnumbers[$cnumber] = true;

                if (empty($chains[$chainid]->userids[$userid])) {
                    $chains[$chainid]->userids[$userid] = new stdClass;
                    $chains[$chainid]->userids[$userid]->cnumbers = array();
                }
                $chains[$chainid]->userids[$userid]->cnumbers[$cnumber] = true;
            }
        }
    }

    /**
     * attempts
     *
     * @uses $DB
     * @param xxx $type "task" or "chain"
     * @param xxx $record (optional, default=null)
     * @param xxx $cnumber (optional, default=null)
     * @param xxx $userid (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function attempts($type, $record=null, $cnumber=null, $userid=null) {
        // combine task attempts into a single task score
        // combine chain attempts into a single chain grade
        global $DB;

        if ($type=='chain') {
            $grade = 'grade';
        } else {
            $grade = 'score';
        }
        $grademethod = $grade.'method';
        $gradeignore = $grade.'ignore';
        $gradelimit = $grade.'limit';

        if (is_null($record)) {
            $record = &$this->TC->$type;
        }
        if (is_null($userid)) {
            $userid = $this->TC->userid;
            $thisuser = true;
        } else {
            $thisuser = false;
        }

        // prepare sql
        if ($type=='chain') {
            $attemptselect = "chainid = $record->id";
            $gradeselect = "parenttype = $record->parenttype AND parentid = $record->parentid";
            $timefield = 'timemodified';
        } else {
            if (is_null($cnumber)) {
                $cnumber = $this->TC->get_cnumber();
            }
            $attemptselect = "taskid = $record->id AND cnumber = $cnumber";
            $gradeselect = "taskid = $record->id AND cnumber = $cnumber";
            $timefield = 'resumestart';
        }

        if ($userid) {
            $attemptselect .= " AND userid=$userid";
        }

        if ($gradeignore) {
            $attemptselect .= " AND NOT ($grade = 0 AND status = ".self::STATUS_ABANDONED.")";
        }

        if ($record->$grademethod==self::GRADEMETHOD_AVERAGE || $record->$gradelimit<100) {
            $precision = 1;
        } else {
            $precision = 0;
        }
        $multiplier = $record->$gradelimit / 100;

        // set the SQL string to determine the $usergrade
        switch ($record->$grademethod) {
            case self::GRADEMETHOD_HIGHEST:
                $usergrade = "ROUND(MAX($grade) * $multiplier, $precision)";
                break;
            case self::GRADEMETHOD_AVERAGE:
                // the 'AVG' function skips abandoned tasks, so use SUM(score)/COUNT(score)
                $usergrade = "ROUND(AVG($grade) * $multiplier, $precision)";
                break;
            case self::GRADEMETHOD_FIRST:
                $usergrade = 'MIN('.$DB->sql_concat($timefield, "'_'", "ROUND($grade * $multiplier, $precision)").')';
                break;
            case self::GRADEMETHOD_LAST:
                $usergrade = 'MAX('.$DB->sql_concat($timefield, "'_'", "ROUND($grade * $multiplier, $precision)").')';
                break;
            default:
                return false; // invalid score/grade mathod
        }

        $fields = "userid AS id, $usergrade AS $grade, COUNT($grade) AS countattempts, MAX(status) AS maxstatus, MIN(status) AS minstatus, SUM(duration) AS duration";
        $table = '{taskchain_'.$type.'_attempts}';

        if ($aggregates = $DB->get_records_sql("SELECT $fields FROM $table WHERE $attemptselect GROUP BY userid")) {

            if ($record->$grademethod==self::GRADEMETHOD_FIRST || $record->$grademethod==self::GRADEMETHOD_LAST) {
                // remove left hand characters in $usergrade (up to and including the underscore)
                foreach ($aggregates as $userid=>$aggregate) {
                    $pos = strpos($aggregate->$grade, '_') + 1;
                    $aggregates[$userid]->$grade = substr($aggregate->$grade, $pos);
                }
            }

            $gradetable = 'taskchain_'.$type.'_'.$grade.'s';
            foreach ($aggregates as $userid=>$aggregate) {

                // set status of task score or chain grade
                $status = 0;

                // if current user has just completed a task attempt
                // try to set task score status from post-conditions
                if ($thisuser && $type=='task' && $this->TC->get_lastattempt('task')) {
                    $nexttaskid = $this->TC->get_available_task($this->TC->lasttaskattempt->taskid);
                    if ($nexttaskid==self::CONDITIONTASKID_ENDOFCHAIN) {
                        // post condition for last task attempt specifies end of chain
                        $status = self::STATUS_COMPLETED;
                        $this->TC->chainattempt->status = $status;
                        if (! $DB->set_field('taskchain_chain_attempts', 'status', $status, array('id' => $this->TC->chainattempt->id))) {
                            print_error('error_updaterecord', 'taskchain', '', 'taskchain_chain_attempts');
                        }
                    } else if ($nexttaskid==$record->id) {
                        // post conditions specify this task is to be repeated
                        $status = self::STATUS_INPROGRESS;
                    }
                }

                if ($status==0) {
                    if ($aggregate->maxstatus==self::STATUS_COMPLETED || $aggregate->maxstatus==self::STATUS_PENDING) {
                        // at least one attempt is completed OR pending completion
                        $status = $aggregate->maxstatus;
                    } else if ($aggregate->minstatus==self::STATUS_INPROGRESS && $record->allowresume) {
                        // at least one attempt can be resumed
                        $status = self::STATUS_INPROGRESS;
                    } else if ($record->attemptlimit==0 || $aggregate->countattempts < $record->attemptlimit) {
                        // new attempt can be started
                        $status = self::STATUS_INPROGRESS;
                    } else if ($aggregate->minstatus==self::STATUS_TIMEDOUT && $aggregate->maxstatus==self::STATUS_TIMEDOUT) {
                        // all attempts are timed out and no new attempts can be started
                        $status = self::STATUS_TIMEDOUT;
                    } else {
                        // an assortment of inprogress, timedout and abandoned attempts
                        // no attempts can be resumed and no new attempt can be started
                        $status = self::STATUS_ABANDONED;
                    }
                }

                $typegrade = "$type$grade"; // taskscore or chaingrade

                // update/add grade record
                if ($graderecord = $DB->get_record_select($gradetable, $gradeselect." AND userid = $userid")) {
                    $graderecord->$grade = round($aggregate->$grade);
                    $graderecord->status = $status;
                    $graderecord->duration = $aggregate->duration;
                    if (! $DB->update_record($gradetable, $graderecord)) {
                        print_error('error_updaterecord', 'taskchain', '', $table);
                    }
                    if ($thisuser) {
                        $this->TC->$typegrade = &$graderecord;
                    }
                } else {
                    // grade record not found - should not happen !
                    $create_grade = 'create_'.$typegrade;
                    $this->TC->$create_grade($aggregate->$grade, $status, $aggregate->duration, $record->id, $cnumber, $userid);
                }
            }
        }
    }

    /**
     * chainattempt
     *
     * @uses $DB
     * @param xxx $chain (optional, default=null)
     * @param xxx $cnumber (optional, default=null)
     * @param xxx $userid (optional, default=null)
     * @param xxx $tasks (optional, default=null)
     * @todo Finish documenting this function
     */
    public function chainattempt($chain=null, $cnumber=null, $userid=null, $tasks=null) {
        // combine task scores into a single chain attempt score

        // Note: $tasks contains only the tasks that are to be regraded
        // i.e. it does NOT contain all the tasks in the chain, so it cannot be relied on to calculate equalweighting
        global $DB;

        // maintain a cache of grading/scoring info for each chain
        static $chains = array();

        if (is_null($chain)) {
            $chain = $this->TC->chain;
        }
        if (array_key_exists($chain->id, $chains)) {
            $get_chain_tasks = false;
        } else {
            $get_chain_tasks = true;
        }
        if (is_null($cnumber)) {
            $cnumber = $this->TC->get_cnumber();
        }
        if (is_null($userid)) {
            $userid = $this->TC->userid;
            $thisuser = true;
        } else {
            $thisuser = false;
        }
        if (is_null($tasks)) {
            $this->TC->get_tasks();
            $tasks = &$this->TC->tasks;
            $use_this_tasks = true;
        } else {
            $use_this_tasks = false;
        }
        if (empty($tasks)) {
        //    return; // no tasks to regrade
        }

        if ($get_chain_tasks) {
            $chains[$chain->id] = (object)array(
                'taskgroups' => array(),
                'equalweighting' => array(),
                'totalweighting' => 0,
                'counttasks' => 0
            );
            if ($use_this_tasks) {
                $chains[$chain->id]->tasks = &$this->TC->tasks;
            } else {
                $chains[$chain->id]->tasks = $DB->get_records('taskchain_tasks', array('chainid'=>$chain->id), 'sortorder', 'id,scoreweighting');
            }
            if ($chains[$chain->id]->tasks) {
                foreach ($chains[$chain->id]->tasks as $task) {
                    if ($task->scoreweighting<0) {
                        $taskgroup = $task->scoreweighting;
                    } else {
                        $taskgroup = 'default';
                        $chains[$chain->id]->totalweighting += $task->scoreweighting;
                    }
                    if (! isset($chains[$chain->id]->taskgroups[$taskgroup])) {
                        $chains[$chain->id]->taskgroups[$taskgroup] = array();
                    }
                    $chains[$chain->id]->taskgroups[$taskgroup][] = $task->id;
                }
                foreach ($chains[$chain->id]->taskgroups as $taskgroup => $ids) {
                    if ($taskgroup=='default') {
                        continue;
                    }
                    if ($chains[$chain->id]->totalweighting<100) {
                        $chains[$chain->id]->equalweighting[$taskgroup] = (100 - $chains[$chain->id]->totalweighting) / count($ids);
                    } else {
                        $chains[$chain->id]->equalweighting[$taskgroup] = 0;
                    }
                }
                if (count($chains[$chain->id]->equalweighting)) {
                    $chains[$chain->id]->totalweighting = max(100, $chains[$chain->id]->totalweighting);
                }
                // Note: totalweighting may not be exactly 100, in the following cases:
                //     (1) no "equalweighting" tasks exist and the sum of task weightings is not equal to 100
                //     (2) "equalweighting" tasks exist, but the sum of other task weightings is more than 100
                // in case (2), the equalweighting is set to zero, i.e. "equalweighting" tasks have no effect on grade
            }
            if (count($chains[$chain->id]->taskgroups)==1) {
                // this chain only has only one equalweighting group
                $taskgroup = reset($chains[$chain->id]->taskgroups);
                $chains[$chain->id]->counttasks = count($taskgroup);
            }
        }

        if (empty($chains[$chain->id]->tasks)) {
            return; // no tasks in this chain
        }

        $taskids = array_keys($chains[$chain->id]->tasks);

        $counttasks = count($taskids);
        $taskids = implode(',', $taskids);

        $grade = 0;
        $duration = 0;
        $minstatus = 0;
        $maxstatus = 0;
        $timemodified = 0;

        $select = "userid=? AND cnumber=? AND taskid in ($taskids)";
        $params = array($userid, $cnumber);
        $taskscores = $DB->get_records_select('taskchain_task_scores', $select, $params);

        $canresume = false;
        $canrestart = false;
        $restarttaskids = array();

        $counttaskscores = 0;
        if ($taskscores) {
            $counttaskscores = count($taskscores);

            // equalweighting task groups are considered to be mutually exclusive,
            // so if this user has attempted tasks from more than one task group,
            // we should select the group which has the most number of attempts
            // and ignore attempts for tasks in other groups
            $taskgroups = array();
            foreach ($taskscores as $taskscore) {
                if (! array_key_exists($taskscore->taskid, $tasks)) {
                    continue;
                }
                $taskgroup = $tasks[$taskscore->taskid]->scoreweighting;
                if ($taskgroup>=0) {
                    $taskgroup = 'default';
                }
                if (! array_key_exists($taskgroup, $taskgroups)) {
                    $taskgroups[$taskgroup] = array();
                }
                $taskgroups[$taskgroup][] = $taskscore->taskid;
            }
            $counttasks = 0;
            $maintaskgroup = 0;
            foreach ($taskgroups as $taskgroup => $ids) {
                if ($taskgroup=='default') {
                    continue;
                }
                $count = count($ids);
                if ($count > $counttasks) {
                    $counttasks = $count;
                    $maintaskgroup = $taskgroup;
                }
            }
            if (empty($taskgroups['default'])) {
                $validtaskids = array();
            } else {
                $validtaskids = $taskgroups['default'];
            }
            if ($maintaskgroup) {
                $validtaskids = array_merge($validtaskids, $taskgroups[$maintaskgroup]);
            }
            $taskids = implode(',', $validtaskids);

            if ($chain->attemptgrademethod==self::GRADEMETHOD_TOTAL) {
                $totalweighting = $chains[$chain->id]->totalweighting;
            } else {
                $totalweighting = 100;
            }

            foreach ($taskscores as $taskscore) {

                if (! in_array($taskscore->taskid, $validtaskids)) {
                    // we are not interested in this task
                    continue;
                }

                if ($totalweighting) {
                    $weighting = $tasks[$taskscore->taskid]->scoreweighting;
                    if ($weighting<0) {
                        $weighting = $chains[$chain->id]->equalweighting[$weighting];
                    }
                    $weightedscore = ($taskscore->score * ($weighting / $totalweighting));
                    switch ($chain->attemptgrademethod) {
                        case self::GRADEMETHOD_TOTAL:
                            $grade += $weightedscore;
                            break;
                        case self::GRADEMETHOD_HIGHEST:
                            if ($grade < $weightedscore) {
                                $grade = $weightedscore;
                            }
                            break;
                        case self::GRADEMETHOD_LAST:
                            if ($timemodified < $taskscore->timemodified) {
                                $grade = $weightedscore;
                            }
                            break;
                        case self::GRADEMETHOD_LASTCOMPLETED:
                            if ($timemodified < $taskscore->timemodified && ($taskscore->status==self::STATUS_COMPLETED)) {
                                $grade = $weightedscore;
                            }
                            break;
                        case self::GRADEMETHOD_LASTTIMEDOUT:
                            if ($timemodified < $taskscore->timemodified && ($taskscore->status==self::STATUS_COMPLETED || $taskscore->status==self::STATUS_TIMEDOUT)) {
                                $grade = $weightedscore;
                            }
                            break;
                        case self::GRADEMETHOD_LASTABANDONED:
                            if ($timemodified < $taskscore->timemodified && ($taskscore->status==self::STATUS_COMPLETED || $taskscore->status==self::STATUS_TIMEDOUT || $taskscore->status==self::STATUS_ABANDONED)) {
                                $grade = $weightedscore;
                            }
                            break;
                    } // end switch
                }

                if ($taskscore->status) {
                    if ($minstatus==0 || $minstatus>$taskscore->status) {
                        $minstatus = $taskscore->status;
                    }
                    if ($maxstatus==0 || $maxstatus<$taskscore->status) {
                        $maxstatus = $taskscore->status;
                    }

                    if ($taskscore->status==self::STATUS_COMPLETED) {
                        // do nothing - cannot resume or restart
                    } else if ($taskscore->status==self::STATUS_INPROGRESS) {
                        if ($tasks[$taskscore->taskid]->allowresume) {
                            $canresume = true;
                        }
                    } else {
                        if ($tasks[$taskscore->taskid]->attemptlimit) {
                            // check this task later
                            $restarttaskids[] = $taskscore->taskid;
                        } else {
                            $canrestart = true;
                        }
                    }
                }

                $duration += $taskscore->duration;
            } // end foreach $tasks

            // don't let grade go above gradelimit
            $grade = min($grade, $chain->gradelimit);

        } // end if $taskscores

        if ($use_this_tasks && $this->TC->chainattempt) {
            // user has just submitted some task results
            $chainattempt = &$this->TC->chainattempt;
        } else {
            // teacher is deleting attempts or regrading
            $params = array('chainid'=>$chain->id, 'cnumber'=>$cnumber, 'userid'=>$userid);
            $chainattempt = $DB->get_record('taskchain_chain_attempts', $params);
        }
        if ($chainattempt) {
            // chain attempt already exists (the usual case)
            $status = $chainattempt->status;
        } else {
            // chain attempt record not found - should not happen !
            $status = self::STATUS_INPROGRESS;
        }

        if ($status==self::STATUS_INPROGRESS && $thisuser && $this->TC->get_lastattempt('task')) {
            $nexttaskid = $this->TC->get_available_task($this->TC->lasttaskattempt->taskid);
        } else {
            $nexttaskid = 0;
        }

        if ($nexttaskid==self::CONDITIONTASKID_ENDOFCHAIN) {
            // post-conditions specify end of task
            $status = self::STATUS_COMPLETED;
        } else if ($nexttaskid) {
            // post-conditions specify different task (or menu)
            $status = self::STATUS_INPROGRESS;
        } else if ($status==self::STATUS_INPROGRESS || $status==self::STATUS_PENDING) {
            $counttasks = $chains[$chain->id]->counttasks;
            if ($chain->timelimit && $duration > $chain->timelimit) {
                // total time on tasks exceeds chain time limit
                if ($status==self::STATUS_PENDING) {
                    $status = self::STATUS_COMPLETED;
                } else {
                    $status = self::STATUS_TIMEDOUT;
                }
            } else if ($counttasks && $counttasks==$counttaskscores && $minstatus==self::STATUS_COMPLETED && $maxstatus==self::STATUS_COMPLETED) {
                // all tasks are completed
                if ($chain->manualcompletion) {
                    $status = self::STATUS_PENDING;
                } else {
                    $status = self::STATUS_COMPLETED;
                }
            } else if ($thisuser) {
                if ($chain->allowresume==self::ALLOWRESUME_NO && $this->TC->get_lastattempt('task') && $this->TC->lasttaskattempt->status==self::STATUS_ABANDONED) {
                    // chain may not be resumed and last task attempt was abandoned
                    $status = self::STATUS_ABANDONED;
                } else if ($counttasks && $counttasks==$counttaskscores) {
                    // all tasks have been attempted
                    if ($canresume==false && $canrestart==false && count($restarttaskids)) {
                        // check to see if any of tasks can be (re)started
                        $table = '{taskchain_task_attempts}';
                        $fields = "taskid AS id, COUNT(*) AS countattempts";
                        $select = "userid=$userid AND cnumber=$cnumber AND taskid IN (".implode(',', $restarttaskids).')';
                        $sql = "SELECT $fields FROM $table WHERE $select GROUP BY taskid";
                        if ($aggregates = $DB->get_records_sql($sql)) {
                            foreach ($aggregates as $taskid=>$aggregate) {
                                if ($aggregate->countattempts < $tasks[$taskid]->attemptlimit) {
                                    $canrestart = true;
                                    break;
                                }
                            }
                        }
                    }
                    if ($canresume || $canrestart) {
                        // do nothing - at least one task can be resumed or restarted
                    } else if ($minstatus==self::STATUS_TIMEDOUT && $maxstatus==self::STATUS_TIMEDOUT) {
                        // tasks are all timed out (and cannot be restarted)
                        $status = self::STATUS_TIMEDOUT;
                    } else {
                        // tasks are a mix of in progress, timed out, abandoned and completed
                        // and no task can be restarted or resumed
                        $status = self::STATUS_ABANDONED;
                    }
                }
            }
        }

        if ($chainattempt) {
            $chainattempt->grade = intval(round($grade));
            $chainattempt->status = intval($status);
            $chainattempt->duration = intval($duration);
            if (method_exists($chainattempt, 'to_stdclass')) {
                $record = $chainattempt->to_stdclass();
            } else {
                $record = $chainattempt;
            }
            if (! $DB->update_record('taskchain_chain_attempts', $record)) {
                print_error('error_updaterecord', 'taskchain', '', 'taskchain_chain_attempts');
            }
        } else if ($counttaskscores) {
            // chain attempt record not found - might happen first time the grade is calculated
            $this->TC->create_chainattempt($grade, $status, $duration, $chain->id, $cnumber, $userid);
        }
    }

    /**
     * chain
     *
     * @todo Finish documenting this function
     */
    public function chain() {
        $this->attempts('chain');
    }

    /**
     * task
     *
     * @todo Finish documenting this function
     */
    public function task() {
        $this->attempts('task');
        $this->chainattempt();
        $this->attempts('chain');
    }
}
