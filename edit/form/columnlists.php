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
 * mod/taskchain/edit/columnlists.form.php
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
require_once(__DIR__.'/helper/columnlists.php');

/**
 * mod_taskchain_edit_columnlists_form
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_edit_columnlists_form extends moodleform {
    /** TaskChain form helper - see mod/taskchain/form/helper/base.php **/
    private $form_helper;

    /**
     * Standard function to define elements on a TaskChain columnlists form
     */
    public function definition() {
        global $TC;

        // set up TaskChain form helper
        $type = $TC->get_columnlisttype();
        $lists = $TC->get_columnlists($type);
        switch ($type) {
            case 'chains': $context = $TC->course->context; break;
            case 'tasks':  $context = $TC->coursemodule->context; break;
            default: $context = null; // shouldn't happen !!
        }
        $this->form_helper = new taskchain_form_helper_columnlists($this->_form, $context, $type);

        // add form sections
        $this->form_helper->add_sections();

        // add action buttons
        $this->add_action_buttons();
    }

    /**
     * Prepares the form before data are set
     *
     * @param array $data (passed by reference) to be set
     * @return void
     */
    public function data_preprocessing(&$data) {
        $this->form_helper->prepare_sections($data);
    }

    /**
     * validation
     *
     * http://docs.moodle.org/en/Development:lib/formslib.php_Validation
     * also see "lang/en/error.php" for a list of common messages
     *
     * @param xxx $data (passed by reference)
     * @param xxx $files
     * @return xxx
     * @todo Finish documenting this function
     */
    public function validation($data, $files)  {
        $errors = parent::validation($data, $files);
        $this->form_helper->validate_sections($errors, $data, $files);
        return $errors;
    }

    /**
     * return the recently submitted data
     * with all fields fixed by the form_helper
     *
     * @return stdclass
     */
    public function get_data() {
        if ($data = parent::get_data()) {
            $this->form_helper->fix_sections($data);
            //$this->form_helper->set_preferences($data);
        }
        return $data;
    }

    /**
     * return javascript to be inserted in footer of page
     *
     * @return string
     */
    public function get_js() {
        return $this->form_helper->get_js();
    }

    /**
     * add_action_buttons
     *
     * @param bool $cancel whether to show cancel button, default true
     * @param string $submitlabel label for submit button, defaults to get_string('savechanges')
     */
    public function add_action_buttons($cancel=true, $submitlabel=null) {
        $this->_form->addElement('header', 'actionshdr', '');
        $elements = array();
        $elements[] = $this->_form->createElement('submit', 'update', get_string('savechanges'));
        $elements[] = $this->_form->createElement('cancel');
        $elements[] = $this->_form->createElement('submit', 'delete', get_string('delete'));
        $elements[] = $this->_form->createElement('submit', 'deleteall', get_string('deleteall')  );
        $this->_form->addGroup($elements, 'action_buttons_elements', '', '', false);
    }
}

