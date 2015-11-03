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
 * mod/taskchain/lib.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information if the module supports a feature
 *
 * the very latest Moodle 2.x expects "mod_taskchain_supports"
 * but since this module may also be run in early Moodle 2.x
 * we leave this function with its legacy name "taskchain_supports"
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @see init_features() in course/moodleform_mod.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/**
 * taskchain_supports
 *
 * @param  string   $feature (see "lib/moodlelib.php")
 * @return boolean  TRUE this module supports $feature, otherwise FALSE
 * @todo Finish documenting this function
 */
function taskchain_supports($feature) {
    $constants = array(
        'FEATURE_ADVANCED_GRADING' => false,
        'FEATURE_BACKUP_MOODLE2'   => true, // default=false
        'FEATURE_COMMENT'          => true,
        'FEATURE_COMPLETION_HAS_RULES' => true,
        'FEATURE_COMPLETION_TRACKS_VIEWS' => true,
        'FEATURE_CONTROLS_GRADE_VISIBILITY' => false,
        'FEATURE_GRADE_HAS_GRADE'  => true, // default=false
        'FEATURE_GRADE_OUTCOMES'   => true,
        'FEATURE_GROUPINGS'        => true, // default=false
        'FEATURE_GROUPMEMBERSONLY' => true, // default=false
        'FEATURE_GROUPS'           => true,
        'FEATURE_IDNUMBER'         => true,
        'FEATURE_MOD_ARCHETYPE'    => MOD_ARCHETYPE_OTHER,
        'FEATURE_MOD_INTRO'        => false, // default=true
        'FEATURE_MODEDIT_DEFAULT_COMPLETION' => true,
        'FEATURE_NO_VIEW_LINK'     => false,
        'FEATURE_PLAGIARISM'       => false,
        'FEATURE_RATE'             => false,
        'FEATURE_SHOW_DESCRIPTION' => true, // default=false (Moodle 2.2)
        'FEATURE_USES_QUESTIONS'   => false,
    );
    foreach ($constants as $constant => $value) {
        if (defined($constant) && $feature==constant($constant)) {
            return $value;
        }
    }
    return false;
}

/**
 * Saves a new instance of the taskchain into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will save a new instance and return the id number
 * of the new instance.
 *
 * @uses $CFG
 * @uses $DB
 * @param object $data An object from the form in mod_form.php
 * @param xxx $mform
 * @return int The id of the newly inserted taskchain record
 * @todo Finish documenting this function
 */
function taskchain_add_instance(stdclass $data, $mform) {
    return taskchain_process_formdata($data, $mform);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdclass $data An object from the form in mod_form.php
 * @param xxx $mform
 * @return bool success
 * @todo Finish documenting this function
 */
function taskchain_update_instance(stdclass $data, $mform) {
    global $DB;

    taskchain_process_formdata($data, $mform);

    $data->id = $data->instance;
    $DB->update_record('taskchain', $data);

    // update gradebook item
    if ($data->grademethod==$mform->get_originalvalue('grademethod', 0)) {
        taskchain_grade_item_update($data);
    } else {
        // recalculate grades for all users
        taskchain_update_grades($data);
    }

    return true;
}

/**
 * Set secondary fields (i.e. fields derived from the form fields)
 * for this TaskChain acitivity
 *
 * @param stdclass $data (passed by reference)
 * @param moodle_form $mform
 */
function taskchain_process_formdata(stdclass &$data, $mform) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

    // set time created/modified
    if (empty($data->instance)) {
        $data->timecreated = time();
    } else {
        $data->timemodified = time();
    }

    // fix up secondary fields
    $mform->data_postprocessing($data);

    // set default name for a TaskChain activity
    $defaultname = get_string('modulename', 'mod_taskchain');

    $update_gradebook = false;
    $time = time();

    $chaintable = 'taskchain_chains';
    $parenttable = '';

    switch (get_class($mform)) {

        case 'mod_taskchain_mod_form':

            $parenttype  = mod_taskchain::PARENTTYPE_ACTIVITY;
            $parenttable = 'taskchain';

            if (empty($data->instance)) {
                $parent = new stdClass();
                $parent->name        = $data->name;
                $parent->course      = $data->course;
                $parent->timecreated = $time;
            } else {
                if (! $parent = $DB->get_record($parenttable, array('id'=>$data->instance, 'course'=>$data->course))) {
                    return get_string('error_getrecord', 'mod_taskchain', $parenttable);
                }
                if ($parent->name != $data->name) {
                    $update_gradebook = true;
                }
            }

            $parent->timemodified = $time;
            break;

        case 'block_taskchain_edit_form':

            $parenttype  = mod_taskchain::PARENTTYPE_BLOCK;
            $parenttable = 'block_instance';

            $parent = new stdClass();
            $parent->blockid    = $data->blockid;
            $parent->pageid     = $data->course;
            $parent->pagetype   = PAGE_COURSE_VIEW;
            $parent->position   = $data->position; // BLOCK_POS_LEFT or BLOCK_POS_RIGHT
            $parent->weight     = $data->weight;
            $parent->visible    = $data->visible;
            $parent->configdata = $data->configdata;

            break;

        default:
            return get_string('error_invalidparenttype', 'mod_taskchain', $parenttype);

    } // end switch

    if (empty($data->instance)) {
        // add parent
        if (! $parent->id = $DB->insert_record($parenttable, $parent)) {
            return get_string('error_insertrecord', 'mod_taskchain', $parenttable);
        }
        // force creation of new chain
        $chain = false;
    } else {
        // update parent
        $parent->id = $data->instance;
        if (! $DB->update_record($parenttable, $parent)) {
            return get_string('error_updaterecord', 'mod_taskchain', $parenttable);
        }
        // get associated chain record
        $chain = $DB->get_record('taskchain_chains', array('parenttype'=>$parenttype, 'parentid'=>$parent->id));
    }

    // set flags to regrade chain and/or update grades
    $regrade_chainattempts = false;
    $regrade_chaingrades = false;
    if ($chain) {
        if ($chain->attemptgrademethod != $data->attemptgrademethod) {
            $regrade_chainattempts = true;
            $regrade_chaingrades = true;
        }
        if ($chain->grademethod != $data->grademethod || $chain->gradeignore != $data->gradeignore) {
            $regrade_chaingrades = true;
        }
        if ($chain->gradelimit != $data->gradelimit || $chain->gradeweighting != $data->gradeweighting) {
            $regrade_chaingrades = true;
        }
    } else {
        // start a new chain
        $chain = new stdClass();
        $chain->parenttype = $parenttype;
        $chain->parentid   = $parent->id;
        $chain->entrytext  = '';
        $chain->exittext   = '';
    }

    // add/update chain fields

    // make sure there are no missing fields
    $fieldnames = mod_taskchain::user_preferences_fieldnames_chain();
    taskchain_set_missing_fields('taskchain_chains', $chain, $data, $fieldnames);

    // unset chain exit page if there are no options or text
    if (empty($chain->exitoptions) && empty($chain->exittext)) {
        $chain->exitpage = 0;
    }

    if (empty($chain->id)) {
        // add new chain
        if (! $chain->id = $DB->insert_record('taskchain_chains', $chain)) {
            return get_string('error_insertrecord', 'mod_taskchain', 'taskchain_chains');
        }
    } else {
        // set default name if necessary
        if (empty($data->name)) {
            $data->name = $defaultname;
        }

        // update existing chain record
        if (! $DB->update_record('taskchain_chains', $chain)) {
            return get_string('error_updaterecord', 'mod_taskchain', 'taskchain_chains');
        }
    }

    // transfer gradelimit and gradeweighting to parent
    // ( required later in "taskchain_get_user_grades()" )
    $parent->gradeweighting = $chain->gradeweighting;
    $parent->gradelimit = $chain->gradelimit;

    // save chain settings as preferences
    // taskchain_set_preferences('chain', $data);

    if (empty($data->instance)) {
        // add tasks, (may update $data->name too)
        taskchain_add_tasks($data, $mform, $chain);

        // set default name if necessary
        if (empty($data->name)) {
            $data->name = $defaultname;
        }

        if ($parent->name != $data->name) {
            if (! $DB->set_field($parenttable, 'name', $data->name, array('id'=>$chain->id))) {
                return get_string('error_updaterecord', 'mod_taskchain', $parenttable);
            }
            $parent->name = $data->name;
        }

        if ($chain->entrytext != $data->entrytext) {
            if (! $DB->set_field($chaintable, 'entrytext', $data->entrytext, array('id'=>$chain->id))) {
                return get_string('error_updaterecord', 'mod_taskchain', $chaintable);
            }
            $chain->entrytext = $data->entrytext;
        }

        if ($chain->exittext != $data->exittext) {
            if (! $DB->set_field($chaintable, 'exittext', $data->exittext, array('id'=>$chain->id))) {
                return get_string('error_updaterecord', 'mod_taskchain', $chaintable);
            }
            $chain->exittext = $data->exittext;
        }

        if ($parenttype==mod_taskchain::PARENTTYPE_ACTIVITY) {
            // add grade item to Moodle gradebook
            taskchain_grade_item_update($parent);
        }
    } else {
        // updating a TaskChain
        if ($regrade_chaingrades) {
            $TC = new mod_taskchain();

            // regrade chain attempts
            if ($regrade_chainattempts) {
                if ($records = $DB->get_records('taskchain_chain_attempts', array('chainid'=>$chain->id), '', 'id,cnumber,userid')) {
                    foreach ($records as $record) {
                        $TC->regrade_chainattempt($chain, $record->cnumber, $record->userid);
                    }
                }
                unset($records);
            }

            // regrade chain grades
            if ($records = $DB->get_records('taskchain_chain_grades', array('parenttype'=>$chain->parenttype, 'parentid'=>$chain->parentid), '', 'id,userid')) {
                foreach ($records as $record) {
                    $TC->regrade_attempts('chain', $chain, 0, $record->userid);
                }
            }
            unset($TC, $records);
            $update_gradebook = true;
        }
        if ($update_gradebook && $parenttype==mod_taskchain::PARENTTYPE_ACTIVITY) {
            // update Moodle gradebook
            taskchain_update_grades($parent);
        }
    }

    // get old event ids so they can be reused
    if ($eventids = $DB->get_records_select('event', "modulename='$parenttable' AND instance=$parent->id", null, 'id', 'id')) {
        $eventids = array_keys($eventids);
    } else {
        $eventids = array();
    }

    // add / update calendar events, if necessary
    taskchain_update_events($parent, $chain, $eventids, true);

    return $parent->id;
}

/**
 * add tasks for a newly created TaskChain activity
 *
 * @uses $CFG
 * @uses $DB
 * @param xxx $data (passed by reference)
 * @param xxx $chain (passed by reference)
 * @param xxx $aftertaskid (optional)
 * @return void (but may update $data)
 */
