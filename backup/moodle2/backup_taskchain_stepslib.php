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
 * mod/taskchain/backup/moodle2/backup_taskchain_stepslib.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @see http://docs.moodle.org/en/Development:Hotpot for XML structure diagram
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/**
 * backup_taskchain_activity_structure_step
 * Defines the complete taskchain structure for backup, with file and id annotations
 *
 * @copyright 2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */

class backup_taskchain_activity_structure_step extends backup_activity_structure_step {

    /** maximum number of questions to retrieve in one DB query */
    const GET_QUESTIONS_LIMIT = 100;

    /**
     * define_structure
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function define_structure()  {

        // are we including userinfo?
        $userinfo = $this->get_setting_value('userinfo');

        ////////////////////////////////////////////////////////////////////////
        // XML nodes declaration - non-user data
        ////////////////////////////////////////////////////////////////////////

        // taskchain
        $fieldnames = array('id', 'course'); // excluded fields
        $fieldnames = $this->get_fieldnames('taskchain', $fieldnames);
        $taskchain  = new backup_nested_element('taskchain', array('id'), $fieldnames);

        // chain
        $fieldnames = array('id', 'parenttype', 'parentid'); // excluded fields
        $fieldnames = $this->get_fieldnames('taskchain_chains', $fieldnames);
        $chain      = new backup_nested_element('chain', array('id'), $fieldnames);

        // tasks
        $tasks      = new backup_nested_element('tasks');
        $fieldnames = array('id', 'chainid'); // excluded fields
        $fieldnames = $this->get_fieldnames('taskchain_tasks', $fieldnames);
        $task       = new backup_nested_element('task', array('id'), $fieldnames);

        // conditions
        $conditions = new backup_nested_element('conditions');
        $fieldnames = array('id', 'taskid'); // excluded fields
        $fieldnames = $this->get_fieldnames('taskchain_conditions', $fieldnames);
        $condition  = new backup_nested_element('condition', array('id'), $fieldnames);

        ////////////////////////////////////////////////////////////////////////
        // XML nodes declaration - user data
        ////////////////////////////////////////////////////////////////////////

        if ($userinfo) {

            // chain grades
            $chaingrades = new backup_nested_element('chaingrades');
            $fieldnames = array('id', 'parenttype', 'parentid'); // excluded fields
            $fieldnames = $this->get_fieldnames('taskchain_chain_grades', $fieldnames);
            $chaingrade = new backup_nested_element('chaingrade', array('id'), $fieldnames);

            // chain attempts
            $chainattempts = new backup_nested_element('chainattempts');
            $fieldnames = array('id', 'chainid'); // excluded fields
            $fieldnames = $this->get_fieldnames('taskchain_chain_attempts', $fieldnames);
            $chainattempt = new backup_nested_element('chainattempt', array('id'), $fieldnames);

            // task scores
            $taskscores = new backup_nested_element('taskscores');
            $fieldnames = array('id', 'taskid'); // excluded fields
            $fieldnames = $this->get_fieldnames('taskchain_task_scores', $fieldnames);
            $taskscore  = new backup_nested_element('taskscore', array('id'), $fieldnames);

            // task attempts
            $taskattempts = new backup_nested_element('taskattempts');
            $fieldnames  = array('id', 'taskid'); // excluded fields
            $fieldnames  = $this->get_fieldnames('taskchain_task_attempts', $fieldnames);
            $taskattempt = new backup_nested_element('taskattempt', array('id'), $fieldnames);

            // questions (in task attempts)
            $questions  = new backup_nested_element('questions');
            $fieldnames = array('id', 'taskid', 'md5key'); // excluded fields
            $fieldnames = $this->get_fieldnames('taskchain_questions', $fieldnames);
            $question   = new backup_nested_element('question', array('id'), $fieldnames);

            // responses (to questions)
            $responses  = new backup_nested_element('responses');
            $fieldnames = array('id', 'questionid'); // excluded fields
            $fieldnames = $this->get_fieldnames('taskchain_responses', $fieldnames);
            $response   = new backup_nested_element('response', array('id'), $fieldnames);

             // strings (used in questions and responses)
            $strings    = new backup_nested_element('strings');
            $fieldnames = array('id', 'md5key'); // excluded fields
            $fieldnames = $this->get_fieldnames('taskchain_strings', $fieldnames);
            $string     = new backup_nested_element('string', array('id'), $fieldnames);
        }

        ////////////////////////////////////////////////////////////////////////
        // build the tree in the order needed for restore
        ////////////////////////////////////////////////////////////////////////

        if ($userinfo) {
            // strings must come before chain
            $taskchain->add_child($strings);
            $strings->add_child($string);
        }

        $taskchain->add_child($chain);
        $chain->add_child($tasks);
        $tasks->add_child($task);
        $task->add_child($conditions);
        $conditions->add_child($condition);

        if ($userinfo) {

            // chain grades
            $chain->add_child($chaingrades);
            $chaingrades->add_child($chaingrade);

            // chain attempts
            $chain->add_child($chainattempts);
            $chainattempts->add_child($chainattempt);

            // task scores
            $task->add_child($taskscores);
            $taskscores->add_child($taskscore);

            // task attempts
            $task->add_child($taskattempts);
            $taskattempts->add_child($taskattempt);

            // questions
            $task->add_child($questions);
            $questions->add_child($question);

            // responses
            $question->add_child($responses);
            $responses->add_child($response);
        }

        ////////////////////////////////////////////////////////////////////////
        // data sources - non-user data
        ////////////////////////////////////////////////////////////////////////

        $taskchain->set_source_table('taskchain', array('id' => backup::VAR_ACTIVITYID));
        $chain->set_source_table('taskchain_chains', array('parentid' => backup::VAR_PARENTID));
        $task->set_source_table('taskchain_tasks', array('chainid' => backup::VAR_PARENTID));
        $condition->set_source_table('taskchain_conditions', array('taskid' => backup::VAR_PARENTID));

        ////////////////////////////////////////////////////////////////////////
        // data sources - user related data
        ////////////////////////////////////////////////////////////////////////

        if ($userinfo) {

            // chain grades
            $taskchainid = $this->get_setting_value(backup::VAR_ACTIVITYID);
            $params = array('parenttype' => array('sqlparam' => 0), 'parentid' => array('sqlparam' => $taskchainid));
            $chaingrade->set_source_sql('SELECT * FROM {taskchain_chain_grades} WHERE parenttype = ? AND parentid = ?', $params);
            //$chaingrade->set_source_sql("SELECT * FROM {taskchain_chain_grades} WHERE parenttype=0 AND parentid=$taskchainid", array());

            // chain attempts
            $params = array('chainid' => backup::VAR_PARENTID);
            $chainattempt->set_source_table('taskchain_chain_attempts', $params);

            // task scores
            $params = array('taskid' => backup::VAR_PARENTID);
            $taskscore->set_source_table('taskchain_task_scores', $params);

            // task attempts
            $params = array('taskid' => backup::VAR_PARENTID);
            $taskattempt->set_source_table('taskchain_task_attempts', $params);

            // questions
            $params = array('taskid' => backup::VAR_PARENTID);
            $question->set_source_table('taskchain_questions', $params);

            // responses
            $params = array('questionid' => backup::VAR_PARENTID);
            $response->set_source_table('taskchain_responses', $params);

            // strings
            list($filter, $params) = $this->get_strings_sql();
            $string->set_source_sql("SELECT * FROM {taskchain_strings} WHERE id $filter", $params);
        }

        ////////////////////////////////////////////////////////////////////////
        // id annotations (foreign keys on non-parent tables)
        ////////////////////////////////////////////////////////////////////////

        $chain->annotate_ids('course_modules', 'entrycm');
        $chain->annotate_ids('course_modules', 'exitcm');

        $condition->annotate_ids('taskchain_conditions', 'conditiontaskid');
        $condition->annotate_ids('taskchain_conditions', 'nexttaskid');

        if ($userinfo) {
            $condition->annotate_ids('groups', 'groupid');
            $chaingrade->annotate_ids('user', 'userid');
            $chainattempt->annotate_ids('user', 'userid');
            $taskscore->annotate_ids('user', 'userid');
            $taskattempt->annotate_ids('user', 'userid');
            $response->annotate_ids('taskchain_task_attempts', 'attemptid');
        }

        ////////////////////////////////////////////////////////////////////////
        // file annotations
        ////////////////////////////////////////////////////////////////////////

        $taskchain->annotate_files('mod_taskchain', 'sourcefile', null);
        $taskchain->annotate_files('mod_taskchain', 'configfile', null);
        $taskchain->annotate_files('mod_taskchain', 'entrytext',  null);
        $taskchain->annotate_files('mod_taskchain', 'exittext',   null);

        // return the root element (taskchain), wrapped into standard activity structure
        return $this->prepare_activity_structure($taskchain);
    }

    /**
     * get_fieldnames
     *
     * @uses $DB
     * @param string $tablename the name of the Moodle table (without prefix)
     * @param array $excluded_fieldnames these field names will be excluded
     * @return array of field names
     */
    protected function get_fieldnames($tablename, array $excluded_fieldnames)   {
        global $DB;
        $fieldnames = array_keys($DB->get_columns($tablename));
        return array_diff($fieldnames, $excluded_fieldnames);
    }

