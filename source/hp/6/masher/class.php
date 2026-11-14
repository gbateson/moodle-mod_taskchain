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
 * mod/taskchain/source/hp/6/masher/class.php
 *
 * @package    mod_taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

// Prevent direct access to this script.
defined('MOODLE_INTERNAL') || die();

// Include required files.
require_once($CFG->dirroot . '/mod/taskchain/source/hp/6/class.php');

/**
 * taskchain_source_hp_6_masher
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod_taskchain
 */
class taskchain_source_hp_6_masher extends taskchain_source_hp_6 {
    /** @var string */
    public $hbs_software = 'hotpot';
    /** @var string */
    public $hbs_tasktype = 'masher';

    /**
     * Get a file that is listed in this Masher file.
     *
     * The file is looked for in the same Moodle file area as this Masher file.
     * A relative path (e.g. "folder/jquiz.htm") is relative to the folder of this Masher file.
     * If $basenameonly is true, any path (e.g. "C:\My Documents\jquiz.jqz") is removed
     * and the file is looked for in the same folder as this Masher file.
     *
     * @param string $path of the listed file
     * @param boolean $basenameonly (optional, default=false)
     * @return stored_file|false
     */
    protected function get_listed_file($path, $basenameonly = false) {
        $path = trim(str_replace('\\', '/', $path));
        if ($path == '' || preg_match('/^[a-z]+:/i', $path) && ! $basenameonly) {
            return false; // Empty, or an absolute path or URL.
        }
        if ($basenameonly) {
            $path = basename($path);
        }
        $filename = basename($path);
        $filepath = $this->file->get_filepath();
        $dirname = dirname($path);
        if ($dirname != '.' && $dirname != '') {
            $filepath .= trim($dirname, '/') . '/';
        }
        $fs = get_file_storage();
        $file = $fs->get_file($this->file->get_contextid(), $this->file->get_component(),
                              $this->file->get_filearea(), $this->file->get_itemid(), $filepath, $filename);
        if ($file && ! $file->is_directory()) {
            return $file;
        }
        return false;
    }
}