function taskchain_add_tasks(&$data, &$mform, &$chain, $aftertaskid=0) {
    global $CFG, $DB;

    if (empty($CFG->formatstringstriptags)) {
        $PARAM = PARAM_CLEAN;
    } else {
        $PARAM = PARAM_TEXT;
    }

    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');
    require_once($CFG->dirroot.'/mod/taskchain/source/class.php');

    $sortorder = 0;
    $taskids = array();
    if ($tasks = $DB->get_records('taskchain_tasks', array('chainid'=>$chain->id), 'sortorder', 'id,sortorder')) {
        foreach ($tasks as $task) {
            $sortorder = $task->sortorder;
            $taskids[] = $task->id;
        }
        unset($tasks);
    }
    $sortorder++;

    if (! isset($data->addtype)) {
        throw new moodle_exception('$data->addtype is not set');
    }

    $context = $mform->get_context();
    $sources = taskchain_source::get_sources($data, $context, 'mod_taskchain', 'sourcefile', $data->addtype);
    $config  = taskchain_source::get_config($data, $context, 'mod_taskchain', 'configfile');

    // set default task name and, if necessary, chain text fields
    $taskname = '';
    if (isset($data->tasknamessource)) { // adding a TaskChain activity
        $fields = array('name', 'entrytext', 'exittext');
        foreach ($fields as $field) {
            $chainfield = 'chain'.$field;
            $fieldsource = $field.'source';
            if (isset($data->$fieldsource) && isset($data->$chainfield)) {
                if ($data->$fieldsource==mod_taskchain::TEXTSOURCE_FILE || $data->$fieldsource==mod_taskchain::TEXTSOURCE_FILEPATH || $data->$fieldsource==mod_taskchain::TEXTSOURCE_FILENAME) {
                    $data->$field = clean_param($data->$chainfield, $PARAM);
                }
            }
            if (empty($data->$field)) {
                $data->$field = '';
            }
        }
        $tasknamessource = $data->tasknamessource;
        if (isset($data->taskname)) {
            $taskname = trim($data->taskname);
        }
    } else {
        $tasknamessource = $data->namesource;
        if (empty($data->name)) {
            $data->name = '';
        } else {
            $taskname = trim($data->name);
        }
    }
    if ($taskname=='') {
        $taskname = get_string('task', 'mod_taskchain');
    }
    $taskname = clean_param($taskname, $PARAM);

    $newtaskids = array();

    if (empty($sources)) {
        $sources = array();
    }

    foreach ($sources as $source) {

        $task = new stdClass();
        $task->chainid = $chain->id;

        // set task name
        switch ($tasknamessource) {
            case mod_taskchain::TEXTSOURCE_FILE:
                if (! $task->name = $source->get_name()) {
                    $task->name = get_string('task', 'mod_taskchain')." ($sortorder)";
                }
                $is_clean_name = false;
                break;
            case mod_taskchain::TEXTSOURCE_FILENAME:
                $task->name = clean_param(basename($source->filepath), PARAM_FILE);
                $is_clean_name = true;
                break;
            case mod_taskchain::TEXTSOURCE_FILEPATH:
                $task->name = str_replace('/', ' ', clean_param($source->filepath, PARAM_PATH));
                $is_clean_name = true;
                break;
            case mod_taskchain::TEXTSOURCE_SPECIFIC:
            default:
                $task->name = '';
                $is_clean_name = true;
        }

        if ($task->name=='') {
            // $taskname has already been cleaned
            $task->name = $taskname." ($sortorder)";
        } else if ($is_clean_name) {
            // task name is already clean
        } else {
            $task->name = clean_param($task->name, $PARAM);
        }

        // set source/config file type, path and location
        $task->sourcetype     = $source->get_type();
        if ($source->location==mod_taskchain::LOCATION_WWW) {
            $task->sourcefile = $source->url;
        } else {
            $task->sourcefile = $source->filepath;
        }
        $task->sourcelocation = $source->location;

        if ($config->location==mod_taskchain::LOCATION_WWW) {
            $task->configfile = $config->url;
        } else {
            $task->configfile = $config->filepath;
        }
        $task->configlocation = $config->location;

        // set default field values (for this teacher)
        $task->outputformat = get_user_preferences('taskchain_task_outputformat_'.$task->sourcetype, '');
        $task->navigation   = get_user_preferences('taskchain_task_navigation', mod_taskchain::NAVIGATION_MOODLE);
        $task->title        = get_user_preferences('taskchain_task_title', mod_taskchain::TEXTSOURCE_SPECIFIC);
        $task->stopbutton   = get_user_preferences('taskchain_task_stopbutton', mod_taskchain::STOPBUTTON_NONE);
        $task->stoptext     = get_user_preferences('taskchain_task_stoptext', '');
        $task->usefilters   = get_user_preferences('taskchain_task_usefilters', mod_taskchain::NO);
        $task->useglossary  = get_user_preferences('taskchain_task_useglossary', mod_taskchain::NO);
        $task->usemediafilter = get_user_preferences('taskchain_task_usemediafilter', '');
        $task->studentfeedback = get_user_preferences('taskchain_task_studentfeedback', mod_taskchain::FEEDBACK_NONE);
        $task->studentfeedbackurl = get_user_preferences('taskchain_task_studentfeedbackurl', '');
        $task->timeopen     = get_user_preferences('taskchain_task_timeopen', 0);
        $task->timeclose    = get_user_preferences('taskchain_task_timeclose', 0);
        $task->timelimit    = get_user_preferences('taskchain_task_timelimit', 0);
        $task->delay1       = get_user_preferences('taskchain_task_delay1', 0);
        $task->delay2       = get_user_preferences('taskchain_task_delay2', 0);
        $task->delay3       = get_user_preferences('taskchain_task_delay3', 2);
        $task->password     = get_user_preferences('taskchain_task_password', '');
        $task->subnet       = get_user_preferences('taskchain_task_subnet', '');
        $task->allowresume  = get_user_preferences('taskchain_task_allowresume', 0);
        $task->reviewoptions = get_user_preferences('taskchain_task_reviewoptions', 0);
        $task->attemptlimit = get_user_preferences('taskchain_task_attemptlimit', 0);
        $task->scoremethod  = get_user_preferences('taskchain_task_scoremethod', mod_taskchain::GRADEMETHOD_HIGHEST);
        $task->scoreignore  = get_user_preferences('taskchain_task_scoreignore', mod_taskchain::NO);
        $task->scorelimit   = get_user_preferences('taskchain_task_scorelimit', 100);
        $task->scoreweighting = get_user_preferences('taskchain_task_scoreweighting', 100);
        $task->sortorder    = $sortorder++;
        $task->clickreporting = get_user_preferences('taskchain_task_clickreporting', mod_taskchain::NO);
        $task->discarddetails = get_user_preferences('taskchain_task_discarddetails', mod_taskchain::NO);

        if (! $task->id = $DB->insert_record('taskchain_tasks', $task)) {
            print_error('error_insertrecord', 'taskchain', '', 'taskchain_tasks');
        }

        $newtaskids[] = $task->id;
    }

    switch ($aftertaskid) {
        case -1:
            // insert new tasks at start of chain
            $taskids = array_merge($newtaskids, $taskids);
            $reorder = true;
            break;
        case 0:
            // insert new tasks at end of chain
            $reorder = false;
            break;
        default:
            // insert new tasks after specific task
            if (($i = array_search($aftertaskid, $taskids))===false) {
                // $aftertaskid is invalid - shouldn't happen !!
                $reorder = false;
            } else {
                $taskids = array_merge(
                    array_slice($taskids, 0, ($i+1)), $newtaskids, array_slice($taskids, ($i+1))
                );
                $reorder = true;
            }
    }

    if ($reorder) {
        $sortorder = 0;
        foreach ($taskids as $taskid) {
            $sortorder++;
            $DB->set_field('taskchain_tasks', 'sortorder', $sortorder, array('id'=>$taskid));
        }
    }
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @uses $CFG
 * @uses $DB
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function taskchain_delete_instance($id) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/lib/gradelib.php');
    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

    // check the taskchain $id is valid
    if (! $taskchain = $DB->get_record('taskchain', array('id' => $id))) {
        return false;
    }

    // get associated $chain activity
    if (! $chain = $DB->get_record('taskchain_chains', array('parenttype' => mod_taskchain::PARENTTYPE_ACTIVITY, 'parentid' => $taskchain->id))) {
        return false;
    }

    // remove tasks data
    if ($taskids = $DB->get_records('taskchain_tasks', array('chainid' => $chain->id), '', 'id')) {
        $taskids = array_keys($taskids);
        taskchain_delete_tasks($taskids);
        unset($taskids);
    }

    // finally remove the chain data and taskchain record
    $DB->delete_records('taskchain_chain_attempts', array('chainid'  => $chain->id));
    $DB->delete_records('taskchain_chain_grades',   array('parentid' => $taskchain->id, 'parenttype' => mod_taskchain::PARENTTYPE_ACTIVITY));
    $DB->delete_records('taskchain_chains',         array('id'       => $chain->id));
    $DB->delete_records('taskchain',                array('id'       => $taskchain->id));

    // gradebook cleanup
    grade_update('mod/taskchain', $taskchain->course, 'mod', 'taskchain', $taskchain->id, 0, null, array('deleted' => true));

    return true;
}

/**
 * taskchain_delete_tasks
 *
 * @todo Finish documenting this function
 */
function taskchain_delete_tasks($taskids) {
    global $DB;

    if (is_string($taskids) || is_int($taskids)) {
        $taskids = array($taskids);
    }

    if ($attemptids = $DB->get_records_list('taskchain_task_attempts', 'taskid', $taskids, '', 'id')) {
        $attemptids = array_keys($attemptids);
        $DB->delete_records_list('taskchain_details',       'attemptid', $attemptids);
        $DB->delete_records_list('taskchain_responses',     'attemptid', $attemptids);
        $DB->delete_records_list('taskchain_task_attempts', 'id',        $attemptids);
        unset($attemptids);
    }
    $DB->delete_records_list('taskchain_cache',       'taskid', $taskids);
    $DB->delete_records_list('taskchain_conditions',  'taskid', $taskids); // conditiontaskid, nexttaskid
    $DB->delete_records_list('taskchain_questions',   'taskid', $taskids);
    $DB->delete_records_list('taskchain_task_scores', 'taskid', $taskids);
    $DB->delete_records_list('taskchain_tasks',       'id',     $taskids);
}

////////////////////////////////////////////////////////////////////////////////
// Course page links API                                                      //
////////////////////////////////////////////////////////////////////////////////

/*
* Given a course_module object, this function returns any
* "extra" information that may be needed when printing
* this activity in a course listing.
*
* This function is called from: {@link course/lib.php} in {@link get_array_of_activities()}
*
* @param object $cm information about this course module
*         $cm->cm       : id in the "course_modules" table
*         $cm->section  : the number of the course section (e.g. week or topic)
*         $cm->mod      : the name of the module (always "taskchain")
*         $cm->instance : id in the "taskchain" table
*         $cm->name     : the name of this taskchain
*         $cm->visible  : is the taskchain visible (=1) or hidden (=0)
*         $cm->extra    : ""
* @return object $info
*         $info->extra  : extra string to include in any link
*                 (e.g. target="_blank" or class="taskchain_completed")
*         $info->icon   : an icon for this course module
*                 allows a different icon for different subtypes of the module
*                 allows a different icon depending on the status of a taskchain
*/
function taskchain_get_coursemodule_info($cm) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

    if (! $taskchain = $DB->get_record('taskchain', array('id'=>$cm->instance))) {
        return false; // shouldn't happen !!
    }
    if (! $chain = $DB->get_record('taskchain_chains', array('parenttype'=>mod_taskchain::PARENTTYPE_ACTIVITY, 'parentid'=>$taskchain->id))) {
        return false; // shouldn't happen !!
    }

    $info = new cached_cm_info();
    $info->name = $taskchain->name;

    //$info->customdata =    ''; // 'id="taskchain-'.$cm->instance.'"'
    //$info->extraclasses =  '';
    //$info->onclick =       '';
    //$info->icon =          '';
    //$info->iconcomponent = '';
    //$info->iconurl =       '';

    if (isset($cm->showdescription) && $cm->showdescription) {
        $context = mod_taskchain::context(CONTEXT_MODULE, $cm->id); // Moodle 2.0 - 2.1
        // Note: "filter" must be set to false, so that filters are run only at display time.
        // Setting "filter" to true, will cause an infinite loop when recreating the course cache.
        $options = array('noclean' => true, 'para' => false, 'filter' => false, 'context' => $context, 'overflowdiv' => true);
        $entrytext = file_rewrite_pluginfile_urls($chain->entrytext, 'pluginfile.php', $context->id, 'mod_taskchain', 'entry', null);
        $info->content = trim(format_text($entrytext, $chain->entryformat, $options, null));
    }

    // create popup link, if necessary
    if ($chain->showpopup) {
        $fullurl = "$CFG->wwwroot/mod/taskchain/view.php?id=$cm->id&inpopup=1";
        $options = explode(',', $chain->popupoptions);
        $options = implode(',', preg_grep('/^MOODLE/', $options, PREG_GREP_INVERT));
        $info->onclick = "window.open('$fullurl', 'taskchain{$cm->instance}', '$options'); return false;";
    }

    return $info;
}

