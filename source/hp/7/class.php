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
 * mod/taskchain/source/hp/7/class.php
 *
 * @package    mod_taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

// Prevent direct access to this script.
defined('MOODLE_INTERNAL') || die();

// Include required files.
require_once($CFG->dirroot . '/mod/taskchain/source/hp/class.php');

/**
 * taskchain_source_hp_7
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod_taskchain
 */
class taskchain_source_hp_7 extends taskchain_source_hp {
    /** @var array file types (i.e. file extensions) that may be HP7 task files */
    const REQUIRED_FILETYPES = array();

    /** @var array strings that must appear in an HP7 task file of this type */
    const REQUIRED_STRINGS = array();

    /** @var array regular expressions that must match an HP7 task file of this type */
    const REQUIRED_MATCHES = array();

    /** @var array strings that must not appear in an HP7 task file of this type */
    const BANNED_STRINGS = array();

    /**
     * Returns true if this file is an HP7 task file of this type, or false otherwise
     *
     * The file type and content are checked using the REQUIRED_FILETYPES,
     * REQUIRED_STRINGS, REQUIRED_MATCHES and BANNED_STRINGS of this class.
     *
     * @return boolean true if the file is a recognized task file, or false otherwise
     */
    public function is_taskfile() {
        if (empty(static::REQUIRED_FILETYPES)) {
            return false;
        }

        $filename = $this->file->get_filename();
        if (! $pos = strrpos($filename, '.')) {
            return false; // No file type (or hidden file) - shouldn't happen !!
        }

        $filetype = strtolower(substr($filename, $pos + 1));
        if (! in_array($filetype, static::REQUIRED_FILETYPES)) {
            return false; // Wrong file type.
        }

        if (! $this->get_filecontents()) {
            return false; // Empty or non-existant file.
        }
        $content = $this->filecontents;

        if ($filetype == 'htm' || $filetype == 'html') {
            $strings = static::required_strings_html($content);
        } else {
            $strings = static::REQUIRED_STRINGS;
        }

        foreach ($strings as $string) {
            if (strpos($content, $string) === false) {
                return false; // Required string not found.
            }
        }

        foreach (static::REQUIRED_MATCHES as $match) {
            if (! preg_match($match, $content)) {
                return false; // Required match not found.
            }
        }

        foreach (static::BANNED_STRINGS as $string) {
            if (strpos($content, $string) !== false) {
                return false; // Banned string found.
            }
        }

        return true;
    }

    /*
     * required_strings_html()
     *
     * @param string $content (passed by reference)
     * @return array of required strings for HTML content
     */
    /**
     * required_strings_html
     *
     * @param mixed $content
     * @return mixed
     */
    public static function required_strings_html(&$content) {
        $strings = array(
            '<!DOCTYPE html>',
            '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>',
            'Made with executable version 7',
            '<div id="ContainerDiv">',
        );
        if (strpos($content, '<div class="ExerciseContainer">')) {
            // JCloze, JCross, JQuiz, and plain JMatch and JMix.
            $strings[] = '<div class="ExerciseContainer">';
        } else {
            // Drag-and-drop versions of JMatch and JMix
            // Note: HP7 JMix drag-and-drop has no "CheckButtonDiv",
            // so we use the start of the container's class instead.
            // The subclasses require the full class string, which
            // includes "JMatchDrag" or "JMixDrag".
            $strings[] = '<div class="ExerciseContainer J';
        }

        $strings = array_merge($strings, static::REQUIRED_STRINGS);
        return $strings;
    }
}
