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
 * mod/taskchain/form/records.php
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
require_once($CFG->dirroot.'/mod/taskchain/edit/form/helper/base.php');

/**
 * taskchain_form_helper_records
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
abstract class taskchain_form_helper_records extends taskchain_form_helper_base {

    /** the default sortorder increment */
    const SORT_INCREMENT       = 10;

    /** constants for filtering task strings */
    const FILTER_NONE           = 0;
    const FILTER_CONTAINS       = 1;
    const FILTER_NOT_CONTAINS   = 2;
    const FILTER_EQUALS         = 3;
    const FILTER_NOT_EQUALS     = 4;
    const FILTER_STARTSWITH     = 5;
    const FILTER_NOT_STARTSWITH = 6;
    const FILTER_ENDSWITH       = 7;
    const FILTER_NOT_ENDSWITH   = 8;
    const FILTER_EMPTY          = 9;
    const FILTER_NOT_EMPTY      = 10;

    /** constants for filtering tasks by position within chain */
    const FILTER_POSITION_ANY   = 0;
    const FILTER_POSITION_START = 1;
    const FILTER_POSITION_END   = 2;

    /** array to store $records in form */
    protected $records = array();

    /** type of $records */
    protected $recordstype = '';

    /** sections and fields in this form **/
    protected $sections = array(
        'filters'  => array('columnlistid'),
        'labels'   => array(), // fields will be fetched from first record in $this->records
        'defaults' => array(), // (as above)
        'selects'  => array(), // (as above)
        'records'  => array(), // (as above)
        'actions'  => array('action'),
        'hidden'   => array('id')
    );

    /** the actions available for this form **/
    protected $actions = array();

    /** the names fields on which records in this form can be sorted **/
    protected $sortfield = array();

    /**
     * __construct
     *
     * @param object $this->mform a MoodleQuickForm
     * @param object $context a context record from the database
     * @param stdClass $record fields from record in the database
     * @param boolean $multiple (optional, default=true)
     * @todo Finish documenting this function
     */
     public function __construct(&$mform, &$context, &$record, &$records) {
        global $CFG;

        parent::__construct($mform, $context, $record, false);

        if (isset($records) && is_array($records)) {

            $objectclass   = 'taskchain_'.$this->recordstype;
            $formclass     = 'taskchain_form_helper_'.$this->recordstype;
            $formclassfile = $CFG->dirroot.'/mod/taskchain/edit/form/helper/'.$this->recordstype.'.php';

            if (! file_exists($formclassfile)) {
                throw new moodle_exception(get_string('error_formhelperfilenotfound', 'mod_taskchain', $formclassfile));
            }

            // get class definition for child $records
            require_once($formclassfile);

            if (! class_exists($objectclass)) {
                throw new moodle_exception(get_string('error_recordclassnotfound', 'mod_taskchain', $objectclass));
            }
            if (! class_exists($formclass)) {
                throw new moodle_exception(get_string('error_formhelperclassnotfound', 'mod_taskchain', $formclass));
            }

            foreach (array_keys($records) as $id) {
                $r = $records[$id]; // a single record
                $r = new $objectclass($r, array('TC' => &$this->TC)); // taskchain_chain
                $r = new $formclass($mform, $context, $r, true);      // taskchain_form_helper_chain
                $this->records[$id] = $r;
            }
        }
    }

    /**
     * this form is not used for adding records, so this function always returns false
     *
     * @return bool always returns false
     */
    public function is_add() {
        return false;
    }

    /**
     * this form only used for updating records, so this function always returns true
     *
     * @return bool always returns true
     */
    public function is_update() {
        return true;
    }

    /**
     * add_field_columnlistid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function add_field_columnlistid($field) {
        global $output;

        $list = array();

        // add standard section names
        if ($record = $this->get_live_records(true)) {
            foreach ($record->get_sections(true) as $section => $fields) {
                // "general" section is added to every other section
                // so we do not display it separately
                if ($section=='general') {
                    continue;
                }
                // skip sections with no fields (e.g. reviewoptions)
                if (empty($fields)) {
                    continue;
                }
                if ($label = $record->get_sectionlabel($section)) {
                    $list[$section] = $label;
                }
            }
        }

        if (count($list)) {
            // prepend "default"
            $list = array_merge(array('default' => get_string('default')), $list);

            // append "all"
            $list = array_merge($list, array('all' => get_string('all')));

            // add custom lists
            $type = $this->TC->get_columnlisttype();
            $list = array_merge($list, $this->TC->get_columnlists($type));
        }

        if (count($list)) {
            $name = $this->get_fieldname($field);
            $label = $this->get_fieldlabel($field);
            $default = $this->get_defaultvalue($field, $this->get_preference($field));
            $name_submit = $this->get_fieldname($field.'submit');
            $name_elements = $this->get_fieldname($field.'_elements');

            $elements = array();
            $elements[] = $this->mform->createElement('select', $name, '', $list);
            $elements[] = $this->mform->createElement('submit', $name_submit, get_string('go'));

            $text = '';
            $text .= '<script type="text/javascript">'."\n";
            $text .= '//<![CDATA['."\n";
            $text .= '    var obj = document.getElementById("id_'.$name.'");'."\n";
            $text .= '    if (obj) {'."\n";
            $text .= '        obj.onchange = function() {window.onbeforeunload = null; this.form.submit()};'."\n";
            $text .= '    }'."\n";
            $text .= '    var obj = document.getElementById("id_'.$name_submit.'");'."\n";
            $text .= '    if (obj) {'."\n";
            $text .= '        obj.style.display = "none";'."\n";
            $text .= '    }'."\n";
            $text .= '//]]>'."\n";
            $text .= '</script>'."\n";

            // command($type, $taskchainscriptname, $id, $params, $popup=false)
            $params = array();
            $popup = array('width' => 300, 'fullheight' => true);
            $text .= $output->command('edit', 'edit/columnlists.php', $this->recordtype, $params, $popup);
            $elements[] = $this->mform->createElement('static', '', '', $text);

            $this->mform->addGroup($elements, $name_elements, $label, ' ', false);

            $this->mform->setType($name, PARAM_ALPHANUM);
            $this->mform->setDefault($name, $default);
            $this->add_helpbutton($name_elements, $field, 'taskchain');

            // make mform CSS class specific to this form
            // helps isolate HTML elements when in CSS3 styles
            $class = $this->mform->getAttribute('class');
            $class = (empty($class) ? '' : "$class ");
            $class = $class.'columnlist'.$default;
            $this->mform->updateAttributes(array('class' => $class));
        }
    }

    /**
     * add_section_labels
     *
     * @todo Finish documenting this function
     */
    public function add_section_labels($section, $fields) {
        if ($record = $this->get_live_records(1)) {
            $record->format_section_labels();
        }
    }

    /**
     * add_section_defaults
     *
     * @todo Finish documenting this function
     */
    public function add_section_defaults($section, $fields) {
        if ($record = $this->get_live_records(1)) {
            $record->is_default_record(true);
            $record->format_section_defaults();
            $record->is_default_record(false);
        }
    }

    /**
     * add_section_selects
     *
     * @todo Finish documenting this function
     */
    public function add_section_selects($section, $fields) {
        if ($record = $this->get_live_records(1)) {
            $record->format_section_selects();
        }
    }

    /**
     * add_section_records
     *
     * @todo Finish documenting this function
     */
    public function add_section_records($section, $fields) {
        $records = $this->get_live_records();
        foreach ($records as $record) {
            $record->format_sections();
        }
    }

    /**
     * add_section_actions
     *
     * @todo Finish documenting this function
     */
    public function add_field_action($field) {

        $name = $this->get_fieldname($field);
        $label = ''; // $this->get_fieldlabel($field)

        $count = count($this->get_live_records());
        $added = false;

        if ($count > 0) {
            $default = 'applydefaults';
        } else {
            $default = 'add'.$this->recordstype.'s'; // e.g. "addtasks"
        }

        $actions = $this->get_actions();
        foreach ($actions as $action => $require_records) {

            // add this $action, if required
            if (empty($require_records) || $count > 0) {

                $method = 'add_action_'.$action;
                if (method_exists($this, $method)) {
                    $this->$method($action, $name);
                } else {
                    // by default we add this action as a radio button
                    $this->mform->addElement('radio', $name, '', get_string($action, 'mod_taskchain'), $action);
                }

                $method = 'add_action_'.$action.'_details';
                if (method_exists($this, $method)) {
                    $this->$method();
                }

                $added = true;
            }
        }
        //
        if ($added) {
            $this->mform->setDefault($name, $default);
            $this->mform->setType($name, PARAM_ALPHA);

            $js = '';
            $js .= '<script type="text/javascript">'."\n";
            $js .= '//<![CDATA['."\n";
            $js .= 'function toggle_actions(obj) {'."\n";

            $js .= '    var targetid = "";'."\n";
            $js .= '    var showid = "";'."\n";
            $js .= '    if (obj && obj.id && obj.name) {'."\n";
            $js .= '        targetid = new RegExp("^(fitem|fgroup)_id_('.implode('|', array_keys($actions)).')");'."\n";
            $js .= '        showid = new RegExp("^(fitem|fgroup)_id_" + obj.id.substr(4 + obj.name.length));'."\n";
            $js .= '    }'."\n";

            $js .= '    var divs = null;'."\n";
            $js .= '    if (targetid && showid) {'."\n";
            $js .= '        var fieldset = document.getElementById("actionshdr");'."\n";
            $js .= '        if (fieldset) {'."\n";
            $js .= '            divs = fieldset.getElementsByTagName("DIV");'."\n";
            $js .= '        }'."\n";
            $js .= '        fieldset = null;'."\n";
            $js .= '    }'."\n";

            $js .= '    var i_max = 0;'."\n";
            $js .= '    if (divs) {'."\n";
            $js .= '        i_max = divs.length;'."\n";
            $js .= '    }'."\n";

            $js .= '    for (i=0; i<i_max; i++) {'."\n";
            $js .= '        if (divs[i].id.match(targetid)) {'."\n";
            $js .= '            if (divs[i].id.match(showid)) {'."\n";
            $js .= '                divs[i].style.display = "";'."\n";
            $js .= '            } else {'."\n";
            $js .= '                divs[i].style.display = "none";'."\n";
            $js .= '            }'."\n";
            $js .= '        }'."\n";
            $js .= '    }'."\n";

            $js .= '    divs = null;'."\n";
            $js .= '    return true;'."\n";
            $js .= '}'."\n";

            // add onclick event handlers to form actions
            foreach ($actions as $action => $require_records) {
                if (empty($require_records) || $count > 0) {
                    $js .= 'var obj = document.getElementById("id_'.$name.'_'.$action.'");'."\n";
                    $js .= 'if (obj) {'."\n";
                    $js .= '    obj.onclick = function() {toggle_actions(this)}'."\n";
                    $js .= '}'."\n";
                }
            }

            // initialize the toggle state (i.e. show or hide) of the form actions
            if ($formid = $this->mform->getAttribute('id')) {
                $js .= 'var obj = document.getElementById("'.$formid.'")'."\n";
                $js .= 'if (obj && obj.elements && obj.elements["'.$name.'"]) {'."\n";
                $js .= '    var i_max = obj.elements["'.$name.'"].length;'."\n";
                $js .= '    for (i=0; i<i_max; i++) {'."\n";
                $js .= '        if (obj.elements["'.$name.'"][i].checked) {'."\n";
                $js .= '            toggle_actions(obj.elements["action"][i]);'."\n";
                $js .= '        }'."\n";
                $js .= '    }'."\n";
                $js .= '}'."\n";
                $js .= 'obj = null;'."\n";
            }

            // set the heights of the "fitem" elements to the full height of their parent nodes
            // and set the width of the FIELDSETs so that they enclose all their child fitem DIVs
            $js .= 'function set_fitem_heights_and_widths() {'."\n";
            $js .= '    var fieldsets = document.getElementsByTagName("FIELDSET")'."\n";
            $js .= '    if (fieldsets) {'."\n";

            $js .= '        var hdrFieldsetId = new RegExp("^labels|defaults|selects|(record[0-9]+)$");'."\n";
            $js .= '        var fcontainerClass = new RegExp("\\\\b"+"fcontainer"+"\\\\b");'."\n";
            $js .= '        var felementClass = new RegExp("\\\\b"+"felement"+"\\\\b");'."\n";
            $js .= '        var fitemClass = new RegExp("\\\\b"+"fitem"+"\\\\b");'."\n";
            $js .= '        var fitemId = new RegExp("^(?:fgroup|fitem)_id_(?:(?:defaultfield|selectfield)_)?([a-z]+).*$");'."\n";
            $js .= '        var maxWidths = new Array();'."\n";

            $js .= '        var f_max = fieldsets.length;'."\n";
            $js .= '        for (var f=0; f<f_max; f++) {'."\n";
            $js .= '            if (fieldsets[f].id.match(hdrFieldsetId)) {'."\n";

            $js .= '                var divs = fieldsets[f].getElementsByTagName("DIV");'."\n";
            $js .= '                if (divs) {'."\n";

            $js .= '                    var maxRight = 0;'."\n";
            $js .= '                    var maxHeight = 0;'."\n";

            $js .= '                    var d_max = divs.length;'."\n";
            $js .= '                    for (var d=0; d<d_max; d++) {'."\n";
            $js .= '                        if (divs[d].className && divs[d].className.match(fitemClass)) {'."\n";

            $js .= '                            if (divs[d].offsetLeft && divs[d].offsetWidth) {'."\n";
            $js .= '                                maxRight = Math.max(maxRight, divs[d].offsetLeft + divs[d].offsetWidth);'."\n";
            $js .= '                            }'."\n";

            $js .= '                            if (divs[d].parentNode && divs[d].parentNode.className && divs[d].parentNode.className.match(fcontainerClass)) {'."\n";
            $js .= '                                if (divs[d].style.width) {'."\n";
            $js .= '                                    divs[d].style.width = null;'."\n";
            $js .= '                                }'."\n";
            $js .= '                            }'."\n";
            $js .= '                            var col = divs[d].id.replace(fitemId, "$1");'."\n";

            $js .= '                            var c_max = divs[d].childNodes.length;'."\n";
            $js .= '                            for (var c=0; c<c_max; c++) {'."\n";

            $js .= '                                var child = divs[d].childNodes[c];'."\n";
            $js .= '                                if (child.className && child.className.match(felementClass)) {'."\n";
            $js .= '                                    if (child.offsetHeight) {'."\n";
            $js .= '                                        maxHeight = Math.max(maxHeight, child.offsetHeight);'."\n";
            $js .= '                                    }'."\n";
            $js .= '                                    if (child.offsetWidth) {'."\n";
            $js .= '                                        if (maxWidths[col]==null) {'."\n";
            $js .= '                                            maxWidths[col] = 0;'."\n";
            $js .= '                                        }'."\n";
            $js .= '                                        maxWidths[col] = Math.max(maxWidths[col], child.offsetWidth);'."\n";
            $js .= '                                    }'."\n";
            $js .= '                                }'."\n";
            $js .= '                                var child = null;'."\n";

            $js .= '                            }'."\n";
            $js .= '                        }'."\n";
            $js .= '                    }'."\n";

            $js .= '                    for (var d=0; d<d_max; d++) {'."\n";
            $js .= '                        if (divs[d].parentNode && divs[d].parentNode.className && divs[d].parentNode.className.match(fcontainerClass)) {'."\n";
            $js .= '                            if (divs[d].className && divs[d].className.match(fitemClass)) {'."\n";
            $js .= '                                divs[d].style.height = maxHeight + "px";'."\n";
            $js .= '                            }'."\n";
            $js .= '                        }'."\n";
            $js .= '                    }'."\n";

            $js .= '                    if (maxRight) {'."\n";
            $js .= '                        fieldsets[f].style.width = (maxRight - fieldsets[f].offsetLeft) + "px";'."\n";
            $js .= '                    }'."\n";

            $js .= '                 }'."\n";
            $js .= '                 divs = null;'."\n";
            $js .= '            }'."\n";
            $js .= '        }'."\n";

            $js .= '        for (var f=0; f<f_max; f++) {'."\n";
            $js .= '            if (fieldsets[f].id.match(hdrFieldsetId)) {'."\n";

            $js .= '                var divs = fieldsets[f].getElementsByTagName("DIV");'."\n";
            $js .= '                if (divs) {'."\n";

            $js .= '                    var d_max = divs.length;'."\n";
            $js .= '                    for (var d=0; d<d_max; d++) {'."\n";
            $js .= '                        if (divs[d].parentNode && divs[d].parentNode.className && divs[d].parentNode.className.match(fcontainerClass)) {'."\n";
            $js .= '                            var col = divs[d].id.replace(fitemId, "$1");'."\n";
            $js .= '                            if (col) {'."\n";
            $js .= '                                if (maxWidths[col] && maxWidths[col] != divs[d].offsetWidth) {'."\n";
            $js .= '                                    divs[d].style.width = maxWidths[col] + "px";'."\n";
            $js .= '                                }'."\n";
            $js .= '                            }'."\n";
            $js .= '                        }'."\n";
            $js .= '                    }'."\n";

            $js .= '                 }'."\n";
            $js .= '                 divs = null;'."\n";
            $js .= '            }'."\n";
            $js .= '        }'."\n";


            $js .= '        hdrFieldsetId = null;'."\n";
            $js .= '        fcontainerClass = null;'."\n";
            $js .= '        felementClass = null;'."\n";
            $js .= '        fitemClass = null;'."\n";
            $js .= '        fitemId = null;'."\n";

            $js .= '    }'."\n";
            $js .= '    fieldsets = null;'."\n";
            $js .= '}'."\n";

            $js .= 'set_fitem_heights_and_widths();'."\n";

            // force bottom borders of final subactions
            $js .= 'function set_bottom_borders() {'."\n";
            $js .= '    var obj = document.getElementById("actionshdr");'."\n";
            if ($count==0) {
                $js .= '    var targetid = new RegExp("^(fitem|fgroup)_id_'.$field.'js$");'."\n";
            } else {
                $js .= '    var targetid = new RegExp("^(fitem|fgroup)_id_'.$field.'_('.implode('|', array_keys($actions)).')$");'."\n";
            }

            $js .= '    var divs = null;'."\n";
            $js .= '    if (obj) {'."\n";
            $js .= '        divs = obj.getElementsByTagName("DIV");'."\n";
            $js .= '    }'."\n";

            $js .= '    var d_max = 0;'."\n";
            $js .= '    if (divs) {'."\n";
            $js .= '        d_max = divs.length;'."\n";
            $js .= '    }'."\n";

            $js .= '    for (var d=0; d<d_max; d++) {'."\n";
            $js .= '        var node = null;'."\n";
            $js .= '        if (divs[d].id.match(targetid)) {'."\n";
            $js .= '            node = divs[d].previousSibling;'."\n";
            $js .= '            while (node && node.nodeType==3) {'."\n";
            $js .= '               node = node.previousSibling;'."\n";
            $js .= '            }'."\n";
            $js .= '        }'."\n";
            $js .= '        if (node) {'."\n";
            $js .= '            node.style.borderBottomColor = "#333333";'."\n";
            $js .= '            node.style.borderBottomStyle = "solid";'."\n";
            $js .= '            node.style.borderBottomWidth = "1px";'."\n";
            $js .= '            node.style.paddingBottomWidth = "6px";'."\n";
            $js .= '        }'."\n";
            $js .= '        node = null;'."\n";
            $js .= '    }'."\n";

            $js .= '    targetid = null;'."\n";
            $js .= '    divs = null;'."\n";
            $js .= '    obj = null;'."\n";
            $js .= '}'."\n";

            $js .= 'set_bottom_borders();'."\n";

            $js .= '//]]>'."\n";
            $js .= '</script>';

            $name = $this->get_fieldname($field.'js');
            $label = '';
            $this->mform->addElement('static', $name, $label, $js);
        }
    }

    /**
     * get_fieldvalue_columnlistid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_fieldvalue_columnlistid() {
        return $this->get_preference('columnlistid');
    }

    /**
     * get_fieldlabel_action
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_fieldlabel_action() {
        return get_string('action');
    }

    /**
     * fix_field_action
     *
     * @todo Finish documenting this function
     */
    public function fix_field_action(&$data) {
        if (empty($data->action)) {
            return;
        }
        $method = 'fix_action_'.$data->action;
        if (method_exists($this, $method)) {
            $this->$method($data);
        }
    }

    /**
     * get_fieldlabel_coursename
     *
     * @return string
     * @todo Finish documenting this function
     */
    protected function get_fieldlabel_coursename() {
        return get_string('coursename', 'grades');
    }

    /**
     * get_actions
     *
     * @todo Finish documenting this function
     */
    public function get_actions() {
        return $this->actions;
    }

    /**
     * get_records
     *
     * @todo Finish documenting this function
     */
    public function get_records() {
        return $this->records;
    }

    /**
     * get_live_records
     * "live" records are those that have NOT been deleted
     *
     * @param integer $limit (optional, default=0)
     * @todo Finish documenting this function
     */
    public function get_live_records($limit=0) {
        $action = optional_param('action', '', PARAM_ALPHA);
        if ($action=='delete'.$this->recordstype.'s') {
            $deleteids = mod_taskchain::optional_param_array('selectrecord', false, PARAM_INT);
        } else {
            $deleteids = false;
        }
        $records = $this->get_records();
        if ($deleteids) {
            foreach ($records as $id => $record) {
                if (array_key_exists($id, $deleteids)) {
                    unset($records[$id]);
                }
            }
        }
        if ($limit==1) {
            return reset($records);
        }
        if ($limit > 0) {
            return array_slice($records, 0, $limit);
        }
        return $records; // i.e. return all records
    }

    /**
     * prepare_records
     *
     * @todo Finish documenting this function
     */
    public function prepare_records() {
        $records = $this->get_live_records();
        foreach ($records as $record) {
            $record->prepare_sections();
        }
    }

    /**
     * add_records
     *
     * @todo Finish documenting this function
     */
    public function add_records() {
        $records = $this->get_live_records();
        foreach ($records as $record) {
            $record->add_sections();
        }
    }

    /**
     * validate_records
     *
     * @todo Finish documenting this function
     */
    public function validate_records(&$errors, &$data, &$files) {
        $records = $this->get_live_records();
        foreach ($records as $record) {
            $record->validate_sections($errors, $data, $files);
        }
    }

    /**
     * fix_data
     *
     * @todo Finish documenting this function
     */
    public function fix_data(&$data) {
        if (! isset($data->action)) {
            $data->action = optional_param('action', '', PARAM_ALPHA);
        }
        if (! isset($data->selectrecord)) {
            $data->selectrecord = mod_taskchain::optional_param_array('selectrecord', array(), PARAM_INT);
        }
    }

    /**
     * fix_records
     *
     * @todo Finish documenting this function
     */
    public function fix_records(&$data) {
        $records = $this->get_live_records();
        foreach ($records as $record) {
            $record->fix_sections($data);
        }
    }

    /**
     * update_records
     *
     * @param array $ids the ids of records that need to be updated
     * @return void, may update records in the database
     * @todo Finish documenting this function
     */
    protected function update_records($ids) {
        $records = $this->get_live_records();
        foreach ($records as $record) {
            $id = $record->get_fieldvalue('id');
            if (in_array($id, $ids)) {
                $record->update_record();
            }
        }
    }

    /**
     * delete_records
     *
     * @param object $data (passed by reference)the recently submitted form data
     * @param array $ids the ids of records that need to be deleted
     * @return void, may delete records in the database
     * @todo Finish documenting this function
     */
    protected function delete_records($ids) {
        $records = $this->get_records();
        foreach ($records as $record) {
            $id = $record->get_fieldvalue('id');
            if (in_array($id, $ids)) {
                $record->delete_record();
                unset($this->records[$id]);
            }
        }
    }

    /**
     * sort_records
     *
     * @param string $field the name of the field on which tasks are to be sorted
     * @param string $direction "asc" or "desc"
     * @return void, but may alter order of $this->tasks array
     * @todo Finish documenting this function
     */
    protected function sort_records($field, $direction) {
        $method = 'sort_'.$field.'_'.$direction;
        if (method_exists($this, $method)) {
            uasort($this->records, array($this, $method));
        }

        $method = 'sort_'.$this->recordstype;
        if (method_exists($this->TC, $method)) {
            $this->TC->$method($field, $direction);
        }
    }

    /**
     * sort_sortorder_desc
     *
     * @param object $a (passed by reference) an object to be compared
     * @param object $b (passed by reference) an object to be compared
     * @return integer -1 ($a > $b), 0 (equal), or 1 ($a < $b)
     * @todo Finish documenting this function
     */
    protected function sort_sortorder_desc(&$a, &$b) {
        return $this->sort($a, $b, 'sortorder', -1, 1);
    }

    /**
     * sort_sortorder_asc
     *
     * @param object $a (passed by reference) an object to be compared
     * @param object $b (passed by reference) an object to be compared
     * @return integer 1 ($a > $b), 0 (equal), or -1 ($a < $b)
     * @todo Finish documenting this function
     */
    protected function sort_sortorder_asc(&$a, &$b) {
        return $this->sort($a, $b, 'sortorder', 1, -1);
    }

    /**
     * sort
     *
     * @param object $a (passed by reference) an object to be compared
     * @param object $b (passed by reference) an object to be compared
     * @param string $field the name of the field on which $a and $b are to be compared
     * @param integer $gt the result to return if $a > $b (asc=1, desc=-1)
     * @param integer $lt the result to return if $a < $b (asc=-1, desc=1)
     * @param integer $eq the result to return if $a==$b (optional, default=0)
     * @return integer 1 ($a > $b), 0 ($a==$b), or -1 ($a < $b)
     * @todo Finish documenting this function
     */
    protected function sort(&$a, &$b, $field, $gt, $lt, $eq=0) {
        $method = 'get_'.$field;

        if (method_exists($a, $method)) {
            $a_value = $a->$method();
        } else if (isset($a->$field)) {
            $a_value = $a->$field;
        } else {
            $a_value = null;
        }

        if (method_exists($b, $method)) {
            $b_value = $b->$method();
        } else if (isset($b->$field)) {
            $b_value = $b->$field;
        } else {
            $b_value = null;
        }

        if ($a_value===null) {
            return ($b_value===null ? $eq : $lt);
        }
        if ($b_value===null) {
            return ($a_value===null ? $eq : $gt);
        }

        if ($a_value < $b_value) {
            return $lt;
        }
        if ($a_value > $b_value) {
            return $gt;
        }
        return $eq; // values are equal
    }

    /**
     * get_filter
     *
     * @param object $data (passed by reference)
     * @param string $formfield
     * @param string $dbfield
     * @return string
     * @todo Finish documenting this function
     */
    protected function get_filter(&$data, $formfield, $dbfield) {
        global $DB;

        $filter = '';
        $params = array();

        $type = $formfield.'type';
        $value = $formfield.'value';

        if (isset($data->$type) && isset($data->$value)) {
            $type = $data->$type;
            $value = $data->$value;
        } else {
            $type = self::FILTER_NONE;
            $value = '';
        }

        // check $type and $value are consistent
        if ($type==self::FILTER_NOT_EMPTY || $type==self::FILTER_NOT_EMPTY) {
            $value = '';
        } else if ($value==='') {
            $type = self::FILTER_NONE;
        }

        // $DB->sql_like($fieldname,      // required
        //               $param,          // required
        //               $casesensitive   = true,
        //               $accentsensitive = true,
        //               $notlike         = false,
        //               $escapechar      = '\\')

        switch($type) {
            case self::FILTER_CONTAINS:
                    $filter = $DB->sql_like($dbfield, '?', false, false, false);
                    $params = array('%'.$value.'%');
                    return array($filter, $params);

            case self::FILTER_NOT_CONTAINS:
                    $filter = $DB->sql_like($dbfield, '?', false, false, true);
                    $params = array('%'.$value.'%');
                    return array($filter, $params);

            case self::FILTER_EQUALS: // (case insensitive)
                    $filter = $DB->sql_like($dbfield, '?', false, false, false);
                    $params = array($value);
                    return array($filter, $params);

            case self::FILTER_NOT_EQUALS: // (case insensitive)
                    $filter = $DB->sql_like($dbfield, '?', false, false, true);
                    $params = array($value);
                    return array($filter, $params);

            case self::FILTER_STARTSWITH:
                    $filter = $DB->sql_like($dbfield, '?', false, false, false);
                    $params = array($value.'%');
                    return array($filter, $params);

            case self::FILTER_NOT_STARTSWITH:
                    $filter = $DB->sql_like($dbfield, '?', false, false, true);
                    $params = array($value.'%');
                    return array($filter, $params);

            case self::FILTER_ENDSWITH:
                    $filter = $DB->sql_like($dbfield, '?', false, false, false);
                    $params = array('%'.$value);
                    return array($filter, $params);

            case self::FILTER_NOT_ENDSWITH:
                    $filter = $DB->sql_like($dbfield, '?', false, false, true);
                    $params = array('%'.$value);
                    return array($filter, $params);

            case self::FILTER_EMPTY:
                    $filter = "$dbfield = ?";
                    $params = array($value);
                    return array($filter, $params);

            case self::FILTER_NOT_EMPTY:
                    $filter = "$dbfield <> ?";
                    $params = array($value);
                    return array($filter, $params);
        }

        return false; // unrecognized $type or empty $value
    }

    /**
     * get_filter_params
     *
     * @param string name of $field to prefix to $dbfield
     * @return array ($formfield => $dbfield) of params to pass to $this->get_filter()
     * @todo Finish documenting this function
     */
    function get_filter_params($field) {
        return array();
    }

    /**
     * get_filter_search
     *
     * @param object $data (passed by reference) the recently submitted form data
     * @param string name of $field
     * @param string $filtername
     * @return array ($select, $result)
     * @todo Finish documenting this function
     */
    protected function get_filter_search(&$data, $field, $filtername) {
        $search = '';
        $result = 0;

        $value = $field.'_'.$filtername.'value';
        $type  = $field.'_'.$filtername.'type';

        if (isset($data->$type) && isset($data->$value)) {
            $type = $data->$type;
            $value = preg_quote($data->$value, '/');
        } else {
            $type = self::FILTER_NONE;
            $value = '';
        }

        if ($type==self::FILTER_EMPTY || $type==self::FILTER_NOT_EMPTY) {
            $value = '';
        } else if ($value==='') {
            $type = self::FILTER_NONE;
        }

        switch ($type) {
            case self::FILTER_CONTAINS:       $result = 1; // no break here
            case self::FILTER_NOT_CONTAINS:   $search = "/$value/i"; break;

            case self::FILTER_EQUALS:         $result = 1; // no break here
            case self::FILTER_NOT_EQUALS:     $search = "/^$value$/i"; break;

            case self::FILTER_STARTSWITH:     $result = 1; // no break here
            case self::FILTER_NOT_STARTSWITH: $search = "/^$value/i"; break;

            case self::FILTER_ENDSWITH:       $result = 1; // no break here
            case self::FILTER_NOT_ENDSWITH:   $search = "/$value$/i"; break;

            case self::FILTER_EMPTY:          $result = 1; // no break here
            case self::FILTER_NOT_EMPTY:      $search = "/^$/"; break;
        }

        return array($search, $result);
    }

    /**
     * get_selected_records
     *
     * @param object $data the recently submitted form $data
     * @param boolean $fullrecord if true, return the full records, otherwise return just the record ids
     * @return array of records (or just their ids)
     * @todo Finish documenting this function
     */
    protected function get_selected_records(&$data, $fullrecord=true) {
        $records = array();
        if (isset($data->selectrecord) && is_array($data->selectrecord)) {
            foreach ($data->selectrecord as $id => $selected) {
                if (array_key_exists($id, $this->records) && $selected) {
                    if ($fullrecord) {
                        $records[$id] = &$this->records[$id];
                    } else {
                        $records[$id] = $id;
                    }
                }
            }
        }
        return $records;
    }

    /**
     * fix_action_applydefaults
     *
     * @param object $data the recently submitted form $data
     * @todo Finish documenting this function
     */
    protected function fix_action_applydefaults(&$data) {
        global $DB, $USER;

        if (empty($this->records) || empty($data->applydefaults)) {
            return; // nothing to do
        }

         // set defaults from selected record if necessary
        if (isset($data->defaultrecord) && array_key_exists($data->defaultrecord, $this->records)) {
            $defaultid = $data->defaultrecord;
            $record = $this->records[$defaultid];

            if (method_exists($record->record, 'to_stdclass')) {
                $defaultdata = $record->record->to_stdclass();
            } else {
                $defaultdata = $record->record;
            }
            $record->set_preferences($defaultdata);
            unset($defaultdata);

        } else {
            $defaultid = 0;
            $record = $this->get_live_records(true);
        }

        // get selected fields
        $defaultfields = array();
        foreach ($record->sections as $section => $fields) {
            foreach ($fields as $field) {
                $select_field = 'selectfield_'.$field;
                if (isset($data->$select_field) && $data->$select_field) {
                    $defaultfields[] = $field;
                }
            }
        }

        if (empty($defaultfields)) {
            return;
        }

        // get default values for selected fields
        $defaults = $record->get_preferences($defaultfields);

        if (empty($defaults)) {
            return;
        }

        // get selected records
        $records = array();
        switch ($data->applydefaults) {

            case 'selected'.$this->recordstype.'s':

                $records = $this->get_selected_records($data, true);
                break;

            case 'filtered'.$this->recordstype.'s':

                $ids = array(); // taskchain ids
                if (empty($data->coursenamefilter)) {
                    $courses = $DB->get_records('course');
                } else {
                    $courses = $DB->get_records('course', array('id' => $data->coursenamefilter));
                }
                if ($courses) {
                    if ($instances = get_all_instances_in_courses('taskchain', $courses, $USER->id, true)) {
                        foreach ($instances as $instance) {
                            list($search, $result) = $this->get_filter_search($data, 'applydefaults', 'filteractivityname');
                            if ($search=='' || ($result==preg_match($search, strip_tags(format_string($instance->name))))) {
                                $ids[] = $instance->id;
                            }
                        }
                        unset ($instances);
                    }
                    unset ($courses);
                }

                if ($ids) {
                    list($select, $from, $where, $params) = $this->get_filter_sql($ids);

                    $filters = $this->get_filter_params('applydefaults');
                    foreach ($filters as $formfield => $dbfield) {

                        if ($filter = $this->get_filter($data, $formfield, $dbfield)) {
                            list($filterwhere, $filterparams) = $filter;
                            $where = "$where AND $filterwhere";
                            $params = array_merge($params, $filterparams);
                        }
                    }

                    if (! $records = $DB->get_records_sql("SELECT $select FROM $from WHERE $where", $params)) {
                        $records = array();
                    }
                }

                $ids = array_keys($records);
                foreach ($ids as $id) {
                    if (array_key_exists($id, $this->records)) {
                        $records[$id] = &$this->records[$id];
                    }
                }

                unset($ids);
                break;

        } // end switch

        if (empty($records)) {
            return;
        }

        // updated selected fields on selected records
        $updated = array();
        foreach ($records as $id => $record) {
            $update_record = false;
            foreach ($defaults as $field => $defaultvalue) {
                if (method_exists($record, 'get_fieldvalue')) {
                    $value = $record->get_fieldvalue($field);
                } else if (isset($record->$field)) {
                    $value = $field;
                } else {
                    $value = null;
                }
                if (is_null($value) || $value == $defaultvalue) {
                    continue;
                }
                if (method_exists($record, 'set_fieldvalue')) {
                    $record->set_fieldvalue($field, $defaultvalue);
                    $updated[$id] = true;
                } else {
                    $record->$field = $defaultvalue;
                    $update_record = true;
                }
            }
            if ($update_record) {
                // update $DB record immediately (task outside current chain)
                $table = 'taskchain_'.$this->recordstype.'s';
                $DB->update_record($table, $record);
            }
            unset($record);
        }

        // allow for post processing (e.g. updating task conditions)
        $this->fix_action_applydefaults_extra($data, $records, $defaults, $updated);
        unset($records);

        if (count($updated)) {
            $updated = array_keys($updated);
            $this->update_records($updated);
        }
    }

    /**
     * fix_action_applydefaults_extra
     *
     * @param xxx $data     (passed by reference)
     * @param xxx $records  (passed by reference)
     * @param xxx $defaults (passed by reference)
     * @param xxx $updated  (passed by reference)
     * @todo Finish documenting this function
     */
    protected function fix_action_applydefaults_extra(&$data, &$records, &$defaults, &$updated) {
    }

    /**
     * add_field_sortfield
     *
     * @todo Finish documenting this function
     */
    protected function get_sortfield_fields() {
        $list = array();
        foreach ($this->sortfield as $field) {
            $list[$field] = $this->get_fieldlabel($field);
        }
        return $list;
    }

    /**
     * add_field_sortfield
     *
     * @todo Finish documenting this function
     */
    protected function add_field_sortfield($field, $nameprefix='') {
        $name = $this->get_fieldname($nameprefix.$field);
        $label = $this->get_fieldlabel($field);
        $list = $this->get_sortfield_fields();
        $this->mform->addElement('select', $name, $label, $list);
        $this->add_helpbutton($name, $field, 'taskchain');
        $this->mform->setType($name, PARAM_ALPHA);
        $this->mform->setDefault($name, 'sortorder');
        $this->mform->disabledIf($name, 'action', 'ne', 'reordertasks');
    }

    /**
     * add_field_sortdirection
     *
     * @todo Finish documenting this function
     */
    protected function add_field_sortdirection($field, $nameprefix='') {
        $name = $this->get_fieldname($nameprefix.$field);
        $label = $this->get_fieldlabel($field);
        $list = array('asc' => get_string('asc'), 'desc' => get_string('desc'));
        $this->mform->addElement('select', $name, $label, $list);
        $this->add_helpbutton($name, $field, 'taskchain');
        $this->mform->setType($name, PARAM_ALPHA);
        $this->mform->setDefault($name, 'asc');
        $this->mform->disabledIf($name, 'action', 'ne', 'reordertasks');
    }

    /**
     * add_field_sortincrement
     *
     * @todo Finish documenting this function
     */
    protected function add_field_sortincrement($field, $nameprefix='') {
        $name = $this->get_fieldname($nameprefix.$field);
        $label = $this->get_fieldlabel($field);
        $types = $this->recordstype.'s';
        $this->mform->addElement('text', $name, $label, array('size' => 2));
        $this->add_helpbutton($name, $field, 'taskchain');
        $this->mform->setType($name, PARAM_INT);
        $this->mform->setDefault($name, self::SORT_INCREMENT);
        $this->mform->disabledIf($name, 'action', 'ne', 'reorder'.$types);
    }
}
