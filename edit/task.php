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
 * mod/taskchain/edit/task.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Include required files */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib.php');
require_once($CFG->dirroot.'/mod/taskchain/edit/form/task.php');

// create object to represent this TaskChain activity
$TC = new mod_taskchain();

if ($TC->task) {
    mod_taskchain::add_to_log($TC->course->id, 'taskchain', 'edittask', 'edit/task.php?id='.$TC->task->id, $TC->taskchain->id, $TC->coursemodule->id);
} else {
    mod_taskchain::add_to_log($TC->course->id, 'taskchain', 'edittask', 'edit/task.php?tc='.$TC->taskchain->id, $TC->taskchain->id, $TC->coursemodule->id);
}

// Set editing mode
mod_taskchain::set_user_editing();

if (isset($TC->task->id)) {
    $PAGE->set_url($TC->url->edit('task', array('id' => $TC->task->id)));
} else {
    $PAGE->set_url($TC->url->edit('task', array('tc' => $TC->taskchain->id)));
}
$PAGE->set_title($TC->taskchain->name);
$PAGE->set_heading($TC->course->fullname);

$output = $PAGE->get_renderer('mod_taskchain');

$mform = new mod_taskchain_edit_task_form();

if ($mform->is_cancelled()) {
    $TC->action = 'editcancelled';
} else if ($newdata = $mform->get_data()) {
    $TC->action = 'datasubmitted';
}

