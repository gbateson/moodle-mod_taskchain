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
 * mod/taskchain/locallib/get.php
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
 * taskchain_get
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_get extends taskchain_base {

    /**
     * get the next, previous or specific Moodle activity
     *
     * @param string $type ("chain" or "unit")
     * @return xxx
     * @todo Finish documenting this function
     */
    public function cm($type)  {
        global $DB;

        // get entry/exit cm id
        $cmid = $this->field('chain', $type.'cm', self::ACTIVITY_NONE);

        if ($cmid==self::ACTIVITY_NONE) {
            return false;
        }

        if (! $modinfo = get_fast_modinfo($this->TC->course)) {
            return false; // no modinfo - shouldn't happen !!
        }

        // check current cm exists
        if (! $cm = $modinfo->get_cm($this->TC->coursemodule->id)) {
            return false; // current cm not found - shouldn't happen !!
        }

        // set default search values
        $id = 0;
        $graded = false;
        $modname = '';
        $sectionnum = -1;

        // restrict search values
        if ($cmid > 0) {
            $id = $cmid;
        } else {
            if ($cmid==self::ACTIVITY_COURSE_GRADED || $cmid==self::ACTIVITY_SECTION_GRADED) {
                $graded = true;
            }
            if ($cmid==self::ACTIVITY_COURSE_TASKCHAIN || $cmid==self::ACTIVITY_SECTION_TASKCHAIN) {
                $modname = 'taskchain';
            }
            if ($cmid==self::ACTIVITY_SECTION_ANY || $cmid==self::ACTIVITY_SECTION_GRADED || $cmid==self::ACTIVITY_SECTION_TASKCHAIN) {
                $sectionnum = $cm->sectionnum;
            }
        }

        // get grade info, if required (we just need to know if an activity has a grade or not)
        if ($graded) {

            // basic SQL to get grade items for graded activities
            $select = 'cm.id, gi.courseid, gi.itemtype, gi.itemmodule, gi.iteminstance, gi.gradetype';
            $from   = '{grade_items} gi'.
                      ' LEFT JOIN {modules} m ON gi.itemmodule = m.name'.
                      ' LEFT JOIN {course_modules} cm ON m.id = cm.module AND gi.iteminstance = cm.instance';
            $where  = "gi.courseid = ? AND gi.itemtype = ? AND gi.gradetype <> ?";
            $params = array($this->TC->course->id, 'mod', GRADE_TYPE_NONE);

            // restrict results to current section, if we can
            if ($sectionnum >= 0) {
                $from  .= ' LEFT JOIN {course_sections} cs ON cs.id = cm.section';
                $where .= ' AND cs.section = ?';
                $params[] = $sectionnum;
            }

            $graded_cmids = $DB->get_records_sql("SELECT $select FROM $from WHERE $where", $params);
        }

        // get cm ids (reverse order if necessary)
        $coursemoduleids = array_keys($modinfo->cms);
        if ($type=='entry') {
            $coursemoduleids = array_reverse($coursemoduleids);
        }

        // search for next, previous or specific course module
        $found = false;
        foreach ($coursemoduleids as $coursemoduleid) {
            if ($id && $id != $coursemoduleid) {
                continue; // wrong activity
            }
            if (! $coursemodule = $modinfo->get_cm($coursemoduleid)) {
                continue; // shouldn't happen !!
            }
            if ($sectionnum >= 0) {
                if ($type=='entry') {
                    if ($coursemodule->sectionnum > $sectionnum) {
                        continue; // later section
                    }
                    if ($coursemodule->sectionnum < $sectionnum) {
                        return false; // previous section
                    }
                } else { // exit (=next)
                    if ($coursemodule->sectionnum < $sectionnum) {
                        continue; // earlier section
                    }
                    if ($coursemodule->sectionnum > $sectionnum) {
                        return false; // later section
                    }
                }
            }
            if ($graded && empty($graded_cmids[$coursemoduleid])) {
                continue; // skip ungraded activity
            }
            if ($modname && $coursemodule->modname != $modname) {
                continue; // wrong module
            }
            if ($coursemodule->modname=='label') {
                continue; // skip labels
            }
            if ($found || $coursemoduleid==$id) {
                if (class_exists('\core_availability\info_module')) {
                    // Moodle >= 2.7
                    $is_visible = \core_availability\info_module::is_user_visible($coursemodule);
                } else {
                    // Moodle <= 2.6
                    $is_visible = coursemodule_visible_for_user($coursemodule);
                }
                if ($is_visible) {
                    return $coursemodule;
                }
                if ($coursemoduleid==$id) {
                    // required cm is not visible to this user
                    return false;
                }
            }
            if ($coursemoduleid==$cm->id) {
                $found = true;
            }
        }

        // next/prev cm not found
        return false;
    }

    /**
     * get_gradeitem
     *
     * @uses $CFG
     * @uses $USER
     * @return xxx
     * @todo Finish documenting this function
     */
    public function gradeitem() {
        global $CFG;
        require_once($CFG->dirroot.'/lib/gradelib.php');

        if (is_null($this->TC->gradeitem)) {
            $this->TC->gradeitem = false;

            $userid = $this->TC->get_userid();
            $courseid = $this->TC->get_courseid();
            $taskchainid = $this->TC->get_taskchainid();

            if ($grades = grade_get_grades($courseid, 'mod', 'taskchain', $taskchainid, $userid)) {
                if (isset($grades->items[0]) && $grades->items[0]->grademax > 0) {
                    // this activity has a grade item
                    if (isset($grades->items[0]->grades[$userid])) {
                        $this->TC->gradeitem = $grades->items[0]->grades[$userid];
                        // grade->grade is the adjusted grade, for a true percent
                        // we need to shift and scale according to grademin and grademax
                        $percent = $this->TC->gradeitem->grade;
                        if ($grades->items[0]->grademax > 0) {
                            $percent = (($percent - $grades->items[0]->grademin) / $grades->items[0]->grademax);
                        }
                        $this->TC->gradeitem->percent = round($percent * 100);
                    }
                }
            }
        }
        return $this->TC->gradeitem;
    }

    /**
     * field
     *
     * @param xxx $type
     * @param xxx $field
     * @param xxx $default
     * @param xxx $optional_param (optional, default=false)
     * @param xxx $param_type (optional, default=PARAM_INT)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function field($type, $field, $default, $optional_param=false, $param_type=PARAM_INT) {
        $value = false;
        $method = 'get_'.$field;
        if ($type=='') {
            if (method_exists($this->TC, $method)) {
                $value = $this->TC->$method();
            } else if (isset($this->TC->$field)) {
                $value = $this->TC->$field;
            }
        } else if (isset($this->TC->$type)) {
            if (method_exists($this->TC->$type, $method)) {
                $value = $this->TC->$type->$method();
            } else if (isset($this->TC->$type->$field)) {
                $value = $this->TC->$type->$field;
            }
        }
        if ($value) {
            return $value;
        } else if ($optional_param) {
            return optional_param($field, $default, $param_type);
        } else {
            return $default;
        }
    }

    /**
     * id
     *
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    public function id($type) {
        return $this->field($type, 'id', 0);
    }

    /**
     * userid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function userid() {
        return $this->field('', 'userid', 0);
    }

    /**
     * courseid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function courseid() {
        return $this->id('course');
    }

    /**
     * coursemoduleid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function coursemoduleid() {
        return $this->id('coursemodule');
    }

    /**
     * taskchainid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function taskchainid() {
        return $this->id('taskchain');
    }

    /**
     * chainid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function chainid() {
        return $this->id('chain');
    }

    /**
     * chainattemptid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function chainattemptid() {
        return $this->id('chainattempt');
    }

    /**
     * chaingradeid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function chaingradeid() {
        return $this->id('chaingrade');
    }

    /**
     * conditionid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function conditionid() {
        return $this->id('condition');
    }

    /**
     * lastchainattemptid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function lastchainattemptid() {
        return $this->id('lastchainattempt');
    }

    /**
     * lasttaskattemptid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function lasttaskattemptid() {
        return $this->id('lasttaskattempt');
    }

    /**
     * taskid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function taskid() {
        if ($taskid = $this->TC->force_taskid()) {
            return $taskid;
        } else {
            return $this->id('task');
        }
    }

    /**
     * taskattemptid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function taskattemptid() {
        return $this->id('taskattempt');
    }

    /**
     * taskscoreid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function taskscoreid() {
        return $this->id('taskscore');
    }

    /**
     * cnumber
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function cnumber() {
        // chain attempt number:
        //    -1 : start new chain attempt
        //     0 : not set
        //    >0 : current chain attempt
        if ($cnumber = $this->TC->force_cnumber()) {
            return $cnumber;
        } else {
            return $this->field('chainattempt', 'cnumber', 0, true);
        }
    }

    /**
     * tnumber
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function tnumber() {
        // task attempt number:
        //    -1 : start new task attempt
        //     0 : not set
        //    >0 : current task attempt
        if ($tnumber = $this->TC->force_tnumber()) {
            return $tnumber;
        } else {
            return $this->field('taskattempt', 'tnumber', 0, true);
        }
    }

    /**
     * lastchainattempttime
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function lastchainattempttime() {
        return $this->field('lastchainattempt', 'timemodified', 0);
    }

    /**
     * lasttaskattempttime
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function lasttaskattempttime() {
        return max($this->field('lasttaskattempt', 'timestart', 0),
                   $this->field('lasttaskattempt', 'timefinish', 0),
                   $this->field('lasttaskattempt', 'resumestart', 0),
                   $this->field('lasttaskattempt', 'resumefinish', 0));
    }

    /**
     * conditiontype
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function conditiontype() {
        return $this->field('condition', 'conditiontype', 0, true);
    }

    /**
     * columnlisttype
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function columnlisttype() {
        return $this->field('', 'columnlisttype', '', true, PARAM_ALPHA);
    }

    /**
     * columnlistid
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function columnlistid() {
        return $this->field('', 'columnlistid', '', true, PARAM_ALPHANUM);
    }

    /**
     * popup
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function popup() {
        return $this->field('', 'popup', 0, true);
    }

    /**
     * tab
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function tab() {
        return $this->field('', 'tab', '', true, PARAM_ALPHA);
    }

    /**
     * mode
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function mode() {
        return $this->field('', 'mode', '', true, PARAM_ALPHA);
    }

    /**
     * columnlists are stored in user preferences
     *     name  : taskchain_($type)_columnlist_($id)
     *     value : ($name):($columns)
     * where ...
     *     $type : 'tasks' or 'chains'
     *     $id   : a two-digit number (e.g. '01')
     *     $name : user-supplied alphanumeric string
     *     $columns : comma-separated list of column (=field) names
     *
     * @param string $type
     * @param boolean $return_columns (optional, default=false)
     * @return array
     * @todo Finish documenting this function
     */
    public function columnlists($type, $return_columns=false) {
        $columnlists = array();
        $asort = false;
        if ($preferences = get_user_preferences()) {
            foreach ($preferences as $name => $value) {
                if (preg_match('/^taskchain_'.$type.'_columnlist_(\d+)$/', $name, $matches)) {
                    $id = $matches[1];
                    list($name, $columns) = explode(':', $value, 2);
                    if ($return_columns) {
                        // $columnlistid => array($column1, $column2, ...)
                        $columnlists[$id] = explode(',', $columns);
                    } else {
                        // $columnlistid => $columnlistname
                        $columnlists[$id] = $name;
                        $asort = true;
                    }
                }
            }
        }
        if ($asort) {
            asort($columnlists);
        }
        return $columnlists;
    }

    /**
     * mycourses
     *
     * @uses $DB
     * @uses $USER
     * @param xxx $userid (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function mycourses($userid=0) {
        // get all courses in which this user can manage activities
        global $DB, $USER;
        if ($userid) {
            $thisuser = false;
            $mycourses = null;
        } else {
            $thisuser = true;
            $userid = $this->TC->userid;
            $mycourses = &$this->TC->mycourses;
        }
        if (is_null($mycourses)) {
            if ($this->TC->can->manageactivities(false, mod_taskchain::context(CONTEXT_SYSTEM))) {
                $mycourses = get_courses(); // system admin
            } else {
                $mycourses = enrol_get_users_courses($userid);
            }
        }
        return $mycourses;
    }

    /**
     * mytaskchains
     *
     * @param xxx $userid (optional, default=0)
     * @param xxx $mycourses (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function mytaskchains($userid=0, $mycourses=null) {
        if ($userid) {
            $thisuser = false;
            $mytaskchains = null;
        } else {
            $thisuser = true;
            $userid = $this->TC->userid;
            $mytaskchains = &$this->TC->mytaskchains;
        }
        if (is_null($mytaskchains)) {
            if (is_null($mycourses)) {
                $mycourses = $this->mycourses($thisuser ? 0 : $userid);
            }
            // get all instances of taskchains in all courses which this user can edit
            if ($instances = get_all_instances_in_courses('taskchain', $mycourses, $userid)) {
                $mytaskchains = array();
                foreach ($instances as $instance) {
                    $mytaskchains[$instance->id] = $instance;
                }
            } else {
                $mytaskchains = false;
            }
        }
        return $mytaskchains;
    }

    ///////////////////////////////////////////////////////
    // get records from TaskChain tables in the Moodle DB
    ///////////////////////////////////////////////////////

    /**
     * taskchains
     *
     * @uses $CFG
     * @param xxx $userid (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function taskchains($userid=0) {
        global $CFG;
        if ($userid) {
            $thisuser = false;
            $taskchains = null;
        } else {
            $thisuser = true;
            $userid = $this->TC->userid;
            $taskchains = &$this->TC->taskchains;
        }
        if (is_null($taskchains)) {
            // get all taskchains in this course that are visible to this user
            $instances = get_all_instances_in_course('taskchain', $this->TC->course, $userid);
            if ($instances) {
                $taskchains = array();
                foreach ($instances as $instance) {
                    $taskchains[$instance->id] = $instance;
                }
            } else {
                $taskchains = false;
            }
        }
        return $taskchains;
    }

    /**
     * chains
     *
     * @uses $DB
     * @param xxx $userid (optional, default=0)
     * @param xxx $taskchains (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function chains($userid=0, $taskchains=null) {
        global $DB;
        if ($userid) {
            $thisuser = false;
            $chains = null;
        } else {
            $thisuser = true;
            $chains = &$this->TC->chains;
        }
        if (is_null($chains)) {
            if (is_null($taskchains)) {
                $taskchains = $this->taskchains($thisuser ? 0 : $userid);
            }
            if ($taskchains) {
                $parentids = implode(',', array_keys($taskchains));
                $select = "parenttype=? AND parentid IN ($parentids)";
                $params = array(self::PARENTTYPE_ACTIVITY);
                $chains = $DB->get_records_select('taskchain_chains', $select, $params);
            } else {
                $chains = false;
            }
        }
        return $chains;
    }

    /**
     * chain
     *
     * @uses $DB
     * @param xxx $taskchainid (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function chain($taskchainid=0) {
        global $DB;
        if ($taskchainid) {
            $chain = null;
        } else {
            $chain = &$this->TC->chain;
            $taskchainid = $this->TC->taskchain->get_id();
        }
        if (is_null($chain)) {
            $params = array('parenttype'=>self::PARENTTYPE_ACTIVITY, 'parentid'=>$taskchainid);
            $chain = $DB->get_record('taskchain_chains', $params);
        }
        return $chain;
    }

    /**
     * chaingrades
     *
     * @uses $DB
     * @param xxx $userid (optional, default=0)
     * @param xxx $chainid (optional, default=0)
     * @param xxx $cnumber (optional, default=0)
     * @param xxx $taskid (optional, default=0)
     * @param xxx $tnumber (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function chaingrades($userid=0, $chainid=0, $cnumber=0, $taskid=0, $tnumber=0) {
        global $DB;
        if ($userid) {
            $thisuser = false;
            $chaingrades = null;
        } else {
            $thisuser = true;
            $userid = $this->TC->userid;
            $chaingrades = &$this->TC->chaingrades;
        }
        if (is_null($chaingrades)) {
            if ($taskchains = $this->taskchains($thisuser ? 0 : $userid)) {
                $parentids = implode(',', array_keys($taskchains));
                $select = "parenttype=? AND parentid IN ($parentids) AND userid=?";
                $params = array(self::PARENTTYPE_ACTIVITY, $userid);
                $chaingrades = $DB->get_records_select('taskchain_chain_grades', $select, $params);
            } else {
                $chaingrades = false;
            }
        }
        return $chaingrades;
    }

    /**
     * chaingrade
     *
     * @uses $DB
     * @param xxx $userid (optional, default=0)
     * @param xxx $chainid (optional, default=0)
     * @param xxx $cnumber (optional, default=0)
     * @param xxx $taskid (optional, default=0)
     * @param xxx $tnumber (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function chaingrade($userid=0, $chainid=0, $cnumber=0, $taskid=0, $tnumber=0) {
        global $DB;
        if ($userid) {
            $thisuser = false;
            $chaingrade = null;
            $chaingradeid = 0;
        } else {
            $thisuser = true;
            $userid = $this->TC->userid;
            $chaingrade = &$this->TC->chaingrade;
            $chaingradeid = $this->chaingradeid();
        }
        if ($chaingradeid==0) {
            if ($chainid) {
                $chain = $DB->get_record('taskchain_chains', array('id'=>$chainid), 'id,parenttype,parentid');
            } else {
                $chain = &$this->TC->chain;
            }
            $params = array('parenttype'=>$chain->parenttype, 'parentid'=>$chain->parentid, 'userid'=>$userid);
            if ($chaingrade = $DB->get_record('taskchain_chain_grades', $params)) {
                $chaingradeid = $chaingrade->id;
                if ($thisuser) {
                    $chaingrade = new taskchain_chain_grade($chaingrade, array('TC' => &$this->TC));
                }
            }
        }
        if ($chaingradeid==0) {
            return null;
        } else {
            return $chaingrade;
        }
    }

    /**
     * chainattempts
     *
     * @uses $DB
     * @param xxx $userid (optional, default=0)
     * @param xxx $chainid (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function chainattempts($userid=0, $chainid=0) {
        global $DB;
        if ($userid) {
            $thisuser = false;
            $chainattempts = null;
        } else {
            $thisuser = true;
            $userid = $this->TC->userid;
            $chainid = $this->TC->get_chainid();
            $chainattempts = &$this->TC->chainattempts;
        }
        if (is_null($chainattempts)) {
            $select = "userid=$userid";
            $params = array();
            if (is_null($this->TC->coursemodule)) {
                // all chain attempts at all chains in the course
                if ($chains = $this->chains($thisuser ? 0 : $userid)) {
                    $chainids = implode(',', array_keys($chains));
                    $select .= " AND chainid IN ($chainids)";
                }
            } else {
                // all chain attempts at one particular cm/taskchain/chain in the course
                if ($chainid) {
                    $select .= ' AND chainid=?';
                    $params[] = $chainid;
                }
            }
            $chainattempts = $DB->get_records_select('taskchain_chain_attempts', $select, $params);
        }
        return $chainattempts;
    }

    /**
     * maxchainattemptgrade
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function maxchainattemptgrade() {
        if (is_null($this->TC->maxchainattemptgrade)) {
            $this->TC->maxchainattemptgrade = 0;
            if ($chainattempts = $this->chainattempts()) {
                foreach ($chainattempts as $chainattempt) {
                    $this->TC->maxchainattemptgrade = max($this->TC->maxchainattemptgrade, $chainattempt->grade);
                }
            }
        }
        return $this->TC->maxchainattemptgrade;
    }

    /**
     * chaincompleted
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function chaincompleted() {
        if (is_null($this->TC->chaincompleted)) {
            $this->TC->chaincompleted = 0;
            if ($chainattempts = $this->chainattempts()) {
                foreach ($chainattempts as $chainattempt) {
                    if ($chainattempt->status==self::STATUS_COMPLETED) {
                        $this->TC->chaincompleted++;
                    }
                }
            }
        }
        return $this->TC->chaincompleted;
    }

    /**
     * chainattempt
     *
     * @uses $DB
     * @param xxx $userid (optional, default=0)
     * @param xxx $chainid (optional, default=0)
     * @param xxx $cnumber (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function chainattempt($userid=0, $chainid=0, $cnumber=0) {
        global $DB;
        if ($userid) {
            $thisuser = false;
            $chainattempt = null;
            $chainattemptid = 0;
        } else {
            $thisuser = true;
            $userid = $this->TC->userid;
            $chainid = $this->TC->get_chainid();
            $cnumber = $this->TC->get_cnumber();
            $chainattempt = &$this->TC->chainattempt;
            $chainattemptid = $this->chainattemptid();
        }
        if ($chainattemptid==0) {
            $select = "chainid=? AND cnumber=? AND userid=?";
            $params = array($chainid, $cnumber, $userid);
            if ($chainattempt = $DB->get_record_select('taskchain_chain_attempts', $select, $params)) {
                $chainattemptid = $chainattempt->id;
                if ($thisuser) {
                    $chainattempt = new taskchain_chain_attempt($chainattempt, array('TC' => &$this->TC));
                }
            }
        }
        if ($chainattemptid==0) {
            return null;
        } else {
            return $chainattempt;
        }
    }

    /**
     * tasks
     *
     * @uses $DB
     * @param xxx $chainid (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function tasks($chainid=0) {
        global $DB;
        if ($chainid) {
            $thisuser = false;
            $tasks    = null;
        } else {
            $thisuser = true;
            $chainid  = $this->TC->get_chainid();
            $tasks    = &$this->TC->tasks;
        }
        if (is_null($tasks)) {
            $select = '';
            $params = array();
            if ($thisuser && is_null($this->TC->coursemodule)) {
                // all tasks in all chains in the course
                if ($chains = $this->TC->get_chains()) {
                    $chainids = implode(',', array_keys($chains));
                    $select = "chainid IN ($chainids)";
                }
            } else {
                // all tasks in one particular cm/taskchain/chain in the course
                if ($chainid) {
                    $select = 'chainid=?';
                    $params = array($chainid);
                }
            }
            if ($select) {
                $tasks = $DB->get_records_select('taskchain_tasks', $select, $params, 'sortorder');
            } else {
                $tasks = false;
            }
        }
        return $tasks;
    }

    /**
     * task
     *
     * @uses $CFG
     * @uses $DB
     * @return xxx
     * @todo Finish documenting this function
     */
    public function task() {
        global $CFG, $DB;

        if (is_null($this->TC->task)) {
            $params = array('id' => $this->TC->get_taskid());
            if (! $task = $DB->get_record('taskchain_tasks', $params)) {
                return false; // shouldn't happen - taskid was invalid !!
            }
            $this->TC->task = new taskchain_task($task, array('TC' => &$this->TC));
            $this->TC->force_taskid(0);
        }

        return $this->TC->get_taskid();
    }

    /**
     * taskscores
     *
     * @uses $DB
     * @param xxx $userid (optional, default=0)
     * @param xxx $chainid (optional, default=0)
     * @param xxx $cnumber (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function taskscores($userid=0, $chainid=0, $cnumber=0) {
        // get all scores by this user for tasks in this chain attempt
        global $DB;
        if ($userid) {
            $thisuser = false;
            $taskscores = null;
        } else {
            $thisuser = true;
            $userid = $this->TC->userid;
            $chainid = $this->TC->get_chainid();
            $cnumber = $this->TC->get_cnumber();
            $taskscores = &$this->TC->taskscores;
        }

        if (is_null($taskscores)) {
            if ($tasks = $this->tasks($chainid)) {
                $taskids = implode(',', array_keys($tasks));
                $select = "taskid IN ($taskids) AND userid=?";
                $params = array($userid);
                if ($cnumber>0) {
                    $select .= ' AND cnumber=?';
                    $params[] = $cnumber;
                }
                $taskscores = $DB->get_records_select('taskchain_task_scores', $select, $params, 'timemodified');
            }
        }
        return $taskscores;
    }

    /**
     * taskscore
     *
     * @uses $DB
     * @param xxx $userid (optional, default=0)
     * @param xxx $cnumber (optional, default=0)
     * @param xxx $taskid (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function taskscore($userid=0, $cnumber=0, $taskid=0) {
        global $DB;
        if ($userid) {
            $thisuser  = false;
            $taskscore = null;
            $taskscoreid = 0;
        } else {
            $thisuser = true;
            $userid  = $this->TC->userid;
            $taskid  = $this->TC->get_taskid();
            $cnumber = $this->TC->get_cnumber();
            $taskscore = &$this->TC->taskscore;
            $taskscoreid = $this->taskscoreid();
        }
        if ($taskscoreid==0) {
            $params = array('taskid'=>$taskid, 'cnumber'=>$cnumber, 'userid'=>$userid);
            if ($taskscore = $DB->get_record('taskchain_task_scores', $params)) {
                $taskscoreid = $taskscore->id;
                if ($thisuser) {
                    $taskscore = new taskchain_task_score($taskscore, array('TC' => &$this->TC));
                }
            }
        }
        if ($taskscoreid==0) {
            return null;
        } else {
            return $taskscore;
        }
    }

    /**
     * taskattempts
     *
     * @uses $DB
     * @param xxx $userid (optional, default=0)
     * @param xxx $chainid (optional, default=0)
     * @param xxx $cnumber (optional, default=0)
     * @param xxx $taskid (optional, default=0)
     * @param xxx $tnumber (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function taskattempts($userid=0, $chainid=0, $cnumber=0, $taskid=0, $tnumber=0) {
        global $DB;
        if ($userid) {
            $thisuser = false;
            $taskattempts = null;
        } else {
            $thisuser = true;
            $userid = $this->TC->userid;
            $taskid = $this->TC->get_taskid();
            $chainid = $this->TC->get_chainid();
            $cnumber = $this->TC->get_cnumber();
            $tnumber = $this->TC->get_tnumber();
            if ($this->TC->taskattempts) {
                $record = reset($this->TC->taskattempts);
                if ($record->taskid != $taskid) {
                    $this->TC->taskattempts = null;
                }
            }
            $taskattempts = &$this->TC->taskattempts;
        }
        if (is_null($taskattempts)) {
            $sort = '';
            $select = '';
            $params = array();
            if ($taskid) {
                $select = 'taskid=? AND userid=?';
                $params = array($taskid, $userid);
                if ($cnumber>0) {
                    $select .= ' AND cnumber=?';
                    $params[] = $cnumber;
                    if ($tnumber>0) {
                        $select .= " AND tnumber=?";
                        $params[] = $tnumber;
                    }
                }
                // time/resume finish is zero for "in progress" attempts, so sort by resumestart (most recent first)
                $sort = 'resumestart DESC';
            } else {
                if ($tasks = $this->tasks($chainid)) {
                    $taskids = implode(',', array_keys($tasks));
                    $select = "taskid IN ($taskids) AND userid=?";
                    $params = array($userid);
                    if ($cnumber>0) {
                        $select .= ' AND cnumber=?';
                        $params[] = $cnumber;
                    }
                }
                // most recent last
                $sort = 'resumestart ASC';
            }
            if ($select) {
                $taskattempts = $DB->get_records_select('taskchain_task_attempts', $select, $params, $sort);
            }
        }
        return $taskattempts;
    }

    /**
     * taskattempt
     *
     * @uses $DB
     * @param xxx $userid (optional, default=0)
     * @param xxx $chainid (optional, default=0)
     * @param xxx $cnumber (optional, default=0)
     * @param xxx $taskid (optional, default=0)
     * @param xxx $tnumber (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function taskattempt($userid=0, $chainid=0, $cnumber=0, $taskid=0, $tnumber=0) {
        global $DB;
        if ($userid) {
            $thisuser = false;
            $taskattempt = null;
            $taskattemptid = 0;
        } else {
            $thisuser = true;
            $userid = $this->TC->userid;
            $taskid = $this->TC->get_taskid();
            $cnumber = $this->TC->get_cnumber();
            $tnumber = $this->TC->get_tnumber();
            $taskattempt = &$this->TC->taskattempt;
            $taskattemptid = $this->taskattemptid();
        }
        if ($taskattemptid==0) {
            $select = 'taskid=? AND cnumber=? AND tnumber=? AND userid=?';
            $params = array($taskid, $cnumber, $tnumber, $userid);
            if ($taskattempt = $DB->get_record_select('taskchain_task_attempts', $select, $params)) {
                $taskattemptid = $taskattempt->id;
                if ($thisuser) {
                    $taskattempt = new taskchain_task_attempt($taskattempt, array('TC' => &$this->TC));
                }
            }
        }
        if ($taskattemptid==0) {
            return null;
        } else {
            return $taskattempt;
        }
    }

    /**
     * attempts
     *
     * @param xxx $type
     * @param xxx $userid (optional, default=0)
     * @param xxx $chainid (optional, default=0)
     * @param xxx $cnumber (optional, default=0)
     * @param xxx $taskid (optional, default=0)
     * @param xxx $tnumber (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function attempts($type, $userid=0, $chainid=0, $cnumber=0, $taskid=0, $tnumber=0) {
        $attempts = "{$type}attempts";
        return $this->$attempts($userid, $chainid, $cnumber, $taskid, $tnumber);
    }

    /**
     * attempt
     *
     * @param xxx $type
     * @param xxx $userid (optional, default=0)
     * @param xxx $chainid (optional, default=0)
     * @param xxx $cnumber (optional, default=0)
     * @param xxx $taskid (optional, default=0)
     * @param xxx $tnumber (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function attempt($type, $userid=0, $chainid=0, $cnumber=0, $taskid=0, $tnumber=0) {
        $attempt = "{$type}attempt";
        return $this->$attempt($userid, $chainid, $cnumber, $taskid, $tnumber);
    }

    /**
     * grade
     *
     * @param xxx $type
     * @param xxx $userid (optional, default=0)
     * @param xxx $chainid (optional, default=0)
     * @param xxx $cnumber (optional, default=0)
     * @param xxx $taskid (optional, default=0)
     * @param xxx $tnumber (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function grade($type, $userid=0, $chainid=0, $cnumber=0, $taskid=0, $tnumber=0) {
        if ($type=='chain') {
            $grade = 'chaingrade';
        } else {
            $grade = 'taskscore';
        }
        return $this->$grade($userid, $chainid, $cnumber, $taskid, $tnumber);
    }

    /**
     * lastattempt
     *
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    public function lastattempt($type) {

        $attempts = "{$type}attempts";
        $lastattempt = "last{$type}attempt";
        $lastattemptid = "last{$type}attemptid";

        if (is_null($this->TC->$lastattempt)) {

            if ($this->attempts($type)) {

                if ($this->TC->get_taskid()) {
                    // most recent attempt is first
                    $this->TC->$lastattempt = reset($this->TC->$attempts);
                } else {
                    // most recent attempt is last
                    $this->TC->$lastattempt = end($this->TC->$attempts);
                }
            }
        }
        return $this->$lastattemptid();
    }

    /**
     * conditions
     *
     * @uses $DB
     * @param xxx $conditiontype (optional, default=0)
     * @param xxx $taskid (optional, default=0)
     * @param xxx $allgroups (optional, default=0)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function conditions($conditiontype=0, $taskid=0, $allgroups=0) {
        global $DB;
        static $groups;

        if (! isset($groups)) {
            // get list of groups (if any) for this user
            if (empty($this->TC->coursemodule->groupmembersonly) || empty($this->TC->coursemodule->groupingid)) {
                $groups = $allgroups;
            } else if ($this->TC->can('mod/taskchain:manage') || $this->TC->can('moodle/site:accessallgroups')) {
                $groups = $allgroups;
            } else {
                // groups in this course/grouping to which this user belongs
                if ($groups = groups_get_all_groups($this->TC->course->id, $this->TC->userid, $this->TC->coursemodule->groupingid)) {
                    $groups = implode(',', array_keys($groups));
                }
            }
        }

        if (empty($taskid)) {
            $taskid = $this->TC->get_taskid();
        }

        switch ($conditiontype) {
            case self::CONDITIONTYPE_PRE:  $conditions = &$this->TC->cache_preconditions; break;
            case self::CONDITIONTYPE_POST: $conditions = &$this->TC->cache_postconditions; break;
            default: $conditions[$taskid] = array(); // invalid $conditiontype - shouldn't happen !!
        }

        if (! isset($conditions[$taskid])) {
            $select = "taskid=$taskid AND conditiontype=$conditiontype";
            if ($groups) {
                // restrict conditions to those for groups in this course/grouping to which this user belongs
                $select .= ' AND groupid IN (0,'.$groups.')';
            } else if ($groups===0) {
                // only select conditions which apply to any and all groups
                $select .= ' AND groupid=0';
            }
            // conditionscore < 0 : the minimum score at which this condition is satisfied
            // conditionscore > 0 : the maximum score at which this condition is satisfied
            // The post-conditions will be ordered by conditionscore:
            // -100 (highest min) ... (lowest min) 0 (lowest max) ... (highest max) 100
            if (! $conditions[$taskid] = $DB->get_records_select('taskchain_conditions', $select, null, 'conditiontaskid,sortorder,conditionscore,attemptcount,attemptduration,attemptdelay')) {
                $conditions[$taskid] = array();
            }
            // store absolute values for settings which can be negative
            foreach ($conditions[$taskid] as $condition) {
                $conditions[$taskid][$condition->id]->abs_conditionscore = abs($condition->conditionscore);
                $conditions[$taskid][$condition->id]->abs_attemptcount = abs($condition->attemptcount);
                $conditions[$taskid][$condition->id]->abs_attemptduration = abs($condition->attemptduration);
                $conditions[$taskid][$condition->id]->abs_attemptdelay = abs($condition->attemptdelay);
            }
        }

        return $conditions[$taskid];
    }

    /**
     * cache_taskattempts
     *
     * @uses $DB
     * @param xxx $taskid
     * @return xxx
     * @todo Finish documenting this function
     */
    public function cache_taskattempts($taskid) {
        global $DB;
        if (! isset($this->TC->cache_taskattempts[$taskid])) {
            $cnumber = $this->TC->get_cnumber();
            if (empty($cnumber) || $cnumber < 0) {
                $this->TC->cache_taskattempts[$taskid] = false;
            } else {
                // get attempts at $taskid in this cnumber
                $select = 'taskid=? AND userid=? AND cnumber=?';
                $params = array($taskid, $this->TC->userid, $cnumber);
                $this->TC->cache_taskattempts[$taskid] = $DB->get_records_select('taskchain_task_attempts', $select, $params, 'resumestart DESC');
            }
            $this->TC->cache_taskattemptsusort[$taskid] = 'time_desc';
        }
        if (empty($this->TC->cache_taskattempts[$taskid])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * available_tasks
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function available_tasks() {
        if (is_null($this->TC->availabletaskids)) {

            // initialize array of ids of tasks which satisfy preconditions
            $this->TC->availabletaskids = array();
            $this->TC->countavailabletaskids = 0;

            // get tasks, if any, in this chain
            if ($this->TC->get_tasks()) {
                $previoustaskid = 0;
                foreach ($this->TC->tasks as $task) {
                    $ok = $this->TC->check_conditions(taskchain::CONDITIONTYPE_PRE, $task->id, $previoustaskid);
                    if ($ok) {
                        // all preconditions were satisfied, so store task id
                        $previoustaskid = $task->id;
                        $this->TC->availabletaskids[] = $task->id;
                        $this->TC->countavailabletaskids++;
                        // store the first (by sort order) available taskid
                        // (used when post-condition specifies MENUNEXTONE or MENUALLONE)
                        if (! $this->TC->availabletaskid && ! $this->cache_taskattempts($task->id)) {
                            $this->TC->availabletaskid = $task->id;
                        }
                    }
                }
            }
        }

        return $this->TC->countavailabletaskids;
    }

    /**
     * available_task
     *
     * @param xxx $taskid
     * @return xxx
     * @todo Finish documenting this function
     */
    public function available_task($taskid) {
        if (! isset($this->TC->cache_available_task[$taskid])) {
            $nexttaskid = $this->TC->check_conditions(taskchain::CONDITIONTYPE_POST, $taskid, 0);
            $this->TC->cache_available_task[$taskid] = $nexttaskid;
        }
        return $this->TC->cache_available_task[$taskid];
    }

    /**
     * userlist
     *
     * @uses $CFG
     * @return xxx
     * @todo Finish documenting this function
     */
    function userlist() {
        global $CFG;

        static $userlist = array();

        if (count($userlist)) {
            return $userlist;
        }

        $str = (object)array(
            'groups'   => get_string('groups'),
            'students' => get_string('existingstudents'),
            'managers' => get_string('coursemanager', 'admin'),
            'others'   => get_string('other')
        );

        // get all users who have ever attempted this TaskChain
        $users = $this->users(true);

        if ($users) {
            $userlist[$str->groups]['users'] = get_string('allusers', 'mod_taskchain').' ('.count($users).')';
        } else {
            // no users with attempts, but we want to force the "groups" to the top of the drop down list
            // so we add a dummy option here (to create the option group), and then remove the dummy later
            $userlist[$str->groups]['dummy'] = 'dummy';
        }

        // keep a running total of students and managers with grades
        $count_participants = 0;

        // get teachers and enrolled students
        $managers = $this->managers();
        $students = $this->students();

        // current students
        if ($students) {
            $count = 0;
            foreach ($students as $user) {
                // exclude mangers
                if (empty($managers[$user->id])) {
                    $userlist[$str->students]["$user->id"] = fullname($user);
                    unset($users[$user->id]);
                    $count++;
                }
            }
            if ($count) {
                $userlist[$str->groups]['students'] = $str->students." ($count)";
                $count_participants += $count;
            }
        }

        // managers (teachers, course-creators, Moodle admins)
        if ($managers) {
            $count = 0;
            foreach ($managers as $user) {
                // only include managers who have attempted some of the tasks
                if (isset($users[$user->id])) {
                    $userlist[$str->managers]["$user->id"] = fullname($user);
                    unset($users[$user->id]);
                    $count++;
                }
            }
            if ($count) {
                $userlist[$str->groups]['managers'] = $str->managers." ($count)";
                $count_participants += $count;
            }
        }

        if ($count_participants) {
            $userlist[$str->groups]['participants'] = get_string('allparticipants'). " ($count_participants)";
        }

        // groupings
        if ($g = $this->all_groupings()) {
            foreach ($g as $gid => $grouping) {
                if ($count = $this->count_grouping_members($gid)) {
                    $userlist[$str->groups]["grouping$gid"] = get_string('grouping', 'group').': '.format_string($grouping->name).' ('.$count.')';
                }
            }
        }

        // groups
        if ($g = $this->all_groups()) {
            foreach ($g as $gid => $group) {
                if ($count = $this->count_group_members($gid)) {
                    $userlist[$str->groups]["group$gid"] = get_string('group').': '.format_string($group->name).' ('.$count.')';
                }
            }
        }

        // remaining $users are probably former students
        if ($users) {
            $count = 0;
            foreach ($users as $user) {
                $userlist[$str->others]["$user->id"] = fullname($user);
                unset($users[$user->id]);
                $count++;
            }
            if ($count) {
                $userlist[$str->groups]['others'] = $str->others." ($count)";
            }
        } else {
            unset($userlist[$str->groups]['dummy']);
        }

        return $userlist;
    }

    /**
     * managers
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function managers() {
        static $managers = null;
        if (is_null($managers)) {
            $groupids = $this->groupids(false);
            $managers = users_by_capability($this->TC->course->context, 'mod/taskchain:grade', 'u.id,u.firstname,u.lastname', 'u.lastname,u.firstname', '', '', $groupids);
        }
        return $managers;
    }

    /**
     * students
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function students() {
        static $students = null;
        if (is_null($students)) {
            $groupids = $this->groupids(false);
            $students = users_by_capability($this->TC->course->context, 'mod/taskchain:attempt', 'u.id,u.firstname,u.lastname', 'u.lastname,u.firstname', '', '', $groupids);
        }
        return $students;
    }

    /**
     * users
     *
     * @uses $DB
     * @param xxx $returnnames (optional, default=false)
     * @return xxx
     * @todo Finish documenting this function
     */
    function users($returnnames=false) {
        global $DB;

        if ($returnnames) {
            $fields = 'DISTINCT userid';
        } else {
            $fields = 'DISTINCT userid AS id, userid';
        }
        $sql = ''
            ."SELECT $fields FROM {taskchain_chain_grades} "
            ."WHERE parenttype = 0 AND parentid IN ("
                ."SELECT id FROM {taskchain} "
                ."WHERE course = {$this->TC->course->id}"
            .')'
        ;
        if ($returnnames) {
            $sql = ''
                ."SELECT u.id, u.firstname, u.lastname FROM {user} u "
                ."WHERE u.id IN ($sql) ORDER BY u.lastname,u.firstname"
            ;
        }
        return $DB->get_records_sql($sql);
    }

    /**
     * groupids
     *
     * @param xxx $return_array (optional, default=true)
     * @return xxx
     * @todo Finish documenting this function
     */
    function groupids($return_array=true) {
        if ($groups = $this->all_groups()) {
            $groupids = array_keys($groups);
        } else {
            $groupids = array();
        }
        if ($return_array) {
            return $groupids;
        }
        // prepare for users_by_capability()
        switch (count($groupids)) {
            case 0: $groupids = ''; break;
            case 1: $groupids = array_pop($groupids); break;
        }
        return $groupids;
    }

    /**
     * all_groups_sql
     *
     * @param xxx $AND (optional, default=' AND ')
     * @param xxx $field (optional, default='userid')
     * @return xxx
     * @todo Finish documenting this function
     */
    function all_groups_sql($AND=' AND ', $field='userid') {
        if ($groupids = implode(',', $this->groupids())) {
            return $AND.$field.' IN (SELECT DISTINCT gm.userid FROM {groups_members} gm WHERE gm.groupid IN ('.$groupids.'))';
        } else {
            return '';
        }
    }

    /**
     * all_groups
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    function all_groups() {
        if (empty($this->TC->course)) {
            return array(); // shouldn't happen !!
        }

        if ($this->TC->can->accessallgroups()) {
            // user can see any groups
            $userid = 0;
        } else {
            // user can only see own group(s) e.g. non-editing teacher
            $userid = $this->userid();
        }

        if (empty($this->TC->coursemodule->groupingid)) {
            $groupingid = 0;
        } else {
            $groupingid = $this->TC->coursemodule->groupingid;
        }

        return groups_get_all_groups($this->TC->course->id, $userid, $groupingid);
    }

    /**
     * all_groupings
     *
     * @uses $CFG
     * @uses $DB
     * @return xxx
     * @todo Finish documenting this function
     */
    function all_groupings() {
        global $CFG, $DB;

        // groupings are ignored when not enabled
        if (empty($CFG->enablegroupings) || empty($this->TC->course)) {
            return false;
        }

        $ids = $this->groupids();
        if ($ids = implode(',', $ids)) {
            $select = 'id IN (SELECT groupingid FROM {groupings_groups} WHERE groupid IN ('.$ids. '))';
        } else {
            $select = 'courseid='.$this->TC->course->id;
        }

        return $DB->get_records_select('groupings', $select, null, 'name ASC');
    }

    /**
     * group_userids
     *
     * @param integer $groupid
     * @return array of ids from mdl_user table
     */
    public function group_userids($groupid) {
        if ($members = groups_get_members($groupid, 'u.id,u.username', 'u.id')) {
            return array_keys($members);
        } else {
            return array();
        }
    }

    /**
     * count_group_members
     *
     * @param integer $groupid
     * @return integer number of unique members in this group
     */
    public function count_group_members($groupid) {
        if ($members = groups_get_members($groupid, 'u.id,u.username', 'u.id')) {
            return count($members);
        } else {
            return 0;
        }
    }

    /**
     * grouping_userids
     *
     * @param integer $groupingid
     * @return array of ids from mdl_user table
     */
    public function grouping_userids($groupingid) {
        global $DB;
        list($sql, $params) = $this->grouping_members_sql($groupingid, 'u.id, u.username', 'u.id');
        if ($members = $DB->get_records_sql($sql, $params)) {
            return array_keys($members);
        } else {
            return array();
        }
    }

    /**
     * count_grouping_members
     *
     * @param integer $groupingid
     * @return integer number of unique members in this grouping
     */
    public function count_grouping_members($groupingid) {
        global $DB;
        list($sql, $params) = $this->grouping_members_sql($groupingid, 'COUNT(*)', '');
        return $DB->get_field_sql($sql, $params);
    }

    /**
     * grouping_members_sql
     *
     * Note that the SQL used by groups_get_grouping_members() function, in "lib/grouplib.php",
     * returns duplicate lines if a user is in more than one group within a grouping
     *
     * @param integer $groupingid
     * @param string  $fields comma-separated list of fields
     * @param string  $sort   comma-separated list of fields (can be empty)
     * @return xxx
     */
    public function grouping_members_sql($groupingid, $fields, $sort) {
        $sql = 'SELECT DISTINCT gm.userid '.
               'FROM {groups_members} gm '.
               'JOIN {groupings_groups} gg ON gm.groupid = gg.groupid '.
               'WHERE gg.groupingid = ?';
        $sql = "SELECT $fields FROM {user} u WHERE u.id IN ($sql)".($sort=='' ? '' : " ORDER BY $sort");
        return array($sql, array($groupingid));
    }

    /**
     * userfilter
     *
     * @uses $CFG
     * @param xxx $AND (optional, default=' AND ')
     * @param xxx $field (optional, default='userid')
     * @return xxx
     * @todo Finish documenting this function
     */
    function userfilter($AND=' AND ', $field='userid') {
        global $CFG;

        $userlist = optional_param('userlist', get_user_preferences('userlist', 'users'), PARAM_ALPHANUM);
        // group, grouping, users, participants, managers, students, others, specific userid

        // check for groups and groupings
        $gid = 0;
        if (substr($userlist, 0, 5)=='group') {
            if (substr($userlist, 5, 3)=='ing') {
                $g = $this->all_groupings();
                $id = intval(substr($userlist, 8));
                $userlist = 'grouping';
            } else {
                $g = $this->all_groups();
                $id = intval(substr($userlist, 5));
                $userlist = 'group';
            }
            if ($g && isset($g[$id])) {
                $gid = $id; // id is valid
            } else {
                $userlist = 'users'; // default
            }
        }

        $userids = array();
        switch ($userlist) {

            case 'users':
                // anyone who has ever attempted TaskChains in this course
                if ($users = $this->users()) {
                    $userids = array_keys($users);
                }
                break;

            case 'grouping':
                // grouping members
                if ($gid) {
                    $userids = $this->grouping_userids($gid);
                }
                break;

            case 'group':
                // group members
                if ($gid) {
                    $userids = $this->group_userids($gid);
                }
                break;

            case 'participants':
                // all students + managers who have attempted a task
                if ($users = $this->users()) {
                    if ($students = $this->students()) {
                        $userids = array_keys($students);
                    }
                    if ($managers = $this->managers()) {
                        $userids = array_merge(
                            $userids , array_intersect(array_keys($managers), array_keys($users))
                        );
                    }
                }
                break;

            case 'managers':
                // all course managers who have attempted a task
                if ($users = $this->users()) {
                    if ($managers = $this->managers()) {
                        $userids = array_intersect(array_keys($managers), array_keys($users));
                    }
                }
                break;

            case 'students':
                // anyone currently allowed to attempt this TaskChain who is not a manager
                if ($students = $this->students()) {
                    $userids = array_keys($students);
                    if ($managers = $this->managers()) {
                        $userids = array_diff($userids, array_keys($managers));
                    }
                }
                break;

            case 'others':
                // anyone who has attempted the task, but is not currently a student or manager
                if ($users = $this->users()) {
                    $userids = array_keys($users);
                    if ($students = $this->students()) {
                        $userids = array_diff($userids, array_keys($students));
                    }
                    if ($managers = $this->managers()) {
                        $userids = array_diff($userids, array_keys($managers));
                    }
                }
                break;

            default: // specific user selected by teacher
                if (is_numeric($userlist)) {
                    $userids[] = $userlist;
                }
        } // end switch

        sort($userids);
        $userids = implode(',', array_unique($userids));

        if ($userids=='') {
            return $AND.'userid > 0'; // no users
        } else if (strpos($userids, ',')===false) {
            return $AND."$field = $userids"; // one user
        } else {
            return $AND."$field IN ($userids)"; // many users
        }
    }
}
