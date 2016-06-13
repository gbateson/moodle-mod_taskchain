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
 * mod/taskchain/edit/chain.php
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
//class mod_taskchain_mod_form extends moodleform_mod {
class mod_taskchain_edit_chain_form extends moodleform {

    /** TaskChain form helper - see mod/taskchain/form/base.php **/
    private $form_helper;

    /**
     * Standard function to define elements on a TaskChain mod form
     */
    public function definition() {
        global $TC;

        // set up TaskChain form helper
        $this->form_helper = new taskchain_form_helper_chain($this->_form, $TC->coursemodule->context, $TC->chain);

        // add form sections
        $this->form_helper->add_sections();

        // add standard buttons ("Save changes" and "Cancel")
        $this->form_helper->add_action_buttons();
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
     * @param array $data (passed by reference) to be set
     * @return void
     */
    public function data_postprocessing(&$data) {
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
     * update_singlefield
     *
     * @param array $data (passed by reference)
     * @return void may modify $this->record
     * @todo Finish documenting this function
     */
    public function update_singlefield(&$data) {
        $this->form_helper->update_singlefield($data);
    }

    /**
     * display_singlefield
     *
     * @todo Finish documenting this function
     */
    public function display_singlefield() {
        $this->form_helper->display_singlefield();
    }
}