////////////////////////////////////////////////////////////////////////////////
// User activity reports API                                                  //
////////////////////////////////////////////////////////////////////////////////

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @uses $CFG
 * @uses $DB
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $taskchain
 * @return stdclass|null
 */
function taskchain_user_outline($course, $user, $mod, $taskchain) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

    $select = 'parenttype = ? AND parentid = ? AND userid = ?';
    $params = array(mod_taskchain::PARENTTYPE_ACTIVITY, $taskchain->id, $user->id);
    if ($grade = $DB->get_records_select('taskchain_chain_grades', $select, $params, 'timemodified DESC', 'id,grade,timemodified')) {
        $grade = reset($grade);
        $grade = (object)array(
            'time' => $grade->timemodified,
            'info' => get_string('grade', 'mod_taskchain').': '.$grade->grade
        );
    }
    return $grade;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param xxx $course
 * @param xxx $user
 * @param xxx $mod
 * @param xxx $taskchain
 * @return string HTML
 * @todo Finish documenting this function
 */
function taskchain_user_complete($course, $user, $mod, $taskchain) {
    $report = taskchain_user_outline($course, $user, $mod, $taskchain);
    if (empty($report)) {
        echo get_string("noactivity", 'mod_taskchain');
    } else {
        $date = userdate($report->time, get_string('strftimerecentfull'));
        echo $report->info.' '.get_string('mostrecently').': '.$date;
    }
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in taskchain activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @uses $CFG
 * @uses $DB
 * @uses $OUTPUT
 * @param stdclass $course
 * @param bool $viewfullnames
 * @param int $timestart
 * @return boolean
 */
function taskchain_print_recent_activity($course, $viewfullnames, $timestart) {
    global $CFG, $DB, $OUTPUT;
    $result = false;

    // the Moodle "logs" table contains the following fields:
    //     time, userid, course, ip, module, cmid, action, url, info

    // this function utilitizes the following index on the log table
    //     log_timcoumodact_ix : time, course, module, action

    // log records are added by the following function in "lib/datalib.php":
    //     add_to_log($courseid, $module, $action, $url='', $info='', $cm=0, $user=0)

    // log records are added by the following HotPot scripts:
    //     (scriptname : log action)
    //     attempt.php : attempt
    //     index.php   : index
    //     report.php  : report
    //     review.php  : review
    //     submit.php  : submit
    //     view.php    : view
    // all these actions have a record in the "log_display" table

    $select = "time > ? AND course = ? AND module = ? AND action IN (?, ?, ?, ?, ?)";
    $params = array($timestart, $course->id, 'taskchain', 'add', 'update', 'view', 'attempt', 'submit');

    if ($logs = $DB->get_records_select('log', $select, $params, 'time ASC')) {

        $modinfo = get_fast_modinfo($course);
        $cmids   = array_keys($modinfo->get_cms());

        $stats = array();
        foreach ($logs as $log) {
            $cmid = $log->cmid;
            if (! in_array($cmid, $cmids)) {
                continue; // invalid $cmid - shouldn't happen !!
            }
            $cm = $modinfo->get_cm($cmid);
            if (! $cm->uservisible) {
                continue; // coursemodule is hidden from user
            }
            $sortorder = array_search($cmid, $cmids);
            if (! array_key_exists($sortorder, $stats)) {
                if (has_capability('mod/taskchain:reviewmyattempts', $cm->context) || has_capability('mod/taskchain:reviewallattempts', $cm->context)) {
                    $viewreport = true;
                } else {
                    $viewreport = false;
                }
                $options = array('context' => $cm->context);
                if (method_exists($cm, 'get_formatted_name')) {
                    $name = $cm->get_formatted_name($options);
                } else {
                    $name = format_string($cm->name, true,  $options);
                }
                $stats[$sortorder] = (object)array(
                    'name'    => $name,
                    'cmid'    => $cmid,
                    'add'     => 0,
                    'update'  => 0,
                    'view'    => 0,
                    'attempt' => 0,
                    'submit'  => 0,
                    'users'   => array(),
                    'viewreport' => $viewreport
                );
            }
            $action = $log->action;
            switch ($action) {
                case 'add':
                case 'update':
                    // store most recent time
                    $stats[$sortorder]->$action = $log->time;
                    break;
                case 'view':
                case 'attempt':
                case 'submit':
                    // increment counter
                    $stats[$sortorder]->$action ++;
                    break;
            }
            $stats[$sortorder]->users[$log->userid] = true;
        }

        $dateformat   = get_string('strftimerecent', 'langconfig'); // strftimerecentfull
        $strusers     = get_string('users');
        $stradded     = get_string('added',    'mod_taskchain');
        $strupdated   = get_string('updated',  'mod_taskchain');
        $strviews     = get_string('views',    'mod_taskchain');
        $strattempts  = get_string('attempts', 'mod_taskchain');
        $strsubmits   = get_string('submits',  'mod_taskchain');

        $print_headline = true;
        ksort($stats);
        foreach ($stats as $stat) {
            $li = array();
            if ($stat->add) {
                $li[] = $stradded.': '.userdate($stat->add, $dateformat);
            }
            if ($stat->update) {
                $li[] = $strupdated.': '.userdate($stat->update, $dateformat);
            }
            if ($stat->viewreport) {
                // link to a detailed report of recent activity for this taskchain
                $url = new moodle_url(
                    '/course/recent.php',
                    array('id'=>$course->id, 'modid'=>$stat->cmid, 'date'=>$timestart)
                );
                if ($count = count($stat->users)) {
                    $li[] = $strusers.': '.html_writer::link($url, $count);
                }
                if ($stat->view) {
                    $li[] = $strviews.': '.html_writer::link($url, $stat->view);
                }
                if ($stat->attempt) {
                    $li[] = $strattempts.': '.html_writer::link($url, $stat->attempt);
                }
                if ($stat->submit) {
                    $li[] = $strsubmits.': '.html_writer::link($url, $stat->submit);
                }
            }
            if (count($li)) {
                if ($print_headline) {
                    $print_headline = false;
                    echo $OUTPUT->heading(get_string('modulenameplural', 'mod_taskchain').':', 3);
                }

                $url = new moodle_url('/mod/taskchain/view.php', array('id'=>$stat->cmid));
                $link = html_writer::link($url, format_string($stat->name));

                $text = html_writer::tag('p', $link).html_writer::alist($li);
                echo html_writer::tag('div', $text, array('class'=>'taskchainrecentactivity'));

                $result = true;
            }
        }
    }
    return $result;
}

/**
 * Returns all activity in course taskchains since a given time
 * This function  returns activity for all taskchains since a given time.
 * It is initiated from the "Full report of recent activity" link in the "Recent Activity" block.
 * Using the "Advanced Search" page (cousre/recent.php?id=99&advancedfilter=1),
 * results may be restricted to a particular course module, user or group
 *
 * This function is called from: {@link course/recent.php}
 *
 * @uses $CFG
 * @uses $DB
 * @param array(object) $activities sequentially indexed array of course module objects
 * @param integer $index length of the $activities array
 * @param integer $timestart start date, as a UNIX date
 * @param integer $courseid id in the "course" table
 * @param integer $coursemoduleid id in the "course_modules" table
 * @param integer $userid id in the "users" table (default = 0)
 * @param integer $groupid id in the "groups" table (default = 0)
 * @return void adds items into $activities and increments $index
 *     for each taskchain attempt, an $activity object is appended
 *     to the $activities array and the $index is incremented
 *     $activity->type : module type (always "taskchain")
 *     $activity->defaultindex : index of this object in the $activities array
 *     $activity->instance : id in the "taskchain" table;
 *     $activity->name : name of this taskchain
 *     $activity->section : section number in which this taskchain appears in the course
 *     $activity->content : array(object) containing information about taskchain attempts to be printed by {@link print_recent_mod_activity()}
 *         $activity->content->attemptid : id in the "taskchain_task_attempts" table
 *         $activity->content->attempt : the number of this attempt at this task by this user
 *         $activity->content->score : the score for this attempt
 *         $activity->content->timestart : the server time at which this attempt started
 *         $activity->content->timefinish : the server time at which this attempt finished
 *     $activity->user : object containing user information
 *         $activity->user->userid : id in the "user" table
 *         $activity->user->fullname : the full name of the user (see {@link lib/moodlelib.php}::{@link fullname()})
 *         $activity->user->picture : $record->picture;
 *     $activity->timestamp : the time that the content was recorded in the database
 */
function taskchain_get_recent_mod_activity(&$activities, &$index, $date, $courseid, $coursemoduleid=0, $userid=0, $groupid=0) {
    global $CFG, $DB, $OUTPUT, $USER;
    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

    // CONTRIB-4025 don't allow students to see each other's scores
    $coursecontext = mod_taskchain::context(CONTEXT_COURSE, $courseid);
    if (! has_capability('mod/taskchain:reviewmyattempts', $coursecontext)) {
        return; // can't view recent activity
    }
    if (! has_capability('mod/taskchain:reviewallattempts', $coursecontext)) {
        $userid = $USER->id; // force this user only
    }

    // we want to detect Moodle >= 2.4
    // method_exists('course_modinfo', 'get_used_module_names')
    // method_exists('cm_info', 'get_modue_type_name')
    // method_exists('cm_info', 'is_user_access_restricted_by_capability')

    $reflector = new ReflectionFunction('get_fast_modinfo');
    if ($reflector->getNumberOfParameters() >= 3) {
        // Moodle >= 2.4 has 3rd parameter ($resetonly)
        $modinfo = get_fast_modinfo($courseid);
        $course  = $modinfo->get_course();
    } else {
        // Moodle <= 2.3
        $course = $DB->get_record('course', array('id' => $courseid));
        $modinfo = get_fast_modinfo($course);
    }
    $cms = $modinfo->get_cms();

    $taskchains = array(); // taskchainid => cmid
    $users      = array(); // cmid => array(userids)

    foreach ($cms as $cmid => $cm) {
        if ($cm->modname=='taskchain' && ($coursemoduleid==0 || $coursemoduleid==$cmid)) {
            // save mapping from taskchainid => coursemoduleid
            $taskchains[$cm->instance] = $cmid;
            // initialize array of users who have recently attempted this HotPot
            $users[$cmid] = array();
        } else {
            // we are not interested in this mod
            unset($cms[$cmid]);
        }
    }
    if (empty($taskchains)) {
        return; // no taskchains
    }

    $userfields = taskchain_get_userfields('u', null, 'theuserid');
    list($where, $params) = $DB->get_in_or_equal(array_keys($taskchains));
    $select = 'tca.*, tc.parentid AS taskchainid, '.$userfields;
    $from   = "{taskchain_chains} tc, {taskchain_chain_attempts} tca, {user} u";
    $where  = 'tc.parenttype = '.mod_taskchain::PARENTTYPE_ACTIVITY.
              " AND tc.parentid $where".
              ' AND tca.chainid = tc.id'.
              ' AND tca.userid = u.id';
    if ($groupid) {
        // restrict search to a users from a particular group
        $from .= ', {groups_members} gm';
        $where .= ' AND tca.userid = gm.userid AND gm.id = ?';
        $params[] = $groupid;
    }
    if ($userid) {
        // restrict search to a single user
        $where = ' AND tca.userid = ?';
        $params[] = $userid;
    }
    $where .= ' AND tca.timemodified > ?';
    $params[] = $date;
    $orderby = 'tca.userid, tca.cnumber';

    if (! $attempts = $DB->get_records_sql("SELECT $select FROM $from WHERE $where ORDER BY $orderby", $params)) {
        return; // no recent attempts at these taskchains
    }

    foreach (array_keys($attempts) as $attemptid) {
        $attempt = &$attempts[$attemptid];

        if (! array_key_exists($attempt->taskchainid, $taskchains)) {
            continue; // invalid taskchainid - shouldn't happen !!
        }

        $cmid = $taskchains[$attempt->taskchainid];
        $userid = $attempt->userid;
        if (! array_key_exists($userid, $users[$cmid])) {
            $users[$cmid][$userid] = (object)array(
                'userid'   => $userid,
                'fullname' => fullname($attempt),
                'picture'  => $OUTPUT->user_picture($attempt, array('courseid' => $courseid)),
                'attempts' => array(),
            );
        }
        // add this attempt by this user at this course module
        $users[$cmid][$userid]->attempts[$attempt->cnumber] = &$attempt;
    }

    foreach ($cms as $cmid => $cm) {
        if (empty($users[$cmid])) {
            continue;
        }
        // add an activity object for each user's attempts at this taskchain
        foreach ($users[$cmid] as $userid => $user) {

            // get index of last (=most recent) attempt
            $max_unumber = max(array_keys($user->attempts));

            $options = array('context' => $cm->context);
            if (method_exists($cm, 'get_formatted_name')) {
                $name = $cm->get_formatted_name($options);
            } else {
                $name = format_string($cm->name, true,  $options);
            }

            $activities[$index++] = (object)array(
                'type' => 'taskchain',
                'cmid' => $cmid,
                'name' => $name,
                'user' => $user,
                'attempts'  => $user->attempts,
                'timestamp' => $user->attempts[$max_unumber]->timemodified
            );
        }
    }
}

/**
 * Print single activity item prepared by {@see taskchain_get_recent_mod_activity()}
 *
 * This function is called from: {@link course/recent.php}
 *
 * @uses $CFG
 * @uses $OUTPUT
 * @param object $activity an object created by {@link get_recent_mod_activity()}
 * @param integer $courseid id in the "course" table
 * @param boolean $detail
 *         true : print a link to the taskchain activity
 *         false : do no print a link to the taskchain activity
 * @param xxx $modnames
 * @param xxx $viewfullnames
 * @todo Finish documenting this function
 */
function taskchain_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    global $CFG, $OUTPUT;
    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

    static $dateformat = null;
    if (is_null($dateformat)) {
        $dateformat = get_string('strftimerecentfull');
    }

    $table = new html_table();
    $table->cellpadding = 3;
    $table->cellspacing = 0;

    if ($detail) {
        $row = new html_table_row();

        $cell = new html_table_cell('&nbsp;', array('width'=>15));
        $row->cells[] = $cell;

        // activity icon and link to activity
        $src = $OUTPUT->pix_url('icon', $activity->type);
        $img = html_writer::tag('img', array('src'=>$src, 'class'=>'icon', $alt=>$activity->name));

        // link to activity
        $href = new moodle_url('/mod/taskchain/view.php', array('id' => $activity->cmid));
        $link = html_writer::link($href, $activity->name);

        $cell = new html_table_cell("$img $link");
        $cell->colspan = 6;
        $row->cells[] = $cell;

        $table->data[] = new html_table_row(array(
            new html_table_cell('&nbsp;', array('width'=>15)),
            new html_table_cell("$img $link")
        ));

        $table->data[] = $row;
    }

    $row = new html_table_row();

    // set rowspan to (number of attempts) + 1
    $rowspan = count($activity->attempts) + 1;

    $cell = new html_table_cell('&nbsp;', array('width'=>15));
    $cell->rowspan = $rowspan;
    $row->cells[] = $cell;

    $cell = new html_table_cell($activity->user->picture, array('width'=>35, 'valign'=>'top', 'class'=>'forumpostpicture'));
    $cell->rowspan = $rowspan;
    $row->cells[] = $cell;

    $href = new moodle_url('/user/view.php', array('id'=>$activity->user->userid, 'course'=>$courseid));
    $cell = new html_table_cell(html_writer::link($href, $activity->user->fullname));
    $cell->colspan = 5;
    $row->cells[] = $cell;

    $table->data[] = $row;

    foreach ($activity->attempts as $attempt) {
        if ($attempt->duration) {
            $duration = '('.mod_taskchain::format_time($attempt->duration).')';
        } else {
            $duration = '&nbsp;';
        }

        $href = new moodle_url('/mod/taskchain/report.php', array('chainattemptid'=>$attempt->id));
        $link = html_writer::link($href, userdate($attempt->timemodified, $dateformat));

        $table->data[] = new html_table_row(array(
            new html_table_cell($attempt->cnumber),
            new html_table_cell($attempt->grade.'%'),
            new html_table_cell(mod_taskchain::format_status($attempt->status, true)),
            new html_table_cell($link),
            new html_table_cell($duration)
        ));
    }

    echo html_writer::table($table);
}

