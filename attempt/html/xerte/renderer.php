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
 * mod/taskchain/attempt/html/xerte/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/attempt/html/renderer.php');

/**
 * mod_taskchain_attempt_html_xerte_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_html_xerte_renderer extends mod_taskchain_attempt_html_renderer {

    // source file types with which this output format can be used
    var $filetypes = array('html_xerte');

    /**
     * constructor function
     *
     * @param xxx $page
     * @param xxx $target
     * @todo Finish documenting this function
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // forcibly disable mediafilters
        $this->TC->task->set_usemediafilter('');
    }

    /**
     * preprocessing
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function preprocessing() {
        if ($this->cache_uptodate) {
            return true;
        }

        if (! $this->TC->task->source) {
            $this->TC->task->get_source();
        }

        if (! $this->TC->task->source->get_filecontents()) {
            // empty source file - shouldn't happen !!
            return false;
        }

        if ($pos = strpos($this->TC->task->source->filecontents, '<title>')) {
            $insert = '<base href="'.$this->TC->task->source->baseurl.'/'.$this->TC->task->source->filepath.'">'."\n";
            $this->TC->task->source->filecontents = substr_replace($this->TC->task->source->filecontents, $insert, $pos, 0);
        }

        // replace external javascript with modified inline javascript
        $search = '/<script[^>]*src\s*=\s*"([^"]*)"[^>]*>\s*<\/script>/';
        $callback = array($this, 'preprocessing_xerte_js');
        $this->TC->task->source->filecontents = preg_replace_callback($search, $callback, $this->TC->task->source->filecontents);

        parent::preprocessing();
    }

    /**
     * preprocessing_xerte_js
     *
     * @param xxx $match
     * @return xxx
     * @todo Finish documenting this function
     */
    public function preprocessing_xerte_js($match) {
        $js = $this->TC->task->source->get_sibling_filecontents($match[1]);

        // set baseurl
        $baseurl = $this->TC->task->source->baseurl;
        if ($pos = strrpos($this->TC->task->source->filepath, '/')) {
            $baseurl .= substr($this->TC->task->source->filepath, 0, $pos);
        } else {
        }
        $baseurl .= '/';

        // several search-and-replace fixes
        //  - add style to center the Flash Object
        //  - convert MainPreloader.swf to absolute URL
        //  - break up "script" strings to prevent unwanted TaskChain postprocessing
        $search = array(
            ' style="'."width:' + rloWidth + 'px; height:' + rloHeight + 'px; ".'"',
            'var FileLocation = getLocation();',
            'MainPreloader.swf',
            'script', 'Script', 'SCRIPT',
        );
        $replace = array(
            ' style="'."width:' + rloWidth + 'px; height:' + rloHeight + 'px; margin:auto;".'"',
            "var FileLocation = '$baseurl';",
            $baseurl.'MainPreloader.swf',
            "scr' + 'ipt", "Scr' + 'ipt", "SCR' + 'IPT",
        );

        if ($this->TC->task->source->get_displayMode()=='fill window') {
            // remove "id" to prevent resizing of Flash object
            // there might be another way to do this
            // e.g. using js to stretch canvas area
            $search[] = ' id="'."rlo' + rloID + '".'"';
            $replace[] = '';
        }

        $js = str_replace($search, $replace, $js);
        return '<script type="text/javascript">'."\n".trim($js)."\n".'</script>'."\n";
    }
}