/** Include required files */
//require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * mod_taskchain_edit_columnlists_form
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_edit_columnlists_form_old extends moodleform {

    var $columnlist = null;

    /**
     * definition
     *
     * @uses $CFG
     * @uses $PAGE
     * @uses $TC
     * @todo Finish documenting this function
     */
    function definition() {
        global $CFG, $TC, $PAGE;

        $mform =&$this->_form;

        $mform->addElement('header', 'columnlistshdr', '');

        $elements = array();
        $options = array_merge(
            array('0' => get_string('add').' ...'),
            $TC->get_columnlists($TC->columnlisttype)
        );
        $elements[] = $mform->createElement('select', 'columnlistid', '', $options);
        $elements[] = $mform->createElement('text', 'columnlistname', '', array('size' => '10'));
        $elements[] = $mform->createElement('static', 'onchangecolumnlistid', '', ''
            .'<script type="text/javascript">'."\n"
            .'//<![CDATA['."\n"
            .'var obj = document.getElementById("id_columnlistid");'."\n"
            .'if (obj) {'."\n"
            .'    obj.onchange = function () {'."\n"
            .'        var href = self.location.href.replace(new RegExp("columnlistid=\\\\w+&?"), "");'."\n"
            .'        if (this.selectedIndex) {'."\n"
            .'            var char = href.charAt(href.length-1);'."\n"
            .'            if (char!="?" && char!="&") {'."\n"
            .'                if (href.indexOf("?")<0) {'."\n"
            .'                    href += "?";'."\n"
            .'                } else {'."\n"
            .'                    href += "&";'."\n"
            .'                }'."\n"
            .'            }'."\n"
            .'            href += "columnlistid=" + this.options[this.selectedIndex].value;'."\n"
            .'        }'."\n"
            .'        self.location.href = href;'."\n"
            .'    }'."\n"
            .'}'."\n"
            .'//]]>'."\n"
            .'</script>'."\n"
        );
        $mform->addGroup($elements, 'columnlists_elements', '', array(' '), false);
        $mform->disabledIf('columnlists_elements', 'columnlists', 'ne', 0);
        $mform->setDefault('columnlists', get_user_preferences('taskchain_'.$TC->columnlisttype.'_columnlists', 0));

        $sections = $this->taskchain_columnlists_sections();

        foreach ($sections as $section => $fields) {
            switch ($section) {

                case 'actions':
                    $mform->addElement('header', $section.'hdr', '');
                    $elements = array();
                    foreach ($fields as $field=>$str) {
                        if ($field=='cancel') {
                            $elements[] = &$mform->createElement('cancel');
                        } else {
                            $elements[] = &$mform->createElement('submit', $field, get_string($str ? $str : $field));
                        }
                    }
                    $mform->addGroup($elements, 'buttons_elements', '', array(' '), false);
                    break;

                default:
                    switch ($section) {
                        case 'general':
                        case 'display':
                            $mform->addElement('header', $section.'hdr', get_string($section, 'form'));
                            break;
                        case 'access':
                            $mform->addElement('header', 'accesscontrolhdr', get_string('accesscontrol', 'lesson'));
                            break;
                        case 'assessment':
                            $mform->addElement('header', $section.'hdr', get_string($section.'hdr', 'taskchain'));
                            break;
                        default:
                            $mform->addElement('header', $section.'hdr', get_string($section, 'taskchain'));
                    }
                    foreach ($fields as $field) {
                        switch ($field) {
                            case 'name':
                                $label = get_string('name');
                                break;
                            case 'password':
                                $label = get_string('requirepassword', 'taskchain');
                                break;
                            case 'subnet':
                                $label = get_string('requiresubnet', 'taskchain');
                                break;
                            case 'reviewoptions':
                                $label = get_string('reviewoptionsheading', 'quiz');
                                break;
                            case 'showpopup':
                                $label = get_string('display', 'resource');
                                break;
                            default:
                                $label = get_string($field, 'taskchain');
                        }
                        $mform->addElement('checkbox', $field, $label);
                    }
            } // end switch $section
        }

        $params = array(
            'id' => $TC->course->id,
            'columnlistid' => 0,
        );
        //taskchain_add_hidden_fields($mform, $params);
    }

    /**
     * taskchain_columnlists_sections
     *
     * @uses $TC
     * @return xxx
     * @todo Finish documenting this function
     */
    function taskchain_columnlists_sections() {
        global $TC;

        if ($TC->columnlisttype=='task') {
            return array(
                'general' => array(
                    'name','sourcelocation','sourcefile','sourcetype','configlocation','configfile'
                ),
                'display' => array(
                    'outputformat','navigation','title','stopbutton','stoptext','usefilters','useglossary','usemediafilter','studentfeedback'
                ),
                'access' => array(
                    'timeopen','timeclose','timelimit','delay1','delay2','delay3','attemptlimit','allowresume','password','subnet','reviewoptions'

                ),
                'assessment' => array(
                    'scoremethod','scoreignore','scorelimit','scoreweighting','clickreporting','discarddetails'
                ),
                'conditions' => array(
                    'preconditions','postconditions'

                ),
                'actions' => array(
                    'update' => 'savechanges', 'cancel' => '', 'delete' => '', 'deleteall' => ''
                )
            );
        }

        if ($TC->columnlisttype=='chain') {
            return array(
                'general' => array(
                    'name','tasks','entrycm','entrygrade','exitcm','exitgrade'
                ),
                'display' => array(
                    'entrypage','entrytext','entryoptions','exitpage','exittext','exitoptions','showpopup','popupoptions'
                ),
                'access' => array(
                    'timeopen','timeclose','timelimit','delay1','delay2','password','subnet','allowresume','allowfreeaccess','attemptlimit'
                ),
                'assessment' => array(
                    'attemptgrademethod','grademethod','gradeignore','gradelimit','gradeweighting'
                ),
                'actions' => array(
                    'update' => 'savechanges', 'cancel' => '', 'delete' => '', 'deleteall' => ''
                ),
            );
        }

        // not 'chain' or 'task'
        return array();
    }

    /**
     * data_preprocessing
     *
     * @uses $TC
     * @param xxx $defaults (passed by reference)
     * @todo Finish documenting this function
     */
    function data_preprocessing(&$defaults){
        global $TC;
        if ($TC->columnlistid) {
            $columnlists = $TC->get_columnlists($TC->columnlisttype, true);
            if (array_key_exists($TC->columnlistid, $columnlists)) {
                foreach ($columnlists[$TC->columnlistid] as $column) {
                    $defaults[$column] = 1;
                }
            }
        }
    }

    /**
     * validation
     *
     * http://docs.moodle.org/en/Development:lib/formslib.php_Validation
     * also see "lang/en/error.php" for a list of common messages
     *
     * @param xxx $data (passed by reference)
     * @param xxx $files
     * @return xxx
     * @todo Finish documenting this function
     */
    public function validation($data, $files)  {
        $errors = array();

        if (! $this->createcolumnlist($data)) {
            $errors['columnlists'] = get_string('error_nocolumns', 'taskchain');
        }

        if (count($errors)) {
            return $errors;
        } else {
            return true;
        }
    }

    /**
     * createcolumnlist
     *
     * @param xxx $data (passed by reference)
     * @return xxx
     * @todo Finish documenting this function
     */
    function createcolumnlist(&$data) {
        if (is_null($this->columnlist)) {
            $this->columnlist = array();

            $sections = $this->taskchain_columnlists_sections();
            foreach ($sections as $section => $fields) {

                if ($section=='hidden' || $section=='actions') {
                    continue;
                }

                foreach ($fields as $field) {
                    if (empty($data[$field])) {
                        continue;
                    }
                    $this->columnlist[] = $field;
                }
            }
        }
        return count($this->columnlist);
    }

    /**
     * display
     *
     * @todo Finish documenting this function
     */
    function display() {
        if (function_exists('print_formslib_js_and_css')) {
            print_formslib_js_and_css($this->_form);
        }
        parent::display();
    }
}
