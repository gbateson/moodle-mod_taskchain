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
 * mod/taskchain/mod_form.php
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
require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/taskchain/edit/form/helper/chain.php');

/**
 * mod_taskchain_mod_form
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_mod_form extends moodleform_mod {

    /** TaskChain form helper - see mod/taskchain/form/base.php **/
    private $form_helper;

    /**
     * Standard function to define elements on a TaskChain mod form
     */
    public function definition() {
        // set up TaskChain form helper
        $this->form_helper = new taskchain_form_helper_chain($this->_form, $this->context, $this->current);

        // add form sections
        $this->form_helper->add_sections();

        // add standard elements and buttons
        $this->standard_coursemodule_elements();
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
     * Fixes the form data that has just been submitted
     * Note: this is not a standard method of the moodleform class
     *
     * @param stdClass $data (passed by reference) to be set
     * @return void
     */
    public function form_postprocessing($data) {
        // update context for newly created coursemodule
        $this->form_helper->set_context(CONTEXT_MODULE, $data->coursemodule);
        $this->form_helper->fix_sections($data);
        $this->form_helper->set_preferences($data);
    }

    /**
     * return the current context
     *
     * @return stdclass
     */
    public function get_context() {
        return $this->form_helper->get_context();
    }

    /**
     * return a field value from the original record
     * this function is useful to see if a value has changed
     *
     * @param string the $field name
     * @param mixed the $default value
     * @return mixed the field value if it exists, $default otherwise
     */
    public function get_originalvalue($field, $default) {
        $this->form_helper->get_originalvalue($field, $default);
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
     * Display module-specific activity completion rules.
     * Part of the API defined by moodleform_mod
     * @return array Array of string IDs of added items, empty array if none
     */
    public function add_completion_rules() {
        $mform = $this->_form;

        // array of elements names to be returned by this method
        $names = array();

        // these fields will be disabled if gradelimit x gradeweighting = 0
        $disablednames = array('completionusegrade');

        // add "minimum grade" completion condition
        $name = 'completionmingrade';
        $label = get_string($name, 'taskchain');
        if (empty($this->current->$name)) {
            $value = 0.0;
        } else {
            $value = floatval($this->current->$name);
        }
        $group = array();
        $group[] = &$mform->createElement('checkbox', $name.'enabled', '', $label);
        $group[] = &$mform->createElement('static', $name.'space', '', ' &nbsp; ');
        $group[] = &$mform->createElement('text', $name, '', array('size' => 3));
        $mform->addGroup($group, $name.'group', '', '', false);
        $mform->setType($name, PARAM_FLOAT);
        $mform->setDefault($name, 0.00);
        $mform->setType($name.'enabled', PARAM_INT);
        $mform->setDefault($name.'enabled', empty($value) ? 0 : 1);
        $mform->disabledIf($name, $name.'enabled', 'notchecked');
        $names[] = $name.'group';
        $disablednames[] = $name.'group';

        // add "grade passed" completion condition
        $name = 'completionpass';
        $label = get_string($name, 'taskchain');
        $mform->addElement('checkbox', $name, '', $label);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, 0);
        $names[] = $name;
        $disablednames[] = $name;

        // add "status completed" completion condition
        $name = 'completioncompleted';
        $label = get_string($name, 'taskchain');
        $mform->addElement('checkbox', $name, '', $label);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, 0);
        $names[] = $name;
        // no need to disable this field :-)

        // disable grade conditions, if necessary
        foreach ($disablednames as $name) {
            if ($mform->elementExists($name)) {
                $mform->disabledIf($name, 'gradelimit', 'eq', 0);
                $mform->disabledIf($name, 'gradeweighting', 'eq', 0);
            }
        }

        return $names;
    }

    /**
     * Called during validation. Indicates whether a module-specific completion rule is selected.
     *
     * @param array $data Input data (not yet validated)
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        if (empty($data['completionmingradeenabled']) || empty($data['completionmingrade'])) {
            if (empty($data['completionpass']) && empty($data['completioncompleted'])) {
                return false;
            }
        }
        return true; // at least one of the module-specific completion conditions is set

    }

    /**
     * Return submitted data if properly submitted
     * or returns NULL if there is no submitted data or validation fails.
     *
     * Note: $slashed param was removed in Moodle 2.0
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        if ($data = parent::get_data()) {
            if (! method_exists('moodleform_mod', 'data_postprocessing')) {
                // In Moodle >= 3.3, the get_data() method calls
                // the data_postprocessing() method automatically,
                // but in Moodle <= 3.2 we have to call it manually
                $data = $this->data_postprocessing($data);
            }
        }
        return $data;
    }

    /**
     * Allows modules to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod in Moodle >= 3.3
     *
     * @param stdClass $data passed by reference
     */
    public function data_postprocessing($data) {
        // Remove completionmingrade, if it is not enabled and greater than 0.0
        if (empty($data->completionmingradeenabled) || empty($data->completionmingrade) || floatval($data->completionmingrade)==0.0) {
            $data->completionmingradeenabled = 0;
            $data->completionmingrade = 0.0;
        }
    }
}
