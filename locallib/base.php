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
 * mod/taskchain/locallib/base.php
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
require_once($CFG->dirroot.'/mod/taskchain/locallib/available.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/can.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/create.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/get.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/regrade.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/require.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/url.php');

/** PHP classes to handle TaskChain DB records */
require_once($CFG->dirroot.'/mod/taskchain/locallib/taskchain.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/taskchain_cache.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/taskchain_chain_attempt.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/taskchain_chain_grade.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/taskchain_chain.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/taskchain_condition.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/taskchain_detail.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/taskchain_question.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/taskchain_response.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/taskchain_string.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/taskchain_task_attempt.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/taskchain_task_score.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib/taskchain_task.php');

/**
 * taskchain_base
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_base {

    /**#@+
     * internal codes for yes/no fields
     *
     * @var integer
     */
    const NO                          = 0;
    const YES                         = 1;
    /**#@-*/

    /**#@+
     * internal codes to indicate whether the parent
     * record of a chain is an TaskChain activity or
     * a TaskChain block
     *
     * @var integer
     */
    const PARENTTYPE_ACTIVITY         = 0;
    const PARENTTYPE_BLOCK            = 1;
    /**#@-*/

    /**#@+
     * internal codes to indicate what text is to be used
     * for the name and introduction of a TaskChain instance
     *
     * @var integer
     */
    const TEXTSOURCE_FILE             = 0; // was TEXTSOURCE_TASK
    const TEXTSOURCE_FILENAME         = 1;
    const TEXTSOURCE_FILEPATH         = 2;
    const TEXTSOURCE_SPECIFIC         = 3;
    /**#@-*/

    /**#@+
     * database codes to indicate what navigation aids are used
     * when the task apears in the browser
     *
     * @var integer
     */
    const NAVIGATION_NONE             = 0; // was 6
    const NAVIGATION_MOODLE           = 1; // was NAVIGATION_BAR
    const NAVIGATION_FRAME            = 2;
    const NAVIGATION_EMBED            = 3; // was NAVIGATION_IFRAME
    const NAVIGATION_ORIGINAL         = 4;
    const NAVIGATION_TOPBAR           = 5; // was NAVIGATION_GIVEUP but that was replaced by stopbutton
    /**#@-*/

    /**#@+
     * database codes to indicate the grademethod and attemptgrademethod for a TaskChain instance
     *
     * @var integer
     */
    const GRADEMETHOD_TOTAL           = 0;
    const GRADEMETHOD_HIGHEST         = 1;
    const GRADEMETHOD_AVERAGE         = 2;
    const GRADEMETHOD_FIRST           = 3;
    const GRADEMETHOD_LAST            = 4;
    const GRADEMETHOD_LASTCOMPLETED   = 5;
    const GRADEMETHOD_LASTTIMEDOUT    = 6;
    const GRADEMETHOD_LASTABANDONED   = 7;
    /**#@-*/

    /**#@+
     * database codes to indicate the source/config location for a TaskChain instance
     *
     * @var integer
     */
    const LOCATION_COURSEFILES        = 0;
    const LOCATION_SITEFILES          = 1;
    const LOCATION_WWW                = 2;
    /**#@-*/

    /**#@+
     * bit-masks used to extract bits from the taskchain "title" setting
     *
     * @var integer
     */
    const TITLE_SOURCE                = 0x03; // 1st - 2nd bits
    const TITLE_CHAINNAME             = 0x04; // 3rd bit
    const TITLE_SORTORDER             = 0x08; // 4th bit
    /**#@-*/

    /**#@+
     * database codes for the following time fields
     *  - timelimit : the maximum length of one attempt
     *  - delay3    : the delay after end of task before control returns to Moodle
     *
     * @var integer
     */
    const TIME_SPECIFIC               = 0;
    const TIME_TEMPLATE               = -1;
    const TIME_AFTEROK                = -2;
    const TIME_DISABLE                = -3;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const ALLOWRESUME_NO              = 0;
    const ALLOWRESUME_YES             = 1;
    const ALLOWRESUME_FORCE           = 2;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const CONTINUE_RESUMETASK         = 1;
    const CONTINUE_RESTARTTASK        = 2;
    const CONTINUE_RESTARTCHAIN       = 3;
    const CONTINUE_ABANDONCHAIN       = 4;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const STATUS_INPROGRESS           = 1;
    const STATUS_TIMEDOUT             = 2;
    const STATUS_ABANDONED            = 3;
    const STATUS_COMPLETED            = 4;
    const STATUS_PAUSED               = 5;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const FEEDBACK_NONE               = 0;
    const FEEDBACK_WEBPAGE            = 1;
    const FEEDBACK_FORMMAIL           = 2;
    const FEEDBACK_MOODLEFORUM        = 3;
    const FEEDBACK_MOODLEMESSAGING    = 4;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const STOPBUTTON_NONE             = 0;
    const STOPBUTTON_LANGPACK         = 1;
    const STOPBUTTON_SPECIFIC         = 2;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const ACTIVITY_NONE               = 0;
    const ACTIVITY_COURSE_ANY         = -1;
    const ACTIVITY_SECTION_ANY        = -2;
    const ACTIVITY_COURSE_GRADED      = -3;
    const ACTIVITY_SECTION_GRADED     = -4;
    const ACTIVITY_COURSE_TASKCHAIN   = -5;
    const ACTIVITY_SECTION_TASKCHAIN  = -6;
    /**#@-*/

    /**#@+
     * possible values for "addtype" field on the main form.
     * This field is not stored in the database, but specifies
     * what items from the source folder are to be added as tasks
     *
     * ADDTYPE_AUTO: if main file is selected, then it is added
     * as TASKCHAIN or CHAINFILE; otherwise we add as CHAINFOLDER
     */
    const ADDTYPE_AUTO                = 0;
    const ADDTYPE_TASKFILE            = 1;
    const ADDTYPE_TASKCHAIN           = 2;
    const ADDTYPE_CHAINFILE           = 3;
    const ADDTYPE_CHAINFOLDER         = 4;
    const ADDTYPE_CHAINFOLDERS        = 5;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const ENTRYOPTIONS_TITLE          = 0x01;
    const ENTRYOPTIONS_GRADING        = 0x02;
    const ENTRYOPTIONS_DATES          = 0x04;
    const ENTRYOPTIONS_ATTEMPTS       = 0x08;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const EXITOPTIONS_TITLE           = 0x01;
    const EXITOPTIONS_ENCOURAGEMENT   = 0x02;
    const EXITOPTIONS_ATTEMPTSCORE    = 0x04;
    const EXITOPTIONS_TASKCHAINGRADE  = 0x08;
    const EXITOPTIONS_RETRY           = 0x10;
    const EXITOPTIONS_INDEX           = 0x20;
    const EXITOPTIONS_COURSE          = 0x40;
    const EXITOPTIONS_GRADES          = 0x80;
    /**#@-*/

    /**#@+
     *
     * three sets of 6 bits define the times at which a task may be reviewed
     * e.g. 0x3f = 0011 1111 (i.e. right most 6 bits)
     *
     * @var integer
     */
    const REVIEW_DURINGATTEMPT        = 0x0003f; // 1st set of 6 bits : during attempt
    const REVIEW_AFTERATTEMPT         = 0x00fc0; // 2nd set of 6 bits : after attempt (but before task closes)
    const REVIEW_AFTERCLOSE           = 0x3f000; // 3rd set of 6 bits : after the task closes
    /**#@-*/

    /**#@+
     * within each group of 6 bits we determine what should be shown
     * e.g. 0x1041 = 00-0001 0000-01 00-0001 (i.e. 3 sets of 6 bits)
     */
    const REVIEW_RESPONSES            = 0x1041; // 1*0x1041 : 1st bit of each 6-bit set : Show student responses
    const REVIEW_ANSWERS              = 0x2082; // 2*0x1041 : 2nd bit of each 6-bit set : Show correct answers
    const REVIEW_SCORES               = 0x4104; // 3*0x1041 : 3rd bit of each 6-bit set : Show scores
    const REVIEW_FEEDBACK             = 0x8208; // 4*0x1041 : 4th bit of each 6-bit set : Show feedback
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const MIN                         = -1;
    const MAX                         = 1;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const EQUALWEIGHTING              = -1;

    const CONDITIONTYPE_PRE           = 1;
    const CONDITIONTYPE_POST          = 2;

    const CONDITIONTASKID_SAME        = -1;
    const CONDITIONTASKID_PREVIOUS    = -2;
    const CONDITIONTASKID_NEXT1       = -3; // was NEXT
    const CONDITIONTASKID_NEXT2       = -4; // was SKIP
    const CONDITIONTASKID_NEXT3       = -5;
    const CONDITIONTASKID_NEXT4       = -6;
    const CONDITIONTASKID_NEXT5       = -7;
    const CONDITIONTASKID_UNSEEN      = -10; // was -5
    const CONDITIONTASKID_UNANSWERED  = -11; // was -6
    const CONDITIONTASKID_INCORRECT   = -12; // was -7
    const CONDITIONTASKID_RANDOM      = -13; // was -8
    const CONDITIONTASKID_MENUNEXT    = -20; // was -10 was -11
    const CONDITIONTASKID_MENUNEXTONE = -21; // was -11
    const CONDITIONTASKID_MENUALL     = -22; // was -12 was -10
    const CONDITIONTASKID_MENUALLONE  = -23; // was -13
    const CONDITIONTASKID_ENDOFCHAIN  = -99;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const ATTEMPTTYPE_ANY             = 0;
    const ATTEMPTTYPE_RECENT          = 1;
    const ATTEMPTTYPE_CONSECUTIVE     = 2;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const COLUMNS_EDITTASKS_ALL       = '';
    const COLUMNS_EDITTASKS_DEFAULT   = '';
    const COLUMNS_EDITTASKS_MINIMUM   = '';
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const COLUMNS_ALL                 = 1;
    const COLUMNS_DEFAULT             = 2;
    const COLUMNS_MINIMUM             = 3;
    const COLUMNS_CUSTOM              = 4;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const ADDTASKS_ATSTART            = -1;
    const ADDTASKS_ATEND              = -2;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const SELECTTASKS_THISTASKCHAIN   = 1;
    const SELECTTASKS_ALLMYTASKCHAINS = 2;
    const SELECTTASKS_ALLMYCOURSES    = 3;
    const SELECTTASKS_WHOLESITE       = 4;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const TASKSACTION_DELETE          = -1;
    const TASKSACTION_DEFAULTS        = -2;
    const TASKSACTION_MOVETOSTART     = -3;
    /**#@-*/

    /**#@+
     *
     *
     * @var integer
     */
    const DELAY3_SPECIFIC             = 0;
    const DELAY3_TEMPLATE             = -1;
    const DELAY3_AFTEROK              = -2;
    const DELAY3_DISABLE              = -3;
    /**#@-*/

    /** @var integer unumber/tnumber value to force creation of new attempt */
    const FORCE_NEW_ATTEMPT           = -1;

    /**#@+
     * values for $CFG->taskchain_bodystyles
     *
     * @var integer
     */
    const BODYSTYLES_BACKGROUND       = 0x01;
    const BODYSTYLES_COLOR            = 0x02;
    const BODYSTYLES_FONT             = 0x04;
    const BODYSTYLES_MARGIN           = 0x08;
    /**#@-*/

    /** reference back to global $TC object */
    public $TC = null;

    /** debugging property to show methods in this class */
    protected $methods = null;

    /////////////////////////////////////////////////////////
    // "magic" methods
    /////////////////////////////////////////////////////////

    /**
     * __construct
     *
     * @uses $CFG
     * @param object $dbrecord (optional, default=null) db record associated with this object
     * @param array $objects (optional, default=null) array ($name => $object) of associated objects
     * @return void, if possible, properties will be assigned from $dbrecord and $objects
     */
    public function __construct($dbrecord=null, $objects=null) {
        if ($dbrecord) {
            foreach (get_object_vars($dbrecord) as $name => $value) {
                $this->__set($name, $value);
            }
        }
        if ($objects) {
            foreach ($objects as $name => $object) {
                $this->link_to_object($name, $object);
            }
        }
        if (debugging('', DEBUG_DEVELOPER)) {
            $this->get_methods(); // this ensures the methods are printed by print_object()
        }
    }

    /**
     * __get
     *
     * @param xxx $name
     * @return xxx
     * @todo Finish documenting this function
     */
    public function __get($name) {
        $method = 'get_'.$name;
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        $this->property_error('get', $name, $method);
    }

    /**
     * __set
     *
     * @param xxx $name
     * @param xxx $value
     * @todo Finish documenting this function
     */
    public function __set($name, $value) {
        $method = 'set_'.$name;
        if (method_exists($this, $method)) {
            $this->$method($value);
            return true;
        }
        $this->property_error('set', $name, $method);
    }

    /////////////////////////////////////////////////////////
    // protected API
    /////////////////////////////////////////////////////////

    /**
     * report an error from a "magic" method
     *
     * @param string $type of magic function error ("get" or "set")
     * @param string $property name of a private or undefined property
     * @param string $method name of a method used to access the $property
     */
    protected function property_error($type, $property, $method) {
        if (property_exists($this, $property)) {
            $hint = 'error_'.$type.'privateproperty';
        } else {
            $hint = 'error_'.$type.'unknownproperty';
        }
        $a = (object)array('class'    => get_class($this),
                           'property' => $property,
                           'method'   => $method.'()');
        $hint = get_string($hint, 'mod_taskchain', $a);
        throw new coding_exception($hint); // $debuginfo
    }

    /////////////////////////////////////////////////////////
    // public API
    /////////////////////////////////////////////////////////

    /**
     * Add a link to an associated taskchain object
     *
     * @param string $name
     * @param object $object
     * @return void, will set the $name property as a reference to the $object
     */
    public function link_to_object($name, &$object) {
        $this->$name = $object;
    }

    /**
     * get_methods
     *
     * @return array, the methods defined on $this object
     */
    public function get_methods() {
        if ($this->methods===null) {
            $this->methods = get_class_methods($this);
            sort($this->methods);
        }
        return $this->methods;
    }

    /**
     * to_stdclass
     *
     * @return stdclass
     * @todo Finish documenting this function
     */
    public function to_stdclass() {
        $stdclass = new stdclass();

        // extract just the "get" methods for this object
        $methods = preg_grep('/^get_[a-z0-9]+$/', $this->get_methods());

        foreach ($methods as $method) {
            $name = substr($method, 4);
            $value = $this->$method();
            if (is_string($value) || is_numeric($value)) {
                $stdclass->$name = $value;
            }
        }

        return $stdclass;
    }
}
