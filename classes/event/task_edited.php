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
 * mod/taskchain/classes/event/task_edited.php
 *
 * @package    mod_taskchain
 * @copyright  2014 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.6
 */

namespace mod_taskchain\event;

/** prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/**
 * The task_edited event class.
 *
 * @package    mod_taskchain
 * @copyright  2014 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.6
 */
class task_edited extends \core\event\base {

    /**
     * Init method
     */
    protected function init() {
        $this->data['objecttable'] = 'taskchain';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised event name
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_task_edited', 'mod_taskchain');
    }

    /**
     * Returns description of this event
     *
     * @return string
     */
    public function get_description() {
        return get_string('event_task_edited_desc', 'mod_taskchain', $this);
    }

    /**
     * Returns relevant URL
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/taskchain/view.php', array('id' => $this->objectid));
    }

    /**
     * Return the legacy event log data
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, 'taskchain', 'OLD_task_edited', 'view.php?id='.$this->objectid, $this->other['taskchainid'], $this->contextinstanceid);
    }

    /**
     * Custom validation
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
    }
}
