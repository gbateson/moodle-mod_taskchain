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
 * mod/taskchain/courselinks.js.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

if (! headers_sent()) {
    header('Content-type: application/javascript');
}

/** Include required files */
require_once('../../config.php');
require_once($CFG->dirroot.'/mod/taskchain/lib.php');
require_once($CFG->dirroot.'/lib/gradelib.php');

/**
 * print_courselinks
 *
 * @uses $CFG
 * @uses $DB
 * @uses $USER
 * @return xxx
 * @todo Finish documenting this function
 */
function print_courselinks() {
    global $CFG, $DB, $USER;

    // sanity check
    if (! isset($USER->id)) {
        return false;
    }

    // get optional parameters
    if (! $id = optional_param('id', 0, PARAM_INT)) {
        return false;
    }
    if (! $course = $DB->get_record('course', array('id'=>$id))) {
        return false;
    }
    if (! $course->context = get_context_instance(CONTEXT_COURSE, $id)) {
        return false;
    }

    $module_names = optional_param('mods', 'taskchain', PARAM_CLEAN);
    $module_names = preg_replace('/[^a-z,]/', '', strtolower($module_names));
    if ($module_names) {
        $module_names = explode(',', $module_names); // convert to array
        $module_names = array_filter($module_names); // remove blanks
    }

    if (isset($USER->display[$course->id])) {
        $section = $USER->display[$course->id];
    } else {
        $section = 0;
    }

    if (! $modinfo = unserialize($course->modinfo)) {
        return false; // no activities ?!
    }

s    if (has_capability('moodle/grade:viewall', $course->context)) {
        // teacher (or admin)
        $showaverages = optional_param('showaverages', 0, PARAM_INT);
    } else {
        // student
        if (empty($course->showgrades)) {
            return false; // this course doesn't show grades to students
        }
        if (! has_capability('moodle/grade:view', $course->context)) {
            return false; // user cannot view grade items - unusual !!
        }
        $showaverages = false;
    }

    if ($showaverages) {
        // a teacher - or at least someone who can view all users' grades
        $userids = array();

        // get groupmode: 0=NOGROUPS, 1=VISIBLEGROUPS, 2=SEPARATEGROUPS
        $groupmode = groups_get_course_groupmode($course);

        if ($groupmode==NOGROUPS || $groupmode==VISIBLEGROUPS || has_capability('moodle/site:accessallgroups', $course->context)) {
            // user can access all student users in the course
            $roleid = "SELECT id FROM {$CFG->prefix}role WHERE shortname='student'";
            if ($records = get_records_select_menu('role_assignments', "roleid=($roleid) AND contextid=".$course->context->id, '', 'id,userid')) {
                $userids = array_values($records);
            }

        } else {
            // user can only see members in groups to which (s)he belongs
            // (e.g. non-editing teacher when groups are separate)
            $groupids = "SELECT id FROM {$CFG->prefix}groups WHERE courseid=$course->id";
            $groupids = "SELECT groupid FROM {$CFG->prefix}group_members WHERE userid=$USER->id AND groupid IN ($groupids)";
            if ($records = get_records_select_menu('groups_members', "groupid IN ($groupids)", '', 'id,userid')) {
                $userids = array_values($records);
            }
        }
    } else {
        // show only the current user's grades (e.g. student)
        $userids = array($USER->id);
    }

    if (empty($userids)) {
        return false; // no users in groups ?!
    }

    $result = false;

    foreach ($modinfo as $cmid => $cm) {
        $show_grade = false;
        $zero_grade = false;
        if ($section==0 || $cm->section==0 || $section==$cm->section) {
            $show_grade = true;
            if ($cm->mod=='label' || $cm->mod=='resource') {
                $zero_grade = true;
            } else if ($module_names && ! in_array($cm->mod, $module_names)) {
                $zero_grade = true;
            }
        }

        // a quick fix so that $cm is set up like an actual course_modules record
        // required for get_context_instance (in lib/accesslib.php)
        $cm->course = $course->id;
        $cm->instance = $cm->id;
        $cm->id = $cm->cm;

        if ($show_grade) {
            if ($visible_for_user = coursemodule_visible_for_user($cm)) {
                // module specific checks
                switch ($cm->mod) {
                    case 'assignment':
                        $capability = 'submit';
                        break;
                    case 'attforblock': // attendance
                        $capability = 'view';
                        break;
                    case 'data':
                        $capability = 'viewentry';
                        break;
                        break;
                    case 'task':
                    case 'hotpot':
                    case 'taskchain':
                        $capability = 'attempt';
                        break;
                    case 'scorm':
                    case 'survey':
                        $capability = 'participate';
                        break;
                    case 'glossary':
                    case 'lesson':
                    default:
                        $capability = '';
                }
                if ($capability) {
                    $visible_for_user = has_capability("mod/$cm->mod:$capability", get_context_instance(CONTEXT_MODULE, $cmid));
                }
            }
            if ($visible_for_user) {
                // get this user's grade for this activity
                if (! $zero_grade) {
                    $grades = grade_get_grades($course->id, 'mod', $cm->mod, $cm->instance, $userids);
                    if (empty($grades->items[0]->grademax) || floatval($grades->items[0]->grademax)==0) {
                        $zero_grade = true;
                    }
                }
                if ($zero_grade) {
                    print 'activityzerogrades["module-'.$cmid.'"] = 1;'."\n";
                } else {
                    $count = 0;
                    $total = 0;
                    foreach ($userids as $userid) {
                        if (isset($grades->items[0]->grades[$userid]->grade))  {
                            $percent = $grades->items[0]->grades[$userid]->grade;
                            if ($grades->items[0]->grademax > 0) {
                                if (isset($grades->items[0]->grademin)) {
                                    $percent -= $grades->items[0]->grademin;
                                }
                                $percent /= $grades->items[0]->grademax;
                            }
                            $count ++;
                            $total += $percent;
                        }
                    }
                    if ($count) {
                        $percent = round($total/$count * 100);
                        if ($showaverages) {
                            $percent = "$percent% ($count)";
                        }
                        print 'activitygrades["module-'.$cmid.'"] = "'.$percent.'";'."\n";
                    }
                }
            } else {
                print 'activityunavailables["module-'.$cmid.'"] = 1;'."\n";
            }
            $result = true;
        }
    }
    return $result;
}
// start javascript content
print '//<![CD'.'ATA['."\n";
?>
var activitygrades = new Array(); // attempted
var activityzerogrades = new Array(); // zero-grade
var activityunavailables = new Array(); // not available
<?php
if (print_courselinks()) {
?>
function modify_taskchain_links() {

    var m = navigator.userAgent.match(new RegExp('MSIE (\\d+)'));
    if (m && m[1]<=7) {
        // IE7 and earlier
        var classAttribute = 'className';
    } else {
        var classAttribute = 'class';
    }
    var showgrades = <?php print optional_param('showgrades', 0, PARAM_INT) ?>;
    var displayasblock = <?php print optional_param('displayasblock', 0, PARAM_INT) ?>;

    var activities = document.getElementsByTagName('li');
    if (activities) {
        var i_max = activities.length;
    } else {
        var i_max = 0;
    }
    for (var i=0; i<i_max; i++) {
        if (typeof(activities[i].id)!='string' || activities[i].id.substr(0, 7)!='module-') {
            continue; // not an item in a list of activities
        }

        var myClassName = activities[i].getAttribute(classAttribute);

        if (typeof(myClassName)!='string' || myClassName.substr(0, 8)!='activity') {
            continue; // not an Moodle activity
        }

        if (myClassName.substr(9)=='label' || myClassName.substr(9)=='resource') {
            continue; // skip labels and resources
        }

        var links = activities[i].getElementsByTagName('a');
        if (links) {
            var ii_max = links.length;
        } else {
            var ii_max = 0;
        }
        for (var ii=ii_max-1; ii>=0; ii--) {
            if (typeof(links[ii].href)=='string') {
                var m = links[ii].href.match(new RegExp('mod/[a-z0-9]+/view\\.php\\?id=([0-9]+)'))
                if (m && m[1]) {
                    break;
                }
            }
        }
        if (ii_max==0 || ii==ii_max) {
            continue; // could not find link to view activity
        }
        if (activityunavailables[activities[i].id]) {
            var grade = '';
            var myClassName = 'dimmed';
        } else if (activityzerogrades[activities[i].id]) {
            var grade = '';
            var myClassName = '';
        } else if (typeof(activitygrades[activities[i].id])=='undefined') {
            var grade = '--';
            var myClassName = 'taskchainnograde';
        } else {
            var grade = parseInt(activitygrades[activities[i].id]);
            if (grade>=90) {
                var myClassName = 'taskchainhighgrade';
            } else if (grade>=60) {
                var myClassName = 'taskchainmediumgrade';
            } else {
                var myClassName = 'taskchainlowgrade';
            }
            if (isNaN(activitygrades[activities[i].id])) {
                grade = activitygrades[activities[i].id];
            } else {
                grade = grade + '%';
            }
        }
        if (showgrades && grade) {
            var div = document.createElement('div');
            div.setAttribute(classAttribute, myClassName);

            var txt = document.createTextNode(grade);
            div.appendChild(txt);

            links[ii].insertBefore(div, links[ii].childNodes[0]);
            myClassName = 'taskchaingrade';
        }
        if (myClassName) {
            var str = links[ii].getAttribute(classAttribute);
            if (str) {
                myClassName = str + ' ' + myClassName;
            }
            links[ii].setAttribute(classAttribute, myClassName);
        }
        if (displayasblock) {
            links[ii].style.display = 'block';
        }
    }
    links = null;
    activities = null;
}
if (typeof(window.onload)=='function') {
    window.onload_taskchain_links = onload;
    window.onload = new Function('window.onload_taskchain_links();modify_taskchain_links();');
} else {
    // no previous onload
    window.onload = modify_taskchain_links;
}
<?php
} // end if print_courselinks()

// finish javascript content
print '//]'.']>'."\n";
