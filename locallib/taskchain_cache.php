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
 * mod/taskchain/locallib/taskchain_cache.php
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
 * taskchain_cache
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_cache extends taskchain_base {

    /** db field: id (primary key, default=null) */
    private $id                  = null;

    /** db field: taskid (integer, default=0) */
    private $taskid              = 0;

    /** db field: slasharguments (string (1), default='') */
    private $slasharguments      = '';

    /** db field: taskchain_enableobfuscate (string (1), default='') */
    private $taskchain_enableobfuscate = '';

    /** db field: taskchain_enableswf (string (1), default='') */
    private $taskchain_enableswf = '';

    /** db field: name (string (255), default='') */
    private $name                = '';

    /** db field: sourcefile (string (255), default='') */
    private $sourcefile          = '';

    /** db field: sourcetype (string (255), default='') */
    private $sourcetype          = '';

    /** db field: sourcelocation (integer, default=0) */
    private $sourcelocation      = 0;

    /** db field: sourcelastmodified (string (255), default='') */
    private $sourcelastmodified  = '';

    /** db field: sourceetag (string (255), default='') */
    private $sourceetag          = '';

    /** db field: configfile (string (255), default='') */
    private $configfile          = '';

    /** db field: configlocation (integer, default=0) */
    private $configlocation      = 0;

    /** db field: configlastmodified (string (255), default='') */
    private $configlastmodified  = '';

    /** db field: configetag (string (255), default='') */
    private $configetag          = '';

    /** db field: navigation (integer, default=0) */
    private $navigation          = 0;

    /** db field: title (integer, default=0) */
    private $title               = 0;

    /** db field: stopbutton (integer, default=0) */
    private $stopbutton          = 0;

    /** db field: stoptext (string (255), default='') */
    private $stoptext            = '';

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

    /** db field: timelimit (integer, default=-1) */
    private $timelimit           = -1;

    /** db field: delay3 (integer, default=-1) */
    private $delay3              = -1;

    /** db field: clickreporting (integer, default=0) */
    private $clickreporting      = 0;

    /** db field: content (string, default='') */
    private $content             = '';

    /** db field: timemodified (integer, default=0) */
    private $timemodified        = 0;

    /** db field: md5key (string (32), default='') */
    private $md5key              = '';

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
     * get the "taskid" property
     *
     * @return integer the current taskid $value
     */
    public function get_taskid() {
        return $this->taskid;
    }

    /**
     * set the "taskid" property
     *
     * @param integer the new taskid $value
     */
    public function set_taskid($value) {
        $this->taskid = $value;
    }

    /**
     * get the "slasharguments" property
     *
     * @return string (1) the current slasharguments $value
     */
    public function get_slasharguments() {
        return $this->slasharguments;
    }

    /**
     * set the "slasharguments" property
     *
     * @param string (1) the new slasharguments $value
     */
    public function set_slasharguments($value) {
        $this->slasharguments = $value;
    }

    /**
     * get the "taskchain_enableobfuscate" property
     *
     * @return string (1) the current taskchain_enableobfuscate $value
     */
    public function get_taskchain_enableobfuscate() {
        return $this->taskchain_enableobfuscate;
    }

    /**
     * set the "taskchain_enableobfuscate" property
     *
     * @param string (1) the new taskchain_enableobfuscate $value
     */
    public function set_taskchain_enableobfuscate($value) {
        $this->taskchain_enableobfuscate = $value;
    }

    /**
     * get the "taskchain_enableswf" property
     *
     * @return string (1) the current taskchain_enableswf $value
     */
    public function get_taskchain_enableswf() {
        return $this->taskchain_enableswf;
    }

    /**
     * set the "taskchain_enableswf" property
     *
     * @param string (1) the new taskchain_enableswf $value
     */
    public function set_taskchain_enableswf($value) {
        $this->taskchain_enableswf = $value;
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
     * get the "sourcelastmodified" property
     *
     * @return string (255) the current sourcelastmodified $value
     */
    public function get_sourcelastmodified() {
        return $this->sourcelastmodified;
    }

    /**
     * set the "sourcelastmodified" property
     *
     * @param string (255) the new sourcelastmodified $value
     */
    public function set_sourcelastmodified($value) {
        $this->sourcelastmodified = $value;
    }

    /**
     * get the "sourceetag" property
     *
     * @return string (255) the current sourceetag $value
     */
    public function get_sourceetag() {
        return $this->sourceetag;
    }

    /**
     * set the "sourceetag" property
     *
     * @param string (255) the new sourceetag $value
     */
    public function set_sourceetag($value) {
        $this->sourceetag = $value;
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
     * get the "configlastmodified" property
     *
     * @return string (255) the current configlastmodified $value
     */
    public function get_configlastmodified() {
        return $this->configlastmodified;
    }

    /**
     * set the "configlastmodified" property
     *
     * @param string (255) the new configlastmodified $value
     */
    public function set_configlastmodified($value) {
        $this->configlastmodified = $value;
    }

    /**
     * get the "configetag" property
     *
     * @return string (255) the current configetag $value
     */
    public function get_configetag() {
        return $this->configetag;
    }

    /**
     * set the "configetag" property
     *
     * @param string (255) the new configetag $value
     */
    public function set_configetag($value) {
        $this->configetag = $value;
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
        return $this->usemediafilter;
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
     * get the "content" property
     *
     * @return string the current content $value
     */
    public function get_content() {
        return $this->content;
    }

    /**
     * set the "content" property
     *
     * @param string the new content $value
     */
    public function set_content($value) {
        $this->content = $value;
    }

    /**
     * get the "timemodified" property
     *
     * @return integer the current timemodified $value
     */
    public function get_timemodified() {
        return $this->timemodified;
    }

    /**
     * set the "timemodified" property
     *
     * @param integer the new timemodified $value
     */
    public function set_timemodified($value) {
        $this->timemodified = $value;
    }

    /**
     * get the "md5key" property
     *
     * @return string (32) the current md5key $value
     */
    public function get_md5key() {
        return $this->md5key;
    }

    /**
     * set the "md5key" property
     *
     * @param string (32) the new md5key $value
     */
    public function set_md5key($value) {
        $this->md5key = $value;
    }
}

