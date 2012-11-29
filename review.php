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
 * mod/taskchain/review.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Include required files */
require_once(dirname(dirname(__DIR__)).'/config.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

$id       = optional_param('id', 0, PARAM_INT); // taskchain_attempts id
$attempt  = $DB->get_record('taskchain_attempts', array('id' => $id), '*', MUST_EXIST);
$taskchain   = $DB->get_record('taskchain', array('id' => $attempt->taskchainid), '*', MUST_EXIST);
$course   = $DB->get_record('course', array('id' => $taskchain->course), '*', MUST_EXIST);
$cm       = get_coursemodule_from_instance('taskchain', $taskchain->id, $course->id, false, MUST_EXIST);

// Check login
require_login($course, true, $cm);
if (! has_capability('mod/taskchain:reviewallattempts', $PAGE->context)) {
    require_capability('mod/taskchain:reviewmyattempts', $PAGE->context);
}

// Create an object to represent this attempt at the current TaskChain activity
$taskchain = mod_taskchain::create($taskchain, $cm, $course, $PAGE->context, $attempt);

// Log this request
add_to_log($course->id, 'taskchain', 'review', 'view.php?id='.$cm->id, $taskchain->id, $cm->id);

// Set editing mode
if ($PAGE->user_allowed_editing()) {
    mod_taskchain::set_user_editing();
}

// initialize $PAGE (and compute blocks)
$PAGE->set_url($taskchain->reurl->view());
$PAGE->set_title($taskchain->name);
$PAGE->set_heading($course->fullname);

// get renderer subtype (e.g. attempt_hp_6_jcloze_xml)
// and load the appropriate storage class for this attempt
$subtype = $taskchain->get_attempt_subtype();
$subdir = str_replace('_', '/', $subtype);
require_once($CFG->dirroot.'/mod/taskchain/'.$subdir.'/review.php');

// create the renderer for this attempt
$output = $PAGE->get_renderer('mod_taskchain');

////////////////////////////////////////////////////////////////////////////////
// Output starts here                                                         //
////////////////////////////////////////////////////////////////////////////////

echo $output->header();

echo $output->heading($taskchain);

echo $output->box_start('generalbox boxaligncenter boxwidthwide');

// show the attempt review page
// use call_user_func() to prevent syntax error in PHP 5.2.x
$class = 'mod_taskchain_'.$subtype.'_review';
echo call_user_func(array($class, 'review'), $taskchain, $class);

echo $output->box_end();

echo $output->continue_button($taskchain->url->report());

echo $output->footer();