/*
 * This function defines what log actions will be selected from the Moodle logs for
 * Administration block -> Course administration -> Reports -> Course participation
 *
 * Note: This is not used by the "standard" logging system in Moodle >= 2.6
 *       Events with crud = 'r' and edulevel = LEVEL_PARTICIPATING
 *       will be considered as view actions.

 * This function is called from: {@link course/report/participation/index.php}
 * @return array(string) of text strings used to log TaskChain VIEW actions
 */
function taskchain_get_view_actions() {
    return array('view', 'index', 'report', 'review');
}

/*
 * This function defines what log actions will be selected from the Moodle logs for
 * Administration block -> Course administration -> Reports -> Course participation
 *
 * Note: This is not used by the "standard" logging system in Moodle >= 2.6
 *       Events with crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post actions.
 *
 * This function is called from: {@link course/report/participation/index.php}
 * @return array(string) of text strings used to log TaskChain POST actions
 */
function taskchain_get_post_actions() {
    return array('submit');
}

/*
 * For the given list of courses, this function creates an HTML report
 * of which TaskChain activities have been completed and which have not

 * This function is called from: {@link course/lib.php}
 *
 * @param array(object) $courses records from the "course" table
 * @param array(array(string)) $htmlarray array, indexed by courseid, of arrays, indexed by module name (e,g, "taskchain), of HTML strings
 *     each HTML string shows a list of the following information about each open TaskChain in the course
 *         TaskChain name and link to the activity  + open/close dates, if any
 *             for teachers:
 *                 how many students have attempted/completed the TaskChain
 *             for students:
 *                 which TaskChains have been completed
 *                 which TaskChains have not been completed yet
 *                 the time remaining for incomplete TaskChains
 * @return no return value is required, but $htmlarray may be updated
 */
function taskchain_print_overview($courses, &$htmlarray) {
    global $CFG, $DB, $USER;
    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

    if (empty($CFG->taskchain_enablemymoodle)) {
        return; // TaskChains are not shown on MyMoodle on this site
    }

    if (! isset($courses) || ! is_array($courses) || ! count($courses)) {
        return; // no courses
    }

    if (! $instances = get_all_instances_in_courses('taskchain', $courses)) {
        return; // no taskchains
    }

    $strtaskchain  = get_string('modulename', 'mod_taskchain');
    $strtimeopen   = get_string('timeopen',   'mod_taskchain');
    $strtimeclose  = get_string('timeclose',  'mod_taskchain');
    $strdateformat = get_string('strftimerecentfull');
    $strattempted  = get_string('attempted',  'mod_taskchain');
    $strcompleted  = get_string('completed',  'mod_taskchain');
    $strnotattemptedyet = get_string('notattemptedyet', 'mod_taskchain');

    $taskchains = array();
    foreach ($instances as $i => $instance) {
        $taskchains[$instance->id] = &$instances[$i];
    }

    // get related chain records - we especially want the time open/close and the grade limit/weighting
    list($select, $params) = $DB->get_in_or_equal(array_keys($taskchains));
    $select = 'parentid '.$select.' AND parenttype = ?';
    $params[] = mod_taskchain::PARENTTYPE_ACTIVITY;
    $fields = 'id,parentid,parenttype,timeopen,timeclose,gradelimit,gradeweighting';
    if (! $chains = $DB->get_records_select('taskchain_chains', $select, $params, '', $fields)) {
        return; // no chains - shouldn't happen !!
    }
    foreach ($chains as $id=>$chain) {
        $taskchains[$chain->parentid]->chain = &$chains[$id];
    }

    // get all grades for this user - saves getting them individually for students later on
    $select .= ' AND userid = ?';
    $params[] = $USER->id;
    if (! $chaingrades = $DB->get_records_select('taskchain_chain_grades', $select, $params)) {
        $chaingrades = array();
    }

    // map taskchains onto grades for this user
    foreach ($chaingrades as $id=>$chaingrade) {
        if (! isset($taskchains[$chaingrade->parentid])) {
            continue; // shouldn't happen !!
        }
        $taskchains[$chaingrade->parentid]->chaingrade = &$chaingrades[$id];
    }

    $now = time();
    foreach ($taskchains as $taskchain) {

        if ($taskchain->chain->timeopen > $now || $taskchain->chain->timeclose < $now) {
            continue; // skip activities that are not open, or are closed
        }

        $str = ''
            .'<div class="taskchain overview">'
            .'<div class="name">'.$strtaskchain. ': '
            .'<a '.($taskchain->visible ? '':' class="dimmed"')
            .'title="'.$strtaskchain.'" href="'.$CFG->wwwroot
            .'/mod/taskchain/view.php?id='.$taskchain->coursemodule.'">'
            .format_string($taskchain->name).'</a></div>'
        ;
        if ($taskchain->chain->timeopen) {
            $str .= '<div class="info">'.$strtimeopen.': '.userdate($taskchain->chain->timeopen, $strdateformat).'</div>';
        }
        if ($taskchain->chain->timeclose) {
            $str .= '<div class="info">'.$strtimeclose.': '.userdate($taskchain->chain->timeclose, $strdateformat).'</div>';
        }

        $modulecontext = mod_taskchain::context(CONTEXT_MODULE, $taskchain->coursemodule);
        if (has_capability('mod/taskchain:reviewallattempts', $modulecontext)) {
            // manager: show class grades stats
            // attempted: 99/99, completed: 99/99
            if ($students = get_users_by_capability($modulecontext, 'mod/taskchain:attempt', 'u.id,u.id', 'u.id', '', '', 0, '', false)) {
                $count = count($students);
                $attempted = 0;
                $completed = 0;
                list($select, $params) = $DB->get_in_or_equal(array_keys($students));
                $select = 'userid '.$select.' AND parentid = ? AND parenttype = ?';
                array_push($params, $taskchain->id, mod_taskchain::PARENTTYPE_ACTIVITY);
                if ($chaingrades = $DB->get_records_select('taskchain_chain_grades', $select, $params)) {
                    $attempted = count($chaingrades);
                    foreach ($chaingrades as $chaingrade) {
                        if ($chaingrade->status==mod_taskchain::STATUS_COMPLETED) {
                            $completed++;
                        }
                    }
                    unset($chaingrades);
                }
                unset($students);
                $str .= '<div class="info">'.$strattempted.': '.$attempted.' / '.$count.', '.$strcompleted.': '.$completed.' / '.$count.'</div>';
            }
        } else {
            // student: show grade and status e.g. 90% (completed)
            if (empty($taskchain->chaingrade)) {
                $str .= '<div class="info">'.$strnotattemptedyet.'</div>';
            } else {
                $href = new moodle_url('/mod/taskchain/report.php', array('chaingradeid' => $taskchain->chaingrade->id));
                if ($taskchain->chain->gradelimit && $taskchain->chain->gradeweighting) {
                    $str .= '<div class="info">'.get_string('grade', 'taskchain').': '.'<a href="'.$href.'">'.$taskchain->chaingrade->grade.'%</a></div>';
                }
                $str .= '<div class="info">'.get_string('status', 'taskchain').': '.'<a href="'.$href.'">'.mod_taskchain::format_status($taskchain->chaingrade->status).'</a></div>';
            }
        }
        $str .= "</div>\n";

        if (empty($htmlarray[$taskchain->course]['taskchain'])) {
            $htmlarray[$taskchain->course]['taskchain'] = $str;
        } else {
            $htmlarray[$taskchain->course]['taskchain'] .= $str;
        }
    }
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 */
function taskchain_cron() {
    return true;
}

/**
 * Returns an array of user ids who are participanting in this taskchain
 *
 * @uses $DB
 * @param int $taskchainid ID of an instance of this module
 * @return array of user ids, empty if there are no participants
 */
function taskchain_get_participants($taskchainid) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

    $select = 'DISTINCT u.id, u.id';
    $from   = '{user} u, {taskchain_chain_grades} tcg';
    $where  = 'u.id = tcg.userid AND tcg.parenttype = ? AND tcg.parentid = ?';
    $params = array(mod_taskchain::PARENTTYPE_ACTIVITY, $taskchainid);

    return $DB->get_records_sql("SELECT $select FROM $from WHERE $where", $params);
}

