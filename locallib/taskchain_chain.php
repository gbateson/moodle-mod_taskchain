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
 * mod/taskchain/locallib/taskchain_chain.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/**
 * taskchain_chain
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_chain extends taskchain_base {

    /** db field: id (primary key, default=null) */
    private $id                  = null;

    /** db field: parenttype (integer, default=0) */
    private $parenttype          = 0;

    /** db field: parentid (integer, default=0) */
    private $parentid            = 0;

    /** db field: entrycm (integer, default=0) */
    private $entrycm             = 0;

    /** db field: entrygrade (integer, default=100) */
    private $entrygrade          = 100;

    /** db field: entrypage (integer, default=0) */
    private $entrypage           = 0;

    /** db field: entrytext (string, default='') */
    private $entrytext           = '';

    /** db field: entryformat (integer, default=0) */
    private $entryformat         = 0;

    /** db field: entryoptions (integer, default=0) */
    private $entryoptions        = 0;

    /** db field: exitpage (integer, default=0) */
    private $exitpage            = 0;

    /** db field: exittext (string, default='') */
    private $exittext            = '';

    /** db field: exitformat (integer, default=0) */
    private $exitformat          = 0;

    /** db field: exitoptions (integer, default=0) */
    private $exitoptions         = 0;

    /** db field: exitcm (integer, default=0) */
    private $exitcm              = 0;

    /** db field: exitgrade (integer, default=0) */
    private $exitgrade           = 0;

    /** db field: showpopup (integer, default=0) */
    private $showpopup           = 0;

    /** db field: popupoptions (string (255), default='') */
    private $popupoptions        = '';

    /** db field: timeopen (integer, default=0) */
    private $timeopen            = 0;

    /** db field: timeclose (integer, default=0) */
    private $timeclose           = 0;

    /** db field: timelimit (integer, default=0) */
    private $timelimit           = 0;

    /** db field: delay1 (integer, default=0) */
    private $delay1              = 0;

    /** db field: delay2 (integer, default=0) */
    private $delay2              = 0;

    /** db field: password (string (255), default='') */
    private $password            = '';

    /** db field: subnet (string (255), default='') */
    private $subnet              = '';

    /** db field: allowresume (integer, default=1) */
    private $allowresume         = 1;

    /** db field: allowfreeaccess (integer, default=0) */
    private $allowfreeaccess     = 0;

    /** db field: manualcompletion (integer, default=0) */
    private $manualcompletion    = 0;

    /** db field: attemptlimit (integer, default=0) */
    private $attemptlimit        = 0;

    /** db field: attemptgrademethod (integer, default=0) */
    private $attemptgrademethod  = 0;

    /** db field: grademethod (integer, default=1) */
    private $grademethod         = 1;

    /** db field: gradeignore (integer, default=0) */
    private $gradeignore         = 0;

    /** db field: gradelimit (integer, default=100) */
    private $gradelimit          = 100;

    /** db field: gradeweighting (integer, default=100) */
    private $gradeweighting      = 100;

    /** name of associated taskchain record */
    //private $name                = null;

    /**
     * get the "id" property
     *
     * @return primary key the current id $value
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * set the "id" property
     *
     * @param primary key the new id $value
     */
    public function set_id($value) {
        $this->id = $value;
    }

    /**
     * get the "parenttype" property
     *
     * @return integer the current parenttype $value
     */
    public function get_parenttype() {
        return $this->parenttype;
    }

    /**
     * set the "parenttype" property
     *
     * @param integer the new parenttype $value
     */
    public function set_parenttype($value) {
        $this->parenttype = $value;
    }

    /**
     * get the "parentid" property
     *
     * @return integer the current parentid $value
     */
    public function get_parentid() {
        return $this->parentid;
    }

    /**
     * set the "parentid" property
     *
     * @param integer the new parentid $value
     */
    public function set_parentid($value) {
        $this->parentid = $value;
    }

    /**
     * get the "entrycm" property
     *
     * @return integer the current entrycm $value
     */
    public function get_entrycm() {
        return $this->entrycm;
    }

    /**
     * set the "entrycm" property
     *
     * @param integer the new entrycm $value
     */
    public function set_entrycm($value) {
        $this->entrycm = $value;
    }

    /**
     * get the "entrygrade" property
     *
     * @return integer the current entrygrade $value
     */
    public function get_entrygrade() {
        return $this->entrygrade;
    }

    /**
     * set the "entrygrade" property
     *
     * @param integer the new entrygrade $value
     */
    public function set_entrygrade($value) {
        $this->entrygrade = $value;
    }

    /**
     * get the "entrypage" property
     *
     * @return integer the current entrypage $value
     */
    public function get_entrypage() {
        return $this->entrypage;
    }

    /**
     * set the "entrypage" property
     *
     * @param integer the new entrypage $value
     */
    public function set_entrypage($value) {
        $this->entrypage = $value;
    }

    /**
     * get the "entrytext" property
     *
     * @return string the current entrytext $value
     */
    public function get_entrytext() {
        return $this->entrytext;
    }

    /**
     * set the "entrytext" property
     *
     * @param string the new entrytext $value
     */
    public function set_entrytext($value) {
        $this->entrytext = $value;
    }

    /**
     * get the "entryformat" property
     *
     * @return integer the current entryformat $value
     */
    public function get_entryformat() {
        return $this->entryformat;
    }

    /**
     * set the "entryformat" property
     *
     * @param integer the new entryformat $value
     */
    public function set_entryformat($value) {
        $this->entryformat = $value;
    }

    /**
     * get the "entryoptions" property
     *
     * @return integer the current entryoptions $value
     */
    public function get_entryoptions() {
        return $this->entryoptions;
    }

    /**
     * set the "entryoptions" property
     *
     * @param integer the new entryoptions $value
     */
    public function set_entryoptions($value) {
        $this->entryoptions = $value;
    }

    /**
     * get the "exitpage" property
     *
     * @return integer the current exitpage $value
     */
    public function get_exitpage() {
        return $this->exitpage;
    }

    /**
     * set the "exitpage" property
     *
     * @param integer the new exitpage $value
     */
    public function set_exitpage($value) {
        $this->exitpage = $value;
    }

    /**
     * get the "exittext" property
     *
     * @return string the current exittext $value
     */
    public function get_exittext() {
        return $this->exittext;
    }

    /**
     * set the "exittext" property
     *
     * @param string the new exittext $value
     */
    public function set_exittext($value) {
        $this->exittext = $value;
    }

    /**
     * get the "exitformat" property
     *
     * @return integer the current exitformat $value
     */
    public function get_exitformat() {
        return $this->exitformat;
    }

    /**
     * set the "exitformat" property
     *
     * @param integer the new exitformat $value
     */
    public function set_exitformat($value) {
        $this->exitformat = $value;
    }

    /**
     * get the "exitoptions" property
     *
     * @return integer the current exitoptions $value
     */
    public function get_exitoptions() {
        return $this->exitoptions;
    }

    /**
     * set the "exitoptions" property
     *
     * @param integer the new exitoptions $value
     */
    public function set_exitoptions($value) {
        $this->exitoptions = $value;
    }

    /**
     * get the "exitcm" property
     *
     * @return integer the current exitcm $value
     */
    public function get_exitcm() {
        return $this->exitcm;
    }

    /**
     * set the "exitcm" property
     *
     * @param integer the new exitcm $value
     */
    public function set_exitcm($value) {
        $this->exitcm = $value;
    }

    /**
     * get the "exitgrade" property
     *
     * @return integer the current exitgrade $value
     */
    public function get_exitgrade() {
        return $this->exitgrade;
    }

    /**
     * set the "exitgrade" property
     *
     * @param integer the new exitgrade $value
     */
    public function set_exitgrade($value) {
        $this->exitgrade = $value;
    }

    /**
     * get the "showpopup" property
     *
     * @return integer the current showpopup $value
     */
    public function get_showpopup() {
        return $this->showpopup;
    }

    /**
     * set the "showpopup" property
     *
     * @param integer the new showpopup $value
     */
    public function set_showpopup($value) {
        $this->showpopup = $value;
    }

    /**
     * get the "popupoptions" property
     *
     * @return string (255) the current popupoptions $value
     */
    public function get_popupoptions() {
        return $this->popupoptions;
    }

    /**
     * set the "popupoptions" property
     *
     * @param string (255) the new popupoptions $value
     */
    public function set_popupoptions($value) {
        $this->popupoptions = $value;
    }

    /**
     * get the "timeopen" property
     *
     * @return integer the current timeopen $value
     */
    public function get_timeopen() {
        return $this->timeopen;
    }

    /**
     * set the "timeopen" property
     *
     * @param integer the new timeopen $value
     */
    public function set_timeopen($value) {
        $this->timeopen = $value;
    }

    /**
     * get the "timeclose" property
     *
     * @return integer the current timeclose $value
     */
    public function get_timeclose() {
        return $this->timeclose;
    }

    /**
     * set the "timeclose" property
     *
     * @param integer the new timeclose $value
     */
    public function set_timeclose($value) {
        $this->timeclose = $value;
    }

    /**
     * get the "timelimit" property
     *
     * @return integer the current timelimit $value
     */
    public function get_timelimit() {
        return $this->timelimit;
    }

    /**
     * set the "timelimit" property
     *
     * @param integer the new timelimit $value
     */
    public function set_timelimit($value) {
        $this->timelimit = $value;
    }

    /**
     * get the "delay1" property
     *
     * @return integer the current delay1 $value
     */
    public function get_delay1() {
        return $this->delay1;
    }

    /**
     * set the "delay1" property
     *
     * @param integer the new delay1 $value
     */
    public function set_delay1($value) {
        $this->delay1 = $value;
    }

    /**
     * get the "delay2" property
     *
     * @return integer the current delay2 $value
     */
    public function get_delay2() {
        return $this->delay2;
    }

    /**
     * set the "delay2" property
     *
     * @param integer the new delay2 $value
     */
    public function set_delay2($value) {
        $this->delay2 = $value;
    }

    /**
     * get the "password" property
     *
     * @return string (255) the current password $value
     */
    public function get_password() {
        return $this->password;
    }

    /**
     * set the "password" property
     *
     * @param string (255) the new password $value
     */
    public function set_password($value) {
        $this->password = $value;
    }

    /**
     * get the "subnet" property
     *
     * @return string (255) the current subnet $value
     */
    public function get_subnet() {
        return $this->subnet;
    }

    /**
     * set the "subnet" property
     *
     * @param string (255) the new subnet $value
     */
    public function set_subnet($value) {
        $this->subnet = $value;
    }

    /**
     * get the "allowresume" property
     *
     * @return integer the current allowresume $value
     */
    public function get_allowresume() {
        return $this->allowresume;
    }

    /**
     * set the "allowresume" property
     *
     * @param integer the new allowresume $value
     */
    public function set_allowresume($value) {
        $this->allowresume = $value;
    }

    /**
     * get the "allowfreeaccess" property
     *
     * @return integer the current allowfreeaccess $value
     */
    public function get_allowfreeaccess() {
        return $this->allowfreeaccess;
    }

    /**
     * set the "allowfreeaccess" property
     *
     * @param integer the new allowfreeaccess $value
     */
    public function set_allowfreeaccess($value) {
        $this->allowfreeaccess = $value;
    }

    /**
     * get the "manualcompletion" property
     *
     * @return integer the current manualcompletion $value
     */
    public function get_manualcompletion() {
        return $this->manualcompletion;
    }

    /**
     * set the "manualcompletion" property
     *
     * @param integer the new manualcompletion $value
     */
    public function set_manualcompletion($value) {
        $this->manualcompletion = $value;
    }

    /**
     * get the "attemptlimit" property
     *
     * @return integer the current attemptlimit $value
     */
    public function get_attemptlimit() {
        return $this->attemptlimit;
    }

    /**
     * set the "attemptlimit" property
     *
     * @param integer the new attemptlimit $value
     */
    public function set_attemptlimit($value) {
        $this->attemptlimit = $value;
    }

    /**
     * get the "attemptgrademethod" property
     *
     * @return integer the current attemptgrademethod $value
     */
    public function get_attemptgrademethod() {
        return $this->attemptgrademethod;
    }

    /**
     * set the "attemptgrademethod" property
     *
     * @param integer the new attemptgrademethod $value
     */
    public function set_attemptgrademethod($value) {
        $this->attemptgrademethod = $value;
    }

    /**
     * get the "grademethod" property
     *
     * @return integer the current grademethod $value
     */
    public function get_grademethod() {
        return $this->grademethod;
    }

    /**
     * set the "grademethod" property
     *
     * @param integer the new grademethod $value
     */
    public function set_grademethod($value) {
        $this->grademethod = $value;
    }

    /**
     * get the "gradeignore" property
     *
     * @return integer the current gradeignore $value
     */
    public function get_gradeignore() {
        return $this->gradeignore;
    }

    /**
     * set the "gradeignore" property
     *
     * @param integer the new gradeignore $value
     */
    public function set_gradeignore($value) {
        $this->gradeignore = $value;
    }

    /**
     * get the "gradelimit" property
     *
     * @return integer the current gradelimit $value
     */
    public function get_gradelimit() {
        return $this->gradelimit;
    }

    /**
     * set the "gradelimit" property
     *
     * @param integer the new gradelimit $value
     */
    public function set_gradelimit($value) {
        $this->gradelimit = $value;
    }

    /**
     * get the "gradeweighting" property
     *
     * @return integer the current gradeweighting $value
     */
    public function get_gradeweighting() {
        return $this->gradeweighting;
    }

    /**
     * set the "gradeweighting" property
     *
     * @param integer the new gradeweighting $value
     */
    public function set_gradeweighting($value) {
        $this->gradeweighting = $value;
    }

    /**
     * get the grade pass value for the current grade item
     *
     * @return integer the current gradepass $value
     */
    public function get_gradepass() {
        $this->get_gradeitem_field('gradepass');
    }

    /**
     * update the grade pass value for the current grade item
     *
     * @param integer the new gradepass $value
     */
    public function set_gradepass($value) {
        $this->set_gradeitem_field('gradepass', $value);
    }

    /**
     * get the grade category id for the current grade item
     *
     * @return integer the current gradecategory $value
     */
    public function get_gradecategory() {
        $this->get_gradeitem_field('categoryid');
    }

    /**
     * update the grade category id for the current grade item
     *
     * @param integer the new gradecategory $value
     */
    public function set_gradecategory($value) {
        $this->set_gradeitem_field('categoryid', $value);
    }

    /**
     * get the value of a grade item field from the Moodle $DB
     *
     * @uses $DB
     * @param string $fieldname
     * @return mixed $value
     */
    protected function get_gradeitem_field($fieldname) {
        global $DB;
        $params = array('itemtype' => 'mod',
                        'itemmodule' => 'taskchain',
                        'iteminstance' => $this->parentid);
        return $DB->get_field('grade_items', $fieldname, $params);
    }

    /**
     * update a grade item field in the Moodle $DB
     *
     * @uses $DB
     * @param string $fieldname
     * @param mixed the new grade item $value
     * @return void, but may update $value in $DB
     */
    protected function set_gradeitem_field($fieldname, $value) {
        global $DB;
        $params = array('itemtype' => 'mod',
                        'itemmodule' => 'taskchain',
                        'iteminstance' => $this->parentid);
        $DB->set_field('grade_items', $fieldname, $value, $params);
    }
}

