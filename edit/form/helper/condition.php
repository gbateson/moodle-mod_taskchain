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
 * mod/taskchain/form/conditions.php
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
class taskchain_form_helper_condition extends taskchain_form_helper_record {

    /** type of record **/
    protected $recordtype = 'condition';

    /** sections and fields in this form **/
    protected $sections = array(
        'headings'  => array('taskheading', 'conditionheading'),
        'condition' => array('groupid', 'sortorder', 'conditiontaskid', 'conditionscore',
                             'attemptcount', 'attempttype', 'attemptduration', 'attemptdelay', 'nexttaskid'),
        'hidden'    => array('id')
    );

    /** default values in a chain record */
    protected $defaultvalues = array(
        'groupid'         => 0, // = any group
        'sortorder'       => 0,
        'conditiontaskid' => 0,
        'conditionscore'  => 0,
        'attempttype'     => mod_taskchain::ATTEMPTTYPE_ANY,
        'attemptcount'    => 0,
        'attempttype'     => 0,
        'attemptduration' => 0,
        'attemptdelay'    => 0,
        'nexttaskid'      => 0,
    );

    /**
     * get_defaultvalue_conditiontaskid
     *
     * PRE conditions may use any task or task constant as conditiontaskid
     * POST conditions always use current (i.e. SAME) task as conditiontaskid
     *
     * @param string $field the field name
     * @return mixed user preference for this field, or $this->defaultvalues[$field], or null
     * @todo Finish documenting this function
     */
    protected function get_defaultvalue_conditiontaskid($field) {
        if ($this->get_conditiontype()==mod_taskchain::CONDITIONTYPE_POST) {
            return mod_taskchain::CONDITIONTASKID_SAME;
        }
        return $this->get_preference($field, $this->defaultvalues[$field]);
    }

    /**
     * get_defaultvalue_nexttaskid
     *
     * PRE conditions always use current (i.e. SAME) task as nexttaskid
     * POST conditions may use any task or task constant as nexttaskid
     *
     * @param string $field the field name
     * @return mixed user preference for this field, or $this->defaultvalues[$field], or null
     * @todo Finish documenting this function
     */
    protected function get_defaultvalue_nexttaskid($field) {
        if ($this->get_conditiontype()==mod_taskchain::CONDITIONTYPE_PRE) {
            return mod_taskchain::CONDITIONTASKID_SAME;
        }
        return $this->get_preference($field, $this->defaultvalues[$field]);
    }

    /**
     * get_conditiontype
     *
     * @return integer the type of this condition
     */
    protected function get_conditiontype() {
        if ($this->is_add()) {
            return $this->TC->get_conditiontype();
        } else {
            return $this->get_fieldvalue('conditiontype');
        }
    }

    /**
     * get_minmax_options
     */
    function get_minmax_options() {
        return array(mod_taskchain::MIN => get_string('minimum', 'mod_taskchain'),
                     mod_taskchain::MAX => get_string('maximum', 'mod_taskchain'));
    }


    /**
     * prepare_template_minmax
     *
     * @param object $data (passed by reference) from form
     * @param string name of minmax $field to fix
     * @todo Finish documenting this function
     */
    protected function prepare_template_minmax(&$data, $field) {

        $name = $this->get_fieldname($field);
        $name_enable = $this->get_fieldname($field.'enable');
        $name_minmax = $this->get_fieldname($field.'minmax');

        if (empty($data[$name])) {
            $data[$name_enable] = mod_taskchain::NO;
            $data[$name_minmax] = mod_taskchain::MIN;
            $data[$name] = 0;
        } else {
            $data[$name_enable] = mod_taskchain::YES;
            $data[$name_minmax] = (($data[$name] < 0) ? mod_taskchain::MIN : mod_taskchain::MAX);
            $data[$name] = abs($data[$name]);
        }
    }

    /**
     * prepare_field_conditionscore
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function prepare_field_conditionscore(&$data) {
        $this->prepare_template_minmax($data, 'conditionscore');
    }

    /**
     * prepare_field_attemptcount
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function prepare_field_attemptcount(&$data) {
        $this->prepare_template_minmax($data, 'attemptcount');
    }

    /**
     * prepare_field_attemptduration
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function prepare_field_attemptduration(&$data) {
        $this->prepare_template_minmax($data, 'attemptduration');
    }

    /**
     * prepare_field_attemptdelay
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function prepare_field_attemptdelay(&$data) {
        $this->prepare_template_minmax($data, 'attemptdelay');
    }

    /**
     * get heading for this form
     *
     * @todo Finish documenting this function
     */
    protected function get_fieldvalue_taskheading() {
        if ($this->TC->get_taskid()) {
            return format_string($this->TC->task->get_name());
        } else {
            return ''; // shouldn't happen !!
        }
    }

