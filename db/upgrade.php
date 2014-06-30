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
 * mod/taskchain/db/upgrade.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/**
 * xmldb_taskchain_upgrade
 *
 * @uses $CFG
 * @uses $DB
 * @param xxx $oldversion
 * @return xxx
 * @todo Finish documenting this function
 */
function xmldb_taskchain_upgrade($oldversion) {
    global $CFG, $DB;

    // this flag will be set to false if any part of this TaskChain upgrade fails
    $result = true;

    // this flag will be set to true if any upgrade needs to empty the TaskChain cache
    $empty_cache = false;

    $dbman = $DB->get_manager();

    $newversion = 2011040106;
    if ($result && $oldversion < $newversion) {
        $tables = array(
            'taskchain_chains' => array(
                // add missing exitgrade field
                new xmldb_field('exitgrade', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0', 'exitcm'),

                // add format fields for entry/exit text
                new xmldb_field('entryformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'entrytext'),
                new xmldb_field('exitformat',  XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'exittext'),

                // force text fields to be longtext
                new xmldb_field('entrytext', XMLDB_TYPE_TEXT, 'long', null, XMLDB_NOTNULL),
                new xmldb_field('exittext', XMLDB_TYPE_TEXT, 'long', null, XMLDB_NOTNULL)
            ),
            // force text fields to be longtext
            'taskchain_cache' => array(
                new xmldb_field('content', XMLDB_TYPE_TEXT, 'long', null, XMLDB_NOTNULL)
            ),
            'taskchain_details' => array(
                new xmldb_field('details', XMLDB_TYPE_TEXT, 'long', null, XMLDB_NOTNULL)
            ),
            'taskchain_questions' => array(
                new xmldb_field('name', XMLDB_TYPE_TEXT, 'long', null, XMLDB_NOTNULL)
            ),
            'taskchain_strings' => array(
                new xmldb_field('string', XMLDB_TYPE_TEXT, 'long', null, XMLDB_NOTNULL)
            )
        );
        foreach ($tables as $tablename => $fields) {
            $table = new xmldb_table($tablename);
            foreach ($fields as $field) {
                xmldb_taskchain_fix_previous_field($dbman, $table, $field);
                if ($dbman->field_exists($table, $field)) {
                    $fieldname = $field->getName();
                    if ($field->getType()==XMLDB_TYPE_TEXT) {
                        $DB->set_field_select($tablename, $fieldname, '', "$fieldname IS NULL");
                    }
                    $dbman->change_field_type($table, $field);
                } else {
                    $dbman->add_field($table, $field);
                }
            }
        }
        upgrade_mod_savepoint($result, "$newversion", 'taskchain');
    }

    $newversion = 2011040107;
    if ($oldversion < $newversion) {
        // force all MySQL integer fields to be signed, the default for Moodle 2.3 and later
        if ($DB->get_dbfamily() == 'mysql') {
            $prefix = $DB->get_prefix();
            $tables = $DB->get_tables();
            foreach ($tables as $table) {
                if (substr($table, 0, 9)=='taskchain') {
                    $rs = $DB->get_recordset_sql("SHOW COLUMNS FROM {$CFG->prefix}$table WHERE type LIKE '%unsigned%'");
                    foreach ($rs as $column) {
                        // copied from as "lib/db/upgradelib.php"
                        $type = preg_replace('/\s*unsigned/i', 'signed', $column->type);
                        $notnull = ($column->null === 'NO') ? 'NOT NULL' : 'NULL';
                        $default = (is_null($column->default) || $column->default === '') ? '' : "DEFAULT '$column->default'";
                        $autoinc = (stripos($column->extra, 'auto_increment') === false)  ? '' : 'AUTO_INCREMENT';
                        $sql = "ALTER TABLE `{$prefix}$table` MODIFY COLUMN `$column->field` $type $notnull $default $autoinc";
                        $DB->change_database_structure($sql);
                    }
                }
            }
        }
        upgrade_mod_savepoint(true, "$newversion", 'taskchain');
    }

    $newversion = 2011040108;
    if ($oldversion < $newversion) {
        update_capabilities('mod/taskchain');
        upgrade_mod_savepoint(true, "$newversion", 'taskchain');
    }

    $newversion = 2011040112;
    if ($oldversion < $newversion) {
        require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

        // set nexttaskid for PRE conditions to be current task
        $taskid = mod_taskchain::CONDITIONTASKID_SAME;
        $params = array('conditiontype' => mod_taskchain::CONDITIONTYPE_PRE);
        $DB->set_field('taskchain_conditions', 'nexttaskid', $taskid, $params);

        // set conditionid for POST conditions to be current task
        $taskid = mod_taskchain::CONDITIONTASKID_SAME;
        $params = array('conditiontype' => mod_taskchain::CONDITIONTYPE_POST);
        $DB->set_field('taskchain_conditions', 'conditiontaskid', $taskid, $params);

        upgrade_mod_savepoint(true, "$newversion", 'taskchain');
    }

    $newversion = 2011040115;
    if ($oldversion < $newversion) {
        require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

        // extract all TaskChain records and associated grade information
        $select = 't.*, tc.id AS chainid, tc.gradelimit, tc.gradeweighting';
        $from   = '{taskchain} t JOIN {taskchain_chains} tc ON t.id=tc.parentid';
        $where  = 'tc.parenttype = ? AND tc.gradelimit > ? AND tc.gradeweighting > ?';
        $params = array(mod_taskchain::PARENTTYPE_ACTIVITY, 0, 0);

        // make sure all TaskChains have grade items
        if ($taskchains = $DB->get_records_sql("SELECT $select FROM $from WHERE $where", $params)) {
            foreach ($taskchains as $taskchain) {
                taskchain_grade_item_update($taskchain);
            }
        }

        $empty_cache = true;
        upgrade_mod_savepoint(true, "$newversion", 'taskchain');
    }

    $newversion = 2011040117;
    if ($oldversion < $newversion) {
        $fields = array('entrycm', 'exitcm');
        foreach ($fields as $field) {
            $DB->set_field('taskchain_chains', $field, -5, array($field => -3));
            $DB->set_field('taskchain_chains', $field, -6, array($field => -4));
        }
        upgrade_mod_savepoint(true, "$newversion", 'taskchain');
    }

    $newversion = 2013111555;
    if ($oldversion < $newversion) {
        $tables = array(
            'taskchain_tasks' => array(
                new xmldb_field('allowpaste', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'stoptext')
            ),
            'taskchain_cache' => array(
                new xmldb_field('taskchain_bodystyles', XMLDB_TYPE_CHAR,    '8',  null, XMLDB_NOTNULL, null, null, 'slasharguments'),
                new xmldb_field('sourcerepositoryid',   XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0',  'sourcelocation'),
                new xmldb_field('configrepositoryid',   XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0',  'configlocation'),
                new xmldb_field('allowpaste',           XMLDB_TYPE_INTEGER, '2',  null, XMLDB_NOTNULL, null, '0',  'stoptext')
            )
        );
        foreach ($tables as $table => $fields) {
            $table = new xmldb_table($table);
            foreach ($fields as $field) {
                xmldb_taskchain_fix_previous_field($dbman, $table, $field);
                if ($dbman->field_exists($table, $field)) {
                    $dbman->change_field_type($table, $field);
                } else {
                    $dbman->add_field($table, $field);
                }
            }
        }
        upgrade_mod_savepoint(true, "$newversion", 'taskchain');
    }

    $newversion = 2014011773;
    if ($oldversion < $newversion) {
        require_once($CFG->dirroot.'/mod/taskchain/locallib.php');
        taskchain_update_grades();
        upgrade_mod_savepoint(true, "$newversion", 'taskchain');
    }

    $newversion = 2014012276;
    if ($oldversion < $newversion) {
        // fix HTML task names such as "Task (99)"

        // get required script libraries
        require_once($CFG->dirroot.'/mod/taskchain/locallib.php');
        require_once($CFG->dirroot.'/mod/taskchain/locallib/base.php');

        // set up SQL query
        $select = 'tt.*, t.id AS taskchainid';
        $from   = '{taskchain_tasks} tt '.
                  'JOIN {taskchain_chains} tc ON tt.chainid = tc.id '.
                  'JOIN {taskchain} t ON tc.parentid = t.id AND tc.parenttype = ?';
        $where  = 'tt.sourcetype IN (?, ?) AND '.$DB->sql_like('tt.name', '?');
        $orderby = 'tt.chainid, tt.sortorder';
        $params = array(mod_taskchain::PARENTTYPE_ACTIVITY,
                        'html_xhtml',
                        'html_xerte',
                        get_string('task', 'taskchain').' (%)');

        // get tasks
        if ($tasks = $DB->get_records_sql("SELECT $select FROM $from WHERE $where ORDER BY $orderby", $params)) {
            global $TC;
            $TC = null;
            foreach ($tasks as $taskid => $task) {
                if ($TC===null || $TC->taskchain->id != $task->taskchainid) {
                    $TC = $DB->get_record('taskchain', array('id' => $task->taskchainid));
                    $TC = new mod_taskchain($TC);
                }
                unset($task->taskchainid);
                $task = new taskchain_task($task, array('TC' => $TC));
                $task->get_source();
                $oldname = $task->get_name();
                $newname = $task->source->get_name();
                if ($newname=='' || $newname==$oldname) {
                    // do nothing
                } else {
                    $DB->set_field('taskchain_tasks', 'name', $newname, array('id' => $taskid));
                }
            }
        }
        upgrade_mod_savepoint(true, "$newversion", 'taskchain');
    }

    $newversion = 2014051606;
    if ($oldversion < $newversion) {

        // select all sections using courselinks.js.php
        // typically there is just one in any QuizPort/TaskChain course
        $select  = $DB->sql_like('summary', '?');
        $params = array('%/courselinks.js.php%');
        if ($ids = $DB->get_records_select_menu('course_sections', $select, $params, '', 'id,course')) {

            // convert "quizport" to "taskchain"
            // and "attforblock" to "attendance"
            $update = '{course_sections}';
            $set    = 'summary = REPLACE(REPLACE(REPLACE(summary, ?, ?), ?, ?), ?, ?)';
            list($where, $params) = $DB->get_in_or_equal(array_keys($ids));
            array_unshift($params, 'quizport', 'taskchain', 'attforblock', 'attendance', 'id=([0-9]+)', 'id=([0-9]+(&section=[0-9]+)?)');
            $DB->execute("UPDATE $update SET $set WHERE id $where", $params);

            // rebuild caches for any affected courses
            foreach (array_unique($ids) as $courseid) {
                rebuild_course_cache($courseid, true);
            }
        }
        upgrade_mod_savepoint(true, "$newversion", 'taskchain');
    }

    $newversion = 2014063024;
    if ($oldversion < $newversion) {

        // get required script libraries
        require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

        // set up SQL query
        $select = 'tt.*, t.id AS taskchainid';
        $from   = '{taskchain_tasks} tt '.
                  'JOIN {taskchain_chains} tc ON tt.chainid = tc.id '.
                  'JOIN {taskchain} t ON tc.parentid = t.id AND tc.parenttype = ?';
        $where  = 'tt.sourcetype = ?';
        $orderby = 'tt.chainid, tt.sortorder';
        $params = array(mod_taskchain::PARENTTYPE_ACTIVITY, 'html_xhtml');

        // get tasks
        if ($tasks = $DB->get_records_sql("SELECT $select FROM $from WHERE $where ORDER BY $orderby", $params)) {
            global $TC;
            $TC = null;
            $classes = mod_taskchain::get_classes('taskchainsource');
            foreach ($tasks as $taskid => $task) {
                if ($TC===null || $TC->taskchain->id != $task->taskchainid) {
                    $TC = $DB->get_record('taskchain', array('id' => $task->taskchainid));
                    $TC = new mod_taskchain($TC);
                }
                unset($task->taskchainid);
                $task = new taskchain_task($task, array('TC' => $TC));
                $file = $task->get_file('source');
                $oldtype = $task->get_sourcetype();
                foreach ($classes as $class) {
                    $object = new $class($file);
                    if (method_exists($object, 'is_taskfile') && ($newtype = $object->is_taskfile())) {
                        if ($newtype==$oldtype) {
                            // do nothing
                        } else {
                            $DB->set_field('taskchain_tasks', 'sourcetype', $newtype, array('id' => $taskid));
                        }
                        break;
                    }
                }
            }
            unset($TC, $classes, $class, $tasks, $task, $taskid, $file, $type);
        }

        $empty_cache = true;
        upgrade_mod_savepoint(true, "$newversion", 'taskchain');
    }

    if ($empty_cache) {
        $DB->delete_records('taskchain_cache');
    }

    return true;
}

/**
 * xmldb_taskchain_fix_previous_fields
 *
 * @param xxx $dbman
 * @param xmldb_table $table
 * @param array of xmldb_field $fields (passed by reference)
 * @return void, but may update some items in $fields array
 */
function xmldb_taskchain_fix_previous_fields($dbman, $table, &$fields) {
    foreach ($fields as $i => $field) {
        xmldb_taskchain_fix_previous_field($dbman, $table, $fields[$i]);
    }
}

/**
 * xmldb_taskchain_fix_previous_field
 *
 * @param xxx $dbman
 * @param xmldb_table $table
 * @param xmldb_field $field (passed by reference)
 * @return void, but may update $field->previous
 */
function xmldb_taskchain_fix_previous_field($dbman, $table, &$field) {
    $previous = $field->getPrevious();
    if (empty($previous) || $dbman->field_exists($table, $previous)) {
        // $previous field exists - do nothing
    } else {
        // $previous field does not exist, so remove it
        $field->setPrevious(null);
    }
}
