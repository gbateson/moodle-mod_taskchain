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
 * mod/taskchain/locallib/available.php
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
 * taskchain_available
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_available extends taskchain_base {

    /////////////////////////////////////////////////////////
    // static API
    /////////////////////////////////////////////////////////


    /**
     * Returns the localized list of grade method settings for a TaskChain instance
     *
     * @return array
     */
    static public function addtypes_list() {
        return array(
            self::ADDTYPE_AUTO         => get_string('addtypeauto', 'taskchain'),
            self::ADDTYPE_TASKFILE     => get_string('addtypetaskfile', 'taskchain'),
            self::ADDTYPE_TASKCHAIN    => get_string('addtypetaskchain', 'taskchain'),
            self::ADDTYPE_CHAINFILE    => get_string('addtypechainfile', 'taskchain'),
            self::ADDTYPE_CHAINFOLDER  => get_string('addtypechainfolder', 'taskchain'),
            self::ADDTYPE_CHAINFOLDERS => get_string('addtypechainfolders', 'taskchain')
        );
    }

    /**
     * Returns the localized list of allowresume settings for a TaskChain instance
     *
     * @return array
     */
    static public function allowresumes_list() {
        return array (
            self::ALLOWRESUME_NO    => get_string('no'),
            self::ALLOWRESUME_YES   => get_string('yes'),
            self::ALLOWRESUME_FORCE => get_string('force')
        );
    }

    /**
     * Returns the localized list of attempt grade method settings for a TaskChain instance
     *
     * @param string $type (optional, default='grade') "grade" or "score"
     * @return array
     */
    static public function attemptgrademethods_list($type='grade') {
        return array (
            self::GRADEMETHOD_TOTAL   => get_string('totaltaskscores', 'taskchain'),
            self::GRADEMETHOD_HIGHEST => get_string('highesttaskscore', 'taskchain'),
            self::GRADEMETHOD_LAST    => get_string('lasttaskattempted', 'taskchain'),
            self::GRADEMETHOD_LASTCOMPLETED => get_string('lasttaskcompleted', 'taskchain'),
            self::GRADEMETHOD_LASTTIMEDOUT  => get_string('lasttasktimedout', 'taskchain'),
            self::GRADEMETHOD_LASTABANDONED => get_string('lasttaskabandoned', 'taskchain')
        );
    }

    /**
     * Returns the localized list of attempt limit settings for a TaskChain instance
     *
     * @return array
     */
    static public function attemptlimits_list() {
        $options = array(
            0 => get_string('attemptsunlimited', 'taskchain'),
        );
        for ($i=1; $i<=10; $i++) {
            $options[$i] = "$i";
        }
        return $options;
    }

    /**
     * Returns the localized list of attempt type settings for a TaskChain condition instance
     *
     * @return array
     */
    static public function attempttypes_list() {
        return array(
            self::ATTEMPTTYPE_ANY => get_string('anyattempts', 'taskchain'),
            self::ATTEMPTTYPE_RECENT => get_string('recentattempts', 'taskchain'),
            self::ATTEMPTTYPE_CONSECUTIVE => get_string('consecutiveattempts', 'taskchain'),
        );
    }

    /**
     * Returns the localized list of entry/exit cm settings for a TaskChain chain
     *
     * @param string $type ("entry" or "exit")
     * @return array
     */
    static public function cms_list($type) {
        return array(
            self::ACTIVITY_COURSE_ANY  => get_string($type.'cmcourse', 'taskchain'),
            self::ACTIVITY_SECTION_ANY => get_string($type.'cmsection', 'taskchain'),
            self::ACTIVITY_COURSE_GRADED  => get_string($type.'gradedcourse', 'taskchain'),
            self::ACTIVITY_SECTION_GRADED => get_string($type.'gradedsection', 'taskchain'),
            self::ACTIVITY_COURSE_TASKCHAIN  => get_string($type.'taskchaincourse', 'taskchain'),
            self::ACTIVITY_SECTION_TASKCHAIN => get_string($type.'taskchainsection', 'taskchain')
        );
    }

    /**
     * Returns the localized list of delay3 settings for a TaskChain task instance
     *
     * @return array
     */
    static public function delay3s_list() {
        return array(
            self::DELAY3_SPECIFIC => get_string('delay3specific', 'taskchain'),
            self::DELAY3_TEMPLATE => get_string('delay3template', 'taskchain'),
            self::DELAY3_AFTEROK  => get_string('delay3afterok', 'taskchain'),
            self::DELAY3_DISABLE  => get_string('delay3disable', 'taskchain'),
        );
    }

    /**
     * Returns the localized list of feedback settings for a TaskChain instance
     *
     * @uses $CFG
     * @return array
     */
    static public function feedbacks_list() {
        global $CFG;
        $list = array (
            self::FEEDBACK_NONE            => get_string('none'),
            self::FEEDBACK_WEBPAGE         => get_string('feedbackwebpage',  'taskchain'),
            self::FEEDBACK_FORMMAIL        => get_string('feedbackformmail', 'taskchain'),
            self::FEEDBACK_MOODLEFORUM     => get_string('feedbackmoodleforum', 'taskchain')
        );
        if ($CFG->messaging) {
            $list[self::FEEDBACK_MOODLEMESSAGING] = get_string('feedbackmoodlemessaging', 'taskchain');
        }
        return $list;
    }

    /**
     * Returns the localized list of maximum grade settings for a TaskChain instance
     *
     * @return array
     */
    static public function gradelimits_list($type='grade') {
        $options = array();
        for ($i=100; $i>=1; $i--) {
            $options[$i] = $i;
        }
        $options[0] = get_string('no'.$type, 'taskchain');
        return $options;
    }

    /**
     * Returns the localized list of grade method settings for a TaskChain instance
     *
     * @param string $type (optional, default='grade') "grade" or "score"
     * @return array
     */
    static public function grademethods_list($type='grade') {
        return array (
            self::GRADEMETHOD_HIGHEST => get_string('highest'.$type, 'taskchain'),
            self::GRADEMETHOD_AVERAGE => get_string('average'.$type, 'taskchain'),
            self::GRADEMETHOD_FIRST   => get_string('firstattempt', 'taskchain'),
            self::GRADEMETHOD_LAST    => get_string('lastattempt', 'taskchain')
        );
    }

    /**
     * Returns the localized list of grade weightings for a TaskChain instance
     *
     * @return array
     */
    static public function gradeweightings_list() {
        $options = array();
        for ($i=100; $i>=1; $i--) {
            $options[$i] = $i;
        }
        $options[0] = get_string('weightingnone', 'taskchain');
        return $options;
    }


    /**
     * Returns the localized list of location settings for a TaskChain task's source/config location
     *
     * @return array
     */
    static public function locations_list() {
        return array(
            self::LOCATION_COURSEFILES => get_string('repository', 'repository'), // get_string('coursefiles'),
            self::LOCATION_SITEFILES   => get_string('sitefiles'),
            self::LOCATION_WWW         => get_string('webpage')
        );
    }

    /**
     * Returns the list of media players for the TaskChain module
     *
     * @return array
     */
    static public function mediafilters_list() {
        $plugins = get_list_of_plugins('mod/taskchain/mediafilter'); // sorted

        if (in_array('moodle', $plugins)) {
            // make 'moodle' the first element in the plugins array
            unset($plugins[array_search('moodle', $plugins)]);
            array_unshift($plugins, 'moodle');
        }

        // define element type for list of mediafilters (select, radio, checkbox)
        $options = array('' => get_string('none'));
        foreach ($plugins as $plugin) {
            $options[$plugin] = get_string('mediafilter_'.$plugin, 'taskchain');
        }
        return $options;
    }

    /**
     * Returns the localized list of grade method settings for a TaskChain instance
     *
     * @return array
     */
    static public function namesources_list() {
        return array (
            self::TEXTSOURCE_FILE     => get_string('textsourcefile', 'taskchain'),
            self::TEXTSOURCE_FILENAME => get_string('textsourcefilename', 'taskchain'),
            self::TEXTSOURCE_FILEPATH => get_string('textsourcefilepath', 'taskchain'),
            self::TEXTSOURCE_SPECIFIC => get_string('textsourcespecific', 'taskchain')
        );
    }

    /**
     * Returns the localized list of navigation settings for a TaskChain instance
     *
     * @return array
     */
    static public function navigations_list() {
        return array (
            self::NAVIGATION_MOODLE   => get_string('navigation_moodle', 'taskchain'),
            self::NAVIGATION_TOPBAR   => get_string('navigation_topbar', 'taskchain'),
            self::NAVIGATION_FRAME    => get_string('navigation_frame', 'taskchain'),
            self::NAVIGATION_EMBED    => get_string('navigation_embed', 'taskchain'),
            self::NAVIGATION_ORIGINAL => get_string('navigation_original', 'taskchain'),
            self::NAVIGATION_NONE     => get_string('navigation_none', 'taskchain')
        );
    }

    /**
     * Returns the localized list of nexttaskid settings for a TaskChain condition instance
     *
     * @return array
     */
    static public function nexttaskids_list() {
        global $TC;

        $list = array(
            self::CONDITIONTASKID_SAME        => get_string('sametask', 'taskchain'),
            self::CONDITIONTASKID_PREVIOUS    => get_string('previoustask', 'taskchain'),
            self::CONDITIONTASKID_NEXT1       => get_string('next1task', 'taskchain'),
            self::CONDITIONTASKID_NEXT2       => get_string('next2task', 'taskchain'),
            self::CONDITIONTASKID_NEXT3       => get_string('next3task', 'taskchain'),
            self::CONDITIONTASKID_NEXT4       => get_string('next4task', 'taskchain'),
            self::CONDITIONTASKID_NEXT5       => get_string('next5task', 'taskchain'),
            self::CONDITIONTASKID_UNSEEN      => get_string('unseentask', 'taskchain'),
            self::CONDITIONTASKID_UNANSWERED  => get_string('unansweredtask', 'taskchain'),
            self::CONDITIONTASKID_INCORRECT   => get_string('incorrecttask', 'taskchain'),
            self::CONDITIONTASKID_RANDOM      => get_string('randomtask', 'taskchain'),
            self::CONDITIONTASKID_MENUNEXT    => get_string('menuofnexttasks', 'taskchain'),
            self::CONDITIONTASKID_MENUNEXTONE => get_string('menuofnexttasksone', 'taskchain'),
            self::CONDITIONTASKID_MENUALL     => get_string('menuofalltasks', 'taskchain'),
            self::CONDITIONTASKID_MENUALLONE  => get_string('menuofalltasksone', 'taskchain'),
            self::CONDITIONTASKID_ENDOFCHAIN  => get_string('endofchain', 'taskchain')
        );
        if ($tasks = $TC->get_tasks()) {
            foreach ($tasks as $task) {
                $list[$task->id] = '['.$task->sortorder.'] '.format_string($task->name);
            }
        }
        return $list;
    }

    /**
     * Returns the localized list of output format setings for a given TaskChain sourcetype
     *
     * @param xxx $sourcetype
     * @return array
     * @todo Finish documenting this function
     */
    static public function outputformats_list($sourcetype) {
        $strman = get_string_manager();

        $outputformats = array();
        if ($sourcetype) {
            $classes = mod_taskchain::get_classes('taskchainattempt', 'renderer.php', 'mod_', '_renderer');
            foreach ($classes as $class) {
                // use call_user_func() to prevent syntax error in PHP 5.2.x
                $sourcetypes = call_user_func(array($class, 'sourcetypes'));
                if (in_array($sourcetype, $sourcetypes)) {
                    // mod_taskchain_attempt_hp_6_jmix_xml_v6_plus_deluxe_renderer
                    // strip prefix, "mod_taskchain_attempt_", and suffix, "_renderer"
                    $outputformat = substr($class, 22, -9);
                    if ($strman->string_exists('outputformat_'.$outputformat, 'taskchain')) {
                        $outputformats[$outputformat] = $strman->get_string('outputformat_'.$outputformat, 'taskchain');
                    } else {
                        $outputformats[$outputformat] = $strman->get_string('outputformat_best', 'taskchain');
                    }
                }
            }
            asort($outputformats);
        }

        $best = array('0' => get_string('outputformat_best', 'taskchain'));

        switch (count($outputformats)) {
            case 0  : return $best; // shouldn't happen !!
            case 1  : return $outputformats; // unusual ?!
            default : return $best + $outputformats;
        }
    }

    /**
     * Returns the localized list of stopbutton_type settings for a TaskChain task
     *
     * @return array
     */
    static public function stopbuttontypes_list() {
        return array(
            'taskchain_giveup' => get_string('giveup',             'taskchain'),
            'specific'         => get_string('stopbuttonspecific', 'taskchain')
        );
    }

    /**
     * Returns the localized list of status settings for a TaskChain attempt
     *
     * @return array
     */
    static public function statuses_list() {
        return array (
            self::STATUS_INPROGRESS => get_string('inprogress', 'taskchain'),
            self::STATUS_TIMEDOUT   => get_string('timedout', 'taskchain'),
            self::STATUS_ABANDONED  => get_string('abandoned', 'taskchain'),
            self::STATUS_COMPLETED  => get_string('completed', 'taskchain')
        );
    }

    /**
     * Returns the localized list of score weightings for a TaskChain task instance
     *
     * @return array
     */
    static public function scorelimits_list() {
        return self::gradelimits_list('score');
    }

    /**
     * Returns the localized list of score method settings for a TaskChain task instance
     *
     * @return array
     */
    static public function scoremethods_list() {
        return self::grademethods_list('score');
    }

    /**
     * Returns the localized list of score weightings for a TaskChain task instance
     *
     * @return array
     */
    static public function scoreweightings_list() {
        return self::gradeweightings_list();
    }

    /**
     * Returns the localized list of grade method settings for a TaskChain instance
     *
     * @return array
     */
    static public function titles_list() {
        return array (
            self::TEXTSOURCE_SPECIFIC => get_string('taskchainname', 'taskchain'),
            self::TEXTSOURCE_FILE     => get_string('textsourcefile', 'taskchain'),
            self::TEXTSOURCE_FILENAME => get_string('textsourcefilename', 'taskchain'),
            self::TEXTSOURCE_FILEPATH => get_string('textsourcefilepath', 'taskchain')
        );
    }
}