/**
 * Is a given scale used by the instance of taskchain?
 *
 * The function asks all installed grading strategy subplugins. The taskchain
 * core itself does not use scales. Both grade for submission and grade for
 * assessments do not use scales.
 *
 * @param int $taskchainid id of taskchain instance
 * @param int $scaleid id of the scale to check
 * @return bool
 */
function taskchain_scale_used($taskchainid, $scaleid) {
    return false;
}

/**
 * Is a given scale used by any instance of taskchain?
 *
 * The function asks all installed grading strategy subplugins. The taskchain
 * core itself does not use scales. Both grade for submission and grade for
 * assessments do not use scales.
 *
 * @param int $scaleid id of the scale to check
 * @return bool
 */
function taskchain_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Returns all other caps used in the module
 *
 * @return array
 */
function taskchain_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * add gradelimit and gradeweighting to taskchain record
 *
 * @param object $taskchain (passed by reference)
 * @return none, (but $taskchain object may be modified)
 */
function taskchain_add_grade_settings(&$taskchain) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

    // cache the taskchain chain record to save multiple fetches of same settings from DB
    static $chain = false;

    if (isset($taskchain->id) && (! isset($taskchain->gradelimit) || ! isset($taskchain->gradeweighting))) {
        if ($chain && $chain->parentid==$taskchain->id) {
            // use previously fetched settings
        } else {
            // fetch new settings from DB
            $params = array('parentid'=>$taskchain->id, 'parenttype'=>mod_taskchain::PARENTTYPE_ACTIVITY);
            $fields = 'id, parentid, parenttype, gradelimit, gradeweighting';
            $chain = $DB->get_record('taskchain_chains', $params, $fields);
        }
        if ($chain) {
            $taskchain->gradelimit = $chain->gradelimit;
            $taskchain->gradeweighting = $chain->gradeweighting;
        }
    }
}

/**
 * Return grade for given user or all users.
 *
 * @param object $taskchain
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function taskchain_get_user_grades($taskchain, $userid=0) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

    taskchain_add_grade_settings($taskchain);

    if ($taskchain->gradelimit && $taskchain->gradeweighting) {
        if ($taskchain->gradeweighting>=100) {
            $precision = 0;
        } else if ($taskchain->gradeweighting>=10) {
            $precision = 1;
        } else { // 1 - 10
            $precision = 2;
        }
        $rawgrade = "ROUND(grade * ($taskchain->gradeweighting / $taskchain->gradelimit), $precision)";
    } else {
        $rawgrade = '0';
    }

    $table = '{taskchain_chain_grades}';
    $fields = "userid AS id, userid, $rawgrade AS rawgrade, timemodified AS datesubmitted";
    $select = 'parenttype = '.mod_taskchain::PARENTTYPE_ACTIVITY." AND parentid = $taskchain->id";
    if ($userid) {
        $select .= " AND userid = $userid";
    }
    return $DB->get_records_sql("SELECT $fields FROM $table WHERE $select GROUP BY userid, grade, timemodified");
}

/**
 * Update all user grades for a given taskchain and userid
 *
 * @param object $taskchain
 * @param int $userid optional user id, 0 means all users
 * @param xxx $nullifnone
 * @return void
 */
function taskchain_update_grades($taskchain=null, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

    taskchain_add_grade_settings($taskchain);

    if ($taskchain===null) {
        // update/create grades for all taskchains

        // set up sql strings
        $strupdating = get_string('updatinggrades', 'mod_taskchain');
        $select = 't.*, tc.gradelimit, tc.gradeweighting, cm.idnumber AS cmidnumber';
        $from   = '{taskchain} t, {taskchain_chains} tc, {course_modules} cm, {modules} m';
        $where  = 't.id = tc.parentid AND tc.parenttype='.mod_taskchain::PARENTTYPE_ACTIVITY.' AND t.id = cm.instance AND cm.module = m.id AND m.name = ?';
        $params = array('taskchain');

        // get previous record index (if any)
        if (! $config = $DB->get_record('config', array('name'=>'taskchain_update_grades'))) {
            $config = (object)array('id'=>0, 'name'=>'taskchain_update_grades', 'value'=>'0');
        }
        $i_min = intval($config->value);

        if ($i_max = $DB->count_records_sql("SELECT COUNT('x') FROM $from WHERE $where", $params)) {
            if ($rs = $DB->get_recordset_sql("SELECT $select FROM $from WHERE $where", $params)) {
                $bar = new progress_bar('taskchainupgradegrades', 500, true);
                $i = 0;
                foreach ($rs as $taskchain) {

                    // update grade
                    if ($i >= $i_min) {
                        upgrade_set_timeout(); // another 3 minutes
                        taskchain_update_grades($taskchain, $userid, $nullifnone);
                    }

                    // update progress bar
                    $i++;
                    $bar->update($i, $i_max, $strupdating.": ($i/$i_max)");

                    // update record index
                    if ($i > $i_min) {
                        $config->value = "$i";
                        if ($config->id) {
                            $DB->update_record('config', $config);
                        } else {
                            $config->id = $DB->insert_record('config', $config);
                        }
                    }
                }
                $rs->close();
            }
        }

        // delete the record index
        if ($config->id) {
            $DB->delete_records('config', array('id'=>$config->id));
        }

    } else {
        // update/create grade for a single taskchain
        if ($grades = taskchain_get_user_grades($taskchain, $userid)) {
            taskchain_grade_item_update($taskchain, $grades);

        } else if ($userid && $nullifnone) {
            // no grades for this user, but we must force the creation of a "null" grade record
            taskchain_grade_item_update($taskchain, (object)array('userid'=>$userid, 'rawgrade'=>null));

        } else {
            // no grades and no userid
            taskchain_grade_item_update($taskchain);
        }
    }
}

/**
 * Update/create grade item for given taskchain
 *
 * @param object $taskchain object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return object grade_item
 */
function taskchain_grade_item_update($taskchain, $grades=null) {

    // set maximum grade for this TaskChain
    taskchain_add_grade_settings($taskchain);
    if (empty($taskchain->gradelimit) || empty($taskchain->gradeweighting)) {
        $grademax = 0;
    } else {
        $grademax = $taskchain->gradelimit * ($taskchain->gradeweighting/100);
    }

    // set up params for grade_update()
    $params = array(
        'itemname' => $taskchain->name
    );
    if (isset($taskchain->cmidnumber)) {
        //cmidnumber may not be always present
        $params['idnumber'] = $taskchain->cmidnumber;
    }
    if ($grademax) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $grademax;
        $params['grademin']  = 0;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
        // Note: when adding a new activity, a gradeitem will *not*
        // be created in the grade book if gradetype==GRADE_TYPE_NONE
        // A gradeitem will be created later if gradetype changes to GRADE_TYPE_VALUE
        // However, the gradeitem will *not* be deleted if the activity's
        // gradetype changes back from GRADE_TYPE_VALUE to GRADE_TYPE_NONE
        // Therefore, we force the removal of empty gradeitems
        $params['deleted'] = true;
    }
    return grade_update('mod/taskchain', $taskchain->course, 'mod', 'taskchain', $taskchain->id, 0, $grades, $params);
}

/**
 * Delete grade item for given taskchain
 *
 * @param object $taskchain object
 * @return object grade_item
 */
