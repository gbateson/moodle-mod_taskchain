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

        // root element describing taskchain instance
        $paths[] = new restore_path_element('taskchain', '/activity/taskchain');

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - user data
        ////////////////////////////////////////////////////////////////////////

        if ($userinfo) {
            $paths[] = new restore_path_element('taskchain_string',   '/activity/taskchain/strings/string');
            $paths[] = new restore_path_element('taskchain_attempt',  '/activity/taskchain/attempts/attempt');
            $paths[] = new restore_path_element('taskchain_question', '/activity/taskchain/questions/question');
            $paths[] = new restore_path_element('taskchain_response', '/activity/taskchain/questions/question/responses/response');
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
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the taskchain record
        $newid = $DB->insert_record('taskchain', $data);

        // inmediately after inserting "activity" record, call this
        $this->apply_activity_instance($newid);
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

        // get $newid
        $conditions = array('md5key' => $data->md5key);
        if ($newid = $DB->get_field('taskchain_strings', 'id', $conditions)) {
            // this string already exists in the destination $DB
        } else {
            $newid = $DB->insert_record('taskchain_strings', $data);
        }

        // store mapping from $oldid to $newid
        $this->set_mapping('taskchain_strings', $oldid, $newid);
    }

    /**
     * process_taskchain_attempt
     *
     * @uses $DB
     * @param xxx $data
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function process_taskchain_attempt($data)  {
        global $DB;

        // convert $data to object
        $data = (object)$data;

        // save $oldid
        $oldid = $data->id;

        // fix fields
        if (! $data->userid = $this->get_mappingid('user', $data->userid)) {
            return false; // invalid userid - shouldn't happen !!
        }
        if (! $data->taskchainid = $this->get_new_parentid('taskchain')) {
            return false; // taskchainid not available - shouldn't happen !!
        }

        // get $newid
        if (! $newid = $DB->insert_record('taskchain_attempts', $data)) {
            return false; // could not add new attempt - shouldn't happen !!
        }

        // store mapping from $oldid to $newid
        $this->set_mapping('taskchain_attempts', $oldid, $newid);

        // reset clickreportid to point to parent attempt
        if (empty($data->clickreportid) || $data->clickreportid==$oldid) {
            // clickreporting is not enabled (this is the usual case)
            $clickreportid = $newid;
        } else {
            // clickreporting is enabled, so get main attempt id
            $clickreportid = $this->get_mappingid('taskchain_attempt', $data->clickreportid);
        }
        if (empty($clickreportid)) {
            $clickreportid = $newid; // old attempt id not avialable - shouldn't happen !!
        }
        $DB->set_field('taskchain_attempts', 'clickreportid', $clickreportid, array('id' => $newid));
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
        if (! $data->taskchainid = $this->get_new_parentid('taskchain')) {
            return false; // taskchainid not available - shouldn't happen !!
        }
        $data->md5key = md5($data->name);
        $this->set_string_ids($data, array('text'), 0);

        // get $newid
        if (! $newid = $DB->insert_record('taskchain_questions', $data)) {
            return false; // could not add new question - shouldn't happen !!
        }

        // store mapping from $oldid to $newid
        $this->set_mapping('taskchain_questions', $oldid, $newid);
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
        if (! $data->questionid = $this->get_new_parentid('question')) {
            return false; // questionid not available - shouldn't happen !!
        }
        if (! isset($data->attemptid)) {
            return false; // attemptid not set - shouldn't happen !!
        }
        if (! $data->attemptid = $this->get_mappingid('taskchain_attempts', $data->attemptid)) {
            return false; // new attemptid not available - shouldn't happen !!
        }
        $this->set_string_ids($data, array('correct', 'wrong', 'ignored'));

        // get $newid
        if (! $newid = $DB->insert_record('taskchain_responses', $data)) {
            return false; // could not add new response - shouldn't happen !!
        }

        // store mapping from $oldid to $newid
        $this->set_mapping('taskchain_attempts', $oldid, $newid);
    }

    /**
     * after_execute
     */
    protected function after_execute()  {
        $this->add_related_files('mod_taskchain', 'entrytext', null);
        $this->add_related_files('mod_taskchain', 'exittext',  null);
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
                foreach ($oldids as $oldid) {
                    if ($newid = $this->get_mappingid('taskchain_strings', $oldid)) {
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
}
