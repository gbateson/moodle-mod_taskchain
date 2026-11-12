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
 * mod/taskchain/form/columnlists.php
 *
 * @package    mod_taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

// Prevent direct access to this script.
defined('MOODLE_INTERNAL') || die();

// Include required files.
require_once($CFG->dirroot . '/mod/taskchain/edit/form/helper/record.php');

/**
 * taskchain_form_helper_condition
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod_taskchain
 */
class taskchain_form_helper_columnlists extends taskchain_form_helper_record {
    /** @var string type of record * */
    protected $recordtype = 'columnlists';

    /** @var string type of records * */
    protected $recordstype = 'columnlists';

    /** @var array sections and fields in this form * */
    protected $sections = array(
        'headings' => array('columnlistsheading'),
        'filters'  => array('columnlistid', 'columnlistname'),
    );

    /** @var array default values in a chain record */
    protected $defaultvalues = array();

    /** @var array these fields will not be included in columnlists */
    protected $excludedfields = array(
        'columnlistid', 'columnlisttype', 'columnlistname',
        'edit', 'sortorder', 'defaultrecord', 'selectrecord',
        'addtype', 'tasknames',
    );

    /**
     * __construct
     *
     * @param object $mform a MoodleQuickForm
     * @param object $context a context record from the database
     * @param string $type "chains" or "tasks"
     * @param boolean $is_multiple (optional, default=false)
     */
    public function __construct(&$mform, &$context, &$type, $is_multiple = false) {
        global $CFG, $TC;

        if (empty($TC)) {
            $TC = new mod_taskchain();
        }

        // Setup $record.
        $record = new stdClass();

        // Get columnlist info.
        $id = $TC->get_columnlistid();
        $type = $TC->get_columnlisttype();
        $lists = $TC->get_columnlists($type, true);

        // Transfer columnlist fields to $record.
        if ($id && isset($lists[$id])) {
            $record->columnlistid = $id;
            $record->columnlisttype = $type;
            foreach ($lists[$id] as $field) {
                $record->$field = 1;
            }
        }

        switch ($type) {
            case 'tasks':
                require_once($CFG->dirroot . '/mod/taskchain/edit/form/helper/task.php');
                $form_helper = new taskchain_form_helper_task($mform, $context, $record);
                break;

            case 'chains':
                require_once($CFG->dirroot . '/mod/taskchain/edit/form/helper/chain.php');
                $form_helper = new taskchain_form_helper_chain($mform, $context, $record);
                break;

            default:
                throw new moodle_exception("Unknown columnlist type: $type");
        }

        // Add record sections after headings.
        $this->sections = array_merge($this->sections, $form_helper->get_sections());
        unset($form_helper);

        // remove "general" section (as those fields are always displayed)
        unset($this->sections['general']);

        // Remove "tasks" section from "Edit chains" page.
        if ($type == 'chains') {
            unset($this->sections['tasks']);
        }

        // Overwrite hidden section.
        $this->sections['hidden'] = array('id');

        $this->TC          = &$TC;
        $this->mform       = $mform;
        $this->context     = $context;
        $this->record      = $record;
        $this->is_multiple = $is_multiple;
    }

    /**
     * validate_sections
     *
     * @param array $errors (passed by reference)
     * @param array $data (passed by reference)
     * @param array $files (passed by reference)
     * @return void may modify $errors and $data
     */
    public function validate_sections(&$errors, &$data, &$files) {
        // Do nothing.
    }

    /**
     * fix_sections
     *
     * @param stdClass $data (passed by reference)
     * @return void may modify $data
     */
    public function fix_sections($data) {
        $data->columnlist = array();
        $sections = $this->get_sections();
        foreach ($sections as $section => $fields) {
            foreach ($fields as $field) {
                if (isset($data->$field)) {
                    if (in_array($field, $this->excludedfields)) {
                        // Do nothing.
                    } else if ($data->$field) {
                        $data->columnlist[$field] = true;
                        unset($data->$field);
                    }
                }
            }
        }
        $data->columnlist = array_keys($data->columnlist);
        // $data->columnlist = implode(',', $data->columnlist);
    }

    /**
     * prepare_field
     *
     * @param array $data (passed by reference)
     * @param string $field name of field
     * @return void may modify $data
     */
    protected function prepare_field(&$data, $field) {
        if (isset($data[$field])) {
            // Field has already be prepared.
        } else {
            // Copy value across from record.
            $data[$field] = $this->get_originalvalue($field, '');
        }
    }

    /**
     * add_field
     *
     * @param string $field name of field
     */
    protected function add_field($field) {
        if (in_array($field, $this->excludedfields)) {
            // Do nothing.
        } else {
            // Default action is to add a checkbox element.
            $name = $this->get_fieldname($field);
            $label = $this->get_fieldlabel($field);
            $this->mform->addElement('checkbox', $name, $label);
            $this->mform->setType($name, PARAM_INT);
        }
    }

    /**
     * add_section_columnlists
     *
     * @param string $section name of section
     * @param array $fields in this section
     * @return xxx
     */
    protected function add_section_filters($section, $fields) {
        global $PAGE;

        // $this->mform->addElement('header', 'filtershdr', '');
        $type = $this->TC->get_columnlisttype();
        $lists = $this->TC->get_columnlists($type);
        $options = array('00' => get_string('add') . ' ...') + $lists;
        $elements = array(
            $this->mform->createElement('select', 'columnlistid', '', $options),
            $this->mform->createElement('text', 'columnlistname', '', array('size' => '10')),
        );
        $this->mform->addGroup($elements, 'columnlists_elements', '', array(' '), false);
        if (count($lists)) {
            $this->mform->disabledIf('columnlists_elements', 'columnlistid', 'ne', '00');
        }
        $this->mform->setType('columnlistid', PARAM_ALPHANUM);
        $this->mform->setType('columnlistname', PARAM_TEXT);

        $M = 'M.mod_taskchain_edit_form_helper_columnlists';
        $module = $this->get_module_js($M);

        $strings = (object)array('all' => get_string('all'),
                                 'reset' => get_string('reset'),
                                 'none' => get_string('none'));

        $PAGE->requires->js_init_call("$M.setup_columnlist", array("id_columnlistid"), false, $module);
        $PAGE->requires->js_init_call("$M.setup_selectcommands", array($strings), false, $module);
    }

    /**
     * get_sectionlabel_conditions
     *
     * @param string $section name of section
     * @return xxx
     */
    protected function get_sectionlabel_conditions() {
        return get_string('conditions', 'mod_taskchain');
    }

    /**
     * get_fieldvalue_columnlistsheading
     *
     * @return xxx
     */
    protected function get_fieldvalue_columnlistsheading() {
        $type = $this->TC->get_columnlisttype();
        return get_string('columnlists' . $type, 'mod_taskchain');
    }

    /**
     * add_action_buttons
     *
     * @return array($name => $text)
     */
    protected function get_action_buttons() {
        return array('submit' => '',
                     'cancel' => '',
                     'delete' => '',
                     'deleteall' => '');
    }
}
