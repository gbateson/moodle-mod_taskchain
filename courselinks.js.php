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
require_once($CFG->dirroot.'/mod/taskchain/locallib.php');
require_once($CFG->dirroot.'/lib/gradelib.php');
if (file_exists($CFG->dirroot.'/course/format/lib.php')) {
    require_once($CFG->dirroot.'/course/lib.php');
}

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
    global $CFG, $DB, $PAGE, $USER;

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
    if (! $course->context = mod_taskchain::context(CONTEXT_COURSE, $id)) {
        return false;
    }

    $numsections = 0;
    if ($course->format=='topics' || $course->format=='weekly') {
        if (function_exists('course_get_format')) {
            // Moodle >= 2.3
            $options = course_get_format($course)->get_format_options();
            $numsections = $options['numsections'];
        } else {
            // Moodle <= 2.2
            $numsections = $course->numsections;
        }
    }

    if ($sectionnum = optional_param('section', 0, PARAM_INT)) {
        // specific section requested
    } else if (isset($_SERVER['HTTP_REFERER']) && preg_match('/section=([0-9]+)/', $_SERVER['HTTP_REFERER'], $matches)) {
        $sectionnum = $matches[1];
    } else if (isset($USER->display[$course->id])) {
        // Moodle <= 2.2
        $sectionnum = $USER->display[$course->id];
    } else {
        // Moodle >= 2.3
        $name = 'taskchain_navigation_'.$course->id;
        $sectionnum = get_user_preferences($name, 0);
    }

    if ($sectionnum < 0 || $sectionnum > $numsections) {
        return false;
    }

    // set page content - needed by coursemodule_visible_for_user()
    $PAGE->set_context($course->context);

    $show_modules = optional_param('mods', '', PARAM_CLEAN);
    $show_modules = preg_replace('/[^a-z,]/', '', strtolower($show_modules));
    if ($show_modules) {
        if ($show_modules=='all') {
            // do nothing
        } else {
            $show_modules = explode(',', $show_modules); // convert to array
            $show_modules = array_filter($show_modules); // remove blanks
        }
    }

    if (! $modinfo = get_fast_modinfo($course->id)) {
        return false; // no activities ?!
    }

    if (has_capability('moodle/grade:viewall', $course->context)) {
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

        // get the current group for this course
        $groupid = groups_get_course_group($course);

        // get groupmode: 0=NOGROUPS, 1=VISIBLEGROUPS, 2=SEPARATEGROUPS
        $groupmode = groups_get_course_groupmode($course);

        if ($groupmode==NOGROUPS || $groupmode==VISIBLEGROUPS || has_capability('moodle/site:accessallgroups', $course->context)) {
            $accessallgroups = true;
        } else {
            $accessallgroups = false;
        }

        if ($groupid==0 && $accessallgroups) {
            // user can access all student users in the course
            $select = 'ra.id, ra.userid';
            $from   = '{role_assignments} ra JOIN {role} r ON ra.roleid = r.id';
            $where  = 'ra.contextid = :contextid AND r.shortname = :shortname';
            $params = array('shortname' => 'student', 'contextid' => $course->context->id);
            if ($records = $DB->get_records_sql_menu("SELECT $select FROM $from WHERE $where", $params)) {
                $userids = array_values($records);
            }

        } else {
            // user can only see members in groups to which (s)he belongs
            // (e.g. non-editing teacher when groups are separate)
            $groupids = 'SELECT id FROM {groups} WHERE courseid = :courseid';
            $params = array('courseid' => $course->id);
            if ($groupid) {
                // a specified group
                $groupids .= ' AND id = :groupid';
                $params['groupid'] = $groupid;
            }
            if ($accessallgroups==false) {
                // user can only see members in groups to which (s)he belongs
                // (e.g. non-editing teacher when groups are separate)
                $groupids = 'SELECT groupid FROM {groups_members} WHERE userid = :userid AND groupid IN ($groupids)';
                $params['userid'] = $USER->id;
            }
            if ($records = $DB->get_records_select_menu('groups_members', 'groupid IN ('.$groupids.')', $params, '', 'id,userid')) {
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

    // resource mods are always hidden
    $hide_modules = array('label', 'book', 'folder', 'imscp', 'page', 'resource', 'url');
    // (plugin_supports('mod', $cm->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER)==MOD_ARCHETYPE_RESOURCE);

    $result = false;

    foreach ($modinfo->cms as $cmid => $cm) {
        $show_grade = false;
        $zero_grade = false;

        if ($sectionnum==0 || $cm->sectionnum==0 || $sectionnum==$cm->sectionnum) {
            $show_grade = true;
            if (in_array($cm->modname, $hide_modules)) {
                $zero_grade = true;
            } else if ($show_modules==='all') {
                // do nothing
            } else if (in_array($cm->modname, $show_modules)) {
                // do nothing
            } else {
                $zero_grade = true;
            }
        }

        if ($show_grade) {
            if (class_exists('\core_availability\info_module')) {
                // Moodle >= 2.7
                $visible_for_user = \core_availability\info_module::is_user_visible($cm);
            } else {
                // Moodle <= 2.6
                $visible_for_user = coursemodule_visible_for_user($cm);
            }
            if ($showaverages || $zero_grade || $visible_for_user==false) {
                 // do nothing
            } else {
                // check activity-specific capabilities
                $assessed = '';
                $capability = '';
                switch ($cm->modname) {
                    case 'assignment':  $capability = 'submit';      break;
                    case 'attendance':  $capability = 'view';        break;
                    case 'data':        $capability = 'writeentry';  $assessed = 'assessed'; break;
                    case 'forum':       $capability = 'replypost';   $assessed = 'assessed'; break;
                    case 'glossary':    $capability = 'write';       $assessed = 'assessed'; break;
                    case 'quiz':        $capability = 'attempt';     break;
                    case 'hotpot':      $capability = 'attempt';     break;
                    case 'taskchain':   $capability = 'attempt';     break;
                    case 'survey':      $capability = 'participate'; break;
                    case 'workshop':    $capability = 'participate'; break;
                    case 'lesson':      // do nothing
                    case 'scorm':       // do nothing
                    default:            // do nothing
                }

                if ($capability) {
                    if (! has_capability("mod/$cm->modname:$capability", mod_taskchain::context(CONTEXT_MODULE, $cmid))) {
                        $zero_grade = true;
                    }
                }

                if ($assessed) {
                    if (! $DB->get_field($cm->modname, $assessed, array('id' => $cm->instance))) {
                        $zero_grade = true;
                    }
                }
            }
            if ($visible_for_user) {
                // get this user's grade for this activity
                if (! $zero_grade) {
                    $grades = grade_get_grades($course->id, 'mod', $cm->modname, $cm->instance, $userids);
                    if (empty($grades->items[0]->grademax) || floatval($grades->items[0]->grademax)==0) {
                        $zero_grade = true;
                    }
                }
                if ($zero_grade) {
                    echo 'activityzerogrades["module-'.$cmid.'"] = 1;'."\n";
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
                        echo 'activitygrades["module-'.$cmid.'"] = "'.$percent.'";'."\n";
                    }
                }
            } else {
                echo 'activityunavailables["module-'.$cmid.'"] = 1;'."\n";
            }
            $result = true;
        }
    }
    return $result;
}
?>
var activitygrades = new Array(); // attempted
var activityzerogrades = new Array(); // zero-grade
var activityunavailables = new Array(); // not available
<?php
if (print_courselinks()) {
?>
function modify_taskchain_links() {

    // this is the expected DOM that we are trying to manipulate
    // LI.activity <================================ activities[i]
    //   DIV (position: relative)
    //     DIV.mod-indent-outer
    //       DIV
    //         DIV.activityinstance
    //           A.taskchaingrade <===================== links[ii]
    //             DIV.taskchain[high|med|low]grade (float: right)
    //             IMG.activityicon
    //             SPAN.instancename
    //         SPAN.actions
    //           IMG (position: absolute) <=== completion checkbox

    var m = navigator.userAgent.match(new RegExp('MSIE (\\d+)'));
    if (m && m[1]<=7) {
        // IE7 and earlier
        var classAttribute = 'className';
    } else {
        var classAttribute = 'class';
    }
    var isediting = <?php echo ($PAGE->user_is_editing() ? 'true' : 'false') ?>;
    var showgrades = <?php echo optional_param('showgrades', 0, PARAM_INT) ?>;
    var displayasblock = <?php echo optional_param('displayasblock', 0, PARAM_INT) ?>;

    var activities = document.getElementsByTagName('li');
    if (activities) {
        var i_max = activities.length;
    } else {
        var i_max = 0;
    }

    // checkbox width may be required to prevent grades
    // from overlapping completion checkboxes, if any
    var checkboxWidth = 0;
    for (var i=0; i<i_max; i++) {
        var obj = activities[i].getElementsByTagName('span');
        if (obj) {
            var ii_max = obj.length;
        } else {
            var ii_max = 0;
        }
        for (var ii=0; ii<ii_max; ii++) {
            if (obj[ii].getAttribute(classAttribute)=='actions') {
                checkboxWidth += obj[ii].offsetWidth;
                break;
            }
        }
        if (checkboxWidth) {
            break;
        }
    }

    for (var i=0; i<i_max; i++) {
        if (typeof(activities[i].id) != 'string' || activities[i].id.substr(0, 7) != 'module-') {
            continue; // not an item in a list of activities
        }

        var myClassName = activities[i].getAttribute(classAttribute);

        if (typeof(myClassName)!='string' || myClassName.substr(0, 8)!='activity') {
            continue; // not an Moodle activity
        }

        // skip labels and resources
        if (myClassName.substr(9, 5)=='label') {
            continue;
        }
        if (myClassName.substr(9, 4)=='book') {
            continue;
        }
        if (myClassName.substr(9, 4)=='book') {
            continue;
        }
        if (myClassName.substr(9, 6)=='folder') {
            continue;
        }
        if (myClassName.substr(9, 5)=='imscp') {
            continue;
        }
        if (myClassName.substr(9, 4)=='page') {
            continue;
        }
        if (myClassName.substr(9, 8)=='resource') {
            continue;
        }
        if (myClassName.substr(9, 3)=='url') {
            continue;
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

            // hide "spacer" images, and replace with left-margin CSS
            var indentWidth = 0;
            var obj = links[ii];
            while (obj = obj.previousSibling) {
                if (obj.tagName=='IMG' && obj.getAttribute(classAttribute)=='spacer') {
                    indentWidth += (parseInt(obj.offsetWidth) || 0);
                    obj.style.display = 'none';
                }
            }
            if (indentWidth) {
                indentWidth += (parseInt(links[ii].style.marginLeft) || 0);
                links[ii].style.marginLeft = indentWidth + 'px';
            }
            if (isediting==false) {

                // get width, w, of widest ancestor
                var w = 0;
                var obj = links[ii];
                while (obj = obj.parentNode) {
                    w = Math.max(w, (parseInt(obj.offsetWidth) || 0));
                    if (window.getComputedStyle) {
                        var style = getComputedStyle(obj);
                    } else {
                        var style = obj.currentStyle // IE
                    }
                    indentWidth += (parseInt(style.marginLeft) || 0);
                    indentWidth += (parseInt(style.paddingLeft) || 0);
                    if (obj.tagName=='LI') {
                        break;
                    }
                }

                // convert <a> to block element
                links[ii].style.display = 'block';

                // expand <a> to full width
                if (w) {
                    if (checkboxWidth) {
                        w -= checkboxWidth;
                        w -= links[ii].firstChild.offsetWidth; // grade width
                    }
                    if (indentWidth) {
                        w -= indentWidth;
                    }
                    links[ii].style.width = w + 'px';
                    links[ii].style.maxWidth = '95%';
                }
            }
        }
    }
    obj = null;
    links = null;
    activities = null;
}

if (window.addEventListener) {
    window.addEventListener('load', modify_taskchain_links, false);
} else if (window.attachEvent) {
    window.attachEvent('onload', modify_taskchain_links);
} else if (typeof(window.onload)=='function') {
    window.onload_taskchain_links = onload;
    window.onload = new Function('onload_taskchain_links();modify_taskchain_links();');
} else {
    window.onload = modify_taskchain_links;
}

<?php
} // end if print_courselinks()
