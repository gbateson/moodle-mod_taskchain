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
 * @package    mod_taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

// Prevent direct access to this script.
defined('MOODLE_INTERNAL') || die();

// Include required files.
require_once($CFG->dirroot . '/mod/taskchain/locallib/available.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/can.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/create.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/get.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/regrade.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/require.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/url.php');

/** PHP classes to handle TaskChain DB records */
require_once($CFG->dirroot . '/mod/taskchain/locallib/taskchain.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/taskchain_cache.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/taskchain_chain_attempt.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/taskchain_chain_grade.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/taskchain_chain.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/taskchain_condition.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/taskchain_detail.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/taskchain_question.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/taskchain_response.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/taskchain_string.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/taskchain_task_attempt.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/taskchain_task_score.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib/taskchain_task.php');

/**
 * taskchain_base
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod_taskchain
 */
class taskchain_base {
    /*
     * internal codes for yes/no fields
     */
    /** @var int */
    const NO                          = 0;
    /** @var int */
    const YES                         = 1;

    /*
     * internal codes to indicate whether the parent
     * record of a chain is an TaskChain activity or
     * a TaskChain block
     */
    /** @var int */
    const PARENTTYPE_ACTIVITY         = 0;
    /** @var int */
    const PARENTTYPE_BLOCK            = 1;

    /*
     * internal codes to indicate what text is to be used
     * for the name and introduction of a TaskChain instance
     */
    /** @var int */
    const TEXTSOURCE_FILE             = 0; // Was TEXTSOURCE_TASK.
    /** @var int */
    const TEXTSOURCE_FILENAME         = 1;
    /** @var int */
    const TEXTSOURCE_FILEPATH         = 2;
    /** @var int */
    const TEXTSOURCE_SPECIFIC         = 3;
    /** @var int */
    const TEXTSOURCE_TASKNAME         = 4; // Added 2017-03-18.

    /*
     * database codes to indicate what navigation aids are used
     * when the task apears in the browser
     */
    /** @var int */
    const NAVIGATION_NONE             = 0; // Was 6.
    /** @var int */
    const NAVIGATION_MOODLE           = 1; // Was NAVIGATION_BAR.
    /** @var int */
    const NAVIGATION_FRAME            = 2;
    /** @var int */
    const NAVIGATION_EMBED            = 3; // Was NAVIGATION_IFRAME.
    /** @var int */
    const NAVIGATION_ORIGINAL         = 4;
    /** @var int */
    const NAVIGATION_TOPBAR           = 5; // Was NAVIGATION_GIVEUP but that was replaced by stopbutton.

    /*
     * database codes to indicate the grademethod and attemptgrademethod for a TaskChain instance
     */
    /** @var int */
    const GRADEMETHOD_TOTAL           = 0;
    /** @var int */
    const GRADEMETHOD_HIGHEST         = 1;
    /** @var int */
    const GRADEMETHOD_AVERAGE         = 2;
    /** @var int */
    const GRADEMETHOD_FIRST           = 3;
    /** @var int */
    const GRADEMETHOD_LAST            = 4;
    /** @var int */
    const GRADEMETHOD_LASTCOMPLETED   = 5;
    /** @var int */
    const GRADEMETHOD_LASTTIMEDOUT    = 6;
    /** @var int */
    const GRADEMETHOD_LASTABANDONED   = 7;

    /*
     * database codes to indicate the source/config location for a TaskChain instance
     */
    /** @var int */
    const LOCATION_COURSEFILES        = 0;
    /** @var int */
    const LOCATION_SITEFILES          = 1;
    /** @var int */
    const LOCATION_WWW                = 2;

    /*
     * bit-masks used to extract bits from the task "title" setting
     */
    /** @var int */
    const TITLE_SOURCE                = 7; // 1st - 3rd bits
    /** @var int */
    const TITLE_CHAINNAME             = 8; // 4th bit
    /** @var int */
    const TITLE_SORTORDER             = 16; // 5th bit

