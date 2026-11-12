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
 * mod/taskchain/attempt/hp/7/jmatch/xml/v7/plus/renderer.php
 *
 * @package    mod_taskchain
 * @copyright  2026 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

// Prevent direct access to this script.
defined('MOODLE_INTERNAL') || die();

// Include required files.
require_once($CFG->dirroot . '/mod/taskchain/attempt/hp/7/jmatch/xml/v7/renderer.php');

/**
 * mod_taskchain_attempt_hp_7_jmatch_xml_v7_plus_renderer
 *
 * @copyright  2026 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod_taskchain
 */
class mod_taskchain_attempt_hp_7_jmatch_xml_v7_plus_renderer extends mod_taskchain_attempt_hp_7_jmatch_xml_v7_renderer {
    /** @var string */
    public $templatefile = 'djmatch7.ht_';

    // Note: HP7 drag-and-drop JMatch creates its own cards (see expand_V7Cards),
    // so we do not need fix_bodycontent_DragAndDrop() here.
}
