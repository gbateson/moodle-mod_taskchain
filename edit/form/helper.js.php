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
 * mod/taskchain/edit/form/helper.js.php
 *
 * @package    mod_taskchain
 * @copyright  2013 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

if (! headers_sent()) {
    header('Content-type: application/javascript');
}

// Tell Moodle we are an ajax script
// this prevents full screen errors.
define('AJAX_SCRIPT', true);

// Include required files.
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib.php');

// Create object to represent current TaskChain activity.
$TC = new mod_taskchain();

$output = $PAGE->get_renderer('mod_taskchain');

$type = required_param('type', PARAM_ALPHANUM); // "task" or "chain"
require_once($CFG->dirroot . "/mod/taskchain/edit/form/$type.php");

$classname = 'mod_taskchain_edit_' . $type . '_form';
$mform = new $classname();

if ($mform->is_cancelled()) {
    $TC->action = 'editcancelled';
} else if ($newdata = $mform->get_data()) {
    $TC->action = 'datasubmitted';
} else {
    $TC->action = 'showfieldform';
}

switch ($TC->action) {
    case 'showfieldform':
        $defaults = (array)$TC->$type->to_stdclass();
        $mform->data_preprocessing($defaults);
        $mform->set_data($defaults);
        $mform->display();
        break;

    case 'datasubmitted':
        $mform->update_singlefield($newdata);
        // Deliberate drop through.

    case 'editcancelled':
        $mform->display_singlefield();
        break;
}

$js = '';
$js .= 'window.TC  = new Object();' . "\n";
$js .= 'TC.error   = "' . addslashes_js(get_string('error')) . '"' . "\n";
$js .= 'TC.loading = "' . addslashes_js(get_string('loading', 'repository')) . '"' . "\n" .
$js .= 'TC.wwwroot = "' . addslashes_js($CFG->wwwroot) . '"' . "\n";