    /**
     * get heading for this form
     *
     * @todo Finish documenting this function
     */
    protected function get_fieldvalue_conditionheading() {
        switch ($this->TC->get_conditiontype()) {
            case mod_taskchain::CONDITIONTYPE_PRE:  $type = 'precondition';  break;
            case mod_taskchain::CONDITIONTYPE_POST: $type = 'postcondition'; break;
            default: return ''; // shouldn't happen !!
        }
        switch ($this->TC->action) {
            case 'add': return get_string('addinganew', 'moodle', mod_taskchain::textlib('strtolower', get_string($type, 'mod_taskchain')));
            case 'edit': return get_string('updatinga', 'moodle', mod_taskchain::textlib('strtolower', get_string($type, 'mod_taskchain')));
            case 'delete': return get_string('delete'.$type, 'mod_taskchain');
            case 'deleteall': return get_string('deleteall'.$type.'s', 'mod_taskchain');
            return ''; // shouldn't happen !!
        }
    }

    /**
     * get_sectionlabel_condition
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_sectionlabel_condition() {
        switch ($this->get_conditiontype()) {
            case mod_taskchain::CONDITIONTYPE_PRE: return get_string('precondition', 'mod_taskchain');
            case mod_taskchain::CONDITIONTYPE_POST: return get_string('postcondition', 'mod_taskchain');
            default: return get_string('general', 'form'); // shouldn't happen !!
        }
    }

    /**
     * add_field_groupid
     *
     * @param string $field, the name of field to add
     * @return void, but adds a field to $this->mform
     */
    protected function add_field_groupid($field) {

        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);

