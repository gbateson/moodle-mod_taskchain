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
            self::ADDTYPE_AUTO         => get_string('addtypeauto', 'mod_taskchain'),
            self::ADDTYPE_TASKFILE     => get_string('addtypetaskfile', 'mod_taskchain'),
            self::ADDTYPE_TASKCHAIN    => get_string('addtypetaskchain', 'mod_taskchain'),
            self::ADDTYPE_CHAINFILE    => get_string('addtypechainfile', 'mod_taskchain'),
            self::ADDTYPE_CHAINFOLDER  => get_string('addtypechainfolder', 'mod_taskchain'),
            self::ADDTYPE_CHAINFOLDERS => get_string('addtypechainfolders', 'mod_taskchain')
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
            self::GRADEMETHOD_TOTAL   => get_string('totaltaskscores', 'mod_taskchain'),
            self::GRADEMETHOD_HIGHEST => get_string('highesttaskscore', 'mod_taskchain'),
            self::GRADEMETHOD_LAST    => get_string('lasttaskattempted', 'mod_taskchain'),
            self::GRADEMETHOD_LASTCOMPLETED => get_string('lasttaskcompleted', 'mod_taskchain'),
            self::GRADEMETHOD_LASTTIMEDOUT  => get_string('lasttasktimedout', 'mod_taskchain'),
            self::GRADEMETHOD_LASTABANDONED => get_string('lasttaskabandoned', 'mod_taskchain')
        );
    }

    /**
     * Returns the localized list of attempt limit settings for a TaskChain instance
     *
     * @return array
     */
    static public function attemptlimits_list() {
        $options = array(
            0 => get_string('attemptsunlimited', 'mod_taskchain'),
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
            self::ATTEMPTTYPE_ANY => get_string('anyattempts', 'mod_taskchain'),
            self::ATTEMPTTYPE_RECENT => get_string('recentattempts', 'mod_taskchain'),
            self::ATTEMPTTYPE_CONSECUTIVE => get_string('consecutiveattempts', 'mod_taskchain'),
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
            self::ACTIVITY_COURSE_ANY  => get_string($type.'cmcourse', 'mod_taskchain'),
            self::ACTIVITY_SECTION_ANY => get_string($type.'cmsection', 'mod_taskchain'),
            self::ACTIVITY_COURSE_GRADED  => get_string($type.'gradedcourse', 'mod_taskchain'),
            self::ACTIVITY_SECTION_GRADED => get_string($type.'gradedsection', 'mod_taskchain'),
            self::ACTIVITY_COURSE_TASKCHAIN  => get_string($type.'taskchaincourse', 'mod_taskchain'),
            self::ACTIVITY_SECTION_TASKCHAIN => get_string($type.'taskchainsection', 'mod_taskchain')
        );
    }

    /**
     * Returns the localized list of timelimit settings for a TaskChain task instance
     *
     * @return array
     */
    static public function timelimits_list() {
        return array(
            self::TIME_SPECIFIC => get_string('timelimitspecific', 'mod_taskchain'),
            self::TIME_TEMPLATE => get_string('timelimittemplate', 'mod_taskchain'),
            self::TIME_DISABLE  => get_string('disable')
        );
    }

    /**
     * Returns the localized list of delay3 settings for a TaskChain task instance
     *
     * @return array
     */
    static public function delay3s_list() {
        return array(
            self::TIME_SPECIFIC => get_string('delay3specific', 'mod_taskchain'),
            self::TIME_TEMPLATE => get_string('delay3template', 'mod_taskchain'),
            self::TIME_AFTEROK  => get_string('delay3afterok', 'mod_taskchain'),
            self::TIME_DISABLE  => get_string('delay3disable', 'mod_taskchain')
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
            self::FEEDBACK_WEBPAGE         => get_string('feedbackwebpage',  'mod_taskchain'),
            self::FEEDBACK_FORMMAIL        => get_string('feedbackformmail', 'mod_taskchain'),
            self::FEEDBACK_MOODLEFORUM     => get_string('feedbackmoodleforum', 'mod_taskchain')
        );
        if ($CFG->messaging) {
            $list[self::FEEDBACK_MOODLEMESSAGING] = get_string('feedbackmoodlemessaging', 'mod_taskchain');
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
        $options[0] = get_string('no'.$type, 'mod_taskchain');
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
            self::GRADEMETHOD_HIGHEST => get_string('highest'.$type, 'mod_taskchain'),
            self::GRADEMETHOD_AVERAGE => get_string('average'.$type, 'mod_taskchain'),
            self::GRADEMETHOD_FIRST   => get_string('firstattempt', 'mod_taskchain'),
            self::GRADEMETHOD_LAST    => get_string('lastattempt', 'mod_taskchain')
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
        $options[0] = get_string('weightingnone', 'mod_taskchain');
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
            $options[$plugin] = get_string('mediafilter_'.$plugin, 'mod_taskchain');
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
            self::TEXTSOURCE_FILE     => get_string('textsourcefile',     'mod_taskchain'),
            self::TEXTSOURCE_FILENAME => get_string('textsourcefilename', 'mod_taskchain'),
            self::TEXTSOURCE_FILEPATH => get_string('textsourcefilepath', 'mod_taskchain'),
            self::TEXTSOURCE_SPECIFIC => get_string('textsourcespecific', 'mod_taskchain')
        );
    }

    /**
     * Returns the localized list of navigation settings for a TaskChain instance
     *
     * @return array
     */
    static public function navigations_list() {
        return array (
            self::NAVIGATION_MOODLE   => get_string('navigation_moodle',   'mod_taskchain'),
            self::NAVIGATION_TOPBAR   => get_string('navigation_topbar',   'mod_taskchain'),
            self::NAVIGATION_FRAME    => get_string('navigation_frame',    'mod_taskchain'),
            self::NAVIGATION_EMBED    => get_string('navigation_embed',    'mod_taskchain'),
            self::NAVIGATION_ORIGINAL => get_string('navigation_original', 'mod_taskchain'),
            self::NAVIGATION_NONE     => get_string('navigation_none',     'mod_taskchain')
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
            self::CONDITIONTASKID_SAME        => get_string('sametask',       'mod_taskchain'),
            self::CONDITIONTASKID_PREVIOUS    => get_string('previoustask',   'mod_taskchain'),
            self::CONDITIONTASKID_NEXT1       => get_string('next1task',      'mod_taskchain'),
            self::CONDITIONTASKID_NEXT2       => get_string('next2task',      'mod_taskchain'),
            self::CONDITIONTASKID_NEXT3       => get_string('next3task',      'mod_taskchain'),
            self::CONDITIONTASKID_NEXT4       => get_string('next4task',      'mod_taskchain'),
            self::CONDITIONTASKID_NEXT5       => get_string('next5task',      'mod_taskchain'),
            self::CONDITIONTASKID_UNSEEN      => get_string('unseentask',     'mod_taskchain'),
            self::CONDITIONTASKID_UNANSWERED  => get_string('unansweredtask', 'mod_taskchain'),
            self::CONDITIONTASKID_INCORRECT   => get_string('incorrecttask',  'mod_taskchain'),
            self::CONDITIONTASKID_RANDOM      => get_string('randomtask',     'mod_taskchain'),
            self::CONDITIONTASKID_MENUNEXT    => get_string('menuofnexttasks',    'mod_taskchain'),
            self::CONDITIONTASKID_MENUNEXTONE => get_string('menuofnexttasksone', 'mod_taskchain'),
            self::CONDITIONTASKID_MENUALL     => get_string('menuofalltasks',     'mod_taskchain'),
            self::CONDITIONTASKID_MENUALLONE  => get_string('menuofalltasksone',  'mod_taskchain'),
            self::CONDITIONTASKID_ENDOFCHAIN  => get_string('endofchain',         'mod_taskchain')
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
                        $outputformats[$outputformat] = $strman->get_string('outputformat_'.$outputformat, 'mod_taskchain');
                    } else {
                        $outputformats[$outputformat] = $strman->get_string('outputformat_best', 'mod_taskchain');
                    }
                }
            }
            asort($outputformats);
        }

        $best = array('0' => get_string('outputformat_best', 'mod_taskchain'));

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
            'taskchain_giveup' => get_string('giveup',             'mod_taskchain'),
            'specific'         => get_string('stopbuttonspecific', 'mod_taskchain'),
            'langpack'         => get_string('stopbuttonlangpack', 'mod_taskchain')
        );
    }

    /**
     * Returns the localized list of reviewoptions_type settings for a TaskChain task
     *
     * @param string $type either "times" or "items"
     * @return array
     */
    static public function reviewoptions_list($type='') {
        if ($type=='times') {
            return array(
                'duringattempt' => self::REVIEW_DURINGATTEMPT,
                'afterattempt'  => self::REVIEW_AFTERATTEMPT,
                'afterclose'    => self::REVIEW_AFTERCLOSE
            );
        }
        if ($type=='items') {
            return array(
                'responses' => self::REVIEW_RESPONSES,
                'answers'   => self::REVIEW_ANSWERS,
                'scores'    => self::REVIEW_SCORES,
                'feedback'  => self::REVIEW_FEEDBACK
            );
        }
        return array(); // shoudn't happen
    }


    /**
     * Returns the localized list of status settings for a TaskChain attempt
     *
     * @return array
     */
    static public function statuses_list() {
        return array (
            self::STATUS_INPROGRESS => get_string('inprogress', 'mod_taskchain'),
            self::STATUS_TIMEDOUT   => get_string('timedout',   'mod_taskchain'),
            self::STATUS_ABANDONED  => get_string('abandoned',  'mod_taskchain'),
            self::STATUS_COMPLETED  => get_string('completed',  'mod_taskchain'),
            self::STATUS_PENDING    => get_string('pending',    'mod_taskchain')
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
            self::TEXTSOURCE_SPECIFIC => get_string('taskchainname',      'mod_taskchain'),
            self::TEXTSOURCE_FILE     => get_string('textsourcefile',     'mod_taskchain'),
            self::TEXTSOURCE_FILENAME => get_string('textsourcefilename', 'mod_taskchain'),
            self::TEXTSOURCE_FILEPATH => get_string('textsourcefilepath', 'mod_taskchain')
        );
    }
}
