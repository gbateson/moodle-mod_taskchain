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
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

// create object to represent this TaskChain activity
$TC = new mod_taskchain();

// Log this request
mod_taskchain::add_to_log($TC->course->id, 'taskchain', 'report', 'review.php?id='.$TC->taskattempt->id, $TC->taskchain->id, $TC->coursemodule->id);

$PAGE->set_url($TC->url->review());
$PAGE->set_title($TC->taskchain->name);
$PAGE->set_heading($TC->course->shortname);
$PAGE->navbar->add(get_string('report'));

$text = '';
if ($TC->get_chaingrade()) {

    $text = get_string('pluginname', 'taskchainreport_chaingrade');
    if ($TC->get_chainattempt()) {

        $url = $TC->url->report('chaingrade', array('chaingradeid' => $TC->chaingrade->id));
        $PAGE->navbar->add($text, $url);

        $text = get_string('pluginname', 'taskchainreport_chainattempt');
        if ($TC->get_taskscore()) {

            $url = $TC->url->report('chainattempt', array('chainattemptid' => $TC->chainattempt->id));
            $PAGE->navbar->add($text, $url);

            $text = get_string('pluginname', 'taskchainreport_taskscore');
            if ($TC->get_taskattempt()) {

                $url = $TC->url->report('taskscore', array('taskscoreid' => $TC->taskscore->id));
                $PAGE->navbar->add($text, $url);

                $text = get_string('pluginname', 'taskchainreport_taskattempt');
            }
        }
    }
}
if ($text) {
    $PAGE->navbar->add($text); // no link on last navbar item
}

// get the taskchain renderer
$output = $PAGE->get_renderer('mod_taskchain');

// get renderer subtype (e.g. attempt_hp_6_jcloze_xml)
// and load the appropriate review class for this attempt
$subtype = $TC->get_attempt_subtype();
$TC->load_class($subtype, 'review.php');

////////////////////////////////////////////////////////////////////////////////
// Output starts here                                                         //
////////////////////////////////////////////////////////////////////////////////

echo $output->header();

echo $output->heading();

echo $output->box_start('generalbox boxaligncenter boxwidthwide');

// show the attempt review page
// use call_user_func() to prevent syntax error in PHP 5.2.x
$class = 'mod_taskchain_'.$subtype.'_review';
echo call_user_func(array($class, 'review'), $TC, $class);

echo $output->box_end();

if ($TC->get_taskscore()) {
    $params = array('taskscoreid' => $TC->taskscore->id);
    echo $output->continue_button($TC->url->report('taskscore', $params));
}

echo $output->footer();
