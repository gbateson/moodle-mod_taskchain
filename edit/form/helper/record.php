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
 * mod/taskchain/form/record.php
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
require_once(dirname(__FILE__).'/base.php');

/**
 * taskchain_form_helper_record
 *
 * methods to format, validate and fix common form sections
 * and elements for fields in "chain" and "task" records
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
abstract class taskchain_form_helper_record extends taskchain_form_helper_base {

    /** should we remove user draft files (true) or not (false) */
    const DELETE_USER_DRAFT_FILES = false;

    /////////////////////////////////////////////////////////
    // prepare_field ...
    /////////////////////////////////////////////////////////

    /**
     * prepare_field_sourcefile
     *
     * @param array $data (passed by reference)
     * @todo Finish documenting this function
     */
    protected function prepare_field_sourcefile(&$data) {
        $this->prepare_template_filearea($data, 'source');
    }

    /**
     * prepare_field_configfile
     *
     * @param array $data (passed by reference)
     * @todo Finish documenting this function
     */
    protected function prepare_field_configfile(&$data) {
        $this->prepare_template_filearea($data, 'config');
    }

    /**
     * prepare_field_timelimit
     *
     * @param array $data (passed by reference)
     * @todo Finish documenting this function
     */
    protected function prepare_field_timelimit(&$data) {
        $name = $this->get_fieldname('timelimit');
        $namespecific = $this->get_fieldname('timelimitspecific');
        if ($data[$name]>0) {
            $data[$namespecific] = $data[$name];
            $data[$name] = mod_taskchain::TIME_SPECIFIC;
        } else {
            $data[$name] = 0;
        }
    }

    /////////////////////////////////////////////////////////
    // prepare_template ...
    /////////////////////////////////////////////////////////

    /**
     * set up a draft file areas
     *
     * @param array $data initial data values
     * @param string $type if filearea ("source" or "config")
     * @return void
     * @todo Finish documenting this function
     */
    protected function prepare_template_filearea(&$data, $type) {

        // set parameters for accessing filearea
        if ($this->is_add()) {
            $contextid = null;
        } else {
            $contextid = $this->context->id;
        }
        $component = 'mod_taskchain';
        $filearea  = $type.'file';
        $options   = mod_taskchain::filearea_options();
        $itemid    = 0;

        // set current source file as the "main file" in this filearea
        if (isset($data['sourcefile'])) {
            $sourcefile = $data['sourcefile'];

            $fs = get_file_storage();
            $area_files = $fs->get_area_files($contextid, $component, $filearea, $itemid);

            foreach ($area_files as $file) {
                if ($file->is_directory()) {
                    continue;
                }
                if ($sourcefile==$file->get_filepath().$file->get_filename()) {
                    $sortorder = 1;
                } else {
                    $sortorder = 0;
                }
                if ($sortorder==$file->get_sortorder()) {
                    // do nothing
                } else {
                    $file->set_sortorder($sortorder);
                }
            }
        }

        // Note: if you call "file_prepare_draft_area()" without setting itemid
        // (the first argument), then it will be assigned automatically, and the files
        // for this context will be transferred automatically, which is what we want
        $data[$type.'itemid'] = $itemid;
        file_prepare_draft_area($data[$type.'itemid'], $contextid, $component, $filearea, 0, $options);
    }

    /////////////////////////////////////////////////////////
    // get_section_label ...
    /////////////////////////////////////////////////////////

    /**
     * get_sectionlabel_general
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_sectionlabel_general() {
        return get_string('general', 'form');
    }

    /**
     * get_sectionlabel_tasks
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_sectionlabel_tasks() {
        return get_string('tasks', 'taskchain');
    }

    /**
     * get_sectionlabel_display
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_sectionlabel_display() {
        return get_string('display', 'form');
    }

    /////////////////////////////////////////////////////////
    // get_field_label ...
    /////////////////////////////////////////////////////////

    /**
     * get_fieldlabel_edit
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_fieldlabel_edit() {
        return get_string('action'); // 'edit'
    }

    /**
     * get_fieldlabel_defaultrecord
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_fieldlabel_defaultrecord() {
        return get_string('default');
    }

    /**
     * get_fieldlabel_select
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_fieldlabel_selectrecord() {
        return get_string('select');
    }

    /**
     * get_fieldlabel_name
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_fieldlabel_name() {
        return get_string('name');
    }

    /**
     * get_fieldlabel_attemptlimit
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_fieldlabel_attemptlimit() {
        return get_string('attemptsallowed', 'taskchain');
    }

    /**
     * get_fieldlabel_password
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_fieldlabel_password() {
        return get_string('requirepassword', 'taskchain');
    }

    /**
     * get_fieldlabel_subnet
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_fieldlabel_subnet() {
        return get_string('requiresubnet', 'taskchain');
    }

    /////////////////////////////////////////////////////////
    // add_field ...
    /////////////////////////////////////////////////////////

    /**
     * add_field_sortorder
     *
     * @todo Finish documenting this function
     */
    protected function add_field_sortorder($field) {
        if ($this->multiple) {
            $name = $this->get_fieldname($field);
            $label = ''; // $this->get_fieldlabel($field);
            $this->mform->addElement('text', $name, $label, array('size' => 2));
            $this->mform->setType($name, PARAM_INT);
            $this->mform->setDefault($name, $this->get_fieldvalue($field));
        }
    }

    /**
     * add_field_edit
     *
     * @todo Finish documenting this function
     */
    protected function add_field_edit($field) {
        // do nothing
    }

    /**
     * add_field_defaultrecord
     *
     * @todo Finish documenting this function
     */
    protected function add_field_defaultrecord($field) {
        // do nothing
    }

    /**
     * add_field_selectrecord
     *
     * @todo Finish documenting this function
     */
    protected function add_field_selectrecord($select) {
        // do nothing
    }

    /**
     * add_field_name
     *
     * @todo Finish documenting this function
     */
    protected function add_field_name($name) {
        $params = array('required' => false,
                        'advanced' => false,
                        'hiddentextfield' => false);
        $this->add_template_textsource('name', $params);
    }

    /**
     * add_field_sourcefile
     *
     * @todo Finish documenting this function
     */
    protected function add_field_sourcefile($field) {
        $params = array('required' => true,
                        'advanced' => false);
        $this->add_template_file('source', $params);
    }

    /**
     * add_field_sourcetype
     *
     * @todo Finish documenting this function
     */
    protected function add_field_sourcetype($field) {
        // do nothing
    }

    /**
     * add_field_sourcelocation
     *
     * @todo Finish documenting this function
     */
    protected function add_field_sourcelocation($field) {
        $this->add_template_location('source');
    }

    /**
     * add_field_configfile
     *
     * @todo Finish documenting this function
     */
    protected function add_field_configfile($field) {
        $params = array('required' => false,
                        'advanced' => true);
        $this->add_template_file('config', $params);
    }

    /**
     * add_field_configlocation
     *
     * @todo Finish documenting this function
     */
    protected function add_field_configlocation($field) {
        $this->add_template_location('config');
    }

    /**
     * add_field_addtype
     *
     * @todo Finish documenting this function
     */
    protected function add_field_addtype($field) {
        $field = 'addtype';
        $name  = $this->get_fieldname($field);
        if ($this->is_add()) {
            $label = $this->get_fieldlabel($field);
            $list  = 'available_'.$field.'s_list';
            $this->mform->addElement('select', $name, $label, $this->TC->$list());
            $this->mform->setDefault($name, $this->get_defaultvalue($field));
            $this->mform->addHelpButton($name, $field, 'taskchain');
            //$this->mform->setAdvanced($name);
        } else {
            $this->mform->addElement('hidden', $name, 0);
        }
    }

    /**
     * add_field_tasknames
     *
     * @todo Finish documenting this function
     */
    protected function add_field_tasknames($field) {
        $params = array('required' => false,
                        'advanced' => false,
                        'hiddentextfield' => true);
        $this->add_template_textsource($field, $params);
    }

    /**
     * add_field_timeopen
     *
     * @todo Finish documenting this function
     */
    protected function add_field_timeopen($field) {
        $this->add_template_time($field);
    }

    /**
     * add_field_timeclose
     *
     * @todo Finish documenting this function
     */
    protected function add_field_timeclose($field) {
        $this->add_template_time($field);
    }

    /**
     * add_field_timelimit
     *
     * @todo Finish documenting this function
     */
    protected function add_field_timelimit($field) {
        $this->add_template_timer($field);
    }

    /**
     * add_field_delay1
     *
     * @todo Finish documenting this function
     */
    protected function add_field_delay1($field) {
        $this->add_template_timer($field);
    }

    /**
     * add_field_delay2
     *
     * @todo Finish documenting this function
     */
    protected function add_field_delay2($field) {
        $this->add_template_timer($field);
    }

    /**
     * add_field_attemptlimit
     *
     * @todo Finish documenting this function
     */
    protected function add_field_attemptlimit($field) {
        $field = 'attemptlimit';
        $name  = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);
        $list  = 'available_'.$field.'s_list';
        $this->mform->addElement('select', $name, $label, $this->TC->$list());
        $this->mform->setDefault($name, $this->get_defaultvalue($field));
        $this->mform->setAdvanced($name);
        $this->mform->addHelpButton($name, $field, 'taskchain');
    }

    /**
     * add_field_allowresume
     *
     * @todo Finish documenting this function
     */
    protected function add_field_allowresume($field) {
        $name  = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);
        $list  = 'available_'.$field.'s_list';
        $this->mform->addElement('select', $name, $label, $this->TC->$list());
        $this->mform->addHelpButton($name, $field, 'taskchain');
        $this->mform->setAdvanced($name);
    }

    /**
     * add_field_password
     *
     * @todo Finish documenting this function
     */
    protected function add_field_password($field) {
        $name  = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);
        $this->mform->addElement('text', $name, $label);
        $this->mform->setType($name, PARAM_TEXT);
        $this->mform->addHelpButton($name, 'requirepassword', 'taskchain');
        $this->mform->setAdvanced($name);
    }

    /**
     * add_field_subnet
     *
     * @todo Finish documenting this function
     */
    protected function add_field_subnet($field) {
        $name  = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);
        $this->mform->addElement('text', $name, $label);
        $this->mform->setType($name, PARAM_TEXT);
        $this->mform->addHelpButton($name, 'requiresubnet', 'taskchain');
        $this->mform->setAdvanced($name);
    }

    /////////////////////////////////////////////////////////
    // add_template ...
    /////////////////////////////////////////////////////////

    /**
     * add_template_time
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function add_template_time($field) {
        $name  = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);
        $this->mform->addElement('date_time_selector', $name, $label, array('optional' => true));
        $this->mform->addHelpButton($name, 'timeopenclose', 'taskchain');
        $this->mform->setAdvanced($name);
    }

    /**
     * add_template_timer
     *
     * @param string $field name of field
     * @param boolean $summary name of field
     * @param mixed either an empty value or an array of select fields to be added $before the timer
     * @param mixed either an empty value or an array of select fields to be added $after the timer
     * @param boolean $advanced true if this is an advanced field, otherwise false
     * @todo Finish documenting this function
     */
    protected function add_template_timer($field, $summary=true, $before=false, $after=false, $advanced=true) {
        $name  = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);

        $name_elements = $this->get_fieldname($field.'_elements');
        $elements = array();

        if ($summary) {
            $elements[] = $this->mform->createElement('static', '', '', get_string($field.'summary', 'taskchain'));
            $elements[] = $this->mform->createElement('static', '', '', html_writer::empty_tag('br'));
        }

        if ($before) {
            foreach ($before as $extraname => $extraoptions) {
                $extraname = $this->get_fieldname($field.$extraname);
                $elements[] = $this->mform->createElement('select', $extraname, '', $extraoptions);
            }
        }

        $optional = (($before || $after) ? 0 : 1);
        $elements[] = $this->mform->createElement('duration', $name, '', array('optional'=>$optional, 'defaultunit'=>1));

        if ($after) {
            foreach ($after as $extraname => $extraoptions) {
                $extraname = $this->get_fieldname($field.$extraname);
                $elements[] = $mform->createElement('select', $extraname, '', $extraoptions);
            }
        }

        $this->mform->addGroup($elements, $name_elements, $label, '', false);
        $this->mform->addHelpButton($name_elements, $field, 'taskchain');

        // the standard disabledIf for the "enable" checkbox doesn't work because we are in group, so ...
        if ($optional) {
            $this->mform->disabledIf($name.'[number]', $name.'[enabled]', 'notchecked');
            $this->mform->disabledIf($name.'[timeunit]', $name.'[enabled]', 'notchecked');
        }
        $this->mform->setDefault($name.'[enabled]', 0);

        if ($before) {
            foreach ($before as $extraname => $extraoptions) {
                $extraname = $this->get_fieldname($field.$extraname);
                $this->mform->setType($extraname, PARAM_INT);
                if ($optional) {
                    $this->mform->disabledIf($extraname, $name.'[enabled]', 'notchecked');
                }
            }
        }

        if ($after) {
            foreach ($before as $extraname => $extraoptions) {
                $extraname = $this->get_fieldname($field.$extraname);
                $this->mform->setType($extraname, PARAM_INT);
                if ($optional) {
                    $this->mform->disabledIf($extraname, $name.'[enabled]', 'notchecked');
                }
            }
        }

        if ($advanced) {
            $this->mform->setAdvanced($name_elements);
        }
    }

    /**
     * add_template_file
     *
     * @param string $type of file ("source" or "config")
     * @param array $params array of parameters, particularly "required" and "advanced"
     * @todo Finish documenting this function
     */
    protected function add_template_file($type, $params) {
        $name = $this->get_fieldname($type.'itemid');
        $label = $this->get_fieldlabel($type.'file');
        $options = array('subdirs'  => 1,
                         'maxbytes' => 0,
                         'maxfiles' => -1,
                         'mainfile' => true,
                         'accepted_types' => '*');
        $this->mform->addElement('filemanager', $name, $label, null, $options);
        if ($params['required']) {
            $this->mform->addRule($name, null, 'required', null, 'client');
        }
        if ($params['advanced']) {
            $this->mform->setAdvanced($name);
        }
        $this->mform->addHelpButton($name, $type.'file', 'taskchain');
    }

    /**
     * add_template_location
     *
     * @param string $type of location ("source" or "config")
     * @todo Finish documenting this function
     */
    protected function add_template_location($type) {
        $name = $this->get_fieldname($type.'location');
        $location = $this->get_original_value($name, 0);
        $this->mform->addElement('hidden', $name, $location);
        $this->mform->setType($name, PARAM_INT);
    }

    /**
     * add_template_textsource
     *
     * @param string $field name of field
     * @param array $params array of parameters, particularly "required" and "advanced"
     * @todo Finish documenting this function
     */
    protected function add_template_textsource($field, $params) {

        // name of required and advanced fields, if any
        $required = '';
        $advanced = '';

        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);
        $size = array('size' => self::TEXT_FIELD_SIZE);

        $name_source = $this->get_fieldname($field.'source');
        $name_elements = $this->get_fieldname($field.'elements');

        if ($this->is_add()) {
            // create a group of form $elements
            $elements = array(
                $this->mform->createElement('select', $name_source, '', $this->TC->available_namesources_list()),
                $this->mform->createElement('text', $name, '', $size)
            );

            $this->mform->addGroup($elements, $name_elements, $label, array(' '), false);
            $this->mform->disabledIf($name_elements, $name_source, 'ne', mod_taskchain::TEXTSOURCE_SPECIFIC);

            $default = $this->get_defaultvalue($name_source);
            $this->mform->setDefault($name_source, $default);
            $this->mform->addHelpButton($name_elements, $field, 'taskchain');

            if ($params['required']) {
                $required = $name_elements;
            }
            if ($params['advanced']) {
                $advanced = $name_elements;
            }
        } else {
            // we are updating - hide text field if it is not needed
            if ($params['hiddentextfield']) {
                $this->mform->addElement('hidden', $name_source, 0);
                $this->mform->addElement('hidden', $name, '');
            } else {
                $this->mform->addElement('text', $name, $label, $size);
                $this->mform->addElement('hidden', $name_source, mod_taskchain::TEXTSOURCE_SPECIFIC);
                $this->mform->addHelpButton($name, $field, 'taskchain');
                $this->mform->addRule($name, get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
                if ($params['required']) {
                    $required = $name;
                }
                if ($params['advanced']) {
                    $advanced = $name;
                }
            }
        }

        if ($required) {
            $this->mform->addRule($required, null, 'required', null, 'client');
        }
        if ($advanced) {
            $this->mform->setAdvanced($advanced);
        }

        $this->mform->setType($name_source, PARAM_INT);
        $this->set_type_text($field);
    }

    /**
     * add_template_method
     *
     * @param string $type of method ("grade" or "score")
     * @todo Finish documenting this function
     */
    protected function add_template_method($type) {
        $this->add_template_list($type.'method');
    }

    /**
     * add_template_ignore
     *
     * @param string $type of ignore flag ("grade" or "score")
     * @todo Finish documenting this function
     */
    protected function add_template_ignore($type) {
        $field = $type.'ignore';
        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);
        $name_method = $this->get_fieldname($field.'method');
        $this->mform->addElement('selectyesno', $name, $label);
        $this->mform->setType($name, PARAM_INT);
        $this->mform->setDefault($name, $this->get_defaultvalue($field));
        $this->mform->addHelpButton($name, $field, 'taskchain');
        $this->mform->setAdvanced($name);
        $this->mform->disabledIf($name, $name_method, 'eq', mod_taskchain::GRADEMETHOD_HIGHEST);
    }

    /**
     * add_template_weighting
     *
     * @param string $type of weighting ("grade" or "score")
     * @todo Finish documenting this function
     */
    protected function add_template_weighting($type) {
        $this->add_template_list($type.'weighting', true);
    }

    /**
     * add_template_limit
     *
     * @param string $type of limit ("grade" or "score")
     * @todo Finish documenting this function
     */
    protected function add_template_limit($type) {
        $this->add_template_list($type.'limit', true);
    }

    /////////////////////////////////////////////////////////
    // validate_field ...
    /////////////////////////////////////////////////////////

    /**
     * validate_field_sourcefile
     *
     * @param array $errors (passed by reference)
     * @param array $data (passed by reference)
     * @param array $files (passed by reference)
     * @todo Finish documenting this function
     */
    protected function validate_field_sourcefile(&$errors, &$data, &$files) {
        global $USER;

        if ($this->multiple) {
            return;
        }

        $fs = get_file_storage();
        $usercontext = $this->TC->context(CONTEXT_USER, $USER->id);

        // check some files have been uploaded
        if ($fs->is_area_empty($usercontext->id, 'user', 'draft', $data['sourceitemid'])) {
            $errors['sourceitemid'] = get_string('required');
        }
    }

    /////////////////////////////////////////////////////////
    // fix_field ...
    /////////////////////////////////////////////////////////

    /**
     * fix_field_name
     *
     * @param object $data (passed by reference) from form
     * @param string name of the $field to fix
     * @todo Finish documenting this function
     */
    protected function fix_field_name(&$data, $field) {
        $this->fix_template_textsource($data, $field, $this->get_default_name());
    }

    /**
     * fix_field_sourcefile
     *
     * @param object $data (passed by reference) from form
     * @param string name of the $field to fix
     * @todo Finish documenting this function
     */
    protected function fix_field_sourcefile(&$data, $field) {
        $this->fix_template_filearea($data, 'source');
    }

    /**
     * fix_field_configfile
     *
     * @param object $data (passed by reference) from form
     * @param string name of the $field to fix
     * @todo Finish documenting this function
     */
    protected function fix_field_configfile(&$data, $field) {
        $this->fix_template_filearea($data, 'config');
    }

    /**
     * fix_field_tasknames
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function fix_field_tasknames(&$data, $field) {
        $this->fix_template_textsource($data, $field);
    }

    /////////////////////////////////////////////////////////
    // fix_template ...
    /////////////////////////////////////////////////////////

    /**
     * fix_template_filearea
     *
     * @param array $data (passed by reference)
     * @param string $type of filearea ("source", "config")
     * @return mixed file object if one was found, or null if no file was found
     * @todo Finish documenting this function
     */
    protected function fix_template_filearea(&$data, $type) {
        $component = 'mod_taskchain';
        $filearea  = $type.'file';
        $itemid    = $type.'itemid';

        if ($data->$itemid) {
            $options = mod_taskchain::filearea_options();
            file_save_draft_area_files($data->$itemid, $this->context->id, $component, $filearea, 0, $options);

            // set main file, if necessary
            $mainfile = taskchain_pluginfile_mainfile($this->context, $component, $filearea);
            if ($mainfile) {
                if ($mainfile->get_sortorder()==0) {
                    if (method_exists($mainfile, 'set_sortorder')) {
                        $mainfile->set_sortorder(1);
                    } else {
                        $mainfile->sortorder = 1;
                    }
                }
            }

            if (self::DELETE_USER_DRAFT_FILES) {
                $this->delete_user_draft_files($data->$itemid);
            }
        }
    }

    /**
     * delete_user_draft_files
     *
     * @param integer $itemid of connected with user draft area to delete
     * @return void, but will remove any files in user draft area
     */
    protected function delete_user_draft_files($itemid) {
        global $USER;
        $fs = get_file_storage();
        $usercontext = $this->TC->context(CONTEXT_USER, $USER->id);
        $fs->delete_area_files($usercontext->id, 'user', 'draft', $itemid);
    }

    /**
     * fix_template_textsource
     *
     * @param object $data (passed by reference) from form
     * @param string $field name of text field ("name", "tasknames", "entrytext" or "exittext")
     * @todo Finish documenting this function
     */
    protected function fix_template_textsource(&$data, $field, $default='') {
        $name = $this->get_fieldname($field);
        $textsource = $this->get_fieldname($field.'source');

        if ($this->is_add() && isset($data->$textsource)) {
            // do nothing - i.e. use the value from the form
        } else {
            $data->$textsource = mod_taskchain::TEXTSOURCE_SPECIFIC;
        }

        switch ($data->$textsource) {

            case mod_taskchain::TEXTSOURCE_FILE:
            case mod_taskchain::TEXTSOURCE_FILENAME:
            case mod_taskchain::TEXTSOURCE_FILEPATH:
                $data->$name = ''; // may get reset later, after we have located all the task files
                $default     = ''; // don't reset $field
                break;

            case mod_taskchain::TEXTSOURCE_SPECIFIC:
            default:
                if (isset($data->$field)) {
                    // remove leading and trailing white space, empty html paragraphs (from IE) and blank lines (from Firefox)
                    $data->$name = preg_replace('/^((<p>\s*<\/p>)|(<br[^>]*>)|\s)+/is', '', $data->$field);
                    $data->$name = preg_replace('/((<p>\s*<\/p>)|(<br[^>]*>)|\s)+$/is', '', $data->$field);
                    $data->$name = trim($data->$field);
                } else {
                    $data->$name = $this->get_original_value($name, '');
                }
        }

        if ($data->$name=='') {
            $data->$name = $default;
        }
    }

    /////////////////////////////////////////////////////////
    // format_field ...
    /////////////////////////////////////////////////////////

    /**
     * format_fieldlabel_addtype
     *
     * @todo Finish documenting this function
     */
    protected function format_fieldlabel_addtype() {
        // do nothing
    }

    /**
     * format_fieldlabel_tasknames
     *
     * @todo Finish documenting this function
     */
    protected function format_fieldlabel_tasknames() {
        // do nothing
    }

    /**
     * format_defaultfield_addtype
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_defaultfield_addtype($field) {
        // do nothing
    }

    /**
     * format_defaultfield_tasknames
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_defaultfield_tasknames($field) {
        // do nothing
    }

    /**
     * format_defaultfield_aftertaskid
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_defaultfield_aftertaskid($field) {
        // do nothing
    }

    /**
     * format_selectfield_addtype
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_selectfield_addtype($field) {
        // do nothing
    }

    /**
     * format_selectfield_tasknames
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_selectfield_tasknames($field) {
        // do nothing
    }

    /**
     * format_selectfield_aftertaskid
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_selectfield_aftertaskid($field) {
        // do nothing
    }

    /**
     * format_fieldvalue_sourcelocation
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_sourcelocation($field, $value) {
        return $this->format_templatevalue_list($field, $value, 'location');
    }

    /**
     * format_fieldvalue_configlocation
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_configlocation($field, $value) {
        return $this->format_templatevalue_list($field, $value, 'location');
    }

    /**
     * format_fieldvalue_navigation
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_navigation($field, $value) {
        return $this->format_templatevalue_list($field, $value);
    }

    /**
     * format_fieldvalue_title
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_title($field, $value) {
        return $this->format_templatevalue_list($field, $value);
    }

    /**
     * format_fieldvalue_stopbutton
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_stopbutton($field, $value) {
        return $this->format_templatevalue_yesno($field, $value);
    }

    /**
     * format_fieldvalue_usefilters
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_usefilters($field, $value) {
        return $this->format_templatevalue_yesno($field, $value);
    }

    /**
     * format_fieldvalue_useglossary
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_useglossary($field, $value) {
        return $this->format_templatevalue_yesno($field, $value);
    }

    /**
     * format_fieldvalue_usemediafilter
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_usemediafilter($field, $value) {
        return $this->format_templatevalue_yesno($field, $value);
    }

    /**
     * format_fieldvalue_studentfeedback
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_studentfeedback($field, $value) {
        return $this->format_templatevalue_list($field, $value, 'feedback');
    }

    /**
     * format_fieldvalue_timeopen
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_timeopen($field, $value) {
        return $this->format_templatevalue_time($field, $value);
    }

    /**
     * format_fieldvalue_timeclose
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_timeclose($field, $value) {
        return $this->format_templatevalue_time($field, $value);
    }

    /**
     * format_fieldvalue_timelimit
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_timelimit($field, $value) {
        if ($value==mod_taskchain::TIME_TEMPLATE) { // =-1
            return get_string('timelimittemplate', 'taskchain');
        } else {
            return $this->format_templatevalue_timer($field, $value);
        }
    }

    /**
     * format_fieldvalue_delay1
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_delay1($field, $value) {
        return $this->format_templatevalue_timer($field, $value);
    }

    /**
     * format_fieldvalue_delay2
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_delay2($field, $value) {
        return $this->format_templatevalue_timer($field, $value);
    }

    /**
     * format_fieldvalue_attemptlimit
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_attemptlimit($field, $value) {
        return $this->format_templatevalue_list($field, $value);
    }

    /**
     * format_fieldvalue_allowresume
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_allowresume($field, $value) {
        return $this->format_templatevalue_list($field, $value);
    }

    /////////////////////////////////////////////////////////
    // format conditions ...
    /////////////////////////////////////////////////////////

    /**
     * format_conditions
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function format_conditions($taskid, $conditiontype, $return_intro=true, $return_js=false, $return_commands=true, $default='') {
        $str = '';

        if ($conditions = $this->TC->get_conditions($conditiontype, $taskid)) {
            $li = array();
            foreach ($conditions as $condition) {
                if ($formatted_condition = $this->format_condition($condition, false, $return_commands)) {
                    if (empty($li[$condition->sortorder])) {
                        $li[$condition->sortorder] = '';
                    }
                    $li[$condition->sortorder] .= '<li>'.$formatted_condition.'</li>';
                }
            }
            if (count($li)) {
                $or = '</ul><p class="taskchainconditionsor">'.get_string('or', 'taskchain').'</p><ul>';
                $str = '<ul>'.implode($or, $li).'</ul>';
            }
            unset($li);
        }

        if ($str) {
            if ($return_intro) {
                if ($conditiontype==mod_taskchain::CONDITIONTYPE_PRE) {
                    $intro = get_string('preconditions_help', 'taskchain');
                } else {
                    $intro = get_string('postconditions_help', 'taskchain');
                }
                $str = '<p class="taskchainconditionsintro">'.$intro.'</p>'.$str;
            }
        } else {
            $str = $default; // e.g. '&nbsp;' for a table cell
        }

        // append icons for add and delete
        if ($return_commands) {
            $str .= $this->format_commands_conditions($conditiontype, $taskid);
        }

        if ($return_js) {
            return addslashes_js($str);
        }

        if ($return_commands) {
            $id = $taskid;
        } else {
            $id = 'default';
        }
        if ($conditiontype==mod_taskchain::CONDITIONTYPE_PRE) {
            $id = 'taskchain_preconditions_'.$id;
        } else {
            $id = 'taskchain_postconditions_'.$id;
        }
        return html_writer::tag('div', $str, array('id'=>$id, 'class'=>'conditions'));
    }

    /**
     * format_condition
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function format_condition(&$condition, $return_js=false, $return_commands=true) {
        $str ='';

        static $groupnames = array();
        if ($condition->groupid) {
            $gid = $condition->groupid;
            if (! isset($groupnames[$gid])) {
                $groupnames[$gid] = groups_get_group_name($gid);
            }
            $str .= $groupnames[$gid].': ';
        }

        switch ($condition->conditiontype) {

            case mod_taskchain::CONDITIONTYPE_PRE:

                switch ($condition->conditiontaskid) {

                    case mod_taskchain::CONDITIONTASKID_SAME:
                        $str .= get_string('sametask', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_PREVIOUS:
                        $str .= get_string('previoustask', 'taskchain');
                        break;

                    default:
                        // specific task id
                        if ($this->TC->get_tasks() && isset($this->TC->tasks[$condition->conditiontaskid])) {
                            $name = $this->TC->tasks[$condition->conditiontaskid]->name;
                            $sort = $this->TC->tasks[$condition->conditiontaskid]->sortorder;
                            $str .= html_writer::tag('b', format_string($name)).' ('.$sort.')';
                        } else {
                            // $str .= 'conditiontaskid='.$condition->conditiontaskid;
                        }
                } // end switch

                if ($details = $this->format_condition_details($condition, true)) {
                    $str .= ': '.$details;
                }

                if ($return_commands) {
                    $str .= $this->format_commands_condition($condition);
                }
                break;

            case (mod_taskchain::CONDITIONTYPE_POST):

                if ($details = $this->format_condition_details($condition)) {
                    $str .= $details.': ';
                }

                switch ($condition->nexttaskid) {

                    case mod_taskchain::CONDITIONTASKID_SAME:
                        $str .= get_string('sametask', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_NEXT1:
                        $str .= get_string('next1task', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_NEXT2:
                        $str .= get_string('next2task', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_NEXT3:
                        $str .= get_string('next3task', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_NEXT4:
                        $str .= get_string('next4task', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_NEXT5:
                        $str .= get_string('next5task', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_PREVIOUS:
                        $str .= get_string('previoustask', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_UNSEEN: // no attempts
                        $str .= get_string('unseentask', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_UNANSWERED: // no responses
                        $str .= get_string('unansweredtask', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_INCORRECT: // score < 100%
                        $str .= get_string('incorrecttask', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_RANDOM:
                        $str .= get_string('randomtask', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_MENUNEXT:
                        $str .= get_string('menuofnexttasks', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_MENUNEXTONE:
                        $str .= get_string('menuofnexttasksone', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_MENUALL:
                       $str .= get_string('menuofalltasks', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_MENUALLONE:
                       $str .= get_string('menuofalltasksone', 'taskchain');
                        break;

                    case mod_taskchain::CONDITIONTASKID_ENDOFCHAIN:
                        $str .= get_string('endofchain', 'taskchain');
                        break;

                    default: // nexttaskid > 0
                        if ($this->TC->get_tasks() && isset($this->TC->tasks[$condition->nexttaskid])) {
                            $name = $this->TC->tasks[$condition->nexttaskid]->name;
                            $sort = $this->TC->tasks[$condition->nexttaskid]->sortorder;
                            $str .= html_writer::tag('b', format_string($name)).' ('.$sort.')';
                        } else {
                            // $str .= '<b>nexttaskid='.$condition->nexttaskid.'</b>';
                        }
                        break;
                }

                if ($return_commands) {
                    $str .= $this->format_commands_condition($condition);
                }
                break;

            default:
                // unknown condition type
        }

        if ($return_js) {
            return addslashes_js($str);
        }

        $id = 'taskchain_condition_'.$condition->id;
        return html_writer::tag('span', $str, array('id'=>$id));
    }

    /**
     * format_condition_details
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function format_condition_details(&$condition, $returnlist=false) {
        static $str;
        if (! isset($str)) {
            $str = (object)array(
                'min' => '&gt;=',
                'max' => '&lt;=',
                'anyattempts'         => get_string('anyattempts',         'taskchain'),
                'recentattempts'      => get_string('recentattempts',      'taskchain'),
                'consecutiveattempts' => get_string('consecutiveattempts', 'taskchain'),
                'conditionscore'      => get_string('score',               'taskchain'),
                'attemptduration'     => get_string('duration',            'taskchain'),
                'attemptdelay'        => get_string('delay',               'taskchain')
            );
        }

        $details = array();

        if ($condition->attemptcount) {
            switch ($condition->attempttype) {
                case mod_taskchain::ATTEMPTTYPE_ANY:         $type = $str->anyattempts; break;
                case mod_taskchain::ATTEMPTTYPE_RECENT:      $type = $str->recentattempts; break;
                case mod_taskchain::ATTEMPTTYPE_CONSECUTIVE: $type = $str->consecutiveattempts; break;
                default: $type = 'attempttype='.$condition->attempttype; // shouldn't happen !!
            }
            if ($condition->attemptcount<0) {
                // minimum number of attempts
                $details['attemptcount'] = get_string('ormore', 'taskchain', abs($condition->attemptcount)).' x '.$type;
            } else {
                // maximum number of attempts
                $details['attemptcount'] = get_string('orless', 'taskchain', $condition->attemptcount).' x '.$type;
            }
        }

        if ($condition->conditionscore) {
            $minmax = ($condition->conditionscore<0 ? $str->min : $str->max);
            $details['conditionscore'] = $minmax.abs($condition->conditionscore).'%';
        }
        if ($condition->attemptduration) {
            $minmax = ($condition->attemptduration<0 ? $str->min : $str->max);
            $details['attemptduration'] = $minmax.format_time(abs($condition->attemptduration));
        }
        if ($condition->attemptdelay) {
            $minmax = ($condition->attemptdelay<0 ? $str->min : $str->max);
            $details['attemptdelay'] = $minmax.format_time(abs($condition->attemptdelay));
        }

        foreach ($details as $name=>$detail) {
            if ($name=='conditionscore' || $name=='attemptcount') {
                $details[$name] = $this->TC->textlib('strtolower', $detail);
            } else {
                $details[$name] = $this->TC->textlib('strtolower', $str->$name.$detail);
            }
        }

        if ($returnlist && count($details) > 1) {
            foreach ($details as $i => $detail) {
                $details[$i] = html_writer::tag('li', $detail);
            }
            return html_writer::tag('ul', implode('', $details));
        } else {
            return implode(', ', $details);
        }
    }

    /**
     * format commands for a collection of conditions
     *
     * @param integer $conditiontype
     * @param integer $taskid (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function format_commands_conditions($conditiontype, $taskid=0) {
        global $output;

        if (! $taskid) {
            $taskid = $this->TC->get_taskid();
        }
        $conditions = $this->TC->get_conditions($conditiontype, $taskid, false);

        $types = array('add');
        if (count($conditions) > 1) {
            $types[] = 'deleteall';
        }

        $params = array(
            'chaingradeid' => 0, 'chainattemptid' => 0, 'cnumber' => 0,
            'taskscoreid'  => 0, 'taskattemptid'  => 0, 'tnumber' => 0,
            'taskid'       => $taskid,                  'inpopup' => 0,
            'conditionid'  => 0, 'conditiontype'  => $conditiontype
        );
        return $output->commands($types, 'edit/condition.php', '', $params, 'taskchainpopup', true);
    }

    /**
     * format commands for a single condition
     *
     * @param $condition (optional, default=false)
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function format_commands_condition($condition=false) {
        global $output;

        if (! $condition) {
            $condition = $this->TC->get_condition();
        }

        $types = array('update', 'delete');
        $params = array(
            'chaingradeid' => 0, 'chainattemptid' => 0, 'cnumber' => 0,
            'taskscoreid'  => 0, 'taskattemptid'  => 0, 'tnumber' => 0,
            'taskid'       => $condition->taskid,       'inpopup' => 0,
            'conditionid'  => $condition->id,    'conditiontype'  => $condition->conditiontype
        );
        return $output->commands($types, 'edit/condition.php', 'conditionid', $params, 'taskchainpopup', true);
    }
}
