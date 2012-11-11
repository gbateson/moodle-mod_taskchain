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
 * mod/taskchain/db/log.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

$logs = array(
    // standard mod actions
    array('module'=>'taskchain', 'action'=>'add', 'mtable'=>'taskchain', 'field'=>'name'),
    array('module'=>'taskchain', 'action'=>'update', 'mtable'=>'taskchain', 'field'=>'name'),
    array('module'=>'taskchain', 'action'=>'view', 'mtable'=>'taskchain', 'field'=>'name'),

    // attempt actions
    array('module'=>'taskchain', 'action'=>'attempt', 'mtable'=>'taskchain', 'field'=>'name'),
    array('module'=>'taskchain', 'action'=>'submit', 'mtable'=>'taskchain', 'field'=>'name'),

    // report actions
    array('module'=>'taskchain', 'action'=>'report', 'mtable'=>'taskchain', 'field'=>'name'),
    array('module'=>'taskchain', 'action'=>'review', 'mtable'=>'taskchain', 'field'=>'name'),

    // edit actions
    array('module'=>'taskchain', 'action'=>'editcolumnlists', 'mtable'=>'taskchain', 'field'=>'name'),
    array('module'=>'taskchain', 'action'=>'editcondition', 'mtable'=>'taskchain', 'field'=>'name'),
    array('module'=>'taskchain', 'action'=>'edittask', 'mtable'=>'taskchain', 'field'=>'name'),
    array('module'=>'taskchain', 'action'=>'edittasks', 'mtable'=>'taskchain', 'field'=>'name'),
);
