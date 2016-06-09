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
            $insert = '<base href="'.$this->TC->task->source->baseurl.$this->TC->task->source->filepath.'">'."\n";
            $this->TC->task->source->filecontents = substr_replace($this->TC->task->source->filecontents, $insert, $pos, 0);
        }

        // replace external javascript with modified inline javascript
        $search = '/(?<!<\!-- )<script[^>]*src\s*=\s*"([^"]*)"[^>]*>\s*<\/script>/';
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

        // clean filename
        $filename = $match[1];
        if ($pos = strpos($filename, '?')) {
            $filename = substr($filename, 0, $pos);
        }

        // special processing for js files
        switch ($filename) {

            // HTML5 files
            case 'offline/js/offlinesupport.js':
            case 'offline/offline_engine_en-GB.js':
            case 'offline/offline_template.js':
            case 'offline/offline_colourChanger.js':
            case 'offline/offline_menu.js':
            case 'offline/offline_language.js':
            case 'offline/hangman.js':
            case 'common_html5/js/popcorn/popcorn-complete.min.js':
            case 'common_html5/js/popcorn/plugins/popcorn.textplus.js':
            case 'common_html5/js/popcorn/plugins/popcorn.subtitleplus.js':
            case 'common_html5/js/popcorn/plugins/popcorn.xot.js':
            case 'common_html5/js/popcorn/plugins/popcorn.mediaplus.js':
            case 'common_html5/js/popcorn/plugins/popcorn.mcq.js':
            case 'common_html5/js/popcorn/plugins/popcorn.slides.js':
            case 'common_html5/js/popcorn/plugins/popcorn.sortholder.js':
            case 'common_html5/js/jquery-1.9.1.min.js':
            case 'common_html5/js/jquery-ui-1.10.4.min.js':
            case 'common_html5/js/jquery.ui.touch-punch.min.js':
            case 'common_html5/js/imageLens.js':
            case 'common_html5/js/gray-gh-pages/js/jquery.gray.min.js':
            case 'common_html5/mediaelement/mediaelement-and-player.js':
            case 'common_html5/js/mediaPlayer.js':
            case 'common_html5/js/swfobject.js':
            case 'common_html5/js/xenith.js':
            case 'common_html5/js/xttracking_noop.js':
            case 'offline/js/mathjax/MathJax.js':
                return $match[0];

            // Flash files
            case 'js/rlohelper.js':
            case 'js/xttracking_noop.js':
            case 'rloObject.js':
                break;

            //default: echo "unknown file: '$filename'";
        }

        // set baseurl
        $baseurl = $this->TC->task->source->baseurl;
        if ($pos = strrpos($this->TC->task->source->filepath, '/')) {
            $baseurl .= substr($this->TC->task->source->filepath, 0, $pos);
        }
        $baseurl .= '/';

        // get javascript from external file
        $js = $this->TC->task->source->get_sibling_filecontents($match[1]);

        // several search-and-replace fixes
        //  - add style to center the Flash Object
        //  - convert MainPreloader.swf to absolute URL
        //  - break up "script" strings to prevent unwanted TaskChain postprocessing
        $search = array(
            'style="'."padding:0px; width:' + rloWidth + 'px; height:' + rloHeight + 'px;".'"', // NEW
            'style="'."width:' + rloWidth + 'px; height:' + rloHeight + 'px; ".'"',             // OLD
            'var FileLocation = xmlPath;',       // NEW
            'var FileLocation = getLocation();', // OLD
            'MainPreloader.swf',
            'script', 'Script', 'SCRIPT',
        );
        $replace = array(
            'style="'."padding:0px; width:' + rloWidth + 'px; height:' + rloHeight + 'px; margin:auto;".'"', // NEW
            'style="'."width:' + rloWidth + 'px; height:' + rloHeight + 'px; margin:auto;".'"', // OLD
            "var FileLocation = '$baseurl';", // NEW
            "var FileLocation = '$baseurl';", // OLD
            $baseurl.'MainPreloader.swf',
            "scr' + 'ipt", "Scr' + 'ipt", "SCR' + 'IPT",
        );

        if ($this->TC->task->source->get_displayMode()=='fill window') {
            // remove "id" to prevent resizing of Flash object
            // there might be another way to do this
            // e.g. using js to stretch canvas area
            $search[] = 'id="'."rlo' + rloID + '".'"';
            $replace[] = '';
        }

        $js = str_replace($search, $replace, $js);
        return '<script type="text/javascript">'."\n".trim($js)."\n".'</script>'."\n";
    }
}