    /*
     * database codes for the following time fields
     *  - timelimit : the maximum length of one attempt
     *  - delay3    : the delay after end of task before control returns to Moodle
     */
    /** @var int */
    const TIME_SPECIFIC               = 0;
    /** @var int */
    const TIME_TEMPLATE               = -1;
    /** @var int */
    const TIME_AFTEROK                = -2;
    /** @var int */
    const TIME_DISABLE                = -3;

    /** @var int */
    const ALLOWRESUME_NO              = 0;
    /** @var int */
    const ALLOWRESUME_YES             = 1;
    /** @var int */
    const ALLOWRESUME_FORCE           = 2;

    /** @var int */
    const CONTINUE_RESUMETASK         = 1;
    /** @var int */
    const CONTINUE_RESTARTTASK        = 2;
    /** @var int */
    const CONTINUE_RESTARTCHAIN       = 3;
    /** @var int */
    const CONTINUE_ABANDONCHAIN       = 4;

    /** @var int */
    const STATUS_INPROGRESS           = 1;
    /** @var int */
    const STATUS_TIMEDOUT             = 2;
    /** @var int */
    const STATUS_ABANDONED            = 3;
    /** @var int */
    const STATUS_COMPLETED            = 4;
    /** @var int */
    const STATUS_PENDING              = 5;

    /** @var int */
    const FEEDBACK_NONE               = 0;
    /** @var int */
    const FEEDBACK_WEBPAGE            = 1;
    /** @var int */
    const FEEDBACK_FORMMAIL           = 2;
    /** @var int */
    const FEEDBACK_MOODLEFORUM        = 3;
    /** @var int */
    const FEEDBACK_MOODLEMESSAGING    = 4;

    /** @var int */
    const STOPBUTTON_NONE             = 0;
    /** @var int */
    const STOPBUTTON_LANGPACK         = 1;
    /** @var int */
    const STOPBUTTON_SPECIFIC         = 2;

    /** @var int */
    const ACTIVITY_NONE               = 0;
    /** @var int */
    const ACTIVITY_COURSE_ANY         = -1;
    /** @var int */
    const ACTIVITY_SECTION_ANY        = -2;
    /** @var int */
    const ACTIVITY_COURSE_GRADED      = -3;
    /** @var int */
    const ACTIVITY_SECTION_GRADED     = -4;
    /** @var int */
    const ACTIVITY_COURSE_TASKCHAIN   = -5;
    /** @var int */
    const ACTIVITY_SECTION_TASKCHAIN  = -6;

    /*
     * possible values for "addtype" field on the main form.
     * This field is not stored in the database, but specifies
     * what items from the source folder are to be added as tasks
     *
     * ADDTYPE_AUTO: if main file is selected, then it is added
     * as TASKCHAIN or CHAINFILE; otherwise we add as CHAINFOLDER
     */
    /** @var int */
    const ADDTYPE_AUTO                = 0;
    /** @var int */
    const ADDTYPE_TASKFILE            = 1;
    /** @var int */
    const ADDTYPE_TASKCHAIN           = 2;
    /** @var int */
    const ADDTYPE_CHAINFILE           = 3;
    /** @var int */
    const ADDTYPE_CHAINFOLDER         = 4;
    /** @var int */
    const ADDTYPE_CHAINFOLDERS        = 5;

    /** @var int */
    const ENTRYOPTIONS_TITLE          = 0x01;
    /** @var int */
    const ENTRYOPTIONS_GRADING        = 0x02;
    /** @var int */
    const ENTRYOPTIONS_DATES          = 0x04;
    /** @var int */
    const ENTRYOPTIONS_ATTEMPTS       = 0x08;

