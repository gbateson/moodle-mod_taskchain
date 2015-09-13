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
 * mod/taskchain/form/chain.php
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
require_once($CFG->dirroot.'/mod/taskchain/edit/form/helper/record.php');

/**
 * taskchain_form_helper_chain
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_form_helper_chain extends taskchain_form_helper_record {

    /** type of record **/
    protected $recordtype = 'chain';

    /** sections and fields in this form **/
    protected $sections = array(
        // Note: "headings" section will be added by mod_moodleform
        'general'    => array('edit', 'defaultrecord', 'selectrecord', 'name', 'showdescription'),
        'tasks'      => array('sourcefile', 'sourcelocation', 'configfile', 'configlocation', 'addtype', 'tasknames'),
        'entrypage'  => array('entrypage', 'entrytext', 'entryoptions', 'entrycm', 'entrygrade'),
        'exitpage'   => array('exitpage', 'exittext', 'exitoptions', 'exitcm', 'exitgrade'),
        'display'    => array('showpopup'),
        'time'       => array('timeopen', 'timeclose', 'timelimit', 'delay1', 'delay2'),
        'attempts'   => array('attemptlimit', 'allowresume', 'allowfreeaccess'),
        'security'   => array('password', 'subnet'),
        'assessment' => array('attemptgrademethod', 'grademethod', 'gradeignore', 'gradelimit', 'gradeweighting', 'gradecategory')
        // Note: "hidden" section will be added by mod_moodleform
    );

    /** the default sections/fields shown on forms for multiple task records */
    protected $defaultsections = array(
        'entrypage'  => array('entrypage'),
        'exitpage'   => array('exitpage'),
        'time'       => array('timeopen', 'timeclose'),
        'assessment' => array('grademethod', 'gradelimit')
    );

    /** default values in a chain record (includes form fields not stored in the database ) */
    protected $defaultvalues= array(
        'name'               => '',
        'namesource'         => mod_taskchain::TEXTSOURCE_FILE,
        'tasknamessource'    => mod_taskchain::TEXTSOURCE_FILE,
        'sourcefile'         => '',
        'sourcelocation'     => mod_taskchain::LOCATION_COURSEFILES,
        'configfile'         => '',
        'configlocation'     => mod_taskchain::LOCATION_COURSEFILES,
        'addtype'            => mod_taskchain::ADDTYPE_AUTO,
        'tasknames'          => mod_taskchain::TEXTSOURCE_FILE,
        'entrypage'          => mod_taskchain::YES,
        'entrytextsource'    => mod_taskchain::TEXTSOURCE_FILE,
        'entrytext'          => '',
        'entryoptions'       => 0,
        'entrycm'            => 0,
        'entrygrade'         => 0,
        'exitpage'           => mod_taskchain::YES,
        'exittextsource'     => mod_taskchain::TEXTSOURCE_FILE,
        'exittext'           => '',
        'exitoptions'        => 0,
        'exitcm'             => 0,
        'exitgrade'          => 0,
        'showpopup'          => mod_taskchain::NO,
        'popup_moodleheader' => mod_taskchain::YES,
        'popup_moodlenavbar' => mod_taskchain::YES,
        'popup_moodlefooter' => mod_taskchain::YES,
        'popup_moodlebutton' => mod_taskchain::YES,
        'popup_resizable'    => mod_taskchain::YES,
        'popup_scrollbars'   => mod_taskchain::YES,
        'popup_directories'  => mod_taskchain::YES,
        'popup_location'     => mod_taskchain::YES,
        'popup_menubar'      => mod_taskchain::YES,
        'popup_toolbar'      => mod_taskchain::YES,
        'popup_status'       => mod_taskchain::YES,
        'popup_width'        => 620,
        'popup_height'       => 450,
        'timeopen'           => 0,
        'timeclose'          => 0,
        'timelimit'          => 0,
        'delay1'             => 0,
        'delay2'             => 0,
        'attemptlimit'       => 0, // = unlimited
        'allowresume'        => mod_taskchain::YES,
        'allowfreeaccess'    => mod_taskchain::NO,
        'password'           => '',
        'subnet'             => '',
        'attemptgrademethod' => mod_taskchain::GRADEMETHOD_TOTAL,
        'grademethod'        => mod_taskchain::GRADEMETHOD_HIGHEST,
        'gradeignore'        => mod_taskchain::NO,
        'gradelimit'         => 100,
        'gradeweighting'     => 100,
        'gradecategory'      => 0
    );

    /** fields that are only used when adding a new record */
    protected $addonlyfields = array('namesource', 'tasknamessource', 'addtype', 'entrytextsource', 'exittextsource');

    /** name of chain **/
    protected $taskchainname = '';

    /**
     * constructor method
     *
     * @param xxx $this->mform
     * @param xxx $context
     * @param xxx $record
     * @param boolean $multiple (optional, default=false)
     * @todo Finish documenting this function
     */
    public function __construct($mform, $context, $record, $multiple=false) {
        global $DB;

        // standard setup
        parent::__construct($mform, $context, $record, $multiple);

        // remove source/config file fields, when updating
        if ($this->is_update() && isset($this->sections['tasks'])) {
            unset($this->sections['tasks']);
        }

        if (! defined('FEATURE_SHOW_DESCRIPTION')) {
            // do nothing (Moodle <= 2.1)
            $i = array_search('showdescription', $this->sections['general']);
            unset($this->sections['general'][$i]);
        }

        if (isset($record->instance)) {
            // get chain fields for a taskchain record
            if (empty($record->instance)) {
                // add a new taskchain
            } else {
                $chain = $DB->get_record('taskchain_chains', array('parenttype' => mod_taskchain::PARENTTYPE_ACTIVITY, 'parentid' => $record->instance));
                foreach ($chain as $field => $value) {
                    $this->record->$field = $value;
                }
            }
        } else if ($parentid = $this->get_fieldvalue('parentid')) {
            $this->taskchainname = $DB->get_field('taskchain', 'name', array('id' => $parentid));
        }
    }

    /////////////////////////////////////////////////////////
    // information methods
    /////////////////////////////////////////////////////////

    /**
     * get grade item, if any, for this activity from the Moodle gradebook
     *
     * Note: could make this general purpose, if we use $this->_cm->modname for 'itemmodule'
     *
     * @uses $DB
     * @return int the grade category of this activity (or 0 is there is no grade item)
     */
    public function get_grade_category() {
        global $DB;
        if ($this->is_update()) {
            if (isset($this->record->instance)) {
                $iteminstance = $this->record->instance;
            } else {
                $iteminstance = $this->record->id;
            }
            $params = array('itemtype'=>'mod', 'itemmodule'=>'taskchain', 'iteminstance'=>$iteminstance);
            if ($categoryid = $DB->get_field('grade_items', 'categoryid', $params)) {
                return $categoryid;
            }
        }
        return 0; // no grade item exists (yet)
    }

    /**
     * get_fieldvalue_name
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function get_fieldvalue_name() {
        if ($this->taskchainname) {
            return $this->taskchainname;
        } else {
            return $this->TC->taskchain->get_name();
        }
    }

    /**
     * get_fieldvalue_showdescription
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function get_fieldvalue_showdescription() {
        if (isset($this->TC->coursemodule->showdescription)) {
            return $this->TC->coursemodule->showdescription;
        } else {
            return 0; // Moodle <= 2.1
        }
    }

    /**
     * get_defaultvalue_entrytextsource
     *
     * @param string $field the field name
     * @return mixed user preference for this field, or $this->defaultvalues[$field], or null
     * @todo Finish documenting this function
     */
    protected function get_defaultvalue_entrytextsource($field) {
        return $this->get_defaultvalue_template_source($field);
    }

    /**
     * get_defaultvalue_exittextsource
     *
     * @param string $field the field name
     * @return mixed user preference for this field, or $this->defaultvalues[$field], or null
     * @todo Finish documenting this function
     */
    protected function get_defaultvalue_exittextsource($field) {
        return $this->get_defaultvalue_template_source($field);
    }

    /////////////////////////////////////////////////////////
    // prepare_field ...
    /////////////////////////////////////////////////////////

    /**
     * prepare_field_entrypage
     *
     * @param xxx $data (passed by reference)
     * @todo Finish documenting this function
     */
    protected function prepare_field_entrypage(&$data) {
        $this->prepare_template_page($data, 'entry');
    }

    /**
     * prepare_field_exitpage
     *
     * @param xxx $data (passed by reference)
     * @todo Finish documenting this function
     */
    protected function prepare_field_exitpage(&$data) {
        $this->prepare_template_page($data, 'exit');
    }

    /**
     * prepare_field_showpopup
     *
     * @param xxx $data (passed by reference)
     * @todo Finish documenting this function
     */
    protected function prepare_field_showpopup(&$data) {
        $field = 'popupoptions';
        $data[$field] = $this->get_originalvalue($field, $this->get_defaultvalue($field));

        $window_options = mod_taskchain::window_options();
        $options = explode(',', strtolower($data[$field]));
        foreach ($options as $option) {
            if (preg_match('/^([a-z]+)(?:=(.*))?$/', $option, $matches)) {
                $name = $matches[1];
                if (in_array($name, $window_options)) {
                    if ($name=='width' || $name=='height') {
                        if (empty($matches[2])) {
                            $data[$name] = '';
                        } else {
                            $data[$name] = intval($matches[2]);
                        }
                    } else {
                        $data[$name] = 1; // enable check box
                    }
                }
            }
        }
    }

    /**
     * prepare_field_gradecategory
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function prepare_field_gradecategory(&$data) {
        $data['gradecategory'] = $this->get_grade_category();
    }

    /////////////////////////////////////////////////////////
    // prepare_template ...
    /////////////////////////////////////////////////////////

    /**
     * prepare_template_page
     *
     * @param xxx $data (passed by reference)
     * @param xxx $type
     * @todo Finish documenting this function
     */
    protected function prepare_template_page(&$data, $type) {

        $page       = $type.'page';
        $text       = $type.'text';
        $textsource = $type.'textsource';
        $format     = $type.'format';
        $options    = $type.'options';
        $editor     = $type.'editor';

        $data[$page]       = $this->get_originalvalue($page,       $this->get_defaultvalue($page));
        $data[$text]       = $this->get_originalvalue($text,       $this->get_defaultvalue($text));
        $data[$textsource] = $this->get_originalvalue($textsource, $this->get_defaultvalue($textsource));
        $data[$format]     = $this->get_originalvalue($format,     editors_get_preferred_format());
        $data[$options]    = $this->get_originalvalue($options,    $this->get_defaultvalue($options));

        // prepare boolean switches for page options
        foreach (mod_taskchain::text_page_options($type) as $name => $mask) {
            $data[$type.'_'.$name] = $data[$options] & $mask;
        }

        // prepare text editor
        $itemid = 0;
        if ($this->is_add()) {
            $contextid = null;
        } else {
            $contextid = $this->context->id;
        }
        $options = mod_taskchain::filearea_options();
        $data[$editor] = array(
            'text'   => file_prepare_draft_area($itemid, $contextid, 'mod_taskchain', $type, 0, $options, $data[$text]),
            'format' => $data[$format],
            'itemid' => file_get_submitted_draft_itemid($itemid)
        );
    }

    /////////////////////////////////////////////////////////
    // add_field ...
    /////////////////////////////////////////////////////////

    /**
     * add_field_entrypage
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_entrypage($field)  {
        $this->add_template_page('entry');
    }

    /**
     * add_field_showdescription
     *
     */
    protected function add_field_showdescription() {
        if (defined('FEATURE_SHOW_DESCRIPTION')) { // Moodle >= 2.2
            $this->mform->addElement('checkbox', 'showdescription', get_string('showdescription', 'mod_taskchain'));
            $this->add_helpbutton('showdescription', 'showdescription', 'taskchain');
        } else {
            $this->mform->addElement('hidden', 'showdescription', 0);
        }
    }

    /**
     * add_field_entrytext
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_entrytext($field)  {
        $this->add_template_pagetext('entry');
    }

    /**
     * add_field_entryoptions
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_entryoptions($field) {
        $groups = array(
            'entryoptions' => array_keys(mod_taskchain::text_page_options('entry'))
        );
        $this->add_template_pageoptions('entry', $groups);
    }

    /**
     * add_field_entrycm
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_entrycm($field) {
        $this->add_template_activitylist('entry');
    }

    /**
     * add_field_entrygrade
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_entrygrade($field) {
        // do nothing - this field was added by add_field_entrycm($field)
    }

    /**
     * add_field_exitpage
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_exitpage($field)  {
        $this->add_template_page('exit');
    }

    /**
     * add_field_exittext
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_exittext($field)  {
        $this->add_template_pagetext('exit');
    }

    /**
     * add_field_exitoptions
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_exitoptions($field) {
        $groups = array(
            'exit_feedback' => array_keys(mod_taskchain::text_page_options('exit', 'feedback')),
            'exit_links'    => array_keys(mod_taskchain::text_page_options('exit', 'links')),
        );
        $this->add_template_pageoptions('exit', $groups);
    }

    /**
     * add_field_exitcm
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_exitcm($field) {
        $this->add_template_activitylist('exit');
    }

    /**
     * add_field_exitgrade
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_exitgrade($field) {
        // do nothing - this field was added by add_field_exitcm($field)
    }

    /**
     * add_field_showpopup
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_showpopup($field) {
        // Same window or new window (=popup) ?
        $options = array(
            0 => get_string('windowsame', 'mod_taskchain'),
            1 => get_string('windownew', 'mod_taskchain')
        );
        $this->mform->addElement('select', 'showpopup', get_string('window', 'mod_taskchain'), $options);
        $this->add_helpbutton('showpopup', 'window', 'taskchain');

        // New window options
        $elements = array();
        foreach (mod_taskchain::window_options('moodle') as $option) {
            $elements[] = $this->mform->createElement('checkbox', $option, '', get_string('window'.$option, 'mod_taskchain'));
        }
        $name = 'window_moodle_elements';
        $this->mform->addGroup($elements, $name, '', html_writer::empty_tag('br'), false);
        $this->mform->disabledIf($name, 'showpopup', 'eq', 0);
        $this->mform->setAdvanced($name);

        $elements = array();
        foreach (mod_taskchain::window_options('yesno') as $option) {
            $elements[] = $this->mform->createElement('checkbox', $option, '', get_string('window'.$option, 'mod_taskchain'));
        }
        $name = 'window_yesno_elements';
        $this->mform->addGroup($elements, $name, '', html_writer::empty_tag('br'), false);
        $this->mform->disabledIf($name, 'showpopup', 'eq', 0);
        $this->mform->setAdvanced($name);

        foreach (mod_taskchain::window_options('numeric') as $option) {
            $elements = array();
            $elements[] = $this->mform->createElement('text', $option, '', array('size'=>'4'));
            $elements[] = $this->mform->createElement('static', '', '', get_string('window'.$option, 'mod_taskchain'));
            $name = 'window_'.$option.'_elements';
            $this->mform->addGroup($elements, $name, '', ' ', false);
            // uncommenting the next line seems to disable the sourcefile and configfile fields
            //$this->mform->disabledIf($name, 'showpopup', 'eq', 0);
            $this->mform->setAdvanced($name);
        }

        // set defaults for window popup options
        foreach (mod_taskchain::window_options() as $option) {
            switch ($option) {
                case 'height': $default = 450; break;
                case 'width' : $default = 620; break;
                default: $default = 1; // checkbox
            }
            $this->mform->setType($option, PARAM_INT);
            $this->mform->setDefault($option, $this->get_defaultvalue('popup_'.$option));
        }
    }

    /**
     * add_field_allowfreeaccess
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_allowfreeaccess($field) {
        $optgroups = array(
            get_string('no') => array(0 => get_string('no')),
        );

        $options = array();
        $str = get_string('grade');
        for ($i=5; $i<=100; $i+=5) {
            $options[$i] = $str.' >= '.$i.'%';
        }
        $optgroups[get_string('yes').': '.$str] = $options;

        $options = array();
        $str = get_string('attempts', 'mod_taskchain');
        for ($i=-1; $i>=-5; $i--) {
            $options[$i] = $str.' >= '.abs($i);
        }
        $optgroups[get_string('yes').': '.$str] = $options;

        $this->mform->addElement('selectgroups', 'allowfreeaccess', get_string('allowfreeaccess', 'mod_taskchain'), $optgroups);
        $this->add_helpbutton('allowfreeaccess', 'allowfreeaccess', 'taskchain');
        $this->mform->setAdvanced('allowfreeaccess');
    }

    /**
     * add_field_attemptgrademethod
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_attemptgrademethod($field) {
        $this->add_template_method('attemptgrade');
    }

    /**
     * add_field_grademethod
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_grademethod($field) {
        $this->add_template_method('grade');
    }

    /**
     * add_field_gradeignore
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_gradeignore($field) {
        $this->add_template_ignore('grade');
    }

    /**
     * add_field_gradeweighting
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_gradeweighting($field) {
        $this->add_template_weighting('grade');
    }

    /**
     * add_field_gradelimit
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_gradelimit($field) {
        $this->add_template_limit('grade');
    }

    /**
     * add_field_gradecategory
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_gradecategory($field) {
        global $PAGE;
        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);
        $options = grade_get_categories_menu($PAGE->course->id);
        $this->mform->addElement('select', $name, $label, $options);
        $this->add_helpbutton($name, 'gradecategoryonmodform', 'grades');
        $this->mform->setType($name, PARAM_INT);
        // this element is not available if gradeweighting==0 or gradelimit==0
        $this->mform->disabledIf($name, 'gradeweighting', 'eq', 0);
        $this->mform->disabledIf($name, 'gradelimit', 'eq', 0);
    }

    /////////////////////////////////////////////////////////
    // add_template ...
    /////////////////////////////////////////////////////////

    /**
     * add_template_page
     *
     * @param string $type of page ("entry" or "exit")
     * @todo Finish documenting this function
     */
    protected function add_template_page($type)  {

        $type_page = $type.'page';
        $type_textsource = $type.'textsource';
        $label = $this->get_fieldlabel($type_page);

        if ($this->is_add()) {
            $options = array(
                mod_taskchain::TEXTSOURCE_FILE => get_string('textsourcefile', 'mod_taskchain'),
                mod_taskchain::TEXTSOURCE_SPECIFIC => get_string('textsourcespecific', 'mod_taskchain')
            );
            $elements = array(
                $this->mform->createElement('selectyesno', $type_page),
                $this->mform->createElement('select', $type_textsource, '', $options)
            );
            $this->mform->addGroup($elements, $type.'page_elements', $label, array(' '), false);
            $this->mform->setAdvanced($type.'page_elements');
            $this->add_helpbutton($type.'page_elements', $type_page, 'taskchain');
            $this->mform->disabledIf($type.'page_elements', $type_page, 'ne', 1);
        } else {
            $this->mform->addElement('selectyesno', $type_page, $label);
            $this->add_helpbutton($type_page, $type_page, 'taskchain');
            $this->mform->addElement('hidden', $type_textsource, mod_taskchain::TEXTSOURCE_SPECIFIC);
        }
        $this->mform->setType($type_page, PARAM_INT);
        $this->mform->setType($type_textsource, PARAM_INT);
    }

    /**
     * add_template_pagetext
     *
     * @param string $type of pagetext ("entry" or "exit")
     * @todo Finish documenting this function
     */
    protected function add_template_pagetext($type) {
        $name = $this->get_fieldname($type.'editor');
        $label = $this->get_fieldlabel($type.'text');

        $options = mod_taskchain::text_editor_options($this->context);
        $this->mform->addElement('editor', $name, $label, null, $options);

        $this->mform->setType($name, PARAM_RAW); // no XSS prevention here, users must be trusted
        $this->mform->setAdvanced($name);

        $this->mform->disabledIf($name.'[text]', $type.'page', 'ne', 1);
        $this->mform->disabledIf($name.'[format]', $type.'page', 'ne', 1);

        if ($this->is_add()) {
            $this->mform->disabledIf($name.'[text]', $type.'textsource', 'ne', mod_taskchain::TEXTSOURCE_SPECIFIC);
            $this->mform->disabledIf($name.'[format]', $type.'textsource', 'ne', mod_taskchain::TEXTSOURCE_SPECIFIC);
        }
    }

    /**
     * add_template_pageoptions
     *
     * @param string $type of page_options ("entry" or "exit")
     * @param array $options
     * @todo Finish documenting this function
     */
    protected function add_template_pageoptions($type, $groups) {

        foreach ($groups as $groupname => $names) {

            $name_elements = $this->get_fieldname($groupname.'_elements');

            $label = $this->get_fieldlabel($groupname);
            $label .= html_writer::empty_tag('br'); // separator

            // select_all_in_element_with_id() is not available in Moodle 2.0
            // $onclick = 'select_all_in_element_with_id("fgroup_id_'.$name_elements.'", true)';
            $onclick = 'select_all_in("DIV", "'.$groupname.'", null)';
            $label .= html_writer::tag('small', get_string('all'), array('onclick' => $onclick));

            $label .= html_writer::tag('small', ' / '); // separator

            // $onclick = 'select_all_in_element_with_id("fgroup_id_'.$name_elements.'", false)';
            $onclick = 'deselect_all_in("DIV", "'.$groupname.'", null)';
            $label .= html_writer::tag('small', get_string('none'), array('onclick' => $onclick));

            $elements = array();
            foreach ($names as $name) {
                if ($name=='title') {
                    $text = $this->get_fieldlabel($name);
                    $name = $type.'_'.$name;
                } else {
                    $name = $type.'_'.$name;
                    $text = $this->get_fieldlabel($name);
                }
                $elements[] = $this->mform->createElement('checkbox', $name, '', $text);
            }

            $this->mform->addGroup($elements, $name_elements, $label, html_writer::empty_tag('br'), false);
            $this->mform->setAdvanced($name_elements);
            $this->add_helpbutton($name_elements, $groupname, 'taskchain');
            $this->mform->disabledIf($name_elements, $type.'page', 'ne', 1);
        }
    }

    /**
     * Add a list of the current course's activity modules to the form.
     *
     * The list is used to specify the "entry" or "exit" activities
     * depending on the setting of the $type parameter.
     *
     * @uses $PAGE
     * @param string $type "entry" or "exit"
     */
    protected function add_template_activitylist($type)  {
        global $PAGE;

        $name = ($type=='entry' ? 'previous' : 'next');
        $optgroups = array(
            get_string('none') => array(mod_taskchain::ACTIVITY_NONE => get_string('none')),
            get_string($name)  => taskchain_available::cms_list($type)
        );

        if ($modinfo = get_fast_modinfo($PAGE->course)) {

            // set course section descriptor
            switch ($PAGE->course->format) {
                case 'weeks': $strsection = get_string('strftimedateshort'); break;
                case 'topics': $strsection = get_string('topic'); break;
                default: $strsection = get_string('section');
            }

            // create activity list
            $sectionnum = -1;
            foreach ($modinfo->cms as $cmid=>$mod) {
                if ($mod->modname=='label') {
                    continue; // ignore labels
                }
                if ($type=='entry' && $mod->modname=='resource') {
                    continue; // ignore resources as entry activities
                }
                if (isset($this->record->id) && $this->record->id==$cmid) {
                    continue; // ignore this taskchain
                }
                if ($sectionnum==$mod->sectionnum) {
                    // do nothing (same section)
                } else {
                    // start new optgroup for this course section
                    $sectionnum = $mod->sectionnum;
                    if ($sectionnum==0) {
                        $optgroup = get_string('activities');
                    } else if ($PAGE->course->format=='weeks') {
                        $date = $PAGE->course->startdate + 7200 + ($sectionnum * 604800);
                        $optgroup = userdate($date, $strsection).' - '.userdate($date + 518400, $strsection);
                    } else {
                        $optgroup = $strsection.': '.$sectionnum;
                    }
                    if (empty($options[$optgroup])) {
                        $options[$optgroup] = array();
                    }
                }

                $name = $this->format_longtext($mod->name);

                // add this activity to the list
                $optgroups[$optgroup][$cmid] = $name;
            }
        }

        // create activity grade element
        $options = array();
        for ($i=100; $i>=0; $i--) {
            $options[$i] = $i.'%';
        }

        // add the activity list and grade element to the form
        $elements = array(
            $this->mform->createElement('selectgroups', $type.'cm', '', $optgroups),
            $this->mform->createElement('select', $type.'grade', '', $options)
        );

        $this->mform->addGroup($elements, $type.'cm_elements', get_string($type.'cm', 'mod_taskchain'), array(' '), false);
        $this->add_helpbutton($type.'cm_elements', $type.'cm', 'taskchain');
        if ($type=='entry') {
            $defaultcm = mod_taskchain::ACTIVITY_NONE;
            $defaultgrade = 100;
        } else { // exit
            $defaultcm = mod_taskchain::ACTIVITY_SECTION_TASKCHAIN;
            $defaultgrade = 0;
        }
        $this->mform->setDefault($type.'cm', get_user_preferences('taskchain_'.$type.'cm', $defaultcm));
        $this->mform->setDefault($type.'grade', get_user_preferences('taskchain_'.$type.'grade', $defaultgrade));
        $this->mform->disabledIf($type.'cm_elements', $type.'cm', 'eq', 0);

        if ($type=='entry') {
            $this->mform->setAdvanced($type.'cm_elements');
        }

        // add module icons, if possible - there is no API for this, so we have to hack :-(
        if ($modinfo) {
            $element = reset($this->mform->getElement($type.'cm_elements')->getElements());
            for ($i=0; $i<count($element->_optGroups); $i++) {
                $optgroup = &$element->_optGroups[$i];
                for ($ii=0; $ii<count($optgroup['options']); $ii++) {
                    $option = &$optgroup['options'][$ii];
                    if (isset($option['attr']['value']) && $option['attr']['value']>0) {
                        $cmid = $option['attr']['value'];
                        $url = $PAGE->theme->pix_url('icon', $modinfo->cms[$cmid]->modname)->out();
                        $option['attr']['style'] = "background-image: url($url); background-repeat: no-repeat; background-position: 1px 2px; min-height: 20px;";
                    }
                }
            }
        }
    }

    /////////////////////////////////////////////////////////
    // fix_template ...
    /////////////////////////////////////////////////////////

    /**
     * fix_template_pagetext
     *
     * @param object $data (passed by reference) from form
     * @param string $type type of options field (""entry" or "exit")
     * @todo Finish documenting this function
     */
    protected function fix_template_pagetext(&$data, $type) {
        // set field names - use $this->get_fieldname() ?
        $textfield   = $type.'text';
        $formatfield = $type.'format';
        $editorfield = $type.'editor';
        $sourcefield = $type.'textsource';

        // ensure text, format and option fields are set
        // (these fields can't be null in the database)
        if (! isset($data->$textfield)) {
            $data->$textfield = $this->get_originalvalue($textfield, '');
        }
        if (! isset($data->$formatfield)) {
            $data->$formatfield = $this->get_originalvalue($formatfield, FORMAT_HTML);
        }
        if (! isset($data->$sourcefield)) {
            $data->$sourcefield = $this->get_originalvalue($sourcefield, mod_taskchain::TEXTSOURCE_SPECIFIC);
        }

        // set text and format fields
        if ($data->$sourcefield==mod_taskchain::TEXTSOURCE_SPECIFIC && isset($data->$editorfield)) {

            // get wysiwyg itemid (it should be there)
            if (isset($data->{$editorfield}['itemid'])) {
                $itemid = $data->{$editorfield}['itemid'];
            } else {
                $itemid = 0;
            }

            // transfer wysiwyg editor text
            if ($itemid) {
                if (isset($data->{$editorfield}['text'])) {
                    // get the text that was sent from the browser
                    $options = mod_taskchain::filearea_options();
                    $text = file_save_draft_area_files($itemid, $this->context->id, 'mod_taskchain', $type, 0, $options, $data->{$editorfield}['text']);

                    // remove leading and trailing white space,
                    //  - empty html paragraphs (from IE)
                    //  - and blank lines (from Firefox)
                    $text = preg_replace('/^((<p>\s*<\/p>)|(<br[^>]*>)|\s)+/is', '', $text);
                    $text = preg_replace('/((<p>\s*<\/p>)|(<br[^>]*>)|\s)+$/is', '', $text);

                    $data->$textfield = $text;
                    $data->$formatfield = $data->{$editorfield}['format'];
                }
            }
        }
        unset($data->$editorfield);
    }

    /**
     * fix_template_pageoptions
     *
     * @param object $data (passed by reference) from form
     * @param string $type type of options field (""entry" or "exit")
     * @todo Finish documenting this function
     */
    protected function fix_template_pageoptions(&$data, $type) {
        // set field names
        $page = $type.'page';
        $options = $type.'options';

        // get options field value
        if (isset($data->$options)) {
            $value = $data->$options;
        } else {
            $value = $this->get_originalvalue($options, 0);
        }

        // check all options for this page type
        $page_options = mod_taskchain::text_page_options($type);
        foreach ($page_options as $name=>$mask) {
            $option = $type.'_'.$name;
            if ($data->$page) {
                if (empty($data->$option)) {
                    // disable this option
                    $value = $value & ~$mask;
                } else {
                    // enable this option
                    $value = $value | $mask;
                }
            }
            unset($data->$option);
        }

        // update options field value
        $data->$options = $value;
    }

    /////////////////////////////////////////////////////////
    // fix_field ...
    /////////////////////////////////////////////////////////

    /**
     * fix_field_entrypage
     *
     * @param object $data (passed by reference) from form
     * @param string name of $field to fix
     * @todo Finish documenting this function
     */
    protected function fix_field_entrypage(&$data, $field) {
        $this->fix_template_notnull($data, $field, 0);
    }

    /**
     * fix_field_entrytext
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function fix_field_entrytext(&$data, $field) {
        $this->fix_template_pagetext($data, 'entry');
    }

    /**
     * fix_field_entryoptions
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function fix_field_entryoptions(&$data, $field) {
        $this->fix_template_pageoptions($data, 'entry');
    }

    /**
     * fix_field_entrycm
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function fix_field_entrycm(&$data, $field) {
        $this->fix_template_notnull($data, $field, 0);
    }

    /**
     * fix_field_entrygrade
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function fix_field_entrygrade(&$data, $field) {
        $this->fix_template_notnull($data, $field, 0);
    }

    /**
     * fix_field_exitpage
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function fix_field_exitpage(&$data, $field) {
        $this->fix_template_notnull($data, $field, 0);
    }

    /**
     * fix_field_exittext
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function fix_field_exittext(&$data, $field) {
        $this->fix_template_pagetext($data, 'exit');
    }

    /**
     * fix_field_exitoptions
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function fix_field_exitoptions(&$data, $field) {
        $this->fix_template_pageoptions($data, 'exit');
    }

    /**
     * fix_field_exitcm
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function fix_field_exitcm(&$data, $field) {
        $this->fix_template_notnull($data, $field, 0);
    }

    /**
     * fix_field_exitgrade
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function fix_field_exitgrade(&$data, $field) {
        $this->fix_template_notnull($data, $field, 0);
    }

    /**
     * fix_field_showpopup
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function fix_field_showpopup(&$data, $field) {
        $popupoptions = array();

        if (empty($data->$field)) {
            $data->$field = 0;
        } else {
            $data->$field = 1;

            $preferences = array();
            $prefix = 'taskchain_'.$this->recordtype.'_popup_';

            $window_options = mod_taskchain::window_options();
            foreach ($window_options as $option) {
                if (empty($data->$option)) {
                    $data->$option = '';
                } else {
                    if ($option=='width' || $option=='height') {
                        $popupoptions[] = $option.'='.$data->$option;
                    } else {
                        $popupoptions[] = $option;
                    }
                }
                $preferences[$prefix.$option] = $data->$option;
                unset($data->$option);
            }
            set_user_preferences($preferences);
        }
        $data->popupoptions = strtoupper(implode(',', $popupoptions));
    }

    /**
     * fix_field_gradecategory
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function fix_field_gradecategory(&$data, $field) {
        if (empty($data->gradelimit) || empty($data->gradeweighting) || empty($data->gradecategory)) {
            unset($data->gradecategory, $data->gradecat);
        } else {
            $data->gradecat = $data->gradecategory;
        }
    }

    /////////////////////////////////////////////////////////
    // format fieldvalue ...
    /////////////////////////////////////////////////////////

    /**
     * format_template_page_options
     *
     * @param string $type of page_options ("entry" or "exit")
     * @param array $options
     * @todo Finish documenting this function
     */
    protected function format_template_page_options($field, $value, $type) {
        $texts = array();

        $groups = array($field => mod_taskchain::text_page_options($type));
        foreach ($groups as $groupname => $names) {

            $elements = array();
            foreach ($names as $name => $mask) {
                if ($value & $mask) {
                    $name = ($name=='title' ? $name : $type.'_'.$name);
                    $texts[] = get_string($name, 'mod_taskchain');
                }
            }
        }
        return implode(', ', $texts);
    }

    /**
     * format_template_cm
     *
     * @param string name of $field
     * @param mixed $value (mod_taskchain::ACTIVITY_xxx constant or coursemodule id)
     * @todo Finish documenting this function
     */
    protected function format_template_cm($field, $value, $type) {
        if (empty($value)) {
            return '';
        }
        if ($value < 0) {
            return $this->format_templatevalue_list($field, $value, 'cm', $type);
        }
        if (! $modinfo = get_fast_modinfo($this->TC->course)) {
            return ''; // no mods - shouldn't happen !!
        }
        if (! $cm = $modinfo->get_cm($value)) {
            return ''; // invalid $value - shouldn't happen !!
        }
        return $this->format_longtext($cm->name);
    }

    /**
     * format_fieldvalue_name
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_name($field, $value) {
        return format_string($value);
    }

    /**
     * format_fieldvalue_showdescription
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_showdescription($field, $value) {
        return $this->format_templatevalue_yesno($field, $value);
    }

    /**
     * format_fieldvalue_entrypage
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_entrypage($field, $value) {
        return $this->format_templatevalue_yesno($field, $value);
    }

    /**
     * format_fieldvalue_entrytext
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_entrytext($field, $value) {
        return $this->format_longtext($value, 15, 12, 0);
    }

    /**
     * format_fieldvalue_entryoptions
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_entryoptions($field, $value) {
        return $this->format_template_page_options($field, $value, 'entry');
    }

    /**
     * format_fieldvalue_entrycm
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_entrycm($field, $value) {
        return $this->format_template_cm($field, $value, 'entry');
    }

    /**
     * format_fieldvalue_entrygrade
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_entrygrade($field, $value) {
        return (empty($value) ? '' : $value.'%');
    }

    /**
     * format_fieldvalue_exitpage
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_exitpage($field, $value) {
        return $this->format_templatevalue_yesno($field, $value);
    }

    /**
     * format_fieldvalue_exittext
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_exittext($field, $value) {
        return $this->format_longtext($value, 15, 12, 0);
    }

    /**
     * format_fieldvalue_exitoptions
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_exitoptions($field, $value) {
        return $this->format_template_page_options($field, $value, 'exit');
    }

    /**
     * format_fieldvalue_exitcm
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_exitcm($field, $value) {
        return $this->format_template_cm($field, $value, 'exit');
    }

    /**
     * format_fieldvalue_exitgrade
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_exitgrade($field, $value) {
        return (empty($value) ? '' : $value.'%');
    }

    /**
     * format_fieldvalue_showpopup
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_showpopup($field, $value) {
        if (empty($value)) {
            return get_string('no');
        }
        return get_string('yes').': '.strtr($value, array(','=>', ', 'MOODLE'=>''));
    }

    /**
     * format_fieldvalue_allowfreeaccess
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_allowfreeaccess($field, $value) {
        if (empty($value)) {
            return get_string('no');
        }
        if ($value>0) {
            return get_string('yes').': '.get_string('grade').' >= '.$value.'%';
        } else {
            return get_string('yes').': '.get_string('attempts', 'quiz').' >= '.abs($value);
        }
    }

    /**
     * format_fieldvalue_attemptgrademethod
     *
     * @param string $field name of field
     * @param mixed $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_attemptgrademethod($field, $value) {
        return $this->format_templatevalue_list($field, $value);
    }

    /**
     * format_fieldvalue_grademethod
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_grademethod($field, $value) {
        return $this->format_templatevalue_list($field, $value, '', 'grade');
    }

    /**
     * format_fieldvalue_gradeignore
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_gradeignore($field, $value) {
        return $this->format_templatevalue_yesno($field, $value);
    }

    /**
     * format_fieldvalue_gradelimit
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_gradelimit($field, $value) {
        return $this->format_templatevalue_list($field, $value, '', 'grade');
    }

    /**
     * format_fieldvalue_gradeweighting
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_gradeweighting($field, $value) {
        return $this->format_templatevalue_list($field, $value);
    }

    /**
     * format_fieldvalue_gradecategory
     *
     * @param string $field name of field
     * @param string $value of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_gradecategory($field, $value) {
        if ($value) {
            if ($category = grade_category::fetch(array('id' => $value))) {
                if ($category->is_course_category()) {
                    return get_string('uncategorised', 'grades');
                } else {
                    return $category->get_name();
                }
            }
        }
        return ''; // category is empty (or invalid !)
    }

    /**
     * format_selectfield_name
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_selectfield_name($field) {
        $name = $this->get_fieldname($field);
        $this->mform->addElement('static', $name, '', '');
    }

    /**
     * get_helpicon_entrytext
     *
     * @uses $OUTPUT
     */
    public function get_helpicon_entrytext() {
        global $OUTPUT;
        return ' '.$OUTPUT->help_icon('entrycm', 'taskchain');
    }

    /**
     * get_helpicon_entrygrade
     *
     * @uses $OUTPUT
     */
    public function get_helpicon_entrygrade() {
        global $OUTPUT;
        return ' '.$OUTPUT->help_icon('entrycm', 'taskchain');
    }

    /**
     * get_helpicon_exittext
     *
     * @uses $OUTPUT
     */
    public function get_helpicon_exittext() {
        global $OUTPUT;
        return ' '.$OUTPUT->help_icon('exitcm', 'taskchain');
    }

    /**
     * get_helpicon_exitgrade
     *
     * @uses $OUTPUT
     */
    public function get_helpicon_exitgrade() {
        global $OUTPUT;
        return ' '.$OUTPUT->help_icon('exitcm', 'taskchain');
    }

    /**
     * get_helpicon_exitoptions
     *
     * @uses $OUTPUT
     */
    public function get_helpicon_exitoptions() {
        global $OUTPUT;
        return ' '.$OUTPUT->help_icon('exitcm', 'taskchain');
    }

    /**
     * get_helpicon_gradecategory
     */
    public function get_helpicon_gradecategory() {
        return '';
    }
}
