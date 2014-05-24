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
 * mod/taskchain/edit/chains.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

// set $TC object

/** Include required files */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib.php');
require_once($CFG->dirroot.'/mod/taskchain/edit/form/chains.php');

// create object to represent this TaskChain activity
$TC = new mod_taskchain();

// get rebuild_course_cache() from "course/lib.php"
// (needed if "showpopup" or "popupoptions" change)
require_once($CFG->dirroot.'/course/lib.php');

mod_taskchain::add_to_log($TC->course->id, 'taskchain', 'editchains', 'edit/chains.php?id='.$TC->course->id);

// Set editing mode
mod_taskchain::set_user_editing();

// initialize $PAGE (and compute blocks)
$PAGE->set_url($TC->url->edit('chains', array('id' => $TC->course->id)));
$PAGE->set_title($TC->course->fullname);
$PAGE->set_heading($TC->course->fullname);

$output = $PAGE->get_renderer('mod_taskchain');

$mform = new mod_taskchain_edit_chains_form();
$newdata = $mform->get_data();

// get all chains (and taskchains) in this course
$TC->get_chains();

if ($setasdefault = optional_param('setasdefault', 0, PARAM_INT)) {
    if ($TC->get_chains() && array_key_exists($setasdefault, $TC->chains)) {
        taskchain_set_preferences('chain', $TC->chains[$setasdefault]);
    } else {
        $setasdefault = 0; // invalid $setasdefault - shouldn't happen !!
    }
}

// display the page
echo $output->header();

// display the form
$mform->display();

echo $output->footer();