    /** @var int */
    const EXITOPTIONS_TITLE           = 0x01;
    /** @var int */
    const EXITOPTIONS_ENCOURAGEMENT   = 0x02;
    /** @var int */
    const EXITOPTIONS_ATTEMPTSCORE    = 0x04;
    /** @var int */
    const EXITOPTIONS_TASKCHAINGRADE  = 0x08;
    /** @var int */
    const EXITOPTIONS_RETRY           = 0x10;
    /** @var int */
    const EXITOPTIONS_INDEX           = 0x20;
    /** @var int */
    const EXITOPTIONS_COURSE          = 0x40;
    /** @var int */
    const EXITOPTIONS_GRADES          = 0x80;

    /*
     * three sets of 6 bits define the times at which a task may be reviewed
     * e.g. 0x3f = 0011 1111 (i.e. right most 6 bits)
     */
    /** @var int */
    const REVIEW_DURINGATTEMPT        = 0x0003f; // 1st set of 6 bits : during attempt
    /** @var int */
    const REVIEW_AFTERATTEMPT         = 0x00fc0; // 2nd set of 6 bits : after attempt (but before task closes)
    /** @var int */
    const REVIEW_AFTERCLOSE           = 0x3f000; // 3rd set of 6 bits : after the task closes

    /*
     * within each group of 6 bits we determine what should be shown
     * e.g. 0x1041 = 00-0001 0000-01 00-0001 (i.e. 3 sets of 6 bits)
     */
    /** @var int */
    const REVIEW_RESPONSES            = 0x1041; // 1*0x1041 : 1st bit of each 6-bit set : Show student responses
    /** @var int */
    const REVIEW_ANSWERS              = 0x2082; // 2*0x1041 : 2nd bit of each 6-bit set : Show correct answers
    /** @var int */
    const REVIEW_SCORES               = 0x4104; // 3*0x1041 : 3rd bit of each 6-bit set : Show scores
    /** @var int */
    const REVIEW_FEEDBACK             = 0x8208; // 4*0x1041 : 4th bit of each 6-bit set : Show feedback

    /** @var int */
    const MIN                         = -1;
    /** @var int */
    const MAX                         = 1;

    /** @var int */
    const EQUALWEIGHTING              = -1;

    /** @var int */
    const CONDITIONTYPE_PRE           = 1;
    /** @var int */
    const CONDITIONTYPE_POST          = 2;

    /** @var int */
    const CONDITIONTASKID_SAME        = -1;
    /** @var int */
    const CONDITIONTASKID_PREVIOUS    = -2;
    /** @var int */
    const CONDITIONTASKID_NEXT1       = -3; // Was NEXT.
    /** @var int */
    const CONDITIONTASKID_NEXT2       = -4; // Was SKIP.
    /** @var int */
    const CONDITIONTASKID_NEXT3       = -5;
    /** @var int */
    const CONDITIONTASKID_NEXT4       = -6;
    /** @var int */
    const CONDITIONTASKID_NEXT5       = -7;
    /** @var int */
    const CONDITIONTASKID_UNSEEN      = -10; // Was -5.
    /** @var int */
    const CONDITIONTASKID_UNANSWERED  = -11; // Was -6.
    /** @var int */
    const CONDITIONTASKID_INCORRECT   = -12; // Was -7.
    /** @var int */
    const CONDITIONTASKID_RANDOM      = -13; // Was -8.
    /** @var int */
    const CONDITIONTASKID_MENUNEXT    = -20; // Was -10 was -11.
    /** @var int */
    const CONDITIONTASKID_MENUNEXTONE = -21; // Was -11.
    /** @var int */
    const CONDITIONTASKID_MENUALL     = -22; // Was -12 was -10.
    /** @var int */
    const CONDITIONTASKID_MENUALLONE  = -23; // Was -13.
    /** @var int */
    const CONDITIONTASKID_ENDOFCHAIN  = -99;

    /** @var int */
    const ATTEMPTTYPE_ANY             = 0;
    /** @var int */
    const ATTEMPTTYPE_RECENT          = 1;
    /** @var int */
    const ATTEMPTTYPE_CONSECUTIVE     = 2;