switch ($TC->action) {
    case 'deleteconfirmed' :

        // delete task
        taskchain_delete_tasks($TC->task->id);

        // unset task objects
        $TC->task = null;
        $TC->taskscore = null;
        $TC->taskattempt = null;
        $TC->force_tnumber(0);

        // set text confirming task deletion
        $text = get_string('task', 'mod_taskchain');
        $text = mod_taskchain::textlib('strtolower', $text);
        $text = get_string('deletedactivity', '', $text);

        // get url of next page
        if ($TC->get_cnumber()) {
            // resume the chain attempt
            $url = $TC->url->attempt();
        } else {
            // resume editing tasks in chain
            $url = $TC->url->edit('tasks', array('taskid' => 0));
        }

        // display simple page with $text and $url
        echo $output->page_quick($text, 'continue', $url);
        break;

    case 'delete' :

        // check the user really wants to delete this task
        $text = get_string('confirmdeletetask', 'mod_taskchain');
        $params = $TC->merge_params(null, null, 'taskid');
        echo $output->page_delete($text, 'edit/task.php', $params);
        break;

    case 'deletecancelled':
    case 'editcancelled':

        if ($TC->get_cnumber() || $TC->get_tnumber()) {
            // resume the chain/task attempt
            redirect($TC->url->attempt());
        } else {
            // resume editing tasks in chain
            redirect($TC->url->edit('tasks', array('taskid' => 0)));
        }
        break;

    case 'datasubmitted':

        // set review options (see lib.forms.php)
        //taskchain_set_reviewoptions($newdata);

        require_once($CFG->dirroot.'/mod/taskchain/source/class.php');

        $sources = taskchain_source::get_sources_from_taskfile($newdata, $TC->coursemodule->context, 'mod_taskchain', 'sourcefile');
        if ($source = reset($sources)) {
            $newdata->sourcefile     = $source->get_file();
            $newdata->sourcelocation = $source->get_location($TC->course->id);
            $newdata->sourcetype     = $source->get_type();
        }
        unset($sources);
        unset($source);

        if ($config = taskchain_source::get_config($newdata, $TC->coursemodule->context, 'mod_taskchain', 'configfile')) {
            $newdata->configfile = $config->filepath;
            $newdata->configlocation = $config->location;
        }
        unset($config);

        // make sure outputformat is valid for this source file
        $list = taskchain_available::outputformats_list($newdata->sourcetype);
        if (isset($newdata->outputformat) && array_key_exists($newdata->outputformat, $list)) {
            // do nothing - outputformat is valid
        } else {
            // outputformat is (no longer) valid
            // reset it to "Best" value e.g. "0"
            $newdata->outputformat = key($list);
        }
        unset($list);

        $newdata->id = $TC->get_taskid();

        if (empty($newdata->id)) {
            // add new TaskChain task(s)
            $aftertaskid = optional_param('aftertaskid', 0, PARAM_INT);
            taskchain_add_tasks($newdata, $mform, $TC->chain, $aftertaskid);
        } else {
            if ($TC->task->scoremethod==$newdata->scoremethod && $TC->task->scorelimit==$newdata->scorelimit && $TC->task->scoreweighting==$newdata->scoreweighting) {
                $regrade = false;
            } else {
                $regrade = true;
                $TC->task->scoremethod = $newdata->scoremethod;
                $TC->task->scorelimit = $newdata->scorelimit;
                $TC->task->scoreweighting = $newdata->scoreweighting;
            }

            // set cache fields
            $TC->task->title = $newdata->title;
            if (isset($newdata->stopbutton)) {
                $TC->task->stopbutton = $newdata->stopbutton;
            }
            if (isset($newdata->stoptext)) {
                $TC->task->stoptext = $newdata->stoptext;
            }

            // update the TaskChain task record
            if (! $DB->update_record('taskchain_tasks', $newdata)) {
                print_error('error_updaterecord', 'taskchain', '', 'tasks');
            }

            // recreate $TC->task using updated values
            $TC->task = $DB->get_record('taskchain_tasks', array('id' => $newdata->id));
            $TC->task = new taskchain_task($TC->task, array('TC' => &$TC));

            // regrade task, if necessary
            if ($regrade) {
                if ($taskscores = $DB->get_records('taskchain_task_scores', array('taskid'=>$TC->task->id), '', 'id,cnumber,userid')) {
                    $userids = array();
                    foreach ($taskscores as $id=>$taskscore) {
                        // set task score from task attempts
                        $TC->regrade_attempts('task', $TC->task, $taskscore->cnumber, $taskscore->userid);
                        if (! array_key_exists($taskscore->userid, $userids)) {
                            $userids[$taskscore->userid] = array();
                        }
                        $userids[$taskscore->userid][] = $taskscore->cnumber;
                    }
                    unset($taskscores);

                    // array of tasks whose attempts are to be updated
                    $tasks = array($TC->task->id => &$TC->task);

                    // transfer grade settings to taskchain record
                    // required by taskchain_update_grades (in taskchain/lib.php)
                    $TC->taskchain->gradelimit = $TC->chain->gradelimit;
                    $TC->taskchain->gradeweighting = $TC->chain->gradeweighting;

                    // update chain grades and attempts
                    foreach ($userids as $userid=>$cnumbers) {
                        foreach ($cnumbers as $cnumber) {
                            // set chain attempt from task score
                            $TC->regrade_chainattempt($TC->chain, $cnumber, $userid, $tasks);
                        }

                        // set chain grade from chain attempts
                        $TC->regrade_attempts('chain', $TC->chain, 0, $userid);

                        // update grades in Moodle gradebook
                        taskchain_update_grades($TC->taskchain, $userid);
                    }
                    unset($userids, $tasks);
                }
            }
        }

        // show the chain or task
        if ($TC->get_cnumber() || $TC->get_tnumber()) {
            // resume the chain/task attempt
            redirect($TC->url->attempt());
        } else {
            // resume editing tasks in chain
            redirect($TC->url->edit('tasks', array('taskid' => 0)));
        }
        break;

    case 'update':
    default:

        // initizialize data in form
        if ($TC->task) {
            // editing a task ($TC->task was set up in mod/taskchain/locallib.php)
            $defaults = (array)$TC->task->to_stdclass();
        } else {
            // adding a new task to this chain
            $defaults = array('chainid'=>$TC->get_chainid());
        }
        $mform->data_preprocessing($defaults);
        $mform->set_data($defaults);
        unset($defaults);

        echo $output->header();

        // display the form
        $mform->display();

        echo $output->footer();
}