function taskchain_grade_item_delete($taskchain) {
    return grade_update('mod/taskchain', $taskchain->course, 'mod', 'taskchain', $taskchain->id, 0, null, array('deleted'=>1));
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area taskchain_intro for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdclass $course
 * @param stdclass $cm
 * @param stdclass $context
 * @return array of [(string)filearea] => (string)description
 */
function taskchain_get_file_areas($course, $cm, $context) {
    return array(
        'sourcefile' => get_string('sourcefile', 'mod_taskchain'),
        'configfile' => get_string('configfile', 'mod_taskchain'),
        'entry'      => get_string('entrytext',  'mod_taskchain'),
        'exit'       => get_string('exittext',   'mod_taskchain')
    );
}

/**
 * Serves the files from the taskchain file areas
 * taskchain files may be media inserted into entrypage, exitpage and sourcefile content
 * Note: some early Moodle 2.0 may require this function to be called "taskchain_pluginfile"
 *
 * @uses $CFG
 * @param stdclass $course
 * @param stdclass $cm
 * @param stdclass $context
 * @param string $filearea
 * @param array $args filepath split into folder and file names
 * @param bool $forcedownload
 * @param array $options (optional, default = array())
 * @return void this should never return to the caller
 */
function mod_taskchain_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options=array()) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

    require_course_login($course, true, $cm);

    switch ($filearea) {
        case 'entry':      $capability = 'mod/taskchain:view'; break;
        case 'sourcefile': $capability = 'mod/taskchain:attempt'; break;
        case 'configfile': $capability = 'mod/taskchain:attempt'; break;
        case 'exit':       $capability = 'mod/taskchain:attempt'; break;
        default: send_file_not_found(); // invalid $filearea !!
    }

    require_capability($capability, $context);

    $fs = get_file_storage();
    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';

    $lifetime = isset($CFG->filelifetime) ? $CFG->filelifetime : 86400;

    if ($file = $fs->get_file($context->id, 'mod_taskchain', $filearea, 0, $filepath, $filename)) {
        // file found - this is what we expect to happen
        send_stored_file($file, $lifetime, 0);
    }

    /////////////////////////////////////////////////////////////
    // If we get to this point, it is because the requested file
    // is not where is was supposed to be, so we will search for
    // it in some other likely locations.
    // If we find it, we will copy it across to where it is
    // supposed to be, so it can be found more quickly next time
    /////////////////////////////////////////////////////////////

    $file_record = array(
        'contextid'=>$context->id, 'component'=>'mod_taskchain', 'filearea'=>$filearea,
        'sortorder'=>0, 'itemid'=>0, 'filepath'=>$filepath, 'filename'=>$filename
    );

    // search in external directory
    if ($file = taskchain_pluginfile_externalfile($context, 'mod_taskchain', $filearea, $filepath, $filename)) {
        send_stored_file($file, $lifetime, 0);
    }

    // search course legacy files
    // $coursecontext = context_course::instance($course->id);
    $coursecontext = mod_taskchain::context(CONTEXT_COURSE, $course->id); // Moodle 2.0 - 2.1
    if ($file = $fs->get_file($coursecontext->id, 'course', 'legacy', 0, $filepath, $filename)) {
        if ($file = $fs->create_file_from_storedfile($file_record, $file)) {
            send_stored_file($file, $lifetime, 0);
        }
    }

    // search local file system
    $oldfilepath = $CFG->dataroot.'/'.$course->id.$filepath.$filename;
    if (file_exists($oldfilepath)) {
        if ($file = $fs->create_file_from_pathname($file_record, $oldfilepath)) {
            send_stored_file($file, $lifetime, 0);
        }
    }

    // search other fileareas for this TaskChain
    $other_fileareas = array('sourcefile', 'configfile', 'entry', 'exit');
    foreach($other_fileareas as $other_filearea) {
        if ($other_filearea==$filearea) {
            continue; // we have already checked this filearea
        }
        if ($file = $fs->get_file($context->id, 'mod_taskchain', $other_filearea, 0, $filepath, $filename)) {
            if ($file = $fs->create_file_from_storedfile($file_record, $file)) {
                send_stored_file($file, $lifetime, 0);
            }
        }
    }

    // file not found :-(
    send_file_not_found();
}

/**
 * taskchain_pluginfile_externalfile
 *
 * if the main file is a link from an external repository
 * look for the target file in the main file's repository
 * Note: this functionality only exists in Moodle >= 2.3
 *
 * @param stdclass $context
 * @param string $component 'mod_taskchain'
 * @param string $filearea  'sourcefile', 'entrytext' or 'exittext'
 * @param string $filepath  despite the name, this is a dir path with leading and trailing "/"
 * @param string $filename
 * @return stdclass if external file found, false otherwise
 */
function taskchain_pluginfile_externalfile($context, $component, $filearea, $filepath, $filename) {

    // get file storage
    $fs = get_file_storage();

    // get main file for this $component/$filearea
    // typically this will be the TaskChain task file
    $mainfile = taskchain_pluginfile_mainfile($context, $component, $filearea);

    // get repository - cautiously :-)
    if (! $mainfile) {
        return false; // no main file - shouldn't happen !!
    }
    if (! method_exists($mainfile, 'get_repository_id')) {
        return false; // no file linking in Moodle 2.0 - 2.2
    }
    if (! $repositoryid = $mainfile->get_repository_id()) {
        return false; // $mainfile is not from an external repository
    }
    if (! $repository = repository::get_repository_by_id($repositoryid, $context)) {
        return false; // $repository is not accessible in this context - shouldn't happen !!
    }

    // save $root_path, because it may get messed up by
    // $repository->get_listing($path), if $path is non-existant
    if (isset($repository->root_path)) {
        $root_path = $repository->root_path;
    } else {
        $root_path = false;
    }

    // get repository type
    switch (true) {
        case isset($repository->options['type']):
            $type = $repository->options['type'];
            break;
        case isset($repository->instance->typeid):
            $type = repository::get_type_by_id($repository->instance->typeid);
            $type = $type->get_typename();
            break;
        default:
            $type = ''; // shouldn't happen !!
    }

    // set paths (within repository) to required file
    // how we do this depends on the repository $typename
    // "filesystem" path is in plain text, others are encoded
    $mainreference = $mainfile->get_reference();
    switch ($type) {
        case 'filesystem':
            $maindirname = dirname($mainreference);
            $encodepath  = false;
            break;
        case 'user':
        case 'coursefiles':
            $params      = file_storage::unpack_reference($mainreference, true);
            $maindirname = $params['filepath'];
            $encodepath  = true;
            break;
        default:
            echo 'unknown repository type in taskchain_pluginfile_externalfile(): '.$type;
            die;
    }

    // initialize array of paths to file in repository
    $paths = array();

    // remove leading and trailing "/" from dir names
    $dirname = trim($filepath, '/');
    $maindirname = trim($maindirname, '/');

    if ($dirname) {
        $paths[$dirname] = $dirname.'/'.$filename;
    }
    if ($maindirname) {
        $paths[$maindirname] = $maindirname.'/'.$filename;
    }
    if ($maindirname && $dirname) {
        $paths["$maindirname/$dirname"] = $maindirname.'/'.$dirname.'/'.$filename;
    }

    // assume path to target dir is same as path to main dir
    $dirs = explode('/', trim($maindirname, '/'));

    // traverse back up main folder hierarchy if necessary
    $count = count(explode('/', $dirname));
    array_splice($dirs, -$count);

    // reconstruct expected dir path for source file
    $path = implode('/', $dirs);
    if ($dirname) {
        $path .= ($path=='' ? '' : '/').$dirname;
    }
    $source = $path.($path=='' ? '' : '/').$filename;
    $paths[$path] = $source;

    // add leading and trailing "/" to dir names
    $dirname = '/'.$dirname.'/';
    $maindirname = '/'.$maindirname.'/';

    // locate $dirname within $maindirname
    // typically it will be absent or occur just once,
    // but it could possibly occur several times
    $search = '/'.preg_quote($dirname, '/').'/i';
    if (preg_match_all($search, $maindirname, $matches, PREG_OFFSET_CAPTURE)) {

        $i_max = count($matches[0]);
        for ($i=0; $i<$i_max; $i++) {
            list($match, $start) = $matches[0][$i];
            $path = substr($maindirname, 0, $start).$match;
            $path = trim($path, '/'); // e.g. hp6.2/html_files
            $paths[$path] = $path.'/'.$filename;
        }
    }

    // setup $params for path encoding, if necessary
    $params = array();
    if ($encodepath) {
        $listing = $repository->get_listing();
        switch (true) {
            case isset($listing['list'][0]['source']): $param = 'source'; break; // file
            case isset($listing['list'][0]['path']):   $param = 'path';   break; // dir
            default: return false; // shouldn't happen !!
        }
        $params = $listing['list'][0][$param];
        $params = json_decode(base64_decode($params), true);
    }

    foreach ($paths as $path => $source) {

        if (! taskchain_pluginfile_dirpath_exists($path, $repository, $type, $encodepath, $params)) {
            continue;
        }

        if ($encodepath) {
            $params['filepath'] = '/'.$path.($path=='' ? '' : '/');
            $params['filename'] = '.'; // "." signifies a directory
            $path = base64_encode(json_encode($params));
        }

        // reset $repository->root_path (filesystem repository only)
        if ($root_path) {
            $repository->root_path = $root_path;
        }

        $listing = $repository->get_listing($path);
        foreach ($listing['list'] as $file) {

            switch (true) {
                case isset($file['source']): $param = 'source'; break; // file
                case isset($file['path']):   $param = 'path';   break; // dir
                default: continue; // shouldn't happen !!
            }

            if ($encodepath) {
                $file[$param] = json_decode(base64_decode($file[$param]), true);
                $file[$param] = trim($file[$param]['filepath'], '/').'/'.$file[$param]['filename'];
            }

            if ($file[$param]==$source) {

                if ($encodepath) {
                    $params['filename'] = $filename;
                    $source = file_storage::pack_reference($params);
                }

                $file_record = array(
                    'contextid' => $context->id, 'component' => $component, 'filearea' => $filearea,
                    'sortorder' => 0, 'itemid' => 0, 'filepath' => $filepath, 'filename' => $filename
                );

                if ($file = $fs->create_file_from_reference($file_record, $repositoryid, $source)) {
                    return $file;
                }
                break; // couldn't create file, so give up and try a different $path
            }
        }
    }

    // external file not found (or found but not created)
    return false;
}

/**
 * Determine if dir path exists or not in repository
 *
 * @param string   $dirpath
 * @param stdclass $repository
 * @param string   $type ("user" or "coursefiles")
 * @param boolean  $encodepath
 * @param array    $params
 * @return boolean true if dir path exists in repository, false otherwise
 */
function taskchain_pluginfile_dirpath_exists($dirpath, $repository, $type, $encodepath, $params) {
    $dirs = explode('/', $dirpath);
    foreach ($dirs as $i => $dir) {
        $dirpath = implode('/', array_slice($dirs, 0, $i));

        if ($encodepath) {
            $params['filepath'] = '/'.$dirpath.($dirpath=='' ? '' : '/');
            $params['filename'] = '.'; // "." signifies a directory
            $dirpath = base64_encode(json_encode($params));
        }

        $exists = false;
        $listing = $repository->get_listing($dirpath);
        foreach ($listing['list'] as $file) {
            if (empty($file['source']) && $file['title']==$dir) {
                $exists = true;
                break;
            }
        }
        if (! $exists) {
            return false;
        }
    }
    // all dirs in path exist - success !!
    return true;
}

/**
 * Gets main file in a file area
 *
 * @param stdclass $context
 * @param string   $component e.g. 'mod_taskchain'
 * @param string   $filearea
 * @param integer  $itemid (optional, default=0)
 * @return stdclass if main file found, false otherwise
 */
function taskchain_pluginfile_mainfile($context, $component, $filearea, $itemid=0) {
    $fs = get_file_storage();

    // the main file for this TaskChain activity
    // (file with lowest sortorder in $filearea)
    $mainfile = false;
    $mainfile_is_empty = true;
    $mainfile_is_archive = false;

    // these file types can't be the mainfile
    $media_filetypes = array('fla', 'flv', 'gif', 'jpeg', 'jpg', 'mp3', 'png', 'swf', 'wav');

    // tgz and zip files will only be used as a last resort
    $archive_filetypes = array('tgz', 'zip');

    $area_files = $fs->get_area_files($context->id, $component, $filearea, $itemid); // , 'sortorder, filename', 0
    foreach ($area_files as $file) {
        if ($file->is_directory()) {
            continue;
        }
        $filename = $file->get_filename();
        if (substr($filename, 0, 1)=='.') {
            continue; // hidden file
        }
        $filetype = strtolower(substr($filename, -3));
        if (in_array($filetype, $media_filetypes)) {
            continue; // media file
        }
        if ($file_is_archive = in_array($filetype, $archive_filetypes)) {
            // only use an archive file if
            // if it is the first file found
            $update = $mainfile_is_empty;
        } else if ($mainfile_is_empty) {
            // always use a non-archive file
            // if it is the first file found
            $update = true;
        } else if ($file->get_sortorder()==0) {
            // always use an unsorted file
            // if the mainfile is an archive file
            $update = $mainfile_is_archive;
        } else if ($mainfile->get_sortorder()==0) {
            // always use a sorted file
            // if the mainfile is an unsorted file
            $update = true;
        } else if ($file->get_sortorder() < $mainfile->get_sortorder()) {
            // always use a sorted file (i.e. sortorder > 0)
            // if its sortorder is lower than the sortorder of the mainfile
            $update = true;
        }
        if ($update) {
            $mainfile = $file;
            $mainfile_is_empty = false;
            $mainfile_is_archive = $file_is_archive;
        }
    }

    return $mainfile;
}

