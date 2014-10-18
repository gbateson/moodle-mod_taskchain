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
 * mod/taskchain/locallib/taskchain_task.php
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
 * taskchain_task
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_task extends taskchain_base {

    /** db field: id (primary key, default=null) */
    private $id                  = null;

    /** db field: chainid (integer, default=0) */
    private $chainid             = 0;

    /** db field: name (string (255), default='') */
    private $name                = '';

    /** db field: sourcefile (string (255), default='') */
    private $sourcefile          = '';

    /** db field: sourcetype (string (255), default='') */
    private $sourcetype          = '';

    /** db field: sourcelocation (integer, default=0) */
    private $sourcelocation      = 0;

    /** db field: configfile (string (255), default='') */
    private $configfile          = '';

    /** db field: configlocation (integer, default=0) */
    private $configlocation      = 0;

    /** db field: outputformat (string (255), default='') */
    private $outputformat        = '';

    /** db field: navigation (integer, default=0) */
    private $navigation          = 0;

    /** db field: title (integer, default=3) */
    private $title               = 3;

    /** db field: stopbutton (integer, default=0) */
    private $stopbutton          = 0;

    /** db field: stoptext (string (255), default='') */
    private $stoptext            = '';

    /** db field: allowpaste (integer, default=0) */
    private $allowpaste          = 0;

    /** db field: usefilters (integer, default=0) */
    private $usefilters          = 0;

    /** db field: useglossary (integer, default=0) */
    private $useglossary         = 0;

    /** db field: usemediafilter (string (255), default='') */
    private $usemediafilter      = '';

    /** db field: studentfeedback (integer, default=0) */
    private $studentfeedback     = 0;

    /** db field: studentfeedbackurl (string (255), default='') */
    private $studentfeedbackurl  = '';

    /** db field: timeopen (integer, default=0) */
    private $timeopen            = 0;

    /** db field: timeclose (integer, default=0) */
    private $timeclose           = 0;

    /** db field: timelimit (integer, default=-1) */
    private $timelimit           = -1;

    /** db field: delay1 (integer, default=0) */
    private $delay1              = 0;

    /** db field: delay2 (integer, default=0) */
    private $delay2              = 0;

    /** db field: delay3 (integer, default=2) */
    private $delay3              = 2;

    /** db field: password (string (255), default='') */
    private $password            = '';

    /** db field: subnet (string (255), default='') */
    private $subnet              = '';

    /** db field: allowresume (integer, default=0) */
    private $allowresume         = 0;

    /** db field: reviewoptions (integer, default=0) */
    private $reviewoptions       = 0;

    /** db field: attemptlimit (integer, default=0) */
    private $attemptlimit        = 0;

    /** db field: scoremethod (integer, default=1) */
    private $scoremethod         = 1;

    /** db field: scoreignore (integer, default=0) */
    private $scoreignore         = 0;

    /** db field: scorelimit (integer, default=100) */
    private $scorelimit          = 100;

    /** db field: scoreweighting (integer, default=-1) */
    private $scoreweighting      = -1;

    /** db field: sortorder (integer, default=0) */
    private $sortorder           = 0;

    /** db field: clickreporting (integer, default=0) */
    private $clickreporting      = 0;

    /** db field: discarddetails (integer, default=0) */
    private $discarddetails      = 0;

    /** object representing the task source file */
    public $source            = null;

    /** object representing the task config file */
    public $config            = null;

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
     * get the "chainid" property
     *
     * @return integer the current chainid $value
     */
    public function get_chainid() {
        return $this->chainid;
    }

    /**
     * set the "chainid" property
     *
     * @param integer the new chainid $value
     */
    public function set_chainid($value) {
        $this->chainid = $value;
    }

    /**
     * get the "name" property
     *
     * @return string (255) the current name $value
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * set the "name" property
     *
     * @param string (255) the new name $value
     */
    public function set_name($value) {
        $this->name = $value;
    }

    /**
     * get the "sourcefile" property
     *
     * @return string (255) the current sourcefile $value
     */
    public function get_sourcefile() {
        return $this->sourcefile;
    }

    /**
     * set the "sourcefile" property
     *
     * @param string (255) the new sourcefile $value
     */
    public function set_sourcefile($value) {
        $this->sourcefile = $value;
    }

    /**
     * get the "sourcetype" property
     *
     * @return string (255) the current sourcetype $value
     */
    public function get_sourcetype() {
        return $this->sourcetype;
    }

    /**
     * set the "sourcetype" property
     *
     * @param string (255) the new sourcetype $value
     */
    public function set_sourcetype($value) {
        $this->sourcetype = $value;
    }

    /**
     * get the "sourcelocation" property
     *
     * @return integer the current sourcelocation $value
     */
    public function get_sourcelocation() {
        return $this->sourcelocation;
    }

    /**
     * set the "sourcelocation" property
     *
     * @param integer the new sourcelocation $value
     */
    public function set_sourcelocation($value) {
        $this->sourcelocation = $value;
    }

    /**
     * get the "configfile" property
     *
     * @return string (255) the current configfile $value
     */
    public function get_configfile() {
        return $this->configfile;
    }

    /**
     * set the "configfile" property
     *
     * @param string (255) the new configfile $value
     */
    public function set_configfile($value) {
        $this->configfile = $value;
    }

    /**
     * get the "configlocation" property
     *
     * @return integer the current configlocation $value
     */
    public function get_configlocation() {
        return $this->configlocation;
    }

    /**
     * set the "configlocation" property
     *
     * @param integer the new configlocation $value
     */
    public function set_configlocation($value) {
        $this->configlocation = $value;
    }

    /**
     * get the "outputformat" property
     *
     * If the outputformat is not specified, the "best" outupt format is returned     *
     * which is the one with the same name as "sourcetype" for this TaskChain
     *
     * @return string (255) the current outputformat $value
     */
    public function get_outputformat() {
        if (empty($this->outputformat)) {
            $source = $this->get_source();
            return $source->get_best_outputformat();
        } else {
            return clean_param($this->outputformat, PARAM_SAFEDIR);
        }
    }

    /**
     * set the "outputformat" property
     *
     * @param string (255) the new outputformat $value
     */
    public function set_outputformat($value) {
        $this->outputformat = $value;
    }

    /**
     * get the "navigation" property
     *
     * @return integer the current navigation $value
     */
    public function get_navigation() {
        return $this->navigation;
    }

    /**
     * set the "navigation" property
     *
     * @param integer the new navigation $value
     */
    public function set_navigation($value) {
        $this->navigation = $value;
    }

    /**
     * get the "title" property
     *
     * @return integer the current title $value
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * set the "title" property
     *
     * @param integer the new title $value
     */
    public function set_title($value) {
        $this->title = $value;
    }

    /**
     * get the "stopbutton" property
     *
     * @return integer the current stopbutton $value
     */
    public function get_stopbutton() {
        return $this->stopbutton;
    }

    /**
     * set the "stopbutton" property
     *
     * @param integer the new stopbutton $value
     */
    public function set_stopbutton($value) {
        $this->stopbutton = $value;
    }

    /**
     * get the "stoptext" property
     *
     * @return string (255) the current stoptext $value
     */
    public function get_stoptext() {
        return $this->stoptext;
    }

    /**
     * set the "stoptext" property
     *
     * @param string (255) the new stoptext $value
     */
    public function set_stoptext($value) {
        $this->stoptext = $value;
    }

    /**
     * get the "allowpaste" property
     *
     * @return integer the current allowpaste $value
     */
    public function get_allowpaste() {
        return $this->allowpaste;
    }

    /**
     * set the "allowpaste" property
     *
     * @param integer the new allowpaste $value
     */
    public function set_allowpaste($value) {
        $this->allowpaste = $value;
    }

    /**
     * get the "usefilters" property
     *
     * @return integer the current usefilters $value
     */
    public function get_usefilters() {
        return $this->usefilters;
    }

    /**
     * set the "usefilters" property
     *
     * @param integer the new usefilters $value
     */
    public function set_usefilters($value) {
        $this->usefilters = $value;
    }

    /**
     * get the "useglossary" property
     *
     * @return integer the current useglossary $value
     */
    public function get_useglossary() {
        return $this->useglossary;
    }

    /**
     * set the "useglossary" property
     *
     * @param integer the new useglossary $value
     */
    public function set_useglossary($value) {
        $this->useglossary = $value;
    }

    /**
     * get the "usemediafilter" property
     *
     * @return string (255) the current usemediafilter $value
     */
    public function get_usemediafilter() {
        switch ($this->usemediafilter) {
            case '0': return '';
            case '1': return 'moodle';
            default : return $this->usemediafilter;
        }
    }

    /**
     * set the "usemediafilter" property
     *
     * @param string (255) the new usemediafilter $value
     */
    public function set_usemediafilter($value) {
        $this->usemediafilter = $value;
    }

    /**
     * get the "studentfeedback" property
     *
     * @return integer the current studentfeedback $value
     */
    public function get_studentfeedback() {
        return $this->studentfeedback;
    }

    /**
     * set the "studentfeedback" property
     *
     * @param integer the new studentfeedback $value
     */
    public function set_studentfeedback($value) {
        $this->studentfeedback = $value;
    }

    /**
     * get the "studentfeedbackurl" property
     *
     * @return string (255) the current studentfeedbackurl $value
     */
    public function get_studentfeedbackurl() {
        return $this->studentfeedbackurl;
    }

    /**
     * set the "studentfeedbackurl" property
     *
     * @param string (255) the new studentfeedbackurl $value
     */
    public function set_studentfeedbackurl($value) {
        $this->studentfeedbackurl = $value;
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
     * get the "delay3" property
     *
     * @return integer the current delay3 $value
     */
    public function get_delay3() {
        return $this->delay3;
    }

    /**
     * set the "delay3" property
     *
     * @param integer the new delay3 $value
     */
    public function set_delay3($value) {
        $this->delay3 = $value;
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
     * get the "reviewoptions" property
     *
     * @return integer the current reviewoptions $value
     */
    public function get_reviewoptions() {
        return $this->reviewoptions;
    }

    /**
     * set the "reviewoptions" property
     *
     * @param integer the new reviewoptions $value
     */
    public function set_reviewoptions($value) {
        $this->reviewoptions = $value;
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
     * get the "scoremethod" property
     *
     * @return integer the current scoremethod $value
     */
    public function get_scoremethod() {
        return $this->scoremethod;
    }

    /**
     * set the "scoremethod" property
     *
     * @param integer the new scoremethod $value
     */
    public function set_scoremethod($value) {
        $this->scoremethod = $value;
    }

    /**
     * get the "scoreignore" property
     *
     * @return integer the current scoreignore $value
     */
    public function get_scoreignore() {
        return $this->scoreignore;
    }

    /**
     * set the "scoreignore" property
     *
     * @param integer the new scoreignore $value
     */
    public function set_scoreignore($value) {
        $this->scoreignore = $value;
    }

    /**
     * get the "scorelimit" property
     *
     * @return integer the current scorelimit $value
     */
    public function get_scorelimit() {
        return $this->scorelimit;
    }

    /**
     * set the "scorelimit" property
     *
     * @param integer the new scorelimit $value
     */
    public function set_scorelimit($value) {
        $this->scorelimit = $value;
    }

    /**
     * get the "scoreweighting" property
     *
     * @return integer the current scoreweighting $value
     */
    public function get_scoreweighting() {
        return $this->scoreweighting;
    }

    /**
     * set the "scoreweighting" property
     *
     * @param integer the new scoreweighting $value
     */
    public function set_scoreweighting($value) {
        $this->scoreweighting = $value;
    }

    /**
     * get the "sortorder" property
     *
     * @return integer the current sortorder $value
     */
    public function get_sortorder() {
        return $this->sortorder;
    }

    /**
     * set the "sortorder" property
     *
     * @param integer the new sortorder $value
     */
    public function set_sortorder($value) {
        $this->sortorder = $value;
    }

    /**
     * get the "clickreporting" property
     *
     * @return integer the current clickreporting $value
     */
    public function get_clickreporting() {
        return $this->clickreporting;
    }

    /**
     * set the "clickreporting" property
     *
     * @param integer the new clickreporting $value
     */
    public function set_clickreporting($value) {
        $this->clickreporting = $value;
    }

    /**
     * get the "discarddetails" property
     *
     * @return integer the current discarddetails $value
     */
    public function get_discarddetails() {
        return $this->discarddetails;
    }

    /**
     * set the "discarddetails" property
     *
     * @param integer the new discarddetails $value
     */
    public function set_discarddetails($value) {
        $this->discarddetails = $value;
    }

    /**
     * get_source
     *
     * @uses $CFG
     * @uses $DB
     * @return source object representing the source file for this TaskChain
     * @todo Finish documenting this function
     */
    public function get_source() {
        global $DB;
        if (empty($this->source)) {
            // get sourcetype e.g. hp_6_jcloze_xml
            $file = $this->get_file('source');
            if (! $sourcetype = clean_param($this->sourcetype, PARAM_SAFEDIR)) {
                throw new moodle_exception('missingsourcetype', 'taskchain');
            }
            // $classname is something like "taskchain_source_hp_6_jcloze_xml"
            $classname = 'taskchain_source_'.$sourcetype;
            $this->TC->load_class($classname, 'class.php');
            $this->source = new $classname($file, $this->TC);
            $this->source->config = $this->get_config();
        }
        return $this->source;
    }

    /**
     * get_config
     *
     * @uses $DB
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_config() {
        global $DB;
        if (is_null($this->config)) {
            if ($file = $this->get_file('config')) {
                $classname = 'taskchain_source';
                $this->TC->load_class($classname, 'class.php');
                $this->config = new $classname($file, $this->TC);
            } else {
                $this->config = false;
            }
        }
        return $this->config;
    }

    /**
     * Returns the stored_file object for this TaskChain's source/config file
     *
     * @param string $type "source" or "config"
     * @uses $CFG
     * @uses $DB
     * @return stored_file
     */
    public function get_file($type=null) {
        global $CFG, $DB;

        if (empty($type)) {
            return null;
        }

        $fs = get_file_storage();

        $typefile = $type.'file';
        $filearea = $type.'file';

        if (empty($this->$typefile) && $type=='config') {
            return false; // ignore empty config file
        }

        $filename = basename($this->$typefile);
        $filepath = dirname($this->$typefile);
        $contextid = $this->TC->coursemodule->context->id;
        //$contextid = $this->TC->course->context->id;

        // require leading and trailing slash on $filepath
        if (substr($filepath, 0, 1)=='/' && substr($filepath, -1)=='/') {
            // do nothing - $filepath is valid
        } else {
            // fix filepath - shouldn't happen !!
            // maybe leftover from a messy upgrade
            if ($filepath=='.' || $filepath=='') {
                $filepath = '/';
            } else {
                $filepath = '/'.ltrim($filepath, '/');
                $filepath = rtrim($filepath, '/').'/';
            }
            $this->$typefile = $filepath.$filename;
            $DB->set_field('taskchain_tasks', $typefile, $this->$typefile, array('id' => $this->id));
        }

        if ($file = $fs->get_file($contextid, 'mod_taskchain', $filearea, 0, $filepath, $filename)) {
            return $file;
        }

        // the source file is missing, probably this TaskChain
        // has recently been upgraded/imported from Moodle 1.9
        // so we are going to try to create the missing stored file

        $file_record = array(
            'contextid'=>$contextid, 'component'=>'mod_taskchain', 'filearea'=>$filearea,
            'sortorder'=>1, 'itemid'=>0, 'filepath'=>$filepath, 'filename'=>$filename
        );

        $coursecontextid  = $this->TC->course->context->id;
        $filehash = sha1('/'.$coursecontextid.'/course/legacy/0'.$filepath.$filename);

        if ($file = $fs->get_file_by_hash($filehash)) {
            // file exists in legacy course files
            if ($file = $fs->create_file_from_storedfile($file_record, $file)) {
                return $file;
            }
        }

        $oldfilepath = $CFG->dataroot.'/'.$this->TC->course->id.$filepath.$filename;
        if (file_exists($oldfilepath)) {
            // file exists on server's filesystem
            if ($file = $fs->create_file_from_pathname($file_record, $oldfilepath)) {
                return $file;
            }
        }

        // file not found - shouldn't happen !!
        return null;
        //  throw new moodle_exception($type.'filenotfound', 'taskchain', '', $this->$typefile);
    }
}

