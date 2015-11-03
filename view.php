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
 * mod/taskchain/view.php
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

// create object to represent this TaskChain activity
$TC = new mod_taskchain();

// Log this request
mod_taskchain::add_to_log($TC->course->id, 'taskchain', 'view', 'view.php?id='.$TC->coursemodule->id, $TC->taskchain->id, $TC->coursemodule->id);

$completion = new completion_info($TC->course);
$completion->set_module_viewed($TC->coursemodule);

if (! $TC->show_entrypage()) {
    // go straight to attempt.php
    redirect($TC->url->attempt());
}

if ($TC->action=='deleteselected') {
    $TC->delete_selected_attempts();
    $completion->update_state($TC->coursemodule);
}

// Set editing mode
mod_taskchain::set_user_editing();

// initialize $PAGE (and compute blocks)
$PAGE->set_url($TC->url->view());
$PAGE->set_title($TC->taskchain->name);
$PAGE->set_heading($TC->course->fullname);
$TC->set_preferred_pagelayout($PAGE);

$output = $PAGE->get_renderer('mod_taskchain');

////////////////////////////////////////////////////////////////////////////////
// Output starts here                                                         //
////////////////////////////////////////////////////////////////////////////////

echo $output->header();

if ($TC->can->attempt() || $TC->can->preview()) {
    echo $output->entrypage();
} else {
    if (isguestuser()) {
        // off guests a choice of logging in or going back.
        $message = html_writer::tag('p', get_string('guestsno', 'mod_taskchain'));
        $message .= html_writer::tag('p', get_string('liketologin'));
        echo $output->confirm($message, get_login_url(), get_referer(false));
    } else {
        // user is not enrolled in this course in a good enough role,
        // show a link to course enrolment page.
        $message = html_writer::tag('p', get_string('youneedtoenrol', 'mod_taskchain'));
        $message .= html_writer::tag('p', $output->continue_button($TC->url->course()));
        echo $output->box($message, 'generalbox', 'notice');
    }
}

echo $output->footer();