/**
 * File browsing support for taskchain file areas
 *
 * @param stdclass $browser
 * @param stdclass $areas
 * @param stdclass $course
 * @param stdclass $cm
 * @param stdclass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return stdclass file_info instance or null if not found
 */
function taskchain_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding taskchain nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @uses $CFG
 * @uses $DB
 * @param navigation_node $navigation_node An object representing the navigation tree node of the taskchain module instance
 * @param stdclass $course
 * @param stdclass $module
 * @param cm_info  $cm
 */
function taskchain_extend_navigation(navigation_node $taskchainnode, stdclass $course, stdclass $module, cm_info $cm) {
    global $CFG, $TC;

    if (empty($TC)) {
        require_once($CFG->dirroot.'/mod/taskchain/locallib.php');
        $TC = new mod_taskchain();
    }

    if (isset($TC->can)) {
        if ($TC->can->preview()) {
            $label = get_string('preview', 'mod_taskchain');
            $params = array('tab'=>'preview', 'cnumber'=>-1);
            $params = $TC->merge_params($params, null, 'coursemoduleid');
            $url   = new moodle_url('/mod/taskchain/attempt.php', $params);
            $type  = navigation_node::TYPE_SETTING;
            $icon  = new pix_icon('t/preview', '');
            $taskchainnode->add($label, $url, $type, null, null, $icon);
        }

        if ($TC->can->reviewattempts()) {
            $type = navigation_node::TYPE_SETTING;
            $icon = new pix_icon('i/report', '');
            foreach ($TC->get_report_modes() as $name => $submodes) {
                $text = get_string($name, 'mod_taskchain');
                if (method_exists('navigation_node', 'create')) {
                    $node = navigation_node::create($text); // Moodle >= 2.2
                } else {
                    $node = new navigation_node(array('text' => $text, 'type' => $type));
                }
                foreach ($submodes as $mode => $params) {
                    $label = get_string('pluginname', 'taskchainreport_'.$mode);
                    if ($mode=='taskattempt') {
                        $url = $TC->url->review();
                    } else {
                        $url = $TC->url->report($mode, $params);
                    }
                    $node->add($label, $url, $type, null, null, $icon);
                }
                if (method_exists($taskchainnode, 'add_node')) {
                    $taskchainnode->add_node($node); // Moodle >= 2.2
                } else {
                    $node->key = $taskchainnode->children->count();
                    $taskchainnode->nodetype = navigation_node::NODETYPE_BRANCH;
                    $taskchainnode->children->add($node);
                }
                unset($node);
            }
        }
    }
}

/**
 * Extends the settings navigation with the TaskChain settings

 * This function is called when the context for the page is a taskchain module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $taskchainnode {@link navigation_node}
 */
function taskchain_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $taskchainnode) {
    global $CFG, $PAGE, $TC;

    // we expect that $TC has been setup, but just to be sure ...
    if (empty($TC)) {
        require_once($CFG->dirroot.'/mod/taskchain/locallib.php');
        $TC = new mod_taskchain();
    }

    // create our new nodes
    $nodes = array();
    if (isset($TC->can) && $TC->can->manage()) {
        $type = navigation_node::TYPE_SETTING;
        $icon = new pix_icon('t/edit', '');

        $params = $TC->merge_params(array('columnlisttype' => 'chains'), null, 'coursemoduleid');
        $action = new moodle_url('/mod/taskchain/edit/chains.php', $params);
        $text   = get_string('editchains', 'mod_taskchain');
        $nodes[] = new navigation_node(array('text'=>$text, 'action'=>$action, 'key'=>'editchains', 'type'=>$type, 'icon'=>$icon));

        $params = $TC->merge_params(array('columnlisttype' => 'tasks'), null, 'coursemoduleid');
        $action = new moodle_url('/mod/taskchain/edit/tasks.php', $params);
        $text   = get_string('edittasks', 'mod_taskchain');
        $nodes[] = new navigation_node(array('text'=>$text, 'action'=>$action, 'key'=>'edittasks', 'type'=>$type, 'icon'=>$icon));
    }

    // only teachers/admins will have new nodes to add
    if (count($nodes)) {

        // We want to add these new nodes after the Edit settings node,
        // and before the locally assigned roles node.

        // detect Moodle >= 2.2 (it has an easy way to do what we want)
        if (method_exists($taskchainnode, 'get_children_key_list')) {

            // in Moodle >= 2.2, we can locate the "Edit settings" node
            // by its key and use that as the "beforekey" for the new nodes
            $keys = $taskchainnode->get_children_key_list();
            $i = array_search('modedit', $keys);
            if ($i===false) {
                $i = 0;
            } else {
                $i = ($i + 1);
            }
            if (array_key_exists($i, $keys)) {
                $beforekey = $keys[$i];
            } else {
                $beforekey = null;
            }
            foreach ($nodes as $node) {
                $taskchainnode->add_node($node, $beforekey);
            }

        } else {
            // in Moodle 2.0 - 2.1, we don't have the $beforekey functionality,
            // so instead, we create a new collection of child nodes by copying
            // the current child nodes one by one and inserting our news nodes
            // after the node whose plain url ends with "/course/modedit.php"
            // Note: this would also work on Moodle >= 2.2, but is obviously
            // rather a hack and not the way things should to be done
            $found = false;
            $children = new navigation_node_collection();
            $max_i = ($taskchainnode->children->count() - 1);
            foreach ($taskchainnode->children as $i => $child) {
                $children->add($child);
                if ($found==false) {
                    $action = $child->action->out_omit_querystring();
                    if (($i==$max_i) || substr($action, -19)=='/course/modedit.php') {
                        $found = true;
                        foreach ($nodes as $node) {
                            $children->add($node);
                        }
                    }
                }
            }
            $taskchainnode->children = $children;
        }
    }
}

////////////////////////////////////////////////////////////////////////////////
// Reset API                                                                  //
////////////////////////////////////////////////////////////////////////////////

/**
 * taskchain_reset_course_form_definition
 *
 * @param xxx $mform (passed by reference)
 * @todo Finish documenting this function
 */
function taskchain_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'taskchainheader', get_string('modulenameplural', 'mod_taskchain'));
    $mform->addElement('checkbox', 'reset_taskchain_deleteallattempts', get_string('deleteallattempts', 'mod_taskchain'));
}

/**
 * taskchain_reset_course_form_defaults
 *
 * @param xxx $course
 * @return xxx
 * @todo Finish documenting this function
 */
function taskchain_reset_course_form_defaults($course) {
    return array('reset_taskchain_deleteallattempts' => 1);
}

/**
 * taskchain_reset_gradebook
 *
 * @uses $DB
 * @param xxx $courseid
 * @param xxx $type (optional, default='')
 * @todo Finish documenting this function
 */
function taskchain_reset_gradebook($courseid, $type='') {
    global $DB;
    $sql = ''
        .'SELECT h.*, cm.idnumber AS cmidnumber, cm.course AS courseid '
        .'FROM {taskchain} h, {course_modules} cm, {modules} m '
        ."WHERE m.name='taskchain' AND m.id=cm.module AND cm.instance=h.id AND h.course=?"
    ;
    if ($taskchains = $DB->get_records_sql($sql, array($courseid))) {
        foreach ($taskchains as $taskchain) {
            taskchain_grade_item_update($taskchain, 'reset');
        }
    }
}

/**
 * taskchain_reset_userdata
 *
 * @uses $DB
 * @param xxx $data
 * @return xxx
 * @todo Finish documenting this function
 */
function taskchain_reset_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

    if (empty($data->reset_taskchain_deleteallattempts)) {
        return array();
    }

    if (! $taskchains = $DB->get_records('taskchain', array('course' => $data->courseid), 'id', 'id')) {
        return array();
    }

    list($select, $params) = $DB->get_in_or_equal(array_keys($taskchains));
    $select = "parentid $select AND parenttype = ?";
    $params[] = mod_taskchain::PARENTTYPE_ACTIVITY;
    if (! $chains = $DB->get_records_select('taskchain_chains', $select, $params, 'id', 'id, parenttype, parentid')) {
        return array();
    }

    // since there may be a large number of records in the taskchain_task_attempts table,
    // we proceed unit by unit to try and limit effect of timeouts and memory overloads

    foreach ($chains as $chain) {

        // $state determines what data is deleted
        //   0 : delete taskchain_details
        //   1 : delete taskchain_responses
        //   2 : delete taskchain_task_attempts
        //   3 : delete taskchain_task_scores
        //   4 : delete taskchain_chain_attempts
        //   5 : delete taskchain_chain_grades
        for ($state=2; $state<=5; $state++) {

            if ($state<=3) {
                // get associated $task records
                if ($taskids = $DB->get_records('taskchain_tasks', array('chainid' => $chain->id), '', 'id')) {

                    // remove task grade/attempts
                    list($select, $params) = $DB->get_in_or_equal(array_keys($taskids));
                    if ($state<=2) {
                        if ($attempts = $DB->get_records_select('taskchain_task_attempts', "taskid $select", $params, 'id', 'id')) {
                            $ids = array_keys($attempts);
                            if ($state==0) {
                                $DB->delete_records_list('taskchain_details',   'attemptid', $ids);
                                $DB->delete_records_list('taskchain_responses', 'attemptid', $ids);
                                $DB->delete_records_list('taskchain_task_attempts',    'id', $ids);
                            }
                        }
                    }
                    if ($state==3) {
                        $DB->delete_records_select('taskchain_task_scores', "taskid $select", $params);
                    }
                }
            }
            if ($state==4) {
                $params = array('chainid'  => $chain->id);
                $DB->delete_records('taskchain_chain_attempts', $params);
            }
            if ($state==5) {
                $params = array('parentid' => $chain->parentid, 'parenttype' => $chain->parenttype);
                $DB->delete_records('taskchain_chain_grades', $params);
            }
        }
    }

    return array(array(
        'component' => get_string('modulenameplural', 'mod_taskchain'),
        'item' => get_string('deleteallattempts', 'mod_taskchain'),
        'error' => false
    ));
}

/*
* This standard function will check all instances of this module
* and make sure there are up-to-date events created for each of them.
* If courseid = 0, then every taskchain event in the site is checked, else
* only taskchain events belonging to the course specified are checked.
* This function is used, in its new format, by restore_refresh_events()
* in backup/backuplib.php
*
* @param int $courseid : relative path (below $CFG->dirroot) of folder holding class definitions
*/
function taskchain_refresh_events($courseid=0) {
    global $CFG, $DB;

    if ($courseid && is_numeric($courseid)) {
        $params = array('course'=>$courseid);
    } else {
        $params = array();
    }
    if (! $taskchains = $DB->get_records('taskchain', $params)) {
        return true; // no taskchains
    }

    // get previous ids for events for these taskchains
    list($filter, $params) = $DB->get_in_or_equal(array_keys($taskchains));
    if ($eventids = $DB->get_records_select('event', "modulename='taskchain' AND instance $filter", $params, 'id', 'id')) {
        $eventids = array_keys($eventids);
    } else {
        $eventids = array();
    }

    // we're going to count the taskchains so we can detect the last one
    $i = 0;
    $count = count($taskchains);

    // add events for these taskchain chains
    // eventids will be reused where possible
    foreach ($taskchains as $taskchain) {
        $i++;
        $delete = ($i==$count);
        taskchain_update_events($taskchain, $chain, $eventids, $delete);
    }

    // all done
    return true;
}

