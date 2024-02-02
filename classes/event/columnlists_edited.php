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
 * mod/taskchain/classes/event/columnlists_edited.php
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
 * The columnlists_edited event class.
 *
 * @package    mod_taskchain
 * @copyright  2014 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.6
 */
class columnlists_edited extends base {

    /**
     * Return the legacy event name
     *
     * @return array
     */
    public static function my_get_legacy_eventname() {
        return 'editcolumnlists';
    }

    /**
     * Init method
     */
    protected function init() {
        $this->data['objecttable'] = 'taskchain';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }
}
