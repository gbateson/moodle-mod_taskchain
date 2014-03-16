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
 * mod/taskchain/backup/moodle2/backup_taskchain_activity_task.class.php
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
require_once($CFG->dirroot . '/mod/taskchain/backup/moodle2/backup_taskchain_stepslib.php');

/**
 * backup_taskchain_activity_task
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class backup_taskchain_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new backup_taskchain_activity_structure_step('taskchain_structure', 'taskchain.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     *
     * @uses $CFG
     * @param xxx $content
     * @return xxx
     * @todo Finish documenting this function
     */
    static public function encode_content_links($content) {
        global $CFG;

        $wwwroot = preg_quote($CFG->wwwroot, '/');

        // Link to the list of taskchains
        $search = '/('.$wwwroot.'\/mod\/taskchain\/index.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@TASKCHAININDEX*$2@$', $content);

        //Link to taskchain view by moduleid
        $search = '/('.$wwwroot.'\/mod\/taskchain\/view.php\?id\=)([0-9]+)/';
        $content= preg_replace($search, '$@TASKCHAINVIEWBYID*$2@$', $content);

        return $content;
    }
}
