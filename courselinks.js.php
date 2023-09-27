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

    $tab = str_repeat(' ', 4);
    $nl = "\n"; // newline

    // sanity check
    if (! isset($USER->id)) {
        return false;
    }

    // use course id to fetch course record and context
    if (! $id = optional_param('id', 0, PARAM_INT)) {
        return false;
    }
    if (! $course = $DB->get_record('course', array('id'=>$id))) {
        return false;
    }
    if (! $course->context = mod_taskchain::context(CONTEXT_COURSE, $id)) {
        return false;
    }

    require_login($course);
    //$PAGE->set_context($course->context);

    $config = (object)array(
        'showgrades' => 0,
        'displayasblock' => 0,
        'lowgrade' => '0',
        'mediumgrade' => '60',
        'highgrade' => '90',
        'gradelinecolor' => '666',
        'gradelinestyle' => 'dashed',
        'gradelinewidth' => '640px'
    );

    $params = array('blockname' => 'taskchain_navigation',
                    'parentcontextid' => $course->context->id);
    if ($instance = $DB->get_record('block_instances', $params)) {
        $instance->config = unserialize(base64_decode($instance->configdata));
        foreach ($config as $name => $value) {
            if (isset($instance->config->$name)) {
                $config->$name = $instance->config->$name;
            }
        }
    }

    // Add the editing switch
    $name = 'isediting';
    $value = ($PAGE->user_is_editing() ? 'true' : 'false');

    echo $nl;
    echo "{$tab}TC.$name = $value;{$nl}";

    // Add other config settings
    foreach ($config as $name => $value) {
        $value = optional_param($name, $value, PARAM_ALPHANUM);
        if ($name == 'gradelinecolor' && preg_match('/^[A-F0-9]+$/', $value)) {
            $value = "#$value";
        }
        if ($name == 'gradelinewidth' && preg_match('/^[0-9]+$/', $value)) {
            $value += 'px';
        }
        echo "{$tab}TC.$name = '$value';{$nl}";
    }

    $numsections = 0;
    if ($course->format=='topics' || $course->format=='weekly') {
        if (function_exists('course_get_format')) {
            $format = course_get_format($course);
            if (method_exists($format, 'get_last_section_number')) {
            	// Moodle >= 3.3
            	$numsections = $format->get_last_section_number();
            } else {
            	// Moodle 2.3 to 3.2
				$options = $format->get_format_options();
				if (isset($options['numsections'])) {
					$numsections = $options['numsections'];
				}
            }
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
                $groupids = 'SELECT groupid FROM {groups_members} WHERE userid = :userid AND groupid IN ('.$groupids.')';
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
                    case 'workshop':    $capability = 'submit';      break;
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
            if ($result == false) {
                echo $nl;
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
                    echo $tab.'TC.activityzerogrades["module-'.$cmid.'"] = 1;'.$nl;
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
                        echo $tab.'TC.activitygrades["module-'.$cmid.'"] = "'.$percent.'";'.$nl;
                    }
                }
            } else {
                echo $tab.'TC.activityunavailables["module-'.$cmid.'"] = 1;'.$nl;
            }
            $result = true;
        }
    }
    return $result;
}
?>
(function() {
    window.TC = {};

    TC.activitygrades = new Array(); // attempted
    TC.activityzerogrades = new Array(); // zero-grade
    TC.activityunavailables = new Array(); // not available
<?php
if (print_courselinks()) {
?>

    TC.modify_taskchain_links = function(){

        document.querySelectorAll("li.activity").forEach(function(li){

            if (li.matches(".label, .book, .file, .folder, .imscp, .page, .resource, .url")) {
                return false; // resources have no score, so ignore them
            }

            var s = "a[href*='/mod/'][href*='/view.php']";
            var a = li.querySelector(".activityname " + s);
            if (a === null) { // Moodle 3.11 and eariler
                a = li.querySelector(".activityinstance " + s);
            }
            if (a === null) {
                return false; // shouldn't happen !!
            }

            // cache the id e.g. module-99
            var id = li.id;

            // Initialize the grade and CSS class
            var grade = "";
            var cssclass = "";

            if (TC.activityunavailables[li.id]) {
                cssclass = "dimmed";
            } else if (TC.activityzerogrades[li.id]) {
                // leave grade and class name blank
            } else if (typeof(TC.activitygrades[li.id]) == "undefined") {
                grade = "--";
                cssclass = "taskchainnograde";
            } else {
                grade = parseInt(TC.activitygrades[li.id]);
                if (grade>=TC.highgrade) {
                    cssclass = "taskchainhighgrade";
                } else if (grade>=TC.mediumgrade) {
                    cssclass = "taskchainmediumgrade";
                } else {
                    cssclass = "taskchainlowgrade";
                }
                if (isNaN(TC.activitygrades[li.id])) {
                    grade = TC.activitygrades[li.id];
                } else {
                    grade = grade + "%";
                }
            }

            if (TC.showgrades && grade) {
                var div = document.createElement("div");
                if (cssclass) {
                    div.classList.add(cssclass);
                }
                div.appendChild(document.createTextNode(grade));
                a.insertBefore(div, a.firstChild);
                a.classList.add("d-block", "taskchaingrade");
                if (TC.displayasblock) {
                    if (TC.gradelinecolor || TC.gradelinestyle) {
                        a.style.setProperty("border-bottom-width", "2px");
                    }
                    if (TC.gradelinecolor) {
                        a.style.setProperty("border-bottom-color", TC.gradelinecolor);
                    }
                    if (TC.gradelinestyle) {
                        a.style.setProperty("border-bottom-style", TC.gradelinestyle);
                    }
                    if (TC.gradelinewidth) {
                        a.style.setProperty("width", "100%");
                        a.style.setProperty("max-width", TC.gradelinewidth);
                    }
                    const p = a.closest(".activity-instance.d-flex");
                    if (p) {
                        p.classList.remove("d-flex");
                        p.parentNode.classList.remove("d-flex");
                    }
                }
            } else if (cssclass) {
                a.classList.add(cssclass);
            }
        });
    };

    if (window.addEventListener) {
        window.addEventListener("load", TC.modify_taskchain_links, false);
    } else if (window.attachEvent) {
        window.attachEvent("onload", TC.modify_taskchain_links);
    }
}());

<?php
} // end if print_courselinks()
