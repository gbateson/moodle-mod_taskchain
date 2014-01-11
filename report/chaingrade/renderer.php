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
 * mod/taskchain/report/chaingrade/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/report/renderer.php');

/**
 * mod_taskchain_report_chaingrade_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_report_chaingrade_renderer extends mod_taskchain_report_renderer {
    public $mode = 'chaingrade';

    public $tablecolumns = array(
        'chaingradegrade',      'chaingradestatus',
        'chaingradeduration',   'chaingradetimemodified',
        'chainattemptcnumber',   'selected',
        'chainattemptgrade',    'chainattemptstatus',
        'chainattemptduration', 'chainattempttimemodified'
    );

    /** columns with this prefix will be suppressed (i.e. only shown once per user) */
    protected $suppressprefix = 'chaingrade';

    public $filterfields = array(
        'grade'=>1, 'timemodified'=>1, 'status'=>0, 'duration'=>1, // 'score'=>1
    );

    /**
     * count_sql
     *
     * @param xxx $userid (optional, default=0)
     * @param xxx $gradeid (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function count_sql($userid=0, $gradeid=0) {
        $select = 'COUNT(1)';
        $from   = '{taskchain_chain_attempts} tc_chn_att '.
                  ' JOIN {taskchain_chains} tc_chn ON tc_chn.id = tc_chn_att.chainid'.
                  ' JOIN {taskchain_chain_grades} tc_chn_grd ON (tc_chn.parenttype = tc_chn_grd.parenttype '.
                                                              'AND tc_chn.parentid = tc_chn_grd.parentid)';
        $where  = 'tc_chn.parenttype = ? AND tc_chn.parentid = ?';
        $params = array(mod_taskchain::PARENTTYPE_ACTIVITY, $this->TC->taskchain->id);

        // restrict to a specific user
        if ($userid) {
            $where .= ' AND tc_chn_att.userid = ?';
            $params[] = $userid;
        }

        // restrict to a specific gradeid
        if ($gradeid) {
            $where = ' AND tc_chn_grd.id = ?';
            $params[] = $gradeid;
        }

        return array($select, $from, $where, $params);
    }

    /**
     * select_sql
     *
     * @param xxx $userid (optional, default=0)
     * @param xxx $gradeid (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function select_sql($userid=0, $gradeid=0) {
        // the standard way to get Moodle grades is thus:
        // $grades = grade_get_grades($this->TC->course->id, 'mod', 'taskchain', $this->TC->id, $userid);
        // $grade = $grades->items[0]->grades[$USER->id]->grade;

        // sql to select all grades for this TaskChain - what about Moodle grade?
        $select = 'tc_chn_att.id AS id, '.
                  'tc_chn_att.chainid AS chainattemptchainid, '.
                  'tc_chn_att.cnumber AS chainattemptcnumber, '.
                  'tc_chn_att.grade AS chainattemptgrade, '.
                  'tc_chn_att.status AS chainattemptstatus, '.
                  'tc_chn_att.duration AS chainattemptduration, '.
                  'tc_chn_att.timemodified AS chainattempttimemodified, '.
                  'tc_chn_grd.id AS chaingradeid, '.
                  'tc_chn_grd.grade AS chaingradegrade, '.
                  'tc_chn_grd.status AS chaingradestatus, '.
                  'tc_chn_grd.duration AS chaingradeduration, '.
                  'tc_chn_grd.timemodified AS chaingradetimemodified';
        $from   = '{taskchain_chain_attempts} tc_chn_att '.
                  ' JOIN {taskchain_chains} tc_chn ON tc_chn.id = tc_chn_att.chainid'.
                  ' JOIN {taskchain_chain_grades} tc_chn_grd ON (tc_chn.parenttype = tc_chn_grd.parenttype '.
                                                              'AND tc_chn.parentid = tc_chn_grd.parentid)';
        if ($this->TC->get_chaingrade()) {
            $where  = 'tc_chn_grd.id = ?';
            $params = array($this->TC->chaingrade->id);
        } else {
            $where  = 'tc_chn_grd.parenttype = ? AND tc_chn_grd.parentid = ?';
            $params = array(mod_taskchain::PARENTTYPE_ACTIVITY, $this->TC->taskchain->id);
        }

        // add user fields. if required
        if (in_array('fullname', $this->tablecolumns)) {
            $select .= ', '.$this->get_userfields('u', null, 'userid');
            $from   .= ' JOIN {user} u ON tc_chn_att.userid=u.id';
        }

        // restrict sql to a specific user
        if ($userid) {
            $where .= ' AND tc_chn_att.userid = ?';
            $params[] = $userid;
        }

        // restrict to a specific gradeid
        if ($gradeid) {
            $where = ' AND tc_chn_grd.id = ?';
            $params[] = $gradeid;
        }

        return array($select, $from, $where, $params);
    }
}
