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
 * mod/taskchain/edit/tasks.php
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
require_once($CFG->dirroot.'/mod/taskchain/edit/form/tasks.php');

// create object to represent this TaskChain activity
$TC = new mod_taskchain();

// create object to represent this TaskChain activity
mod_taskchain::add_to_log($TC->course->id, 'taskchain', 'edittasks', 'edit/tasks.php?id='.$TC->coursemodule->id, $TC->taskchain->id, $TC->coursemodule->id);

// Set editing mode
mod_taskchain::set_user_editing();

// initialize $PAGE (and compute blocks)
$PAGE->set_url($TC->url->edit('tasks', array('id' => $TC->coursemodule->id)));
$PAGE->set_title($TC->taskchain->name);
$PAGE->set_heading($TC->taskchain->name);

$output = $PAGE->get_renderer('mod_taskchain');

$mform = new mod_taskchain_edit_tasks_form();
$newdata = $mform->get_data();

if (isset($newdata->action) && $newdata->action=='addtasks' && isset($newdata->addtasks)) {
    switch ($newdata->addtasks) {
        case 'start' : $aftertaskid = -1; break;
        case 'end'   : $aftertaskid = 0; break;
        case 'after' : $aftertaskid = (empty($newdata->addtasks_taskid) ? 0 : $newdata->addtasks_taskid); break;
        default      : $aftertaskid = 0;
    }
    $params = array('taskattemptid' => 0, 'taskscoreid' => 0, 'taskid' => 0, 'aftertaskid' => $aftertaskid);
    redirect($TC->url->edit('task', $params));
}

// display the page
echo $output->header();

// display the form
$mform->display();

echo $output->footer();
