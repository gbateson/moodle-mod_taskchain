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
 * mod/taskchain/locallib/can.php
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
 * taskchain_can
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_can extends taskchain_base {

    /** @var boolean cache of the "mod/taskchain:addinstance" capability */
    private $canaddinstance         = null;

    /** @var boolean cache of the "mod/taskchain:attempt" capability */
    private $canattempt             = null;

    /** @var boolean cache of the "mod/taskchain:deleteallattempts" capability */
    private $candeleteallattempts   = null;

    /** @var boolean cache of the "mod/taskchain:deletemyattempts" capability */
    private $candeletemyattempts    = null;

    /** @var boolean cache of the "mod/taskchain:manage" capability */
    private $canmanage              = null;

    /** @var boolean cache of the "mod/taskchain:preview" capability */
    private $canpreview             = null;

    /** @var boolean cache of the "mod/taskchain:regrade" capability */
    private $canregrade             = null;

    /** @var boolean cache of the "mod/taskchain:reviewallattempts" capability */
    private $canreviewallattempts   = null;

    /** @var boolean cache of the "mod/taskchain:reviewmyattempts" capability */
    private $canreviewmyattempts    = null;

    /** @var boolean cache of the "mod/taskchain:view" capability */
    private $canview                = null;

    /** @var boolean cache of the "moodle/course:manageactivities" capability */
    private $manageactivities       = null;

    /** @var boolean cache of the "moodle/site:accessallgroups" capability */
    private $accessallgroups        = null;

    /** @var boolean cache of the "moodle/site:accessallgroups" capability */
    private $canmoodlesiteaccessallgroups     = null;

    /** switches to cache user's ability to start or resume an attempt */
    public $canstarttaskattempts    = null;
    public $canstartchainattempts   = null;
    public $canresumetaskattempts   = null;
    public $canresumechainattempts  = null;

    /** @var object current context */
    public $context                 = null;

    /** string prefix for capabilities */
    const PREFIX                    = 'mod/taskchain';

    /////////////////////////////////////////////////////////
    // "magic" methods
    /////////////////////////////////////////////////////////

    /**
     * Constructor function
     *
     * @param context object (optional, default=null)
     * @return void, will update the $context property
     */
    public function __construct($dbrecord=null, $objects=null) {
        global $PAGE;

        // do standard setup - this should initialize $this->TC
        parent::__construct($dbrecord, $objects);

        switch (true) {
            case isset($this->TC->coursemodule->context) : $this->context = $this->TC->coursemodule->context; break;
            case isset($this->TC->course->context)       : $this->context = $this->TC->course->context; break;
            case isset($PAGE->context)                   : $this->context = $PAGE->context; break;
        }
    }

    // we could remove the methods declared explicitly below
    // and use just the following __call function instead,
    // however this may make it harder to understand
    //
    // @param string $name of method to be called
    // @param array $arguments to be passed to method
    // @return boolean, true if user has capability, otherwise false
    //

    /**
     * __call
     *
     * @param xxx $name
     * @param xxx $params
     * @return xxx
     * @todo Finish documenting this function
     */
    public function __call($name, $params) {
        $propertyname = 'can'.$name;
        if (property_exists($this, $propertyname)) {
            $require = array_shift($params);
            return $this->can($name, $require);
        }
        return false; // shouldn't happen
    }

    /////////////////////////////////////////////////////////
    // public API
    /////////////////////////////////////////////////////////

    /**
     * Determine whether or not the current user has the required capability
     *
     * @type string the name of the required capability (optionally without leading "mod/taskchain:")
     * @require boolean true if capability is required, false otherwise
     * @return boolean true of user has required capability, otherwise false
     */
    public function can($type, $require=false, $context=null) {
        if (strpos($type, ':')===false) {
            $field = 'can'.$type;
            $capability = self::PREFIX.':'.$type;
        } else {
            $field = 'can'.str_replace(array('/', ':'), '', $type);
            $capability = $type;
        }

        if ($context) {
            if ($require) {
                return require_capability($capability, $context);
            } else {
                return has_capability($capability, $context);
            }
        }

        if (is_null($this->$field)) {
            $this->$field = has_capability($capability, $this->context);
        }

        if ($require && $this->$field==false) {
            throw new required_capability_exception($context, $capability, 'nopermissions', '');
        }

        return $this->$field;
    }


    /**
     * addinstance
     *
     * @return boolean true if user has "mod/taskchain:addinstance" capability; false otherwise
     */
    public function addinstance($require=false, $context=null) {
        return $this->can('addinstance', $require, $context);
    }

    /**
     * attempt
     *
     * @return boolean true if user has "mod/taskchain:attempt" capability; false otherwise
     */
    public function attempt($require=false, $context=null) {
        return $this->can('attempt', $require, $context);
    }

    /**
     * deleteallattempts
     *
     * @return boolean true if user has "mod/taskchain:deleteallattempts" capability; false otherwise
     */
    public function deleteallattempts($require=false, $context=null) {
        return $this->can('deleteallattempts', $require, $context);
    }

    /**
     * deletemyattempts
     *
     * @return boolean true if user has "mod/taskchain:deletemyattempts" capability; false otherwise
     */
    public function deletemyattempts($require=false, $context=null) {
        return $this->can('deletemyattempts', $require, $context);
    }

    /**
     * deleteattempts
     *
     * @return boolean true if user can delete attempts; false otherwise
     */
    function deleteattempts($require=false, $context=null) {
        return ($this->deleteallattempts(false, $context) || $this->deletemyattempts($require, $context));
    }

    /**
     * manage
     *
     * @return boolean true if user has "mod/taskchain:manage" capability; false otherwise
     */
    public function manage($require=false, $context=null) {
        return $this->can('manage', $require, $context);
    }

    /**
     * preview
     *
     * @return boolean true if user has "mod/taskchain:preview" capability; false otherwise
     */
    public function preview($require=false, $context=null) {
        return $this->can('preview', $require, $context);
    }

    /**
     * regrade
     *
     * @return boolean true if user has "mod/taskchain:regrade" capability; false otherwise
     */
    public function regrade($require=false, $context=null) {
        return $this->can('regrade', $require, $context);
    }

    /**
     * reviewallattempts
     *
     * @return boolean true if user has "mod/taskchain:reviewallattempts" capability; false otherwise
     */
    public function reviewallattempts($require=false, $context=null) {
        return $this->can('reviewallattempts', $require, $context);
    }

    /**
     * reviewmyattempts
     *
     * @return boolean true if user has "mod/taskchain:reviewmyattempts" capability; false otherwise
     */
    public function reviewmyattempts($require=false, $context=null) {
        return $this->can('reviewmyattempts', $require, $context);
    }

    /**
     * can_reviewallattempts
     *
     * @return xxx
     */
    function reviewattempts($require=false, $context=null) {
        return ($this->reviewallattempts(false, $context) || $this->reviewmyattempts($require, $context));
    }

    /**
     * view
     *
     * @return boolean true if user has "mod/taskchain:view" capability; false otherwise
     */
    public function view($require=false, $context=null) {
        return $this->can('view', $require, $context);
    }

    /**
     * attempts
     *
     * @param xxx $type "chain" or "task"
     * @param xxx $value (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function attempts($type, $value=null) {
        $field = 'can'.$type.'attempts';
        if (is_null($value)) {
            return $this->$field; // get
        } else {
            $this->$field = $value; // set
        }
    }

    /**
     * manageactivities
     *
     * @param xxx $require (optional, default=false)
     * @param xxx $context (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    function manageactivities($require=false, $context=null) {
        return $this->can('moodle/course:manageactivities', $require, $context);
    }

    /**
     * accessallgroups
     *
     * @param xxx $context (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    function accessallgroups($context=null) {
        // get groupmode: 0=NOGROUPS, 1=VISIBLEGROUPS, 2=SEPARATEGROUPS
        if (empty($this->TC->coursemodule)) {
            $groupmode = groups_get_course_groupmode($this->TC->course);
        } else {
            $groupmode = groups_get_activity_groupmode($this->TC->coursemodule);
        }
        return ($groupmode==NOGROUPS || $groupmode==VISIBLEGROUPS || $this->can('moodle/site:accessallgroups', false, $context));
    }
}
