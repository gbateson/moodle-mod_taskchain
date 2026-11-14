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
 * mod/taskchain/source/hp/6/masher/html/class.php
 *
 * @package    mod_taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

// Prevent direct access to this script.
defined('MOODLE_INTERNAL') || die();

// Include required files.
require_once($CFG->dirroot . '/mod/taskchain/source/hp/6/masher/class.php');

/**
 * taskchain_source_hp_6_masher_html
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod_taskchain
 */
class taskchain_source_hp_6_masher_html extends taskchain_source_hp_6_masher {
    /**
     * is_chainfile
     *
     * If this file is an index page exported by the Hot Potatoes Masher,
     * return the list of files that are linked from the index.
     *
     * @return array|false array of stored_file objects, or false if this is not a Masher index page
     */
    public function is_chainfile() {
        if (! preg_match('/\.html?$/i', $this->file->get_filename())) {
            return false; // Wrong file type.
        }

        if (! $this->get_filecontents()) {
            return false; // Empty or non-existant file.
        }

        $search = '/<!-- Made with executable version Hot ?Potatoes: Masher Version [^>]* -->/is';
        if (! preg_match($search, $this->filecontents)) {
            return false; // Not a masher index.htm.
        }

        if (! preg_match('/<ul class="Index"[^>]*>(.*?)<\/ul>/is', $this->filecontents, $list)) {
            return false; // No list of links - shouldn't happen.
        }

        // Get the URLs of the links in the list
        // (note: the <li> items in the list may not have closing tags).
        if (! preg_match_all('/<a href="([^"]*)"[^>]*>/is', $list[1], $matches)) {
            return false; // Empty list - shouldn't happen.
        }

        $files = array();
        foreach ($matches[1] as $url) {
            if ($file = $this->get_listed_file(html_entity_decode($url))) {
                $files[] = $file;
            }
        }

        if (count($files)) {
            return $files;
        } else {
            return false;
        }
    }
}