    /**
     * get_strings_sql
     *
     * we want all the strings used in responses and questions for the current TaskChain
     * - taskchain_questions.text    : a single taskchain_strings.id
     * - taskchain_responses.correct : a comma-separated list of taskchain_strings.id's
     * - taskchain_responses.wrong   : a comma-separated list of taskchain_strings.id's
     * - taskchain_responses.ignored : a comma-separated list of taskchain_strings.id's
     *
     * @uses $DB
     * @return array ($filter, $params) to extract strings used in this TaskChain
     */
    protected function get_strings_sql() {
        global $DB;

        // array to store the string ids
        $stringids = array();

        // the response fields that contain string ids
        $stringfields = array('correct', 'wrong', 'ignored');

        // the id of the current taskchain
        $taskchainid = $this->get_setting_value(backup::VAR_ACTIVITYID);

        $select = 'tq.*, tc.id AS chainid, t.id AS taskchainid';
        $from   = '{taskchain_questions} tq '.
                  'JOIN {taskchain_tasks} tt ON tq.taskid = tt.id '.
                  'JOIN {taskchain_chains} tc ON tt.chainid = tc.id '.
                  'JOIN {taskchain} t ON tc.parenttype = ? AND tc.parentid = t.id';
        $where  = 't.id = ?';
        $order  = 'tq.id, tq.text';
        $params = array(0, $taskchainid); // 0 = mod_taskchain::PARENTTYPE_ACTIVITY

        // get questions in this taskchain
        if ($questions = $DB->get_records_sql("SELECT $select FROM $from WHERE $where ORDER BY $order", $params)) {

            // extract string ids in the "text" field of these questions
            foreach ($questions as $question) {
                if ($id = intval(trim($question->text))) {
                    $stringids[$id] = true;
                }
            }

            $questions = array_keys($questions);
            while (($questionids = array_splice($questions, 0, self::GET_QUESTIONS_LIMIT)) && count($questionids)) {

                // get the responses to these questions
                list($filter, $params) = $DB->get_in_or_equal($questionids);
                if ($responses = $DB->get_records_select('taskchain_responses', "questionid $filter", $params)) {

                    // extract string ids from the string fields of these responses
                    foreach ($responses as $response) {
                        foreach ($stringfields as $stringfield) {
                            $ids = explode(',', trim($response->$stringfield));
                            foreach ($ids as $id) {
                                if ($id = intval($id)) {
                                    $stringids[$id] = true;
                                }
                            }
                        }
                    }
                } // end if $responses
            } // while $questionids
        } // end if $questions

        // get the distinct string ids
        $stringids = array_keys($stringids);

        switch (count($stringids)) {
            case 0:  $filter = '< 0'; break;
            case 1:  $filter = '='.$stringids[0]; break;
            default: $filter = 'IN ('.implode(',', $stringids).')';
        }

        // Note: we don't put the ids into $params like this
        // - return $DB->get_in_or_equal($stringids);
        // because Moodle 2.0 backup expects only backup::VAR_xxx
        // constants, which are all negative, in $params, and will
        // throw an exception for any positive values in $params
        // - baseelementincorrectfinalorattribute
        //   backup/util/structure/base_final_element.class.php
        return array($filter, array());
    }
}
