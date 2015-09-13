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
 * mod/taskchain/form/tasks.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/** Include required files */
require_once($CFG->dirroot.'/mod/taskchain/edit/form/helper/records.php');

/**
 * taskchain_form_helper_tasks
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_form_helper_tasks extends taskchain_form_helper_records {

    protected $recordtype = 'chain';
    protected $recordstype = 'task';

    protected $actions = array(
        // $value       => $require_records
        'reordertasks'  => true,
        'addtasks'      => false,
        'movetasks'     => true,
        'applydefaults' => true,
        'deletetasks'   => true
    );

    protected $sortfield = array('sortorder', 'name', 'sourcefile', 'timeopen', 'timeclose', 'scorelimit', 'scoreweighting');

    /**
     * add_action_movetasks
     *
     * @todo Finish documenting this function
     */
    protected function add_action_movetasks($action, $name) {
        $records = $this->get_live_records();
        if (count($records) > 1) {
            $showfield = true;
        } else {
            $showfield = false;
            if ($mycourses = $this->TC->get_mycourses()) {
                if ($mytaskchains = $this->TC->get_mytaskchains()) {
                    $courseid = 0;
                    $coursename = '';
                    foreach ($mytaskchains as $mytaskchain) {
                        if ($mytaskchain->id==$this->TC->taskchain->id) {
                            continue; // skip current taskchain
                        }
                        $showfield = true;
                        break;
                    }
                }
            }
        }
        if ($showfield) {
            $this->mform->addElement('radio', $name, '', get_string($action, 'mod_taskchain'), $action);
        }
    }

    /**
     * add_action_reordertasks_details
     *
     * @todo Finish documenting this function
     */
    protected function add_action_reordertasks_details() {
        $nameprefix = 'reordertasks_';
        $this->add_field_sortfield('sortfield', $nameprefix);
        $this->add_field_sortdirection('sortdirection', $nameprefix);
        $this->add_field_sortincrement('sortincrement', $nameprefix);
    }

    /**
     * add_action_addtasks_details
     *
     * @todo Finish documenting this function
     */
    protected function add_action_addtasks_details() {
        $this->add_actiontemplate_tasks('add');
    }

    /**
     * add_action_movetasks_details
     *
     * @todo Finish documenting this function
     */
    protected function add_action_movetasks_details() {
        $this->add_actiontemplate_tasks('move', 2, 1);
    }

    /**
     * add_action_applydefaults_details
     *
     * @todo Finish documenting this function
     */
    protected function add_action_applydefaults_details() {
        $field = 'applydefaults';
        $name  = $this->get_fieldname($field);
        $label = ''; // $this->get_fieldname($field);

        $this->mform->addElement('radio', $name, '', get_string('selectedtasks', 'mod_taskchain'), 'selectedtasks');
        $this->mform->addElement('radio', $name, '', get_string('filteredtasks', 'mod_taskchain'), 'filteredtasks');
        $this->mform->setType($name, PARAM_ALPHA);
        $this->mform->setDefault($name, 'selectedtasks');
        $this->mform->disabledIf($name, 'action', 'ne', $field);

        $filterlist = array(
            self::FILTER_CONTAINS       => get_string('contains',       'filters'),
            self::FILTER_NOT_CONTAINS   => get_string('doesnotcontain', 'filters'),
            self::FILTER_EQUALS         => get_string('isequalto',      'filters'),
            self::FILTER_NOT_EQUALS     => get_string('notisequalto',   'mod_taskchain'),
            self::FILTER_STARTSWITH     => get_string('startswith',     'filters'),
            self::FILTER_NOT_STARTSWITH => get_string('notstartswith',  'mod_taskchain'),
            self::FILTER_ENDSWITH       => get_string('endswith',       'filters'),
            self::FILTER_NOT_ENDSWITH   => get_string('notendswith',    'mod_taskchain'),
            self::FILTER_EMPTY          => get_string('isempty',        'filters'),
            self::FILTER_NOT_EMPTY      => get_string('notisempty',     'mod_taskchain')
        );
        $filters = array(
            'coursename', 'activityname', 'taskname', 'taskposition', 'sourcefile', 'sourcetype'
        );
        foreach ($filters as $filter) {
            $filtername = 'filter'.$filter;
            $filterlabel = $this->get_fieldlabel($filter);
            $name_filter = $this->get_fieldname($field.'_'.$filtername);
            $name_disabled = ''; // may be set below

            if ($filter=='coursename') {
                $courseid = $this->TC->get_courseid();
                if ($mycourses = $this->TC->get_mycourses()) {
                    $list = array();
                    if (count($mycourses)>1) {
                        $list[0] = get_string('all');
                    }
                    foreach ($mycourses as $mycourse) {
                        $shortname = format_string($mycourse->shortname);
                        if ($mycourse->id==SITEID) {
                            $shortname = get_string('frontpage', 'admin').': '.$shortname;
                        }
                        $list[$mycourse->id] = $shortname;
                    }
                } else {
                    $list = array($courseid => format_string($this->TC->course->shortname));
                }
                $this->mform->addElement('select', $name_filter, $filterlabel, $list);
                $this->mform->setDefault($name_filter, $courseid);
                $this->mform->setType($name_filter, PARAM_INT);
                $name_disabled = $name_filter;

            } else if ($filter=='taskposition') {

                $filtername = 'filter'.$filter;
                $filterlabel = $this->get_fieldlabel($filter);
                $name_filter = $this->get_fieldname($field.'_'.$filtername);
                $list = array(self::FILTER_POSITION_ANY   => get_string('any'),
                              self::FILTER_POSITION_START => get_string('startofchain', 'mod_taskchain'),
                              self::FILTER_POSITION_END   => get_string('endofchain',   'mod_taskchain'));
                $this->mform->addElement('select', $name_filter, $filterlabel, $list);
                $this->mform->setDefault($name_filter, self::FILTER_POSITION_ANY);
                $this->mform->setType($name_filter, PARAM_INT);
                $name_disabled = $name_filter;

            } else {

                $name_type   = $this->get_fieldname($field.'_'.$filtername.'type');
                $name_value  = $this->get_fieldname($field.'_'.$filtername.'value');
                $name_elements = $this->get_fieldname($field.'_'.$filtername.'_elements');

                $elements = array();
                $elements[] = $this->mform->createElement('select', $name_type,   '', $filterlist);
                $elements[] = $this->mform->createElement('text',   $name_value,  '', array('size', $this->text_field_size));
                $this->mform->addGroup($elements, $name_elements, $filterlabel, ' ', false);

                $this->mform->setType($name_type, PARAM_INT);
                $this->mform->setType($name_value, PARAM_ALPHAEXT);

                $name_disabled = $name_elements;
            }

            if ($name_disabled) {
                $this->mform->disabledIf($name_disabled, 'action', 'ne', $field);
                $this->mform->disabledIf($name_disabled, $name, 'ne', 'filteredtasks');
            }
        }
    }

    /**
     * add_actiontemplate_tasks
     *
     * @param string  $type
     * @param integer $min_record_count (optional, default=0)
     * @param integer $min_taskchain_count (optional, default=0)
     * @todo Finish documenting this function
     */
    protected function add_actiontemplate_tasks($type, $min_record_count=0, $min_taskchain_count=0) {
        $field = $type.'tasks'; // "addtasks" or "movetasks"
        $name = $this->get_fieldname($field);

        // by default we do not add any fields here
        // if any fields are added, set this to TRUE
        $added = false;
        $defaultvalue = 'start';

        // get list of tasks (if any)
        $list = array();
        foreach ($this->get_live_records() as $recordid => $record) {
            $recordname = $record->get_fieldvalue('name');
            $list[$recordid] = $this->format_longtext($recordname, 70, 32, 32);
        }
        $count = count($list);

        // add "startofchain" if required
        if ($count >= $min_record_count) {
            $added = true;
            $text = get_string('startofchain', 'mod_taskchain');
            $this->mform->addElement('radio', $name, '', $text, 'start');
        }

        // add "aftertaskid" (as a group) if required
        if ($count >= $min_record_count && $count > 1) {
            $added = true;
            $elements = array();

            $text = get_string('aftertaskid',  'mod_taskchain');
            $elements[] = $this->mform->createElement('radio', $name, '', $text, 'after');

            $name_taskid = $this->get_fieldname($field.'_taskid');
            $elements[] = $this->mform->createElement('select', $name_taskid, '', $list);

            $name_elements = $this->get_fieldname($field.'after_elements');
            $this->mform->addGroup($elements, $name_elements, '', ' ', false);

            $this->mform->setType($name_taskid, PARAM_INT);
            $this->mform->setDefault($name_taskid, $recordid); // end($list); key($list);
            $this->mform->disabledIf($name_taskid, 'action', 'ne', $field);
            $defaultvalue = 'after';
        }

        // add "endofchain", if required
        if ($count >= $min_record_count && $count==1) {
            $added = true;
            $text = get_string('endofchain', 'mod_taskchain');
            $this->mform->addElement('radio', $name, '', $text, 'end');
            $defaultvalue = 'end';
        }

        // get list of taskchains, if required ("movetasks" only)
        $list = array();
        if ($min_taskchain_count) {
            if ($mycourses = $this->TC->get_mycourses()) {
                if ($mytaskchains = $this->TC->get_mytaskchains()) {
                    $courseid = 0;
                    $coursename = '';
                    foreach ($mytaskchains as $mytaskchain) {
                        if ($mytaskchain->id==$this->TC->taskchain->id) {
                            continue; // skip current taskchain
                        }
                        if ($courseid==$mytaskchain->course) {
                            // do nothing - same course as previous taskchain
                        } else {
                            $courseid = $mytaskchain->course;
                            $coursename = format_string($mycourses[$courseid]->shortname);
                            $list[$coursename] = array();
                        }
                        $list[$coursename][$mytaskchain->id] = $this->format_longtext($mytaskchain->name);
                    }
                }
            }
        }

        // display list of taskchains, if any
        if ($min_taskchain_count && count($list) >= $min_taskchain_count) {
            $added = true;
            $elements = array();

            $text = get_string('chain', 'mod_taskchain');
            $elements[] = $this->mform->createElement('radio', $name, '', $text, 'mytaskchain');

            $name_taskchainid = $this->get_fieldname($field.'_taskchainid');
            $elements[] = $this->mform->createElement('selectgroups', $name_taskchainid, '', $list);

            $name_elements = $this->get_fieldname($field.'chain_elements');
            $this->mform->addGroup($elements, $name_elements, '', ' ', false);

            $this->mform->setType($name_taskchainid, PARAM_INT);
        }

        if ($added) {
            $this->mform->setType($name, PARAM_ALPHA);
            $this->mform->setDefault($name, $defaultvalue);
            $this->mform->disabledIf($name, 'action', 'ne', $field);
        }
    }

    /**
     * fix_action_reordertasks
     *
     * @param object $data the recently submitted form $data
     * @todo Finish documenting this function
     */
    protected function fix_action_reordertasks(&$data) {
        $nameprefix = 'reordertasks_';

        $name = $nameprefix.'sortdirection';
        if (empty($data->$name) || $data->$name=='asc') {
            $sortdirection = 'asc';
        } else {
            $sortdirection = 'desc';
        }

        $name = $nameprefix.'sortfield';
        if (empty($data->$name) || $data->$name=='sortorder') {
            $sortorder = $data->sortorder;
        } else {
            $field = $data->$name;
            $sortorder = array();
            $records = $this->get_live_records();
            foreach ($records as $record) {
                $id = $record->get_fieldvalue('id');
                $value = $record->get_fieldvalue($field);
                $sortorder[$id] = $value;
            }
        }
        if ($sortdirection=='asc') {
            asort($sortorder);
        } else {
            arsort($sortorder);
        }
        $this->fix_action_reorder_records($data, 'sortorder', array_keys($sortorder), $sortdirection);
    }

    /**
     * fix_action_reorder_records
     *
     * @param object $data (passed by reference) the recently submitted form $data
     * @param string the name of the $data->$field that contains that sorted recordids
     * @todo Finish documenting this function
     */
    protected function fix_action_reorder_records(&$data, $field, $ids, $sortdirection='asc') {
        $updated = array();
        $update_form = false;

        $sortorder = 0;

        $name = 'reorder'.$this->recordstype.'s_sortincrement';
        $sortincrement = (empty($data->$name) ? self::SORT_INCREMENT : $data->$name);

        foreach ($ids as $id) {
            if (array_key_exists($id, $this->records)) {
                $sortorder += $sortincrement;
                if ($data->{$field}[$id] != $sortorder) {
                    $data->{$field}[$id] = $sortorder;
                    $update_form = true;
                }
                if ($this->records[$id]->get_fieldvalue($field) != $sortorder) {
                    $this->records[$id]->set_fieldvalue($field, $sortorder);
                    $updated[$id] = true;
                }
            }
        }
        if (count($updated)) {
            $updated = array_keys($updated);
            $this->update_records($updated);
            $this->sort_records($field, $sortdirection);
            $update_form = true;
        }
        if ($update_form) {
            asort($data->$field);
            $recordids = array_keys($data->$field);
            $this->sort_form_elements($recordids);
        }
    }

    /**
     * fix_action_applydefaults_extra
     *
     * @param xxx $data     (passed by reference)
     * @param xxx $records  (passed by reference)
     * @param xxx $defaults (passed by reference)
     * @param xxx $updated  (passed by reference)
     * @todo Finish documenting this function
     */
    protected function fix_action_applydefaults_extra(&$data, &$records, &$defaults, &$updated) {
        $fields = array(
            'preconditions'  => mod_taskchain::CONDITIONTYPE_PRE,
            'postconditions' => mod_taskchain::CONDITIONTYPE_POST
        );
        foreach ($fields as $field => $type) {
            if (isset($defaults[$field])) {
                $this->fix_action_applydefaults_conditions($field, $type, $defaults[$field], $records, $updated);
            }
        }
    }

    /**
     * set_fieldvalue_conditions
     *
     * @param integer $field ("preconditions" or "postconditions")
     * @param integer $type (1=PRE or 2=POST)
     * @param integer $targetid id of task with default conditions
     * @param xxx $records  (passed by reference)
     * @param xxx $updated  (passed by reference)
     * @todo Finish documenting this function
     */
    protected function fix_action_applydefaults_conditions($field, $type, $targetid, &$records, &$updated) {
        global $DB;

        if (empty($records)) {
            return false;
        }

        // get all record ids (not including the target id)
        $recordids = array_keys($records);
        $recordids = preg_grep('/^'.$targetid.'$/', $recordids, PREG_GREP_INVERT);

        if (empty($recordids)) {
            return false;
        }

        // prepare SQL select filter
        list($select, $params) = $DB->get_in_or_equal($recordids);
        $select = 'taskid '.$select.' AND conditiontype = ?';
        $params[] = $type;

        if ($conditionids = $DB->get_records_select('taskchain_conditions', $select, $params, 'id', 'id')) {
            $conditionids = array_keys($conditionids);
        } else {
            $conditionids = array();
        }

        // get new conditions, if necessary
        $params = array('taskid' => $targetid, 'conditiontype' => $type);
        if ($conditions = $DB->get_records('taskchain_conditions', $params, 'sortorder')) {
            // conditions were found - do nothing
        } else {
            $conditions = array();
        }

        // update/add conditions as necessary
        foreach ($recordids as $recordid) {
            foreach ($conditions as $condition) {
                $condition->taskid = $recordid;
                if ($condition->id = array_shift($conditionids)) {
                    if (! $DB->update_record('taskchain_conditions', $condition)) {
                        print_error('error_updaterecord', 'taskchain', '', "taskchain_conditions (id=$condition->id)");
                    }
                } else {
                    unset($condition->id);
                    if (! $condition->id = $DB->insert_record('taskchain_conditions', $condition)) {
                        print_error('error_insertrecord', 'taskchain', '', 'taskchain_conditions');
                    }
                }
            }
        }

        // remove any superfluous conditions
        if (count($conditionids)) {
            $DB->delete_records_list('taskchain_conditions', 'id', $conditionids);
        }

        // update default conditions
        $name = $field.'_elements[0]';
        if ($this->mform->elementExists($name) && array_key_exists($targetid, $this->records)) {
            $record = &$this->records[$targetid];
            $value = $record->format_conditions($targetid, $type, false, false, false);
            $this->mform->getElement($name)->setValue($value);
            unset($record);
        }

        // update conditions of each record in form, if necessary
        foreach ($recordids as $recordid) {

            // update form field if this is one the current tasks
            if (array_key_exists($recordid, $this->records)) {
                $record = &$this->records[$recordid];

                // clear cache for this task's conditions
                // if we don't do this here, the "format_conditions"
                // method will use the conditions cached earlier
                // and will not include the newly copied conditions

                if ($type==mod_taskchain::CONDITIONTYPE_PRE) {
                    unset($this->TC->cache_preconditions[$recordid]);
                } else {
                    unset($this->TC->cache_postconditions[$recordid]);
                }

                // get form element name and check it exists
                $name = $record->get_fieldname($field.'_elements');
                if ($this->mform->elementExists($name)) {
                    $value = $record->format_conditions($recordid, $type, false);
                    $this->mform->getElement($name)->setValue($value);
                }

                // release $record reference
                unset($record);
            }
        }
    }

    /**
     * get_filter_sql
     *
     * @return array $ids of records to be selected
     * @return array ($select, $from, $where, $params) to be passed to $DB->get_records_sql()
     * @todo Finish documenting this function
     */
    protected function get_filter_sql($ids) {
        global $DB;
        list($where, $params) = $DB->get_in_or_equal($ids);
        $select = 'tc_tsk.*';
        $from   = "{taskchain} tc,".
                  "{taskchain_chains} tc_chn,".
                  "{taskchain_tasks} tc_tsk";
        $where  = 'tc.id '.$where.' '.
                  'AND tc.id = tc_chn.parentid '.
                  'AND tc_chn.parenttype = ? '.
                  'AND tc_chn.id = tc_tsk.chainid';
        $params[] = mod_taskchain::PARENTTYPE_ACTIVITY;
        return array($select, $from, $where, $params);
    }

    /**
     * get_filter_params
     *
     * @return array ($formfield => $dbfield) of params to pass to $this->get_filter()
     * @todo Finish documenting this function
     */
    function get_filter_params($field) {
        return array($field.'_filteractivityname' => 'tc.name',
                     $field.'_filtertaskname'     => 'tc_tsk.name',
                     $field.'_filtersourcetype'   => 'tc_tsk.sourcetype',
                     $field.'_filtersourcefile'   => 'tc_tsk.sourcefile');
    }

    /**
     * fix_action_movetasks
     *
     * @param object $data the recently submitted form $data
     * @todo Finish documenting this function
     */
    protected function fix_action_movetasks(&$data) {

        $type        = $this->recordstype;        // e.g. task
        $types       = $type.'s';                 // e.g. tasks
        $movetypes   = 'move'.$types;             // e.g. movetasks
        $moveafterid = $movetypes.'_'.$type.'id'; // e.g. movetasks_taskid

        if (empty($data->$movetypes)) {
            return;
        }

        $targetids = $this->get_selected_records($data, false);
        $targetids = array_values($targetids);
        if (empty($targetids)) {
            return;
        }

        $recordids = array_keys($this->records);
        $recordids = array_diff($recordids, $targetids);
        $recordids = array_values($recordids);

        switch ($data->$movetypes) {

            case 'start' : $recordids = array_merge($targetids, $recordids); break;
            case 'end'   : $recordids = array_merge($recordids, $targetids); break;

            case 'after':
                if (empty($data->$moveafterid)) {
                    return;
                }
                $i = array_search($data->$moveafterid, $recordids);
                if ($i===false) {
                    return;
                }
                $recordids = array_merge(array_slice($recordids, 0, ($i+1)), $targetids, array_slice($recordids, ($i+1)));
                break;

            case 'mytaskchain':
                $ok = false;
                if ($taskchainid = optional_param('movetaskstaskchainid', 0, PARAM_INT)) {
                    if ($TC->get_mytaskchains() && array_key_exists($taskchainid, $TC->mytaskchains)) {
                        $ok = true;
                    }
                }
                if (! $ok) {
                    $TC->print_error(get_string('error_getrecord', 'mod_taskchain', "taskchain (id=$taskchainid)"));
                }
                if (! $chainid = $DB->get_field('taskchain_chains', 'id', array('parenttype'=>TASKCHAIN_PARENTTYPE_ACTIVITY, 'parentid'=>$taskchainid))) {
                    $TC->print_error(get_string('error_getrecord', 'mod_taskchain', "taskchain_chains (parentid=$taskchainid)"));
                }
                $tables = ''
                    ."{taskchain_chains} qu,"
                    ."{taskchain_tasks} qq"
                ;
                $select = ''
                    .'qu.parentid = '.$taskchainid.' '
                    .'AND qu.parenttype = '.TASKCHAIN_PARENTTYPE_ACTIVITY.' '
                    .'AND qu.id = qq.chainid'
                ;
                $sortorder = $DB->count_records_sql(
                    "SELECT MAX(qq.sortorder) FROM $tables WHERE $select"
                );
                foreach ($targetids as $recordid) {
                    $sortorder += $TC->sortincrement;
                    $TC->tasks[$recordid]->sortorder = $sortorder;
                    $TC->tasks[$recordid]->chainid = $chainid;
                    if (! $DB->update_record('taskchain_tasks', $TC->tasks[$recordid])) {
                        print_error('error_updaterecord', 'taskchain', '', 'taskchain_tasks');
                    }
                    unset($TC->tasks[$recordid]);
                }
                break;

        } // end switch $movetasks

        // sort the records in the current TaskChain into their new order
        $this->fix_action_reorder_records($data, 'sortorder', $recordids);
    }

    /**
     * fix_action_deletetasks
     *
     * @param object $data the recently submitted form $data
     * @todo Finish documenting this function
     */
    protected function fix_action_deletetasks(&$data) {
        $ids = $this->get_selected_records($data, false);
        if (count($ids)) {
            $this->delete_records($ids);
        }
    }
}
