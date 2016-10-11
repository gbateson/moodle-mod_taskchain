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
 * mod/taskchain/edit/condition.php
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
require_once($CFG->dirroot.'/mod/taskchain/edit/form/condition.php');

// create object to represent this TaskChain activity
$TC = new mod_taskchain();;

if (isset($TC->condition->id)) {
    mod_taskchain::add_to_log($TC->course->id, 'taskchain', 'editcondition', 'edit/condition.php?id='.$TC->condition->id, $TC->taskchain->id, $TC->coursemodule->id);
} else {
    mod_taskchain::add_to_log($TC->course->id, 'taskchain', 'editcondition', 'edit/condition.php?cm='.$TC->coursemodule->id, $TC->taskchain->id, $TC->coursemodule->id);
}

// Set editing mode
mod_taskchain::set_user_editing();

// initialize $PAGE (and compute blocks)
$PAGE->set_url($TC->url->edit('condition', array('id' => $TC->get_conditionid())));
$PAGE->set_title($TC->taskchain->name);
$PAGE->set_heading($TC->course->fullname);

if ($TC->inpopup) {
    // $PAGE->set_pagelayout('popup');
    $PAGE->set_pagelayout('embedded');
}

$output = $PAGE->get_renderer('mod_taskchain');

$mform = new mod_taskchain_edit_condition_form();

if ($mform->is_cancelled()) {
    $TC->action = 'editcancelled';
} else if ($newdata = $mform->get_data()) {
    $TC->action = 'datasubmitted';
}

switch ($TC->action) {

    case 'deleteconfirmed' :

            $type = '';
            switch ($TC->get_conditiontype()) {
                case mod_taskchain::CONDITIONTYPE_PRE:  $type = 'precondition'; break;
                case mod_taskchain::CONDITIONTYPE_POST: $type = 'postcondition'; break;
            }

            if ($TC->get_conditionid()) {
                // delete a single condition ($TC->condition)
                $select = 'id='.$TC->get_conditionid();
            } else {
                // delete all pre/post conditions
                $select = 'taskid='.$TC->get_taskid().' AND conditiontype='.$TC->get_conditiontype();
                $type   = 'all'.$type.'s';
            }

            if ($DB->delete_records_select('taskchain_conditions', $select)) {
                // success
                $text = get_string($type, 'mod_taskchain');
                $text = mod_taskchain::textlib('strtolower', $text);
                $text = get_string('deletedactivity', '', $text);
                echo $output->page_quick($text, 'close');
            } else {
                print_error('error_deleterecords', 'taskchain', 'taskchain_conditions');
            }
        break;

    case 'delete' :
        $type = '';
        switch ($TC->get_conditiontype()) {
            case mod_taskchain::CONDITIONTYPE_PRE:  $type = 'precondition'; break;
            case mod_taskchain::CONDITIONTYPE_POST: $type = 'postcondition'; break;
        }
        $text = get_string('confirmdelete'.$type, 'mod_taskchain');
        $params = array('id'=>$TC->get_conditionid());
        echo $output->page_delete($text, 'edit/condition.php', $params);
        break;

    case 'deleteall' :
        $type = '';
        switch ($TC->get_conditiontype()) {
            case mod_taskchain::CONDITIONTYPE_PRE:  $type = 'preconditions'; break;
            case mod_taskchain::CONDITIONTYPE_POST: $type = 'postconditions'; break;
        }
        $text = get_string('confirmdeleteall'.$type, 'mod_taskchain');
        $params = array('taskid' => $TC->get_taskid(), 'conditiontype' => $TC->get_conditiontype());
        echo $output->page_delete($text, 'edit/condition.php', $params);
        break;

    case 'deletecancelled':
    case 'editcancelled':
        close_window();
        break;

    case 'datasubmitted':
        // $newdata object holds the submitted data

        if ($newdata->id = $TC->get_conditionid()) {
            // updating a TaskChain task condition
            if (! $DB->update_record('taskchain_conditions', $newdata)) {
                print_error('error_updaterecord', 'taskchain', '', 'taskchain_conditions');
            }
        } else {
            // adding a new TaskChain task condition
            if (! $newdata->id = $DB->insert_record('taskchain_conditions', $newdata)) {
                print_error('error_insertrecord', 'taskchain', '', 'taskchain_conditions');
             }
        }

        if ($TC->inpopup) {
            echo $output->page_quick(get_string('changessaved'), 'close');
        } else {
            $params = array('taskid' => 0, 'conditionid' => 0, 'conditiontype' => 0);
            redirect($TC->url->edit('tasks', $params));
        }
        break;

    case 'update':
    default:

        // initizialize data in form
        if ($TC->get_conditionid()) {
            // editing a condition
            $defaults = (array)$TC->condition->to_stdclass();
        } else {
            // adding a new condition to this task
            $defaults = array('taskid'=>$TC->get_taskid());
        }
        $mform->data_preprocessing($defaults);
        $mform->set_data($defaults);
        unset($defaults);

        echo $output->header();

        // display the form
        $mform->display();

        echo $output->footer();
}
