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
 * mod/taskchain/submit.php
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

// Log this request
mod_taskchain::add_to_log($TC->course->id, 'taskchain', 'submit', 'view.php?id='.$TC->coursemodule->id, $TC->taskchain->id, $TC->coursemodule->id);

// Set editing mode
mod_taskchain::set_user_editing();

// initialize $PAGE (and compute blocks)
$PAGE->set_url($TC->url->submit());
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

// check whether user can submit to this chain and task attempt
if ($error = $TC->require_chain_cansubmit()) {
    $TC->print_error($error);
}
if ($error = $TC->require_task_cansubmit()) {
    $TC->print_error($error);
}

// get renderer subtype (e.g. attempt_hp_6_jcloze_xml)
// and load the appropriate storage class for this attempt
$subtype = $TC->get_attempt_subtype();
$TC->load_class($subtype, 'storage.php');

// store the results (use call_user_func to prevent syntax errors in PHP 5.2.x)
$storage = 'mod_taskchain_'.$subtype.'_storage';
call_user_func(array($storage, 'store'), $TC);

// transfer gradelimit and gradeweighting to $TC->taskchain
// (required for taskchain_get_user_grades() in "mod/taskchain/lib.php")
$TC->taskchain->gradelimit = $TC->chain->gradelimit;
$TC->taskchain->gradeweighting = $TC->chain->gradeweighting;

// update grades for this user
taskchain_update_grades($TC->taskchain, $USER->id);

// update completion, if necessary
if ($TC->taskchain->completionmingrade || $TC->taskchain->completionpass || $TC->taskchain->completioncompleted) {
    $completion = new completion_info($TC->course);
    $completion->update_state($TC->coursemodule);
}

// do the stuff the $TC->task->output->redirect() used to do

if ($TC->task->delay3==mod_taskchain::DELAY3_DISABLE || $TC->taskattempt->status==mod_taskchain::STATUS_INPROGRESS || $TC->taskattempt->redirect==0) {
    // we need some check here to see if the user is trying to navigate away
    // from the page in which case we should just die and not send the header
    header("HTTP/1.0 204 No Response");
    // Note: don't use header("Status: 204"); because it can confuse PHP+FastCGI
    // http://moodle.org/mod/forum/discuss.php?d=108330
    die;
    // script will die here
}

if ($TC->taskattempt->status==mod_taskchain::STATUS_ABANDONED) {
    $can_continue = call_user_func(array($storage, 'can_continue'), $TC);
    if ($can_continue==mod_taskchain::CONTINUE_RESUMETASK || $can_continue==mod_taskchain::CONTINUE_RESTARTTASK) {
        $stop = true;
    } else if ($can_continue==mod_taskchain::CONTINUE_RESTARTCHAIN && ! $TC->has_entrypage()) {
        $stop = true;
    } else {
        $stop = false;
    }
    if ($stop) {
        $url = $TC->url->course();
        if ($TC->inpopup) {
            echo ''
                .'<script type="text/javascript">'."\n"
                .'//<![CDATA['."\n"
                ."if (window.opener && !opener.closed) {\n"
                ."    opener.location = '$url';\n"
                ."}\n"
                .'//]]>'."\n"
                ."</script>\n"
            ;
            close_window();
            // script will die here
        } else {
            redirect($url);
            // script will die here
        }
    }

    // mod_taskchain::CONTINUE_ABANDONCHAIN
    $TC->chainattempt = null;
}

$TC->task = null;
$TC->force_tnumber(0);
$TC->taskattempt = null;
$TC->taskscore   = null;
$TC->cache_available_task = array();

$TC->set_preferred_pagelayout($PAGE);

// decide which task to show
if ($error = $TC->require_next_task()) {
    $TC->print_error($error);
}

// create the renderer for this attempt
$output = $PAGE->get_renderer('mod_taskchain');

if ($TC->get_taskid() != mod_taskchain::CONDITIONTASKID_ENDOFCHAIN) {
    //$params = array('taskid'=>$taskid, 'tnumber'=>-1, 'taskattemptid'=>0, 'taskscoreid'=>0);
    $url = $output->format_url('attempt.php', 'coursemoduleid', array());
    redirect($url);
}

// if we don't need an exit page, go straight back to the next activity or course page (or retry this taskchain)
if (! $TC->has_exitpage()) {
    if ($TC->require_exitgrade() && $TC->chainattempt->grade < $TC->chain->exitgrade) {
        // score was not good enough, so do automatic retry
        redirect($TC->url->attempt());
    }
    if ($exitcm = $TC->get_cm('exit')) {
        // display next activity
        redirect($TC->url->view($exitcm));
    } else {
        // return to course page
        redirect($TC->url->course());
    }
}

////////////////////////////////////////////////////////////////////////////////
// Output starts here                                                         //
////////////////////////////////////////////////////////////////////////////////

echo $output->header();

if ($TC->can->attempt() || $TC->can->preview()) {
    echo $output->exitpage();
} else {
    if (isguestuser()) {
        // offer guests a choice of logging in or going back.
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
