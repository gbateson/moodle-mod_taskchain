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
 * Behat steps for mod_taskchain.
 *
 * @package   mod_taskchain
 * @category  test
 * @copyright 2026 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat steps for mod_taskchain.
 *
 * These steps go directly to TaskChain pages, so that the tests
 * do not depend on the course page and navigation of each Moodle version.
 *
 * @package   mod_taskchain
 * @category  test
 * @copyright 2026 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_taskchain extends behat_base {
    /**
     * Get the course module id of the TaskChain activity with the given name.
     *
     * @param string $name
     * @return int
     */
    protected function get_taskchain_cmid($name) {
        global $DB;
        $taskchain = $DB->get_record('taskchain', ['name' => $name], 'id, course', MUST_EXIST);
        $cm = get_coursemodule_from_instance('taskchain', $taskchain->id, $taskchain->course, false, MUST_EXIST);
        return $cm->id;
    }

    /**
     * Go to the main page of a TaskChain activity.
     *
     * @Given /^I am on the "(?P<name_string>(?:[^"]|\\")*)" TaskChain view page$/
     * @param string $name name of the TaskChain activity
     */
    public function i_am_on_the_taskchain_page($name) {
        $url = new moodle_url('/mod/taskchain/view.php', ['id' => $this->get_taskchain_cmid($name)]);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
    }

    /**
     * Go to the report page of a TaskChain activity.
     *
     * @Given /^I am on the "(?P<name_string>(?:[^"]|\\")*)" TaskChain report page$/
     * @param string $name name of the TaskChain activity
     */
    public function i_am_on_the_taskchain_report_page($name) {
        $url = new moodle_url('/mod/taskchain/report.php', ['id' => $this->get_taskchain_cmid($name)]);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
    }
}
