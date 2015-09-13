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
 * mod/taskchain/form/chains.php
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
require_once($CFG->dirroot.'/mod/taskchain/edit/form/helper/records.php');

/**
 * taskchain_form_helper_chains
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_form_helper_chains extends taskchain_form_helper_records {

    protected $recordtype = 'course';
    protected $recordstype = 'chain';
    protected $actions = array('applydefaults' => true);

    /**
     * add_action_applydefaults_details
     *
     * @todo Finish documenting this function
     */
    protected function add_action_applydefaults_details() {
        $field = 'applydefaults';
        $name  = $this->get_fieldname($field);
        $label = ''; // $this->get_fieldname($field);

        $this->mform->addElement('radio', $name, '', get_string('selectedchains', 'mod_taskchain'), 'selectedchains');
        $this->mform->addElement('radio', $name, '', get_string('filteredchains', 'mod_taskchain'), 'filteredchains');
        $this->mform->setType($name, PARAM_ALPHA);
        $this->mform->setDefault($name, 'selectedchains');
        $this->mform->disabledIf($name, 'action', 'ne', $field);

        $filterlist = array(
            self::FILTER_CONTAINS       => get_string('contains',       'filters'),
            self::FILTER_NOT_CONTAINS   => get_string('doesnotcontain', 'filters'),
            self::FILTER_EQUALS         => get_string('isequalto',      'filters'),
            self::FILTER_NOT_EQUALS     => get_string('notisequalto',   'mod_taskchain'),
            self::FILTER_STARTSWITH     => get_string('startswith',     'filters'),
            self::FILTER_NOT_STARTSWITH => get_string('notstartswith',  'mod_taskchain'),
            self::FILTER_ENDSWITH       => get_string('endswith',       'filters'),
            self::FILTER_NOT_ENDSWITH   => get_string('notendswith',    'mod_taskchain'),
            self::FILTER_EMPTY          => get_string('isempty',        'filters'),
            self::FILTER_NOT_EMPTY      => get_string('notisempty',     'mod_taskchain')
        );
        $filters = array(
            'coursename', 'activityname'
        );
        foreach ($filters as $filter) {
            $filtername = 'filter'.$filter;
            $filterlabel = $this->get_fieldlabel($filter);
            $name_filter = $this->get_fieldname($field.'_'.$filtername);
            $name_disabled = ''; // may be set below

            if ($filter=='coursename') {
                $courseid = $this->TC->get_courseid();
                if ($mycourses = $this->TC->get_mycourses()) {
                    $list = array();
                    if (count($mycourses)>1) {
                        $list[0] = get_string('all');
                    }
                    foreach ($mycourses as $mycourse) {
                        $shortname = format_string($mycourse->shortname);
                        if ($mycourse->id==SITEID) {
                            $shortname = get_string('frontpage', 'admin').': '.$shortname;
                        }
                        $list[$mycourse->id] = $shortname;
                    }
                } else {
                    $list = array($courseid => format_string($this->TC->course->shortname));
                }
                $this->mform->addElement('select', $name_filter, $filterlabel, $list);
                $this->mform->setDefault($name_filter, $courseid);
                $this->mform->setType($name_filter, PARAM_INT);
                $name_disabled = $name_filter;

            } else { // activityname (and any others)

                $name_type   = $this->get_fieldname($field.'_'.$filtername.'type');
                $name_value  = $this->get_fieldname($field.'_'.$filtername.'value');
                $name_elements = $this->get_fieldname($field.'_'.$filtername.'_elements');

                $elements = array();
                $elements[] = $this->mform->createElement('select', $name_type,   '', $filterlist);
                $elements[] = $this->mform->createElement('text',   $name_value,  '', array('size', $this->text_field_size));
                $this->mform->addGroup($elements, $name_elements, $filterlabel, ' ', false);

                $this->mform->setType($name_type, PARAM_INT);
                $this->mform->setType($name_value, PARAM_ALPHAEXT);

                $name_disabled = $name_elements;
            }

            if ($name_disabled) {
                $this->mform->disabledIf($name_disabled, 'action', 'ne', $field);
                $this->mform->disabledIf($name_disabled, $name, 'ne', 'filteredchains');
            }
        }
    }

    /**
     * get_filter_sql
     *
     * @return array taskchains $ids of records to be selected
     * @return array ($select, $from, $where, $params) to be passed to $DB->get_records_sql()
     * @todo Finish documenting this function
     */
    protected function get_filter_sql($ids) {
        global $DB;
        list($where, $params) = $DB->get_in_or_equal($ids);
        $select = 'tc_chn.*';
        $from   = "{taskchain} tc,".
                  "{taskchain_chains} tc_chn";
        $where  = 'tc.id '.$where.' '.
                  'AND tc.id = tc_chn.parentid '.
                  'AND tc_chn.parenttype = ?';
        $params[] = mod_taskchain::PARENTTYPE_ACTIVITY;
        return array($select, $from, $where, $params);
    }

    /**
     * get_filter_params
     *
     * @return array ($formfield => $dbfield) of params to pass to $this->get_filter()
     * @todo Finish documenting this function
     */
    function get_filter_params($field) {
        return array($field.'_filteractivityname' => 'tc.name');
    }
}