    /** @var string */
    const COLUMNS_EDITTASKS_ALL       = '';
    /** @var string */
    const COLUMNS_EDITTASKS_DEFAULT   = '';
    /** @var string */
    const COLUMNS_EDITTASKS_MINIMUM   = '';

    /** @var int */
    const COLUMNS_ALL                 = 1;
    /** @var int */
    const COLUMNS_DEFAULT             = 2;
    /** @var int */
    const COLUMNS_MINIMUM             = 3;
    /** @var int */
    const COLUMNS_CUSTOM              = 4;

    /** @var int */
    const ADDTASKS_ATSTART            = -1;
    /** @var int */
    const ADDTASKS_ATEND              = -2;

    /** @var int */
    const SELECTTASKS_THISTASKCHAIN   = 1;
    /** @var int */
    const SELECTTASKS_ALLMYTASKCHAINS = 2;
    /** @var int */
    const SELECTTASKS_ALLMYCOURSES    = 3;
    /** @var int */
    const SELECTTASKS_WHOLESITE       = 4;

    /** @var int */
    const TASKSACTION_DELETE          = -1;
    /** @var int */
    const TASKSACTION_DEFAULTS        = -2;
    /** @var int */
    const TASKSACTION_MOVETOSTART     = -3;

    /** @var integer unumber/tnumber value to force creation of new attempt */
    const FORCE_NEW_ATTEMPT           = -1;

    /*
     * values for $CFG->taskchain_bodystyles
     */
    /** @var int */
    const BODYSTYLES_BACKGROUND       = 0x01;
    /** @var int */
    const BODYSTYLES_COLOR            = 0x02;
    /** @var int */
    const BODYSTYLES_FONT             = 0x04;
    /** @var int */
    const BODYSTYLES_MARGIN           = 0x08;

    /** @var mixed reference back to global $TC object */
    public $TC = null;

    /** @var mixed debugging property to show methods in this class */
    protected $methods = null;

    /*///////////////////////////////////////////////////////
    // "magic" methods
    ///////////////////////////////////////////////////////*/

    /**
     * __construct
     *
     * @uses $CFG
     * @param object $dbrecord (optional, default=null) db record associated with this object
     * @param array $objects (optional, default=null) array ($name => $object) of associated objects
     * @return void, if possible, properties will be assigned from $dbrecord and $objects
     */
    public function __construct($dbrecord = null, $objects = null) {
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
     */
    public function __get($name) {
        $method = 'get_' . $name;
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
     */
    public function __set($name, $value) {
        $method = 'set_' . $name;
        if (method_exists($this, $method)) {
            $this->$method($value);
            return true;
        }
        $this->property_error('set', $name, $method);
    }

    /*///////////////////////////////////////////////////////
    // protected API
    ///////////////////////////////////////////////////////*/

    /**
     * report an error from a "magic" method
     *
     * @param string $type of magic function error ("get" or "set")
     * @param string $property name of a private or undefined property
     * @param string $method name of a method used to access the $property
     */
    protected function property_error($type, $property, $method) {
        if (property_exists($this, $property)) {
            $hint = 'error_' . $type . 'privateproperty';
        } else {
            $hint = 'error_' . $type . 'unknownproperty';
        }
        $a = (object)array('class'    => get_class($this),
                           'property' => $property,
                           'method'   => $method . '()');
        $hint = get_string($hint, 'mod_taskchain', $a);
        throw new coding_exception($hint); // $debuginfo
    }

    /*///////////////////////////////////////////////////////
    // public API
    ///////////////////////////////////////////////////////*/

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
        if ($this->methods === null) {
            $this->methods = get_class_methods($this);
            sort($this->methods);
        }
        return $this->methods;
    }

    /**
     * to_stdclass
     *
     * @return stdclass
     */
    public function to_stdclass() {
        $stdclass = new stdclass();

        // Extract just the "get" methods for this object.
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
