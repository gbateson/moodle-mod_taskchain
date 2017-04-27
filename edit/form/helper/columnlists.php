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
 * taskchain_form_helper_condition
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_form_helper_columnlists extends taskchain_form_helper_record {

    /** type of record **/
    protected $recordtype = 'columnlists';

    /** type of records **/
    protected $recordstype = 'columnlists';

    /** sections and fields in this form **/
    protected $sections = array(
        'headings' => array('columnlistsheading'),
        'filters'  => array('columnlistid', 'columnlistname')
    );

    /** default values in a chain record */
    protected $defaultvalues = array();

    /** these fields will not be included in columnlists */
    protected $excludedfields = array(
        'columnlistid', 'columnlisttype', 'columnlistname',
        'edit', 'sortorder', 'defaultrecord', 'selectrecord',
        'addtype', 'tasknames'
    );

    /**
     * __construct
     *
     * @param object $mform a MoodleQuickForm
     * @param object $context a context record from the database
     * @param string $type "chains" or "tasks"
     * @param boolean $is_multiple (optional, default=false)
     * @todo Finish documenting this function
     */
    public function __construct(&$mform, &$context, &$type, $is_multiple=false) {
        global $CFG, $TC;

        if (empty($TC)) {
            $TC = new mod_taskchain();
        }

        // setup $record
        $record = new stdClass();

        // get columnlist info
        $id = $TC->get_columnlistid();
        $type = $TC->get_columnlisttype();
        $lists = $TC->get_columnlists($type, true);

        // transfer columnlist fields to $record
        if ($id && isset($lists[$id])) {
            $record->columnlistid = $id;
            $record->columnlisttype = $type;
            foreach ($lists[$id] as $field) {
                $record->$field = 1;
            }
        }

        switch ($type) {

            case 'tasks':
                require_once($CFG->dirroot.'/mod/taskchain/edit/form/helper/task.php');
                $form_helper = new taskchain_form_helper_task($mform, $context, $record);
                break;

            case 'chains':
                require_once($CFG->dirroot.'/mod/taskchain/edit/form/helper/chain.php');
                $form_helper = new taskchain_form_helper_chain($mform, $context, $record);
                break;

            default: throw new moodle_exception("Unknown columnlist type: $type");
        }

        // add record sections after headings
        $this->sections = array_merge($this->sections, $form_helper->get_sections());
        unset($form_helper);

        // remove "general" section (as those fields are always displayed)
        unset($this->sections['general']);

        // remove "tasks" section from "Edit chains" page
        if ($type=='chains') {
            unset($this->sections['tasks']);
        }

        // overwrite hidden section
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
     * @todo Finish documenting this function
     */
    public function validate_sections(&$errors, &$data, &$files) {
        // do nothing
    }

    /**
     * fix_sections
     *
     * @param stdClass $data (passed by reference)
     * @return void may modify $data
     * @todo Finish documenting this function
     */
    public function fix_sections($data) {
        $data->columnlist = array();
        $sections = $this->get_sections();
        foreach ($sections as $section => $fields) {
            foreach ($fields as $field) {
                if (isset($data->$field)) {
                    if (in_array($field, $this->excludedfields)) {
                        // do nothing
                    } else if ($data->$field) {
                        $data->columnlist[$field] = true;
                        unset($data->$field);
                    }
                }
            }
        }
        $data->columnlist = array_keys($data->columnlist);
        //$data->columnlist = implode(',', $data->columnlist);
    }

    /**
     * prepare_field
     *
     * @param array $data (passed by reference)
     * @param string $field name of field
     * @return void may modify $data
     * @todo Finish documenting this function
     */
    protected function prepare_field(&$data, $field) {
        if (isset($data[$field])) {
            // field has already be prepared
        } else {
            // copy value across from record
            $data[$field] = $this->get_originalvalue($field, '');
        }
    }

    /**
     * add_field
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function add_field($field) {
        if (in_array($field, $this->excludedfields)) {
            // do nothing
        } else {
            // default action is to add a checkbox element
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
     * @todo Finish documenting this function
     */
    protected function add_section_filters($section, $fields) {
        $this->mform->addElement('header', 'filtershdr', '');

        $type = $this->TC->get_columnlisttype();
        $lists = $this->TC->get_columnlists($type);
        $options = array('00' => get_string('add').' ...') + $lists;

        // javascript to auto submit the form if a new columnlist is selected
        $js = '';
        $js .= '<script type="text/javascript">'."\n";
        $js .= '//<![CDATA['."\n";
        $js .= 'var obj = document.getElementById("id_columnlistid");'."\n";
        $js .= 'if (obj) {'."\n";
        $js .= '    obj.onchange = function () {'."\n";
        $js .= '        var href = self.location.href.replace(new RegExp("columnlistid=\\\\w+&?"), "");'."\n";
        $js .= '        var char = href.charAt(href.length-1);'."\n";
        $js .= '        if (char!="?" && char!="&") {'."\n";
        $js .= '            if (href.indexOf("?")<0) {'."\n";
        $js .= '                href += "?";'."\n";
        $js .= '            } else {'."\n";
        $js .= '                href += "&";'."\n";
        $js .= '            }'."\n";
        $js .= '        }'."\n";
        $js .= '        href += "columnlistid=" + this.options[this.selectedIndex].value;'."\n";
        $js .= '        window.onbeforeunload = null;'."\n";
        $js .= '        self.location.href = href;'."\n";
        $js .= '    }'."\n";
        $js .= '}'."\n";
        $js .= '//]]>'."\n";
        $js .= '</script>'."\n";

        $elements = array();
        $elements[] = $this->mform->createElement('select', 'columnlistid', '', $options);
        $elements[] = $this->mform->createElement('text', 'columnlistname', '', array('size' => '10'));
        $elements[] = $this->mform->createElement('static', 'onchangecolumnlistid', '', $js);
        $this->mform->addGroup($elements, 'columnlists_elements', '', array(' '), false);
        if (count($lists)) {
            $this->mform->disabledIf('columnlists_elements', 'columnlistid', 'ne', '00');
        }
        $this->mform->setType('columnlistid', PARAM_ALPHANUM);
        $this->mform->setType('columnlistname', PARAM_TEXT);
    }

    /**
     * get_sectionlabel
     *
     * @param string $section name of section
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_sectionlabel($section) {
        $method = 'get_sectionlabel_'.$section;
        if (method_exists($this, $method)) {
            $label = $this->$method();
        } else {
            $label = get_string($section.'hdr', 'mod_taskchain');
        }

        $uselinks = false;
        if ($uselinks) {
            $links = '';
            $onclick = 'select_all_in_element_with_id("id_'.$section.'hdr", true); return false;';
            $links .= html_writer::tag('a', get_string('all'), array('onclick' => $onclick));
            $links .= ' / ';
            $onclick = 'select_all_in_element_with_id("id_'.$section.'hdr", false); return false;';
            $links .= html_writer::tag('a', get_string('none'), array('onclick' => $onclick));
            $links = html_writer::tag('span', $links, array('class' => 'allnonelinks'));
            return $label.' '.$links;
        } else {
            $title = get_string('selectall').' / '.get_string('deselectall');
            $onclick = 'select_all_in_element_with_id("id_'.$section.'hdr", this.checked);';
            $checkbox = html_writer::empty_tag('input', array('type' => 'checkbox', 'onclick' => $onclick, 'title' => $title));
            return $checkbox.' '.$label;
        }
    }

    /**
     * get_sectionlabel_conditions
     *
     * @param string $section name of section
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_sectionlabel_conditions() {
        return get_string('conditions', 'mod_taskchain');
    }

    /**
     * get_fieldvalue_columnlistsheading
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_fieldvalue_columnlistsheading() {
        $type = $this->TC->get_columnlisttype();
        return get_string('columnlists'.$type, 'mod_taskchain');
    }

    /**
     * add_action_buttons
     *
     * @return array($name => $text)
     * @todo Finish documenting this function
     */
    protected function get_action_buttons() {
        return array('submit' => '',
                     'cancel' => '',
                     'delete' => '',
                     'deleteall' => '');
    }

    /////////////////////////////////////////////////////////
    // get javascript
    /////////////////////////////////////////////////////////

    /**
     * return javascript to be inserted in footer of "page_quick" page
     *
     * @param string $type
     * @return string
     */
    public function get_js() {
        $js = '';

        if ($type = $this->TC->get_columnlisttype()) {
            // add javascript to modify conditions in opening window
            $js .= '<script type="text/javascript">'."\n";
            $js .= '//<![CDATA['."\n";
            $js .= '    if (window.opener) {'."\n";

            // locate the list of columnlists on the opening window
            $js .= '        var obj = opener.document.getElementById("id_columnlistid");'."\n";
            $js .= '        if (obj) {'."\n";

            // remove all user defined columnlists
            $js .= '            var selectedValue = obj.options[obj.selectedIndex].value;'."\n";
            $js .= '            var numericValue = new RegExp("^[0-9]+$");'."\n";
            $js .= '            var i_max = obj.options.length - 1;'."\n";
            $js .= '            for (var i=i_max; i>=0; i--) {'."\n";
            $js .= '                if (obj.options[i].value.match(numericValue)) {;'."\n";
            $js .= '                    if (obj.remove) {'."\n";
            $js .= '                        obj.remove(i);'."\n";
            $js .= '                    } else {'."\n";
            $js .= '                        obj.options[i] = null;'."\n";
            $js .= '                    }'."\n";
            $js .= '                }'."\n";
            $js .= '            }'."\n";

            // prepare new columnlists
            $lists = $this->TC->get_columnlists($type);
            foreach ($lists as $id => $name) {
                $lists[$id] = '"'.$id.'":"'.$name.'"'; // JSON
            }
            $lists = implode(',', array_values($lists));

            // append new columnlists
            $js .= '            var i_max = obj.options.length;'."\n";
            $js .= '            var columnlists = {'.$lists.'};'."\n";
            $js .= '            for (id in columnlists) {'."\n";
            $js .= '                var option = document.createElement("option");'."\n";
            $js .= '                option.setAttribute("value", id);'."\n";
            $js .= '                option.setAttribute("label", columnlists[id]);'."\n";
            $js .= '                if (id==selectedValue) {'."\n";
            $js .= '                    option.setAttribute("selected", true);'."\n";
            $js .= '                }'."\n";
            $js .= '                obj.appendChild(option);'."\n";
            $js .= '            }'."\n";

            // close the javascript
            $js .= '        }'."\n";
            $js .= '        window.close()'."\n";
            $js .= '    }'."\n";
            $js .= '//]]>'."\n";
            $js .= '</script>'."\n";
        }
        return $js;
    }
}
