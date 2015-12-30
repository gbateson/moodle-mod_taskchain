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
 * mod/taskchain/index.php
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

$id = required_param('id', PARAM_INT);   // course

if (! $course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_course_login($course);

mod_taskchain::add_to_log($course->id, 'taskchain', 'index', "index.php?id=$course->id", '');

$PAGE->set_url('/mod/taskchain/index.php', array('id' => $course->id));
$PAGE->set_title($course->fullname);
$PAGE->set_heading($course->shortname);
$PAGE->navbar->add(get_string('modulenameplural', 'mod_taskchain'));

/// Output starts here

echo $OUTPUT->header();

/// Get all the appropriate data

if (! $taskchains = get_all_instances_in_course('taskchain', $course)) {
    echo $OUTPUT->heading(get_string('notaskchains', 'mod_taskchain'), 2);
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $course->id)));
    echo $OUTPUT->footer();
    die();
}

// get list of taskchain ids
$taskchainids = array();
foreach ($taskchains as $taskchain) {
    $taskchainids[] = $taskchain->id;
}

// get total number of attempts, users and details for these taskchains
if (has_capability('mod/taskchain:reviewallattempts', $PAGE->context)) {
    $show_aggregates = true;
    $single_user = false;
} else if (has_capability('mod/taskchain:reviewmyattempts', $PAGE->context)) {
    $show_aggregates = true;
    $single_user = true;
} else {
    $show_aggregates = false;
    $single_user = true;
}

if ($show_aggregates) {
    $tables = '{taskchain_chain_grades} tcg';
    $fields = 'tcg.parentid AS taskchainid, COUNT(*) AS attemptcount, COUNT(DISTINCT tcg.userid) AS usercount, ROUND(SUM(tcg.grade) / COUNT(tcg.grade), 0) AS averagescore, MAX(tcg.grade) AS maxgrade';

    list($select, $params) = $DB->get_in_or_equal($taskchainids, SQL_PARAMS_NAMED, 'parentid');
    $select = "tcg.parenttype = :parenttype AND tcg.parentid $select";
    $params['parenttype'] = mod_taskchain::PARENTTYPE_ACTIVITY;

    if ($single_user) {
        // restrict results to this user only
        $select .= ' AND tcg.userid=:userid';
        $params['userid'] = $USER->id;
    }
    $aggregates = $DB->get_records_sql("SELECT $fields FROM $tables WHERE $select GROUP BY tcg.parentid", $params);
} else {
    $aggregates = array();
}

$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    if (method_exists('course_modinfo', 'get_section_info_all')) {
        // Moodle >= 2.3
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();
    } else {
        // Moodle 2.0 - 2.2
        $sections = get_all_sections($course->id);
    }
}

/// Print the list of instances (your module will probably extend this)

$strsectionname = get_string('sectionname', 'format_'.$course->format);
$strname        = get_string('name');
$strhighest     = get_string('highestgrade', 'mod_taskchain');
$straverage     = get_string('averagegrade', 'mod_taskchain');
$strattempts    = get_string('attempts', 'mod_taskchain');

$table = new html_table();

if ($usesections) {
    $table->head  = array($strsectionname, $strname, $strhighest, $straverage, $strattempts);
    $table->align = array('center', 'left', 'center', 'center', 'left');
} else {
    $table->head  = array($strname, $strhighest, $straverage, $strattempts);
    $table->align = array('left', 'center', 'center', 'left');
}

foreach ($taskchains as $taskchain) {
    $row = new html_table_row();

    if ($usesections) {
        $text = get_section_name($course, $sections[$taskchain->section]);
        $row->cells[] = new html_table_cell($text);
    }

    if ($taskchain->visible) {
        $class = '';
    } else {
        $class = 'dimmed';
    }

    $href = new moodle_url('/mod/taskchain/view.php', array('id' => $taskchain->coursemodule));
    $params = array('href' => $href, 'class' => $class);

    $text = html_writer::tag('a', $taskchain->name, $params);
    $row->cells[] = new html_table_cell($text);

    if (empty($aggregates[$taskchain->id]) || empty($aggregates[$taskchain->id]->attemptcount)) {
        $row->cells[] = new html_table_cell('0'); // average score
        $row->cells[] = new html_table_cell('0'); // max score
        $row->cells[] = new html_table_cell('&nbsp;'); // reports
    } else {
        $href = new moodle_url('/mod/taskchain/report.php', array('id' => $taskchain->coursemodule));
        $params = array('href' => $href, 'class' => $class);

        $text = html_writer::tag('a', $aggregates[$taskchain->id]->maxgrade, $params);
        $row->cells[] = new html_table_cell($text);

        $text = html_writer::tag('a', $aggregates[$taskchain->id]->averagescore, $params);
        $row->cells[] = new html_table_cell($text);

        $text = get_string('viewreports', 'mod_taskchain', $aggregates[$taskchain->id]->usercount);
        $text = html_writer::tag('a', $text, $params);
        $row->cells[] = new html_table_cell($text);
    }

    $table->data[] = $row;
}

echo $OUTPUT->heading(get_string('modulenameplural', 'mod_taskchain'), 2);
echo html_writer::table($table);

/// Finish the page

echo $OUTPUT->footer();
