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
 * mod/taskchain/report/chaingrades/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/report/chaingrade/renderer.php');

/**
 * mod_taskchain_report_chaingrades_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_report_chaingrades_renderer extends mod_taskchain_report_chaingrade_renderer {
    public $mode = 'chaingrades';

    protected $headerfields = array();

    public $tablecolumns = array(
        'chaingradegrade',      'chaingradestatus',
        'chaingradeduration',   'chaingradetimemodified',
        'chainattemptcnumber',   'selected',
        'chainattemptgrade',    'chainattemptstatus',
        'chainattemptduration', 'chainattempttimemodified'
    );
    public $has_usercolumns = true;

    /** id param name and table name */
    public $id_param_name = 'chainid';
    public $id_param_table = 'taskchain_chains';

    /**
     * select_sql_record
     *
     * @param string   $select  (passed by reference)
     * @param string   $from    (passed by reference)
     * @param string   $where   (passed by reference)
     * @param array    $params  (passed by reference)
     * @param integer  $userid  (optional, default=0)
     * @param object   $record  (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function select_sql_record(&$select, &$from, &$where, &$params, $userid=0, $record=null) {
        $from   = '{taskchain_chain_attempts} tc_chn_att '.
                  ' JOIN {taskchain_chains} tc_chn ON tc_chn.id = tc_chn_att.chainid'.
                  ' JOIN {taskchain_chain_grades} tc_chn_grd ON (tc_chn.parenttype  = tc_chn_grd.parenttype AND '.
                                                                'tc_chn.parentid    = tc_chn_grd.parentid AND '.
                                                                'tc_chn_att.userid  = tc_chn_grd.userid)';
        // restrict sql to a specific chaingrade /user
        if ($record) {
            $where = 'tc_chn.id = ?';
            $params[] = $record->id;
        } else {
            $where  = 'tc_chn_grd.parenttype = ? AND tc_chn_grd.parentid = ?';
            $params = array(mod_taskchain::PARENTTYPE_ACTIVITY, $this->TC->taskchain->id);
            if ($userid) {
                $where .= ' AND tc_chn_grd.userid = ?';
                $params[] = $userid;
            }
        }
    }
}
