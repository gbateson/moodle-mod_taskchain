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
 * mod/taskchain/source/hp/6/masher/xml/class.php
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
 * taskchain_source_hp_6_masher_xml
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod_taskchain
 */
class taskchain_source_hp_6_masher_xml extends taskchain_source_hp_6_masher {
    /**
     * is_chainfile
     *
     * If this file is a Hot Potatoes Masher file (*.jms), return the list of files
     * in the Masher unit. For each item in the unit, the output file (*.htm) is used
     * if it has been uploaded, otherwise the original data file (e.g. *.jqz) is used.
     *
     * @return array|false array of stored_file objects, or false if this is not a Masher file
     */
    public function is_chainfile() {
        if (! preg_match('/\.jms$/i', $this->file->get_filename())) {
            return false; // This is not a Hot Potatoes masher file.
        }

        if (! $this->get_filecontents()) {
            return false; // Empty or non-existant file.
        }

        // Create xml tree for this file.
        $xml = taskchain_xmlize($this->filecontents, 0);

        // Check we have the expected xml tree structure.
        $root_tag = $this->hbs_software . '-' . $this->hbs_tasktype . '-file'; // "hotpot-masher-file"
        if (empty($xml[$root_tag]['#']['hotpot-file-list'][0]['#']['hotpot-file'])) {
            return false; // Not a masher file - or an empty one.
        }

        $files = array();
        foreach ($xml[$root_tag]['#']['hotpot-file-list'][0]['#']['hotpot-file'] as $item) {
            // $item['#']['data-file-name'][0]['#']    : C:\My Documents\HotPots\jquiz.jqz
            // $item['#']['output-file-name'][0]['#']  : jquiz.htm
            // $item['#']['next-ex-file-name'][0]['#'] : jquiz-v6.htm
            // $item['#']['output-type'][0]['#']       : 2
            $file = false;
            if (isset($item['#']['output-file-name'][0]['#'])) {
                $file = $this->get_listed_file($item['#']['output-file-name'][0]['#']);
            }
            if ($file === false && isset($item['#']['data-file-name'][0]['#'])) {
                // The output file was not found, so look for the original data file.
                $file = $this->get_listed_file($item['#']['data-file-name'][0]['#'], true);
            }
            if ($file) {
                $files[] = $file;
            }
        }

        if (count($files)) {
            return $files;
        } else {
            return false;
        }
    }

    /**
     * xml_get_name
     *
     * @param bool $textonly (optional, default=true)
     * @return string
     */
    public function xml_get_name($textonly = true) {
        if (! isset($this->name)) {
            $this->name = '';
            $this->title = '';

            if (! $this->xml_get_filecontents()) {
                return false; // Could not detect Hot Potatoes masher file - shouldn't happen !!
            }

            $this->title = $this->xml_value('unit-title');
            $this->name = trim(strip_tags($this->title));
        }
        if ($textonly) {
            return $this->name;
        } else {
            return $this->title;
        }
    }

    /**
     * get_title
     *
     * @return string
     */
    public function get_title() {
        return $this->xml_get_name(false);
    }
}
