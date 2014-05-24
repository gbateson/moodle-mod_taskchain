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
 * mod/taskchain/attempt.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Include required files */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

$TC = new mod_taskchain();

// write to log
mod_taskchain::add_to_log($TC->course->id, 'taskchain', 'attempt', 'view.php?id='.$TC->coursemodule->id, $TC->taskchain->id, $TC->coursemodule->id);

// Set editing mode
mod_taskchain::set_user_editing();

// initialize $PAGE (and compute blocks)
$PAGE->set_url($TC->url->attempt());
$PAGE->set_title($TC->taskchain->name);
$PAGE->set_heading($TC->course->fullname);

// check chain visibility, network address, password and popup
if ($error = $TC->require_chain_access()) {
    $TC->print_error($error);
}

// check chain is set up and is currently available (=open and not closed)
if ($error = $TC->require_chain_availability()) {
    $TC->print_error($error);
}

// decide which task to show
if ($error = $TC->require_next_task()) {
    $TC->print_error($error);
}

// allow the TaskChain activity to set its preferred page layout
$TC->set_preferred_pagelayout($PAGE);

// load attempt renderer for current task
$subtype = $TC->get_attempt_subtype();
$TC->load_class($subtype, 'renderer.php');

// create the renderer for this attempt
$output = $PAGE->get_renderer('mod_taskchain', $subtype);

// print access warnings, if required
if ($warnings = $output->warnings('chain')) {
    echo $output->header();
    echo $warnings;
    echo $output->footer();
    exit;
}

////////////////////////////////////////////////////////////////////////////////
// Output starts here                                                         //
////////////////////////////////////////////////////////////////////////////////

if (empty($TC->task)) {
    echo $output->header();
    echo $output->taskmenu();
    echo $output->footer();
} else {
    echo $output->render_attempt();
}