/**
 * Update calendar events for a single TaskChain activity
 * This function is intended to be called just after
 * a TaskChain activity has been created or edited.
 *
 * @param xxx $taskchain
 */
function taskchain_update_events_wrapper($taskchain) {
    global $DB;
    if ($eventids = $DB->get_records('event', array('modulename'=>'taskchain', 'instance'=>$taskchain->id), 'id', 'id')) {
        $eventids = array_keys($eventids);
    } else {
        $eventids = array();
    }
    taskchain_update_events($taskchain, $chain, $eventids, true);
}

/**
 * taskchain_update_events
 *
 * @uses $CFG
 * @uses $DB
 * @param xxx $taskchain (passed by reference)
 * @param xxx $chain (passed by reference)
 * @param xxx $eventids (passed by reference)
 * @param xxx $delete
 * @todo Finish documenting this function
 */
function taskchain_update_events(&$taskchain, &$chain, &$eventids, $delete) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/calendar/lib.php');

    static $stropens = '';
    static $strcloses = '';
    static $maxduration = null;

    // check to see if this user is allowed
    // to manage calendar events in this course

    // Moodle >= 2.2 is supposed to get contexts like this:
    // $systemcontext = context_system::instance();
    // $coursecontext = context_course::instance($taskchain->course);

    $capability = 'moodle/calendar:manageentries';
    if (has_capability($capability, mod_taskchain::context(CONTEXT_SYSTEM))) {
        $can_manage_events = true; // site admin
    } else if (has_capability($capability, mod_taskchain::context(CONTEXT_COURSE, $taskchain->course))) {
        $can_manage_events = true; // course admin/teacher
    } else {
        $can_manage_events = false; // not allowed to add/edit calendar events !!
    }

    // don't check calendar capabiltiies
    // when adding or updating events
    $checkcapabilties = false;

    // cache text strings and max duration (first time only)
    if (is_null($maxduration)) {
        if (isset($CFG->taskchain_maxeventlength)) {
            $maxeventlength = $CFG->taskchain_maxeventlength;
        } else {
            $maxeventlength = 5; // 5 days is default
        }
        // set $maxduration (secs) from $maxeventlength (days)
        $maxduration = $maxeventlength * DAYSECS;

        $stropens = get_string('activityopens', 'mod_taskchain');
        $strcloses = get_string('activitycloses', 'mod_taskchain');
    }

    // array to hold events for this taskchain
    $events = array();

    // only setup calendar events,
    // if this user is allowed to
    if ($can_manage_events) {

        // set duration
        if ($chain->timeclose && $chain->timeopen) {
            $duration = max(0, $chain->timeclose - $chain->timeopen);
        } else {
            $duration = 0;
        }

        if ($duration > $maxduration) {
            // long duration, two events
            $events[] = (object)array(
                'name' => $taskchain->name.' ('.$stropens.')',
                'eventtype' => 'open',
                'timestart' => $chain->timeopen,
                'timeduration' => 0
            );
            $events[] = (object)array(
                'name' => $taskchain->name.' ('.$strcloses.')',
                'eventtype' => 'close',
                'timestart' => $chain->timeclose,
                'timeduration' => 0
            );
        } else if ($duration) {
            // short duration, just a single event
            if ($duration < DAYSECS) {
                // less than a day (1:07 p.m.)
                $fmt = get_string('strftimetime');
            } else if ($duration < WEEKSECS) {
                // less than a week (Thu, 13:07)
                $fmt = get_string('strftimedaytime');
            } else if ($duration < YEARSECS) {
                // more than a week (2 Feb, 13:07)
                $fmt = get_string('strftimerecent');
            } else {
                // more than a year (Thu, 2 Feb 2012, 01:07 pm)
                $fmt = get_string('strftimerecentfull');
            }
            // short duration, just a single event
            $events[] = (object)array(
                'name' => $taskchain->name.' ('.userdate($chain->timeopen, $fmt).' - '.userdate($chain->timeclose, $fmt).')',
                'eventtype' => 'open',
                'timestart' => $chain->timeopen,
                'timeduration' => $duration,
            );
        } else if ($chain->timeopen) {
            // only an open date
            $events[] = (object)array(
                'name' => $taskchain->name.' ('.$stropens.')',
                'eventtype' => 'open',
                'timestart' => $chain->timeopen,
                'timeduration' => 0,
            );
        } else if ($chain->timeclose) {
            // only a closing date
            $events[] = (object)array(
                'name' => $taskchain->name.' ('.$strcloses.')',
                'eventtype' => 'close',
                'timestart' => $chain->timeclose,
                'timeduration' => 0,
            );
        }
    }

    // cache description and visiblity (saves doing it twice for long events)
    if (empty($chain->entrytext)) {
        $description = '';
    } else {
        $description = $chain->entrytext;
    }
    $visible = instance_is_visible('taskchain', $taskchain);

    foreach ($events as $event) {
        $event->groupid = 0;
        $event->userid = 0;
        $event->courseid = $taskchain->course;
        $event->modulename = 'taskchain';
        $event->instance = $taskchain->id;
        $event->description = $description;
        $event->visible = $visible;
        if (count($eventids)) {
            $event->id = array_shift($eventids);
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event, $checkcapabilties);
        } else {
            calendar_event::create($event, $checkcapabilties);
        }
    }

    // delete surplus events, if required
    // (no need to check capabilities here)
    if ($delete) {
        while (count($eventids)) {
            $id = array_shift($eventids);
            $event = calendar_event::load($id);
            $event->delete();
        }
    }
}

/**
 * taskchain_set_missing_fields
 *
 * @uses $DB
 * @param xxx $table (passed by reference)
 * @param xxx $record (passed by reference)
 * @param xxx $formdata (passed by reference)
 * @param string $prefix prefix to add to field name for user preferences
 * @param array $fieldnames list of fields to be gotten from user preferences
 * @todo Finish documenting this function
 */
function taskchain_set_missing_fields($table, &$record, &$formdata, $fieldnames) {
    global $DB;

    // get info about table columns
    static $columns = array();
    if (empty($columns[$table])) {
        $columns[$table] = $DB->get_columns($table);
    }

    // set all empty fields (except "id")
    foreach ($columns[$table] as $column) {
        $name = $column->name;
        if ($name=='id') {
            // do nothing
        } else if (isset($formdata->$name)) {
            $record->$name = $formdata->$name;
        } else if (isset($record->$name)) {
            // do nothing
        } else {
            // first, we try to get a value from the user_preferences
            if (in_array($name, $fieldnames)) {
                $default = get_user_preferences($table.$name);
            } else {
                $default = null;
            }
            if (is_null($default)) {
                // next, we try to get the field's default setting
                if (isset($column->default_value)) {
                    $default = $column->default_value;
                } else {
                    // finally, if there isn't a default value for this field,
                    // we use a sensible default value for this type of field
                    if (preg_match('/[INTD]/', $column->meta_type)) {
                        $default = 0;
                    } else {
                        $default = '';
                    }
                }
            }
            $record->$name = $default;
        }
    }
}

/**
 * taskchain_get_userfields
 *
 * @param string $tableprefix name of database table prefix in query
 * @param array  $extrafields extra fields to be included in result (do not include TEXT columns because it would break SELECT DISTINCT in MSSQL and ORACLE)
 * @param string $idalias     alias of id field
 * @param string $fieldprefix prefix to add to all columns in their aliases, does not apply to 'id'
 * @return string
 */
 function taskchain_get_userfields($tableprefix = '', array $extrafields = NULL, $idalias = 'id', $fieldprefix = '') {
    if (class_exists('user_picture')) { // Moodle >= 2.6
        return user_picture::fields($tableprefix, $extrafields, $idalias, $fieldprefix);
    }
    // Moodle <= 2.5
    $fields = array('id', 'firstname', 'lastname', 'picture', 'imagealt', 'email');
    if ($tableprefix || $extrafields || $idalias) {
        if ($tableprefix) {
            $tableprefix .= '.';
        }
        if ($extrafields) {
            $fields = array_unique(array_merge($fields, $extrafields));
        }
        if ($idalias) {
            $idalias = " AS $idalias";
        }
        if ($fieldprefix) {
            $fieldprefix = " AS $fieldprefix";
        }
        foreach ($fields as $i => $field) {
            $fields[$i] = "$tableprefix$field".($field=='id' ? $idalias : ($fieldprefix=='' ? '' : "$fieldprefix$field"));
        }
    }
    return implode(',', $fields);
    //return 'u.id AS userid, u.username, u.firstname, u.lastname, u.picture, u.imagealt, u.email';
}

/**
 * Obtains the automatic completion state for this taskchain
 * based on the conditions in taskchain settings.
 *
 * @param  object  $course record from "course" table
 * @param  object  $cm     record from "course_modules" table
 * @param  integer $userid id from "user" table
 * @param  bool    $type   of comparison (or/and; used as return value if there are no conditions)
 * @return mixed   TRUE if completed, FALSE if not, or $type if no conditions are set
 */
function taskchain_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

    // set default return $state
    $state = $type;

    // get the taskchain record
    if ($taskchain = $DB->get_record('taskchain', array('id' => $cm->instance))) {

        // get grade, if necessary
        $grade = false;
        if ($taskchain->completionmingrade || $taskchain->completionpass) {
            require_once($CFG->dirroot.'/lib/gradelib.php');
            $params = array('courseid'     => $course->id,
                            'itemtype'     => 'mod',
                            'itemmodule'   => 'taskchain',
                            'iteminstance' => $cm->instance);
            if ($grade_item = grade_item::fetch($params)) {
                $grades = grade_grade::fetch_users_grades($grade_item, array($userid), false);
                if (isset($grades[$userid])) {
                    $grade = $grades[$userid];
                }
                unset($grades);
            }
            unset($grade_item);
        }

        // the TaskChain completion conditions
        $conditions = array('completionmingrade',
                            'completionpass',
                            'completioncompleted');

        foreach ($conditions as $condition) {
            if (empty($taskchain->$condition)) {
                continue;
            }
            switch ($condition) {
                case 'completionmingrade':
                    $state = ($grade && $grade->finalgrade >= $taskchain->completionmingrade);
                    break;
                case 'completionpass':
                    $state = ($grade && $grade->is_passed());
                    break;
                case 'completioncompleted':
                    require_once($CFG->dirroot.'/mod/taskchain/locallib.php');
                    $params = array('parenttype' => mod_taskchain::PARENTTYPE_ACTIVITY,
                                    'parentid'   => $cm->instance,
                                    'userid'     => $userid,
                                    'status'     => mod_taskchain::STATUS_COMPLETED);
                    $state = $DB->record_exists('taskchain_chain_grades', $params);
                    break;

            }
            // finish early if possible
            if ($type==COMPLETION_AND && $state==false) {
                return false;
            }
            if ($type==COMPLETION_OR && $state) {
                return true;
            }
        }
    }

    return $state;
}
