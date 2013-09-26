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
 * mod/taskchain/mediafilter/moodle/class.php
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
require_once($CFG->dirroot.'/mod/taskchain/mediafilter/class.php');

/**
 * taskchain_mediafilter_moodle
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_mediafilter_moodle extends taskchain_mediafilter {

    /*
     * mediaplugin_filter
     *
     * @param xxx $taskchain
     * @param xxx $text
     * @param xxx $options (optional, ddefault=array())
     */
    public function mediaplugin_filter($taskchain, $text, $options=array()) {
        global $CFG, $PAGE;
        static $eolas_fix_applied = 0;

        // insert media players using Moodle's standard mediaplugin filter
        $filter = new filter_mediaplugin($taskchain->context, array());
        $newtext = $filter->filter($text);

        if ($newtext==$text) {
            // do nothing
        } else if ($eolas_fix_applied==$taskchain->id) {
            // eolas_fix.js and ufo.js have already been added for this task
        } else {
            if ($eolas_fix_applied==0) {
                // 1st task - eolas_fix.js was added by filter/mediaplugin/filter.php
            } else {
                // 2nd (or later) task - e.g. we are being called by taskchain_cron()
                $PAGE->requires->js('/mod/taskchain/mediafilter/eolas_fix.js');
                //$newtext .= '<script defer="defer" src="'.$CFG->wwwroot.'/mod/taskchain/mediafilter/eolas_fix.js" type="text/javascript"></script>';
            }
            $PAGE->requires->js('/mod/taskchain/mediafilter/ufo.js', true);
            //$newtext .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/taskchain/mediafilter/ufo.js"></script>';
            $eolas_fix_applied = $taskchain->id;
        }

        return $newtext;
    }
}
