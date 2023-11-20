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
 * mod/taskchain/backup/moodle2/restore_taskchain_stepslib.php
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
 * restore_taskchain_activity_structure_step
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class restore_taskchain_activity_structure_step extends restore_activity_structure_step {

    /**
     * define_structure
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function define_structure()  {

        $paths = array();

        $userinfo = $this->get_setting_value('userinfo'); // are we including userinfo?

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - non-user data
        ////////////////////////////////////////////////////////////////////////

        $paths[] = new restore_path_element('taskchain',           '/activity/taskchain');
        $paths[] = new restore_path_element('taskchain_chain',     '/activity/taskchain/chain');
        $paths[] = new restore_path_element('taskchain_task',      '/activity/taskchain/chain/tasks/task');
        $paths[] = new restore_path_element('taskchain_condition', '/activity/taskchain/chain/tasks/task/conditions/condition');

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - user data
        ////////////////////////////////////////////////////////////////////////

        if ($userinfo) {
            $paths[] = new restore_path_element('taskchain_string',       '/activity/taskchain/strings/string');
            $paths[] = new restore_path_element('taskchain_chaingrade',   '/activity/taskchain/chain/chaingrades/chaingrade');
            $paths[] = new restore_path_element('taskchain_chainattempt', '/activity/taskchain/chain/chainattempts/chainattempt');
            $paths[] = new restore_path_element('taskchain_taskscore',    '/activity/taskchain/chain/tasks/task/taskscores/taskscore');
            $paths[] = new restore_path_element('taskchain_taskattempt',  '/activity/taskchain/chain/tasks/task/taskattempts/taskattempt');
            $paths[] = new restore_path_element('taskchain_question',     '/activity/taskchain/chain/tasks/task/questions/question');
            $paths[] = new restore_path_element('taskchain_response',     '/activity/taskchain/chain/tasks/task/questions/question/responses/response');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    static public function define_decode_contents() {
        return array(
            new restore_decode_content('taskchain', array('entrytext'), 'entrytext'),
            new restore_decode_content('taskchain', array('exittext'),  'exittext')
        );
    }

    /**
     * process_taskchain
     *
     * @uses $DB
     * @param xxx $data
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function process_taskchain($data)  {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields
        if (! $data->course = $this->get_courseid()) {
            return false; // missing courseid - shouldn't happen !!
        }

        // add new record
        if (! $newid = $DB->insert_record('taskchain', $data)) {
            return false; // could not add new record - shouldn't happen !!
        }

        // inmediately after inserting "activity" record, call this
        $this->apply_activity_instance($newid);
    }

    /**
     * process_taskchain_chain
     *
     * @uses $DB
     * @param xxx $data
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function process_taskchain_chain($data)  {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields
        $data->parenttype = 0; // mod_taskchain::PARENTTYPE_ACTIVITY
        $data->parentid = $this->get_new_parentid('taskchain');
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);

        // add new record
        if (! $newid = $DB->insert_record('taskchain_chains', $data)) {
            return false; // could not add new record - shouldn't happen !!
        }

        // store mapping from $oldid to $newid
        $this->set_mapping('taskchain_chain', $oldid, $newid);
    }

    /**
     * process_taskchain_task
     *
     * @uses $DB
     * @param xxx $data
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function process_taskchain_task($data)  {
        global $CFG, $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields
        $data->chainid = $this->get_new_parentid('taskchain_chain');
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);

        if ($this->task->get_old_moduleversion() < 2017031738) {
            require_once($CFG->dirroot.'/mod/taskchain/locallib.php');
            $data->titletext = '';
            // shift CHAINNAME and SORTORDER bits to the left (i.e. multiply by 2)
            $title = 0;
            $title += ($data->title & (mod_taskchain::TITLE_SORTORDER >> 1));
            $title += ($data->title & (mod_taskchain::TITLE_CHAINNAME >> 1));
            $title *= 2;
            $title += ($data->title & (mod_taskchain::TITLE_SOURCE >> 1));
            if (($title & mod_taskchain::TITLE_SOURCE)==mod_taskchain::TEXTSOURCE_SPECIFIC) {
                // replace SPECIFIC with TASKNAME
                $data->title = 0;
                $data->title += ($title & mod_taskchain::TITLE_SORTORDER);
                $data->title += ($title & mod_taskchain::TITLE_CHAINNAME);
                $data->title += (mod_taskchain::TEXTSOURCE_TASKNAME);
            } else {
                $data->title = $title;
            }
        }

        // add new record
        if (! $newid = $DB->insert_record('taskchain_tasks', $data)) {
            return false; // could not add new record - shouldn't happen !!
        }

        // store mapping from $oldid to $newid
        $this->set_mapping('taskchain_task', $oldid, $newid);
    }

    /**
     * process_taskchain_condition
     *
     * @uses $DB
     * @param xxx $data
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function process_taskchain_condition($data)  {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields
        $data->taskid = $this->get_new_parentid('taskchain_task');
        if ($this->get_setting_value('userinfo')) {
            $data->groupid = $this->get_mappingid('group', $data->groupid);
        } else {
            $data->groupid = 0; // no user data or groups available :-(
        }

        // add new record
        if (! $newid = $DB->insert_record('taskchain_conditions', $data)) {
            return false; // could not add new record - shouldn't happen !!
        }
    }

    /**
     * process_taskchain_chaingrade
     *
     * @uses $DB
     * @param xxx $data
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function process_taskchain_chaingrade($data)  {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields
        if (! $data->userid = $this->get_mappingid('user', $data->userid)) {
            return false; // invalid userid - shouldn't happen !!
        }
        if (! $data->parentid = $this->get_new_parentid('taskchain')) {
            return false; // parentid not available - shouldn't happen !!
        }
        $data->parenttype = 0; // mod_taskchain::PARENTTYPE_ACTIVITY

        // add new record
        if (! $newid = $DB->insert_record('taskchain_chain_grades', $data)) {
            return false; // could not add new record - shouldn't happen !!
        }
    }

    /**
     * process_taskchain_chainattempt
     *
     * @uses $DB
     * @param xxx $data
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function process_taskchain_chainattempt($data)  {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields
        if (! $data->userid = $this->get_mappingid('user', $data->userid)) {
            return false; // invalid userid - shouldn't happen !!
        }
        if (! $data->chainid = $this->get_new_parentid('taskchain_chain')) {
            return false; // parentid not available - shouldn't happen !!
        }

        // add new record
        if (! $newid = $DB->insert_record('taskchain_chain_attempts', $data)) {
            return false; // could not add new record - shouldn't happen !!
        }
    }

    /**
     * process_taskchain_taskscore
     *
     * @uses $DB
     * @param xxx $data
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function process_taskchain_taskscore($data)  {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields
        if (! $data->userid = $this->get_mappingid('user', $data->userid)) {
            return false; // invalid userid - shouldn't happen !!
        }
        if (! $data->taskid = $this->get_new_parentid('taskchain_task')) {
            return false; // taskid not available - shouldn't happen !!
        }

        // add new record
        if (! $newid = $DB->insert_record('taskchain_task_scores', $data)) {
            return false; // could not add new record - shouldn't happen !!
        }
    }

    /**
     * process_taskchain_taskattempt
     *
     * @uses $DB
     * @param xxx $data
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function process_taskchain_taskattempt($data)  {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields
        if (! $data->userid = $this->get_mappingid('user', $data->userid)) {
            return false; // invalid userid - shouldn't happen !!
        }
        if (! $data->taskid = $this->get_new_parentid('taskchain_task')) {
            return false; // taskid not available - shouldn't happen !!
        }

        // add new record
        if (! $newid = $DB->insert_record('taskchain_task_attempts', $data)) {
            return false; // could not add new record - shouldn't happen !!
        }

        // store mapping from $oldid to $newid
        $this->set_mapping('taskchain_taskattempt', $oldid, $newid);

        // reset clickreportid to point to parent attempt
        if (empty($data->clickreportid) || $data->clickreportid==$oldid) {
            // clickreporting is not enabled (this is the usual case)
            $clickreportid = $newid;
        } else {
            // clickreporting is enabled, so get main attempt id
            $clickreportid = $this->get_mappingid('taskchain_taskattempt', $data->clickreportid);
        }
        if (empty($clickreportid)) {
            $clickreportid = $newid; // old attempt id not avialable - shouldn't happen !!
        }
        $DB->set_field('taskchain_task_attempts', 'clickreportid', $clickreportid, array('id' => $newid));
    }

    /**
     * process_taskchain_question
     *
     * @uses $DB
     * @param xxx $data
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function process_taskchain_question($data)   {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields
        if (! $data->taskid = $this->get_new_parentid('taskchain_task')) {
            return false; // taskid not available - shouldn't happen !!
        }
        $data->md5key = md5($data->name);
        $this->set_string_ids($data, array('text'), 0);

        // add new record
        if (! $newid = $DB->insert_record('taskchain_questions', $data)) {
            return false; // could not add new record - shouldn't happen !!
        }

        // store mapping from $oldid to $newid
        $this->set_mapping('taskchain_question', $oldid, $newid);
    }

    /**
     * process_taskchain_response
     *
     * @uses $DB
     * @param xxx $data
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function process_taskchain_response($data)   {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields
        if (! $data->questionid = $this->get_new_parentid('taskchain_question')) {
            return false; // questionid not available - shouldn't happen !!
        }
        if (! isset($data->attemptid)) {
            return false; // attemptid not set - shouldn't happen !!
        }
        if (! $data->attemptid = $this->get_mappingid('taskchain_taskattempt', $data->attemptid)) {
            return false; // new attemptid not available - shouldn't happen !!
        }
        $this->set_string_ids($data, array('correct', 'wrong', 'ignored'));

        // add new record
        if (! $newid = $DB->insert_record('taskchain_responses', $data)) {
            return false; // could not add new record - shouldn't happen !!
        }
    }

    /**
     * process_taskchain_string
     *
     * @uses $DB
     * @param xxx $data
     * @todo Finish documenting this function
     */
    protected function process_taskchain_string($data)   {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields
        $data->md5key = md5($data->string);

        // add new record, if necessary
        $params = array('md5key' => $data->md5key);
        if (! $newid = $DB->get_field('taskchain_strings', 'id', $params)) {
            if (! $newid = $DB->insert_record('taskchain_strings', $data)) {
                return false; // could not add new record - shouldn't happen !!
            }
        }

        // store mapping from $oldid to $newid
        $this->set_mapping('taskchain_string', $oldid, $newid);
    }

    /**
     * set_string_ids
     *
     * @param xxx $data (passed by reference)
     * @param xxx $fieldnames
     * @param xxx $default (optional, default='')
     * @todo Finish documenting this function
     */
    protected function set_string_ids(&$data, $fieldnames, $default='')  {

        foreach ($fieldnames as $fieldname) {

            $newids = array();
            if (isset($data->$fieldname)) {
                $oldids = explode(',', $data->$fieldname);
                $oldids = array_filter($oldids); // remove blanks
                foreach ($oldids as $oldid) {
                    if ($newid = $this->get_mappingid('taskchain_string', $oldid)) {
                        $newids[] = $newid;
                    } else {
                        // new string id not available - should we report it ?
                    }
                }
            }

            if (count($newids)) {
                $data->$fieldname = implode(',', $newids);
            } else {
                $data->$fieldname = $default;
            }
        }
    }

    /**
     * after_execute
     *
     * @uses $DB
     */
    protected function after_execute()  {
        global $DB;

        // restore files
        $this->add_related_files('mod_taskchain', 'sourcefile', null);
        $this->add_related_files('mod_taskchain', 'configfile', null);
        $this->add_related_files('mod_taskchain', 'entrytext',  null);
        $this->add_related_files('mod_taskchain', 'exittext',   null);

        // get most recently restored taskchain chain record (there should only be one)
        $params = array('parenttype' => 0, 'parentid' => $this->task->get_activityid());
        if (! $chain = $DB->get_record('taskchain_chains', $params)) {
            return false; // shouldn;t happen !!
        }

        // remap $chain->entrycm and $chain->exitcm
        $keys = array('entrycm' => 'course_module', 'exitcm' => 'course_module');
        $this->after_execute_foreignkeys($chain, 'taskchain_chains', $keys);

        // select all recently restored conditions
        $select = 'taskid IN (SELECT id FROM {taskchain_tasks} WHERE chainid = ?)';
        $rs = $DB->get_recordset_select('taskchain_conditions', $select, array($chain->id));

        // remap all $condition->conditiontaskid and $condition->nexttaskid
        $keys = array('conditiontaskid' => 'taskchain_task', 'nexttaskid' => 'taskchain_task');
        foreach ($rs as $condition) {
            $this->after_execute_foreignkeys($condition, 'taskchain_conditions', $keys);
        }
        $rs->close();
    }

    /**
     * after_execute_foreignkeys
     *
     * @uses $DB
     * @param object $record (passed by reference)
     * @param string $tablename table from which $record was extracted
     * @param array $keys map record $field => $itemname
     * @return void, but may update $record and DB tables
     * @todo Finish documenting this function
     */
    protected function after_execute_foreignkeys(&$record, $table, $keys, $default=0)  {
        global $DB;
        $update = false;
        foreach ($keys as $field => $itemname) {
            if ($record->$field > 0) {
                $record->$field = $this->get_mappingid($itemname, $record->$field);
                if ($record->$field===false || $record->$field===null) {
                    $record->$field = $default; // shouldn't happen !!
                }
                $update = true;
            }
        }
        if ($update) {
            $DB->update_record($table, $record);
        }
    }
}
