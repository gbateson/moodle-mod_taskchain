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
 * mod/taskchain/form/task.php
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
 * taskchain_form_helper_task
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_form_helper_task extends taskchain_form_helper_record {

    /** type of record **/
    protected $recordtype = 'task';

    /** sections and fields in this form **/
    protected $sections = array(
        'general'    => array('sortorder', 'edit', 'defaultrecord', 'selectrecord', 'name'),
        'tasks'      => array('sourcefile', 'sourcetype', 'sourcelocation', 'configfile', 'configlocation', 'addtype', 'tasknames'),
        'display'    => array('outputformat', 'navigation', 'title', 'stopbutton', 'stoptext', 'allowpaste', 'usefilters', 'useglossary', 'usemediafilter', 'studentfeedback', 'studentfeedbackurl'),
        'time'       => array('timeopen', 'timeclose', 'timelimit', 'delay1', 'delay2', 'delay3'),
        'attempts'   => array('attemptlimit', 'allowresume'),
        'security'   => array('password', 'subnet'),
        'assessment' => array('scoremethod', 'scoreignore', 'scorelimit', 'scoreweighting', 'clickreporting', 'discarddetails'),
        'reviewoptions'  => array('reviewoptions'),
        'conditions'  => array('preconditions', 'postconditions'),
        'hidden'     => array('id', 'aftertaskid')
    );

    /** the default sections/fields shown on forms for multiple task records */
    protected $defaultsections = array(
        'tasks'      => array('sourcefile'),
        'time'       => array('timeopen', 'timeclose'),
        'conditions' => array('preconditions', 'postconditions'),
        'hidden'     => array('id', 'aftertaskid')
    );

    /** default values in a task record (includes form fields not stored in the database ) */
    protected $defaultvalues = array(
        'name'            => '',
        'namesource'      => mod_taskchain::TEXTSOURCE_FILE,
        'tasknamessource' => mod_taskchain::TEXTSOURCE_FILE,
        'sourcefile'      => '',
        'sourcetype'      => '',
        'sourcelocation'  => mod_taskchain::LOCATION_COURSEFILES,
        'configfile'      => '',
        'configlocation'  => mod_taskchain::LOCATION_COURSEFILES,
        'addtype'         => mod_taskchain::ADDTYPE_AUTO,
        'outputformat'    => '',
        'navigation'      => mod_taskchain::NAVIGATION_MOODLE,
        'titlesource'     => mod_taskchain::TEXTSOURCE_FILE,
        'titleprependchainname' => mod_taskchain::NO,
        'titleappendsortorder'  => mod_taskchain::NO,
        'title'           => mod_taskchain::TEXTSOURCE_FILE,
        'stopbutton'      => mod_taskchain::NO,
        'stoptext'        => '',
        'allowpaste'      => mod_taskchain::NO,
        'usefilters'      => mod_taskchain::NO,
        'useglossary'     => mod_taskchain::NO,
        'usemediafilter'  => mod_taskchain::NO,
        'studentfeedback' => mod_taskchain::NO,
        'studentfeedbackurl' => '',
        'timeopen'        => 0, // = disabled
        'timeclose'       => 0, // = disabled
        'timelimit'       => 0, // = disabled
        'delay1'          => 0, // = disabled
        'delay2'          => 0, // = disabled
        'delay3'          => 0, // = disabled
        'attemptlimit'    => 0, // = unlimited
        'allowresume'     => mod_taskchain::NO,
        'password'        => '',
        'subnet'          => '',
        'scoremethod'     => mod_taskchain::GRADEMETHOD_HIGHEST,
        'scoreignore'     => mod_taskchain::NO,
        'scorelimit'      => 100,
        'scoreweighting'  => 100,
        'clickreporting'  => mod_taskchain::NO,
        'discarddetails'  => mod_taskchain::YES,
        'addtype'         => mod_taskchain::ADDTYPE_AUTO,
        'tasknames'       => mod_taskchain::TEXTSOURCE_FILE,
        'preconditions'   => '',
        'postconditions'  => ''
    );

    /** fields that are only used when adding a new record */
    protected $addonlyfields = array('namesource', 'tasknamessource', 'addtype');

    /**
     * prepare title's "source", "prependchainname" and "appendsortorder" values
     *
     * @param array $data (passed by reference)
     * @todo Finish documenting this function
     */
    protected function prepare_field_title(&$data) {
        $field = 'title';
        $value = $this->get_fieldvalue($field);

        $name = $this->get_fieldname($field.'source');
        $data[$name] = ($value & mod_taskchain::TITLE_SOURCE);

        $name = $this->get_fieldname($field.'prependchainname');
        if ($value & mod_taskchain::TITLE_CHAINNAME) {
            $data[$name] = 1;
        } else {
            $data[$name] = 0;
        }

        $name = $this->get_fieldname($field.'appendsortorder');
        if ($value & mod_taskchain::TITLE_SORTORDER) {
            $data[$name] = 1;
        } else {
            $data[$name] = 0;
        }
    }

    /**
     * prepare_field_reviewoptions
     *
     * @param array $data (passed by reference)
     * @todo Finish documenting this function
     */
    protected function prepare_field_reviewoptions(&$data) {
        $name = $this->get_fieldname('reviewoptions');
        $value = $this->get_fieldvalue('reviewoptions');

        $times = taskchain_available::reviewoptions_list('times');
        $items = taskchain_available::reviewoptions_list('items');

        foreach ($times as $timename => $timevalue) {
            foreach ($items as $itemname => $itemvalue) {
                $data[$name.$timename.$itemname] = min(1, $value & $timevalue & $itemvalue);
            }
        }
    }

    /**
     * prepare_field_stopbutton
     * set stopbutton's "yesno" and "type" values
     *
     * @param array $data (passed by reference)
     * @todo Finish documenting this function
     */
    protected function prepare_field_stopbutton(&$data) {
        $field = 'stopbutton';
        $name = $this->get_fieldname($field);
        $name_yesno = $this->get_fieldname($field.'yesno');
        $name_type  = $this->get_fieldname($field.'type');
        $name_text  = $this->get_fieldname('stoptext');

        if (empty($data[$name])) {
            $data[$name_yesno] = 0; // NO
            $data[$name_type] = 'taskchain_giveup';
            $data[$name_text] = '';
        } else {
            $data[$name_yesno] = 1; // YES
            if ($data[$name]==mod_taskchain::STOPBUTTON_SPECIFIC) {
                $data[$name_type] = 'specific';
            } else if ($data[$name_text]=='taskchain_giveup') {
                $data[$name_type] = 'taskchain_giveup';
                $data[$name_text] = '';
            } else {
                $data[$name_type] = 'langpack';
            }
        }
    }

    /**
     * prepare task's "delay3" values
     *
     * @param array $data (passed by reference)
     * @todo Finish documenting this function
     */
    protected function prepare_field_delay3(&$data) {
        $field = 'delay3';
        $name = $this->get_fieldname($field);
        $name_options = $this->get_fieldname($field.'options');
        if ($data[$name] < 0) {
            $data[$name_options] = $data[$name];
            $data[$name] = 0;
        } else {
            $data[$name_options] = mod_taskchain::DELAY3_SPECIFIC;
        }
    }

    /**
     * prepare the "aftertaskid" values
     *
     * @param array $data (passed by reference)
     * @todo Finish documenting this function
     */
    protected function prepare_field_aftertaskid(&$data) {
        $field = 'aftertaskid';
        $name = $this->get_fieldname($field);
        $data[$name] = $this->get_fieldvalue_aftertaskid();
    }

    /**
     * add_field_outputformat
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_outputformat($field) {
        $name  = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);
        $sourcetype = $this->get_originalvalue('sourcetype', '');
        $options = taskchain_available::outputformats_list($sourcetype);
        $this->mform->addElement('select', $name, $label, $options);
        $this->add_helpbutton($name, $field, 'taskchain');
        //$this->mform->setAdvanced($name);
    }

    /**
     * add_field_navigation
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_navigation($field) {
        $this->add_template_list($field);
    }

    /**
     * add_field_title
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_title($field) {
        $name = $this->get_fieldname($field);
        $label = get_string($field, 'mod_taskchain');

        $field_source  = $field.'source';
        $field_prepend = $field.'prependchainname';
        $field_append  = $field.'appendsortorder';

        $name_source   = $this->get_fieldname($field_source);
        $name_prepend  = $this->get_fieldname($field_prepend);
        $name_append   = $this->get_fieldname($field_append);
        $name_elements = $this->get_fieldname($field.'_elements');

        $elements = array();
        $elements[] = $this->mform->createElement('select',   $name_source,  '', taskchain_available::titles_list());
        $elements[] = $this->mform->createElement('checkbox', $name_prepend, '', get_string($field_prepend, 'mod_taskchain'));
        $elements[] = $this->mform->createElement('checkbox', $name_append,  '', get_string($field_append,  'mod_taskchain'));

        $this->mform->addGroup($elements, $name_elements, $label, html_writer::empty_tag('br'), false);
        $this->add_helpbutton($name_elements, $field, 'taskchain');
        $this->mform->setAdvanced($name_elements);

        $this->mform->setType($name_source,  PARAM_INT);
        $this->mform->setType($name_prepend, PARAM_INT);
        $this->mform->setType($name_append,  PARAM_INT);

        $this->mform->setDefault($name_source,  $this->get_defaultvalue($field_source));
        $this->mform->setDefault($name_prepend, $this->get_defaultvalue($field_prepend));
        $this->mform->setDefault($name_append,  $this->get_defaultvalue($field_append));
    }

    /**
     * add_field_stopbutton
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_stopbutton($field) {
        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);

        $name_yesno = $this->get_fieldname($field.'yesno');
        $name_type  = $this->get_fieldname($field.'type');
        $name_text  = $this->get_fieldname('stoptext');
        $name_elements = $this->get_fieldname($field.'_elements');

        $elements = array();
        $elements[] = $this->mform->createElement('selectyesno', $name_yesno, '');
        $elements[] = $this->mform->createElement('select', $name_type, '', taskchain_available::stopbuttontypes_list());
        $elements[] = $this->mform->createElement('text', $name_text, '', array('size' => '20'));

        $this->mform->addGroup($elements, $name_elements, $label, ' ', false);
        $this->add_helpbutton($name_elements, $field, 'taskchain');
        $this->mform->setAdvanced($name_elements);

        $this->mform->setType($name_yesno, PARAM_INT);
        $this->mform->setType($name_type,  PARAM_ALPHAEXT);
        $this->mform->setType($name_text,  PARAM_TEXT);

        $this->mform->disabledIf($name_elements, $name_yesno, 'ne', '1');
        $this->mform->disabledIf($name_text,     $name_type,  'eq', 'taskchain_giveup');
    }

    /**
     * add_field_stoptext
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_stoptext($field) {
        // do nothing - this field was added by add_field_stopbutton($field)
    }

    /**
     * add_field_allowpaste
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_allowpaste($field) {
        $this->add_template_yesno($field);
    }

    /**
     * add_field_usefilters
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_usefilters($field) {
        $this->add_template_yesno($field);
    }

    /**
     * add_field_useglossary
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_useglossary($field) {
        $this->add_template_yesno($field);
    }

    /**
     * add_field_usemediafilter
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_usemediafilter($field) {
        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);
        $this->mform->addElement('select', $name, $label, taskchain_available::mediafilters_list());
        $this->mform->setDefault($name, $this->get_defaultvalue($field));
        $this->mform->setType($name, PARAM_SAFEDIR); // [a-zA-Z0-9_-]
        $this->add_helpbutton($name, $field, 'taskchain');
    }

    /**
     * add_field_studentfeedback
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_studentfeedback($field) {
        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);

        $name_url = $this->get_fieldname($field.'url');
        $name_elements = $this->get_fieldname($field.'_elements');

        $elements = array();
        $elements[] = $this->mform->createElement('select', $name, '', taskchain_available::feedbacks_list());
        $elements[] = $this->mform->createElement('text', $name_url, '', array('size'=>$this->text_field_size));
        $this->mform->addGroup($elements, $name_elements, $label, array(' '), false);

        $this->mform->disabledIf($name_url, $name, 'eq', mod_taskchain::FEEDBACK_NONE);
        $this->mform->disabledIf($name_url, $name, 'eq', mod_taskchain::FEEDBACK_MOODLEFORUM);
        $this->mform->disabledIf($name_url, $name, 'eq', mod_taskchain::FEEDBACK_MOODLEMESSAGING);

        $this->add_helpbutton($name_elements, $field, 'taskchain');
        $this->mform->setAdvanced($name_elements);

        $this->mform->setType($name, PARAM_INT);
        $this->mform->setType($name_url, PARAM_URL);
    }

    /**
     * add_field_studentfeedbackurl
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_studentfeedbackurl($field) {
        // do nothing - this field was added by add_field_studentfeedback($field)
    }

    /**
     * add_field_delay3
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_delay3($field) {
        $name = $this->get_fieldname($field);
        $name_options = $this->get_fieldname($field.'options');
        $this->add_template_timer($field, true, array('options' => taskchain_available::delay3s_list()));
        $this->mform->disabledIf($name.'[number]',   $name_options, 'ne', mod_taskchain::DELAY3_SPECIFIC);
        $this->mform->disabledIf($name.'[timeunit]', $name_options, 'ne', mod_taskchain::DELAY3_SPECIFIC);
    }

    /**
     * add_field_scoremethod
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_scoremethod($field) {
        $this->add_template_method('score');
    }

    /**
     * add_field_scoreignore
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_scoreignore($field) {
        $this->add_template_ignore('score');
    }

    /**
     * add_field_scorelimit
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_scorelimit($field) {
        $this->add_template_limit('score');
    }

    /**
     * add_field_scoreweighting
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_scoreweighting($field) {
        $this->add_template_weighting('score');
    }

    /**
     * add_field_clickreporting
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_clickreporting($field) {
        $this->add_template_yesno($field);
    }

    /**
     * add_field_discarddetails
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_discarddetails($field) {
        $this->add_template_yesno($field);
    }

    /**
     * add_field_reviewoptions
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_reviewoptions($field) {
        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);

        $times = taskchain_available::reviewoptions_list('times');
        $items = taskchain_available::reviewoptions_list('items');

        foreach ($times as $timename => $timevalue) {

            // set groupname and id
            $groupname = $name.$timename.'_elements';
            $groupid = 'fgroup_id_'.$groupname;

            // set All/None links
            $allnone = '';
            $allnone .= html_writer::tag('a', get_string('all'), array('onclick' => 'select_all_in("DIV", "fitem", "'.$groupid.'")'));
            $allnone .= ' / ';
            $allnone .= html_writer::tag('a', get_string('none'), array('onclick' => 'deselect_all_in("DIV", "fitem", "'.$groupid.'")'));

            $elements = array();
            foreach ($items as $itemname => $itemvalue) {
                $fieldname = $name.$timename.$itemname; // e.g. duringattemptresponses
                $elements[] = &$this->mform->createElement('checkbox', $fieldname, '', get_string($itemname, 'quiz'));
                $this->mform->setType($fieldname, PARAM_INT);
            }
            $elements[] = &$this->mform->createElement('static', '', '', html_writer::tag('span', $allnone));

            $this->mform->addGroup($elements, $groupname, get_string('review'.$timename, 'mod_taskchain'), null, false);
            if ($timename=='afterclose') {
                $this->mform->disabledIf('afterclose_elements', 'timeclose[off]', 'checked');
            }
        }
    }

    /**
     * add_field_preconditions
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_preconditions($field) {
        $type = mod_taskchain::CONDITIONTYPE_PRE;
        $this->format_fieldtemplate_conditions($field, $type);
    }

    /**
     * add_field_postconditions
     *
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function add_field_postconditions($field) {
        $type = mod_taskchain::CONDITIONTYPE_POST;
        $this->format_fieldtemplate_conditions($field, $type);
    }

    /**
     * get_sectionlabel_conditions
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_sectionlabel_conditions() {
        return get_string('conditions', 'mod_taskchain');
    }

    /**
     * get_fieldvalue_aftertaskid
     *
     * @todo Finish documenting this function
     */
    protected function get_fieldvalue_aftertaskid() {
        return optional_param('aftertaskid', 0, PARAM_INT);
    }

    /**
     * get_defaultvalue_tasknamessource
     *
     * @param string $field the field name
     * @return mixed user preference for this field, or $this->defaultvalues[$field], or null
     * @todo Finish documenting this function
     */
    protected function get_defaultvalue_tasknamessource($field) {
        return $this->get_defaultvalue_template_source($field);
    }

    /**
     * validate_field_studentfeedbackurl
     *
     * @param array $errors (passed by reference)
     * @param xxx $data (passed by reference)
     * @param xxx $files (passed by reference)
     * @todo Finish documenting this function
     */
    protected function validate_field_studentfeedbackurl(&$errors, &$data, &$files) {
        $field = 'studentfeedback';
        $name  = $this->get_fieldname($field);
        $name_url = $this->get_fieldname($field.'url');
        $name_elements = $this->get_fieldname($field.'_elements');

        if (empty($data[$name])) {
            // do nothing
        } else if ($data[$name]==mod_taskchain::FEEDBACK_WEBPAGE || $data[$name]==mod_taskchain::FEEDBACK_FORMMAIL) {
            if (empty($data[$name_url]) || ! preg_match('/^https?:\/\/.+/', $data[$name_url])) {
                // empty or invalid url
                $errors[$name_elements] = get_string('invalidurl', 'error');
            }
        }
    }

    /**
     * fix_field_title
     *
     * @param array $data (passed by reference)
     * @param string name of $field
     * @return void may modify $data
     * @todo Finish documenting this function
     */
    protected function fix_field_title(&$data, $field) {
        $value = 0;

        $name = $this->get_fieldname($field.'source');
        if (isset($data->$name)) {
            $value |= ($data->$name & mod_taskchain::TITLE_SOURCE); // 1st/2nd bits
            unset($data->$name);
        }

        $name = $this->get_fieldname($field.'prependchainname');
        if (isset($data->$name)) {
            if ($data->$name) {
                $value |= mod_taskchain::TITLE_CHAINNAME; // 3rd bit
            }
            unset($data->$name);
        }

        $name = $this->get_fieldname($field.'appendsortorder');
        if (isset($data->$name)) {
            if ($data->$name) {
                $value |= mod_taskchain::TITLE_SORTORDER; // 4th bit
            }
            unset($data->$name);
        }

        $name = $this->get_fieldname($field);
        $data->$name = $value;
    }

    /**
     * fix_field_stopbutton
     *
     * @param array $data (passed by reference)
     * @param string name of $field
     * @return void may modify $data
     * @todo Finish documenting this function
     */
    protected function fix_field_stopbutton(&$data, $field) {
        $name  = $this->get_fieldname($field);
        $name_yesno = $this->get_fieldname($field.'yesno');
        $name_type  = $this->get_fieldname($field.'type');
        $name_text  = $this->get_fieldname('stoptext');

        switch (true) {
            case empty($data->$name_yesno) || empty($data->$name_type):
                $data->$name = mod_taskchain::STOPBUTTON_NONE;
                $data->$name_text = '';
                break;

            case $data->$name_type=='specific' && $data->$name_text:
                $data->$name = mod_taskchain::STOPBUTTON_SPECIFIC;
                break;

            case $data->$name_type=='langpack' && $data->$name_text:
                $data->$name = mod_taskchain::STOPBUTTON_LANGPACK;
                break;

            default: // button required, but no text/string specified
                $data->$name = mod_taskchain::STOPBUTTON_LANGPACK;
                $data->$name_text = 'taskchain_giveup';
        }

        unset($data->$name_yesno,
              $data->$name_type);
    }

    /**
     * fix_field_delay3
     *
     * @param array $data (passed by reference)
     * @param string name of $field
     * @return void may modify $data
     * @todo Finish documenting this function
     */
    protected function fix_field_delay3(&$data, $field) {
        $name = $this->get_fieldname($field);
        $name_options = $this->get_fieldname($field.'options');
        switch ($data->$name_options) {
            case mod_taskchain::DELAY3_TEMPLATE: // -1
            case mod_taskchain::DELAY3_AFTEROK:  // -2
            case mod_taskchain::DELAY3_DISABLE:  // -3
                $data->$name = $data->$name_options;
                break;
            case mod_taskchain::DELAY3_SPECIFIC: // =0
            default:
                $data->$name = max(0, $data->$name);
                break;
        }
    }

    /**
     * fix_field_reviewoptions
     *
     * @param array $data (passed by reference)
     * @param string name of $field
     * @return void may modify $data
     * @todo Finish documenting this function
     */
    protected function fix_field_reviewoptions(&$data, $field) {
        $name = $this->get_fieldname($field);
        $data->$name = 0;

        $times = taskchain_available::reviewoptions_list('times');
        $items = taskchain_available::reviewoptions_list('items');

        foreach ($times as $timename => $timevalue) {
            foreach ($items as $itemname => $itemvalue) {
                $fieldname = $name.$timename.$itemname; // e.g. duringattemptresponses
                if (isset($data->$fieldname)) {
                    if ($data->$fieldname) {
                        $data->$name += ($timevalue & $itemvalue);
                    }
                    unset($data->$fieldname);
                }
            }
        }
    }

    /**
     * format_field_addtype
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function format_field_addtype($field) {
        return '';
    }

    /**
     * format_field_tasknames
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function format_field_tasknames($field) {
        return '';
    }

    /**
     * format_fieldvalue_outputformat
     *
     * @param string $field name of field
     * @param mixed the $value to be formatted
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_outputformat($field, $value) {
        $type = $this->get_fieldvalue('sourcetype');
        $list = taskchain_available::outputformats_list($type);
        if (array_key_exists($value, $list)) {
            return $list[$value];
        } else {
            return array_shift($list); // "Best" output format
        }
    }

    /**
     * format_fieldvalue_title
     *
     * @param string $field name of field
     * @param mixed the $value to be formatted
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_title($field, $value) {
        $title = $this->format_templatevalue_list($field, ($value & mod_taskchain::TITLE_SOURCE));
        if ($value & mod_taskchain::TITLE_CHAINNAME) {
            $title = get_string('taskchainname', 'mod_taskchain').': '.$title;
        }
        if ($value & mod_taskchain::TITLE_SORTORDER) {
            $title = $title.' ('.get_string('sortorder', 'mod_taskchain').')';
        }
        return $title;
    }

    /**
     * format_fieldvalue_delay3
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_delay3($field, $value) {
        if ($value < 0) {
            return $this->format_templatevalue_list($field, $value);
        } else {
            return $this->format_templatevalue_timer($field, $value);
        }
    }

    /**
     * format_fieldvalue_scoremethod
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_scoremethod($field, $value) {
        return $this->format_templatevalue_list($field, $value, 'grademethod', 'score');
    }

    /**
     * format_fieldvalue_scoreignore
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_scoreignore($field, $value) {
        return $this->format_templatevalue_yesno($field, $value);
    }

    /**
     * format_fieldvalue_scorelimit
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_scorelimit($field, $value) {
        return $this->format_templatevalue_list($field, $value, 'gradelimit', 'score');
    }

    /**
     * format_fieldvalue_scoreweighting
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_scoreweighting($field, $value) {
        return $this->format_templatevalue_list($field, $value, 'gradeweighting');
    }

    /**
     * format_fieldvalue_clickreporting
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_clickreporting($field, $value) {
        return $this->format_templatevalue_yesno($field, $value);
    }

    /**
     * format_fieldvalue_discarddetails
     *
     * @param string $value of field from the record
     * @return string formatted version of the value
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_discarddetails($field, $value) {
        return $this->format_templatevalue_yesno($field, $value);
    }

    /**
     * format_field_reviewoptions
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue_reviewoptions($field, $value) {

        $times = taskchain_available::reviewoptions_list('times');
        $items = taskchain_available::reviewoptions_list('items');

        $strtimes = array();
        foreach ($times as $timename => $timevalue) {
            $stritems = array();
            foreach ($items as $itemname => $itemvalue) {
                if ($value & $timevalue & $itemvalue) {
                    $stritems[] = get_string($itemname.'short', 'mod_taskchain');
                }
            }
            if ($stritems = implode(', ', $stritems)) {
                $stritems = get_string($timename, 'mod_taskchain').': '.$stritems;
                $strtimes[] = html_writer::tag('span', $stritems, array('class' => 'reviewoptionsitems'));
            }
        }
        return implode(' ', $strtimes);
    }

    /**
     * format_field_preconditions
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function format_field_preconditions($field) {
        $type = mod_taskchain::CONDITIONTYPE_PRE;
        $this->format_fieldtemplate_conditions($field, $type);
    }

    /**
     * format_field_postconditions
     *
     * @todo Finish documenting this function
     */
    protected function format_field_postconditions($field) {
        $type = mod_taskchain::CONDITIONTYPE_POST;
        $this->format_fieldtemplate_conditions($field, $type);
    }

    /**
     * format_fieldtemplate_conditions
     *
     * @param string $type CONDITIONTYPE_PRE or CONDITIONTYPE_POST
     * @param string $name "preconditions" or "postconditions"
     * @todo Finish documenting this function
     */
    protected function format_fieldtemplate_conditions($field, $type) {
        $elements = array();
        $taskid = $this->get_fieldvalue('id');
        $return_intro = ($this->is_multiple ? false : true);
        $text = $this->format_conditions($taskid, $type, $return_intro);
        $elements[] = $this->mform->createElement('static', '', '', $text);
        $name_elements = $this->get_fieldname($field.'_elements');
        $this->mform->addGroup($elements, $name_elements, '', html_writer::empty_tag('br'));
    }

    /**
     * get_datavalue_preconditions
     *
     * @param stdclass $data (passed by reference) recently submitted form $data or db record
     * @param string name of required user preference $field
     * @todo Finish documenting this function
     */
    protected function get_datavalue_preconditions(&$data, $field, $default=0) {
        return (empty($data->id) ? 0 : $data->id);
    }

    /**
     * get_datavalue_postconditions
     *
     * @param object $data (passed by reference) recently submitted form $data
     * @param string name of required user preference $field
     * @todo Finish documenting this function
     */
    protected function get_datavalue_postconditions(&$data, $field, $default=0) {
        return (isset($data->id) ? $data->id : $default);
    }

    /**
     * get_fieldvalue_preconditions
     *
     * @param string name of required user preference $field
     * @todo Finish documenting this function
     */
    protected function get_fieldvalue_preconditions() {
        return $this->get_fieldvalue('id');
    }

    /**
     * get_fieldvalue_postconditions
     *
     * @param string name of required user preference $field
     * @todo Finish documenting this function
     */
    protected function get_fieldvalue_postconditions() {
        return $this->get_fieldvalue('id');
    }

    /**
     * format_defaultfield_preconditions
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function format_defaultfield_preconditions($field) {
        $type = mod_taskchain::CONDITIONTYPE_PRE;
        $this->format_defaultfield_conditions($field, $type);
    }

    /**
     * format_defaultfield_postconditions
     *
     * @param string name of $field
     * @todo Finish documenting this function
     */
    protected function format_defaultfield_postconditions($field) {
        $type = mod_taskchain::CONDITIONTYPE_POST;
        $this->format_defaultfield_conditions($field, $type);
    }

    /**
     * format_defaultfield_conditions
     *
     * @param string name of $field ("preconditions" or "postconditions")
     * @param string $type CONDITIONTYPE_PRE or CONDITIONTYPE_POST
     * @todo Finish documenting this function
     */
    protected function format_defaultfield_conditions($field, $type) {
        $name = 'defaultfield_'.$field;
        $taskid = $this->get_defaultvalue($field);

        if (empty($taskid)) {
            $text = '';
        } else {
            $return_intro = ($this->is_multiple ? false : true);
            $text = $this->format_conditions($taskid, $type, $return_intro, false, false);
        }
        $text = html_writer::tag('span', $text, array('class' => 'defaultfield'));

        $elements[] = $this->mform->createElement('static', '', '', $text);

        $name_elements = $this->get_fieldname($field.'_elements');
        // $name_elements = $field.'_elements'.($this->is_multiple ? '[0]' : '');
        $this->mform->addGroup($elements, $name_elements, '', html_writer::empty_tag('br'));
    }

    /**
     * get_helpicon_reviewoptions
     */
    public function get_helpicon_reviewoptions() {
        return '';
    }

    /**
     * get_helpicon_stoptext
     *
     * @uses $OUTPUT
     */
    public function get_helpicon_stoptext() {
        global $OUTPUT;
        return ' '.$OUTPUT->help_icon('stopbutton', 'taskchain');
    }

    /**
     * get_helpicon_studentfeedbackurl
     *
     * @uses $OUTPUT
     */
    public function get_helpicon_studentfeedbackurl() {
        global $OUTPUT;
        return ' '.$OUTPUT->help_icon('studentfeedback', 'taskchain');
    }
}
