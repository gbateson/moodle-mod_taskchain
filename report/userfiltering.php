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
 * mod/taskchain/report/userfiltering.php
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
require_once($CFG->dirroot.'/user/filters/lib.php');

/**
 * taskchain_user_filtering
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_user_filtering extends user_filtering {

    /**
     * taskchain_filter_group
     *
     * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     * @since      Moodle 2.0
     * @package    mod
     * @subpackage taskchain
     */
    class taskchain_filter_group extends user_filter_select {

    /**
     * taskchain_filter_status
     *
     * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     * @since      Moodle 2.0
     * @package    mod
     * @subpackage taskchain
     */
    class taskchain_filter_status extends user_filter_select {

    /**
     * taskchain_filter_number
     *
     * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     * @since      Moodle 2.0
     * @package    mod
     * @subpackage taskchain
     */
    class taskchain_filter_number extends user_filter_select {

    /**
     * taskchain_filter_duration
     *
     * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     * @since      Moodle 2.0
     * @package    mod
     * @subpackage taskchain
     */
    class taskchain_filter_duration extends taskchain_filter_number {

    /**
     * setupForm
     *
     * @param xxx $mform (passed by reference)
     * @todo Finish documenting this function
     */
    public function setupForm(&$mform)  {
        $objs = array(
            $mform->createElement('select', $this->_name.'_op', null, $this->get_operators()),
            $mform->createElement('duration', $this->_name, null, array('optional'=>0, 'defaultchain'=>1))
        );
        $mform->addElement('group', $this->_name.'_grp', $this->_label, $objs, '', false);
        $mform->disabledIf($this->_name.'_grp', $this->_name.'_op', 'eq', 0);

        $mform->setType($this->_name.'_op', PARAM_INT);
        $mform->setType($this->_name.'[number]', PARAM_INT);
        $mform->setType($this->_name.'[timechain]', PARAM_INT);

        if (!is_null($this->_default)) {
            $mform->setDefault($this->_name, $this->_default);
        }

        if ($this->_advanced) {
            $mform->setAdvanced($this->_name.'_grp');
        }
    }

    /**
     * Returns a human friendly description of the filter used as label.
     *
     * @param array $data filter settings
     * @return string active filter label
     */
    public function get_label($data) {
        $operator  = $data['operator'];
        $value     = $data['value'];
        $operators = $this->get_operators();

        if (empty($operator)) {
            return '';
        }

        $a = (object)array(
            'label'    => $this->_label,
            'value'    => '"'.s(format_time($value)).'"',
            'operator' => $operators[$operator]
        );

        return get_string('selectlabel', 'filters', $a);
    }
}
