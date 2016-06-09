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
 * mod/taskchain/source/html/xerte/class.php
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
require_once($CFG->dirroot.'/mod/taskchain/source/html/class.php');

/**
 * taskchain_source_html_xerte
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_source_html_xerte extends taskchain_source_html {
    // properties of the icon for this source file type
    var $icon = 'mod/taskchain/file/html/xerte/icon.gif';

    // xmlized content of template.xml
    var $template_xml = null;

    /**
     * returns taskchain_file object if $filename is a task file, or false otherwise
     *
     * @param xxx $sourcefile
     * @return xxx
     * @todo Finish documenting this function
     */
    public function is_taskfile() {
        if (! preg_match('/\.html?$/', $this->file->get_filename())) {
            // not an html file
            return false;
        }
        if (! $this->get_filecontents()) {
            // empty or non-existant file
            return false;
        }

        // Xerte Flash format
        if (preg_match('/<script[^>]*src\s*=\s*"[^"]*rloObject.js"[^>]*>/', $this->filecontents)) {
            // myRLO = new rloObject('800','600','template.rlt');
            // myRLO = new rloObject('800','600','numbers_3.rlo');
            // myRLO = new rloObject('800','600','Nottingham.rlt', '', 'template.xml', 'http://myserver.com/xertetoolkits/', linkId)
            $search = "/myRLO = new rloObject\('\d*','\d*','[^']*.rl[ot]'[^)]*\)/";
            return preg_match($search, $this->filecontents);
        }

        // Xerte HTML5 offline format
        if (preg_match('/<script[^>]*src\s*=\s*"[^"]*offlinesupport.js"[^>]*>/', $this->filecontents)) {
            $search = '/<body[^"]*onload\s*=\s*"XTInitialise\(\);"[^"]*onbeforeunload\s*=\s*"XTTerminate\(\);"[^>]*>/';
            return preg_match($search, $this->filecontents);
        }

        // not a recognized Xerte file
        return false;
    }

    /**
     * get_template_xml
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_template_xml() {
        if (is_null($this->template_xml)) {
            $this->template_xml = $this->get_sibling_filecontents('template.xml', true);
        }
        return $this->template_xml;
    }

    /**
     * get_template_value
     *
     * @param xxx $tags
     * @param xxx $default (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_template_value($tags, $default=null) {
        $value = $this->get_template_xml();
        foreach($tags as $tag) {
            if (! is_array($value)) {
                return $default;
            }
            if(! array_key_exists($tag, $value)) {
                return $default;
            }
            $value = $value[$tag];
        }
        return $value;
    }

    /**
     * get_name
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_name() {
        $tags = array('learningObject', '@', 'name');
        if ($name = $this->get_template_value($tags)) {
            return $name;
        }
        return parent::get_name();
    }

    /**
     * get_displayMode
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_displayMode() {
        $tags = array('learningObject', '@', 'displayMode');
        return $this->get_template_value($tags, 'default');
    }

    /**
     * get_entrytext
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_entrytext() {
        return '';
    }

    /**
     * get_entrytext
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_exittext() {
        return '';
    }
}