        if ($groups = $this->TC->get_all_groups()) {
            $options = array(
                '0' => get_string('anygroup', 'mod_taskchain')
            );
            foreach($groups as $group) {
                $options[$group->id] = format_string($group->name);
            }
            $this->mform->addElement('select', $name, $label, $options);
            $this->mform->setType($name, PARAM_INT);
            $this->mform->setDefault($name, $this->get_defaultvalue($field));
            $this->add_helpbutton($name, $field, 'taskchain');
        } else {
            $this->mform->addElement('hidden', $name, '0');
            $this->mform->setType($name, PARAM_INT);
        }
    }

    /**
     * add_field_sortorder
     *
     * @param string $field, the name of field to add
     * @return void, but adds a field to $this->mform
     */
    protected function add_field_sortorder($field) {
        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);

        $conditiontype = $this->get_conditiontype();
        if ($conditiontype==mod_taskchain::CONDITIONTYPE_PRE) {
            $options = array();
            for ($i=0; $i<8; $i++) {
                $options[$i] = $i;
            }
            $this->mform->addElement('select', $name, $label, $options);
            $this->mform->setType($name, PARAM_INT);
            $this->mform->setDefault($name, $this->get_defaultvalue($field));
            $this->add_helpbutton($name, $field, 'taskchain');
            $this->mform->setAdvanced($name);
        } else {
            // post-conditions don't use the sort order field (but they could)
            $this->mform->addElement('hidden', $name, 0);
            $this->mform->setType($name, PARAM_INT);
        }
    }

    /**
     * add_field_conditiontaskid
     *
     * @param string $field, the name of field to add
     * @return void, but adds a field to $this->mform
     */
    protected function add_field_conditiontaskid($field) {

        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);

        $conditiontype = $this->get_conditiontype();
        if ($conditiontype==mod_taskchain::CONDITIONTYPE_PRE) {
            // pre-conditions allow teacher to specify the task to which this condition refers
            $options = array(
                mod_taskchain::CONDITIONTASKID_PREVIOUS => get_string('previoustask', 'mod_taskchain')
            );
            if ($tasks = $this->TC->get_tasks()) {
                // a specific TaskChain task from this chain
                foreach ($tasks as $task) {
                    $options[$task->id] = '['.$task->sortorder.'] '.format_string($task->name);
                }
            }
            $this->mform->addElement('select', $name, $label, $options);
            $this->mform->setType($name, PARAM_INT);
            $this->mform->setDefault($name, $this->get_defaultvalue($field));
            $this->add_helpbutton($name, $field, 'taskchain');
        } else {
            // post-conditions always use the current task as the condition task
            $this->mform->addElement('hidden', $name, $this->TC->get_taskid());
            $this->mform->setType($name, PARAM_INT);
        }
    }

    /**
     * add_field_conditionscore
     *
     * @param string $field, the name of field to add
     * @return void, but adds a field to $this->mform
     */
    protected function add_field_conditionscore($field) {
        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);

        $name_minmax = $this->get_fieldname($field.'minmax');
        $name_enable = $this->get_fieldname($field.'enable');
        $name_elements = $this->get_fieldname($field.'_elements');

        $elements = array();
        $elements[] = $this->mform->createElement('select', $name_minmax, '', $this->get_minmax_options());
        $elements[] = $this->mform->createElement('text', $name, '', array('size' => '4'));
        $elements[] = $this->mform->createElement('checkbox', $name_enable, '', get_string('enable'));

        $this->mform->addGroup($elements, $name_elements, $label, array(' '), false);
        $this->add_helpbutton($name_elements, $field, 'taskchain');

        $this->mform->setType($name_minmax, PARAM_INT);
        $this->mform->setType($name, PARAM_INT);
        $this->mform->setType($name_enable, PARAM_INT);
        $this->mform->setDefault($name_enable, 0); // i.e. disabled by default
        $this->mform->disabledIf($name_elements, $name_enable, 'notchecked');
    }

    /**
     * add_field_attemptcount
     *
     * @param string $field, the name of field to add
     * @return void, but adds a field to $this->mform
     */
    protected function add_field_attemptcount($field) {
        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);

        $name_minmax = $this->get_fieldname($field.'minmax');
        $name_enable = $this->get_fieldname($field.'enable');
        $name_elements = $this->get_fieldname($field.'_elements');

        $options = array('0'=>'');
        for ($i=1; $i<=20; $i++) {
            $options[$i] = $i;
        }

        $elements = array();
        $elements[] = $this->mform->createElement('select', $name_minmax, '', $this->get_minmax_options());
        $elements[] = $this->mform->createElement('text', $name, '', array('size' => '4'));
        $elements[] = $this->mform->createElement('checkbox', $name_enable, '', get_string('enable'));
        $this->mform->addGroup($elements, $name_elements, $label, array(' '), false);

        $this->mform->setType($name_minmax, PARAM_INT);
        $this->mform->setType($name, PARAM_INT);
        $this->mform->setType($name_enable, PARAM_INT);
        $this->mform->setDefault($name_enable, 0); // i.e. disabled by default
        $this->mform->disabledIf($name_elements, $name_enable, 'notchecked');
        $this->add_helpbutton($name_elements, $field, 'taskchain');
        $this->mform->setAdvanced($name_elements);
    }


    /**
     * disabledIf_attemptcount_zero
     *
     * @param string $name of field to be disabled if attempt count is zero
     * @todo Finish documenting this function
     */
    protected function disabledIf_attemptcount_zero($name) {
        //$this->mform->disabledIf($name, 'attemptcount', 'eq', 0);
        $this->mform->disabledIf($name, 'attemptcountenable', 'notchecked');
    }

    /**
     * add_field_attempttype
     *
     * @param string $field, the name of field to add
     * @return void, but adds a field to $this->mform
     */
    protected function add_field_attempttype($field) {
        $name = $this->get_fieldname($field);
        $this->add_template_list($field);
        $this->disabledIf_attemptcount_zero($name);
        $this->mform->setAdvanced($name);
    }

    /**
     * add_field_attemptduration
     *
     * @param string $field, the name of field to add
     * @return void, but adds a field to $this->mform
     */
    protected function add_field_attemptduration($field) {
        $before = array('minmax' => $this->get_minmax_options());
        $this->add_template_timer($field, false, $before);
        $name_elements = $this->get_fieldname($field.'_elements');
        $this->disabledIf_attemptcount_zero($name_elements);
    }

    /**
     * add_field_attemptdelay
     *
     * @param string $field, the name of field to add
     * @return void, but adds a field to $this->mform
     */
    protected function add_field_attemptdelay($field) {
        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);

        $conditiontype = $this->get_conditiontype();
        if ($conditiontype==mod_taskchain::CONDITIONTYPE_PRE) {
            $before = array('minmax' => $this->get_minmax_options());
            $this->add_template_timer($field, false, $before);
            $name_elements = $this->get_fieldname($field.'_elements');
            $this->disabledIf_attemptcount_zero($name_elements);
            $this->mform->setAdvanced($name_elements);
        } else {
            // post-conditions: time elapsed is always 0?
            $this->mform->addElement('hidden', $name, 0);
            $this->mform->setType($name, PARAM_INT);
        }
    }

    /**
     * add_field_nexttaskid
     *
     * @param string $field, the name of field to add
     * @return void, but adds a field to $this->mform
     */
    protected function add_field_nexttaskid($field) {
        $name = $this->get_fieldname($field);

        $conditiontype = $this->get_conditiontype();
        if ($conditiontype==mod_taskchain::CONDITIONTYPE_PRE) {
            // pre-conditions always have the current task as the next task
            $this->mform->addElement('hidden', $name, $this->TC->get_taskid());
            $this->mform->setType($name, PARAM_INT);
        } else {
            // post-conditions allow teacher to specify next task
            $this->add_template_list($field);
        }
    }

    /**
     * fix_field_conditionscore
     *
     * @param object $data (passed by reference) from form
     * @param string name of $field to fix
     * @todo Finish documenting this function
     */
    protected function fix_field_conditionscore(&$data, $field) {
        $this->fix_template_minmax($data, $field);
    }

    /**
     * fix_field_attemptcount
     *
     * @param object $data (passed by reference) from form
     * @param string name of $field to fix
     * @todo Finish documenting this function
     */
    protected function fix_field_attemptcount(&$data, $field) {
        $this->fix_template_minmax($data, $field);
    }

    /**
     * fix_field_attemptduration
     *
     * @param object $data (passed by reference) from form
     * @param string name of $field to fix
     * @todo Finish documenting this function
     */
    protected function fix_field_attemptduration(&$data, $field) {
        $this->fix_template_minmax($data, $field);
    }

    /**
     * fix_field_attemptdelay
     *
     * @param object $data (passed by reference) from form
     * @todo Finish documenting this function
     */
    protected function fix_field_attemptdelay(&$data, $field) {
        $this->fix_template_minmax($data, $field);
    }

    /**
     * fix_template_minmax
     *
     * @param object $data (passed by reference) from form
     * @param string name of minmax $field to fix
     * @todo Finish documenting this function
     */
    protected function fix_template_minmax(&$data, $field) {

        $name = $this->get_fieldname($field);
        $name_enable = $this->get_fieldname($field.'enable');
        $name_minmax = $this->get_fieldname($field.'minmax');

        if (empty($data->$name) || empty($data->$name_enable)) {
            $data->$name = 0;
        } else if (isset($data->$name_minmax)) {
            $data->$name = abs($data->$name) * $data->$name_minmax;
        }
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

        if ($conditiontype = $this->TC->get_conditiontype()) {
            $type = '';
            switch ($conditiontype) {
                case mod_taskchain::CONDITIONTYPE_PRE:  $type = 'preconditions'; break;
                case mod_taskchain::CONDITIONTYPE_POST: $type = 'postconditions'; break;
            }
            $taskid = $this->TC->get_taskid();

            // set this task to by the default task for conditions of this $type
            $name = 'taskchain_task_'.$type;
            set_user_preferences(array($name => $taskid));

            // add javascript to modify conditions in opening window
            $js .=  '<script type="text/javascript">'."\n";
            $js .=  '//<![CDATA['."\n";
            $js .=  '    if (window.opener) {'."\n";

            // modify the default conditions, if any, in the opening window
            $js .=  '        var obj = opener.document.getElementById("taskchain_'.$type.'_default");'."\n";
            $js .=  '        if (obj) {'."\n";
            $js .=  '            obj.innerHTML = "'.$this->format_conditions($taskid, $conditiontype, false, true, false).'";'."\n";
            $js .=  '        }'."\n";

            // locate the conditions DIV in the opening window
            $js .=  '        var obj = opener.document.getElementById("taskchain_'.$type.'_'.$taskid.'");'."\n";
            $js .=  '        if (obj) {'."\n";

            // remove the old conditions intro (on task edit page only)
            $js .=  '            var intro = null;'."\n";
            $js .=  '            var p = obj.getElementsByTagName("p");'."\n";
            $js .=  '            var i_max = p.length;'."\n";
            $js .=  '            for (var i=0; i<i_max; i++) {'."\n";
            $js .=  '                if (p[i].className=="taskchainconditionsintro") {'."\n";
            $js .=  '                    intro = p[i].parentNode.removeChild(p[i]);'."\n";
            $js .=  '                    break;'."\n";
            $js .=  '                }'."\n";
            $js .=  '            }'."\n";
            $js .=  '            p = null;'."\n";

            // add the modified conditions
            $js .=  '            obj.innerHTML = "'.$this->format_conditions($taskid, $conditiontype, false, true, true).'";'."\n";

            // restore the conditions intro text (if necessary)
            $js .=  '            if (intro) {'."\n";
            $js .=  '                obj.insertBefore(intro, obj.firstChild)'."\n";
            $js .=  '                intro = null;'."\n";
            $js .=  '            }'."\n";

            // adjust row heights as necessary
            $js .=  '            if (window.opener.set_fitem_heights_and_widths) {'."\n";
            $js .=  '                window.opener.set_fitem_heights_and_widths();'."\n";
            $js .=  '            }'."\n";

            // close the javascript
            $js .=  '        }'."\n";
            $js .=  '        window.close()'."\n";
            $js .=  '    }'."\n";
            $js .=  '//]]>'."\n";
            $js .=  '</script>'."\n";
        }
        return $js;
    }
}
