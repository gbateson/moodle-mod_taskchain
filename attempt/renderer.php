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
 * mod/taskchain/attempt/renderer.php
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
require_once($CFG->dirroot.'/mod/taskchain/renderer.php');

/**
 * mod_taskchain_attempt_renderer
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_renderer extends mod_taskchain_renderer {

    /**
     * most outputformats use the taskchain cache
     * but those that don't can switch this flag off
     */
    const USE_CACHE = true;

    /** xml declaration */
    protected $xmldeclaration;

    /** doctype tag */
    protected $doctype;

    /** html tag attributes */
    protected $htmlattributes;

    /** head tag attributes */
    protected $headattributes;

    /** head content */
    public $headcontent;

    /** body tag attributes */
    protected $bodyattributes;

    /** body content */
    public $bodycontent;

    /** name of html frame */
    protected $framename;

    /** boolean flag indicating whether or not we should use the Moodle theme */
    protected $usemoodletheme;

    /** name of theme containiner */
    protected $themecontainer;

    /** the id for the embedded object used for mod_taskchain::NAVIGATION_EMBED */
    protected $embed_object_id  = 'taskchain_embed_object';

    /** the onload function (in iframe.js) for the embedded object (if used) */
    protected $embed_object_onload  = 'set_embed_object_height';

    /** object to store the taskchain_cache record for the task */
    protected $cache;

    /** boolean flag that indicates whether the cache content is uptodate or not */
    protected $cache_uptodate;

    /** external javascripts required for this attempt */
    protected $javascripts = array();

    /**
     * normally we fix the audio players immediately they have been created,
     * but it may be necessary to defer till page elements have been created,
     * and possibly shuffled/removed
     */
    protected $fix_audio_immediately = true;

    /**
     * these $CFG fields must match those in the "taskchain_cache" table
     * "wwwroot" is not stored explicitly because it is included in the md5key
     */
    protected $cache_CFG_fields = array(
        'slasharguments','taskchain_bodystyles','taskchain_enableobfuscate','taskchain_enableswf'
    );

    /**
     * these fields in the task record must match those in the "taskchain_cache" table
     * "outputformat" is not stored explicitly because it is included in the md5key
     */
    protected $cache_task_fields = array(
        'name','sourcefile','sourcetype','sourcelocation','configfile','configlocation','title',
        'navigation','stopbutton','stoptext','allowpaste','usefilters','useglossary','usemediafilter',
        'studentfeedback','studentfeedbackurl','timelimit','delay3','clickreporting'
    );

    /** these fields are treated as text fields */
    public $cache_text_fields = array(
        'slasharguments','taskchain_enableobfuscate','taskchain_enableswf','name',
        'sourcefile','sourcetype','sourcelastmodified','sourceetag',
        'configfile','configlastmodified','configetag',
        'usemediafilter','studentfeedbackurl','stoptext'
    );

    /* these fields from the source and config file objects are stored in the cache */
    public $cache_remote_fields = array(
        'lastmodified','etag'
    );

    /**
     * these fields will be serialized and then stored in the "content" field of the "taskchain_cache" table
     */
    protected $cache_content_fields = array(
        'xmldeclaration','doctype','htmlattributes','headattributes','headcontent','bodyattributes','bodycontent'
    );

    protected $chainid = 0;
    protected $cnumber = 0;
    protected $chaingradeid = 0;
    protected $chainattemptid = 0;

    protected $taskid = 0;
    protected $tnumber = 0;
    protected $taskscoreid = 0;
    protected $taskattemptid = 0;

    /**
     * constructor function
     *
     * @param xxx $page
     * @param xxx $target
     * @todo Finish documenting this function
     */
    public function __construct(moodle_page $page, $target)  {

        parent::__construct($page, $target);

        // add js event API
        array_push($this->javascripts, 'mod/taskchain/attempt/event.js');

        // set the frame name, if any
        $this->framename = optional_param('framename', '', PARAM_ALPHA);

        if (empty($this->TC->task)) {
            $navigation = '';
        } else {
            $navigation = $this->TC->task->get_navigation();
        }

        switch ($navigation) {
            case mod_taskchain::NAVIGATION_NONE:
            case mod_taskchain::NAVIGATION_FRAME:
            case mod_taskchain::NAVIGATION_EMBED:
            case mod_taskchain::NAVIGATION_ORIGINAL:
                $this->usemoodletheme = true; // ($this->framename=='top');
                $this->themecontainer = 'page-mod-taskchain-attempt';
                break;
            case mod_taskchain::NAVIGATION_MOODLE:
            case mod_taskchain::NAVIGATION_TOPBAR:
            default:
                // using any of the following elements as "themecontainer",
                // results in incorrect calculation of ViewportSize in themes
                // that use the CSS "overflow: hidden":
                //   - region-main
                //   - region-main-wrap
                //   - region-post-box
                //   - region-main-box
                //   - page-content
                // using any of the following elements as "themecontainer" is OK:
                //   - page-content-wrapper
                //   - wrapper
                //   - page (doesn't work)
                //   - my-page-wrapper (doesn't work)
                //   - page-mod-taskchain-attempt (doesn't work)
                $this->usemoodletheme = true;
                $this->themecontainer = 'region-main';
                break;
        }
    }

    /**
     * the source file types with which this output format can be used
     *
     * @return array of source file types
     */
    static public function sourcetypes() {
        return array();
    }

    /**
     * Returns html code for an attempt at a TaskChain instance
     * $this->output is a core_renderer (see "lib/outputrenderers.php")
     *
     * you can set page properties like this
     *  - $this->page->set_title($title);
     *  - $this->page->set_heading($this->page->course->fullname);
     *  - $this->page->requires->js('/mod/taskchain/attempt/iframe.js', true); // add to head
     *  - $this->page->requires->js_module('taskchain'); // gets mod/taskchain/module.js
     *  - $this->page->requires->js_init_call('M.mod_taskchain.secure_window.init');
     *
     * @param taskchain object
     * @param xxx $cacheonly (optional, default=false)
     * @return string html
     * @todo Finish documenting this function
     */
    public function render_attempt($cacheonly=false) {

        if (empty($this->TC->task)) {
            $navigation = '';
        } else {
            $navigation = $this->TC->task->get_navigation();
        }

        // if necessary, print container page and associated frames
        $basetag = '';
        if ($navigation==mod_taskchain::NAVIGATION_FRAME || $navigation==mod_taskchain::NAVIGATION_EMBED) {
            if ($cacheonly) {
                $this->framename = 'main';
                $this->usemoodletheme = false;
            } else if ($navigation==mod_taskchain::NAVIGATION_FRAME) {
                if ($this->framename=='') {
                    $this->print_frameset();
                    die;
                }
                if ($this->framename=='top') {
                    $this->print_topframe();
                    die;
                }
            } else if ($navigation==mod_taskchain::NAVIGATION_EMBED) {
                if ($this->framename=='') {
                    $this->print_embed_object_page();
                    die;
                }
            }
            // otherwise we print the "main" frame below

            // set basetag to ensure links and forms can escape from frame
            $basetag = $this->basetag();
        }

        // try to get content from cache
        $this->get_fields_from_cache();

        if ($cacheonly && $this->cache_uptodate) {
            return true;
        }

        // do pre-processing, if required
        $this->preprocessing();

        // generate the main parts of the page
        $this->set_xmldeclaration();
        $this->set_doctype();
        $this->set_htmlattributes();
        $this->set_headattributes();
        $this->set_headcontent();
        $this->set_bodyattributes();
        $this->set_bodycontent();

        // save content to cache (if necessary)
        $this->set_fields_in_cache();

        if ($cacheonly) {
            return true;
        }

        // do post-processing, if required
        $this->postprocessing();

        if (! $this->bodycontent) {
            throw new moodle_exception('sourcefilenotfound', 'taskchain', '', $this->TC->task->sourcefile);
        }

        echo $this->taskchain_header();
        echo $this->taskchain_content();
        echo $this->taskchain_footer();
    }

    /////////////////////////////////////////////////////////////////////
    // functions to prepare browser content                            //
    /////////////////////////////////////////////////////////////////////

    /**
     * preprocessing
     *
     * @todo Finish documenting this function
     */
    protected function preprocessing()  {
        // pre-processing for this output format
        // e.g. convert source to ideal format for this output format
    }

    /**
     * set_xmldeclaration
     *
     * @todo Finish documenting this function
     */
    protected function set_xmldeclaration()  {
        if (! isset($this->xmldeclaration)) {
            $this->xmldeclaration = '';
        }
    }

    /**
     * set_doctype
     *
     * @todo Finish documenting this function
     */
    protected function set_doctype()  {
        if (! isset($this->doctype)) {
            $this->doctype = '';
        }
    }

    /**
     * set_htmlattributes
     *
     * @todo Finish documenting this function
     */
    protected function set_htmlattributes()  {
        if (! isset($this->htmlattributes)) {
            $this->htmlattributes = '';
        }
    }

    /**
     * set_headattributes
     *
     * @todo Finish documenting this function
     */
    protected function set_headattributes()  {
        if (! isset($this->headattributes)) {
            $this->headattributes = '';
        }
    }

    /**
     * set_headcontent
     *
     * @todo Finish documenting this function
     */
    protected function set_headcontent()  {
        if (! isset($this->headcontent)) {
            $this->headcontent = '';
        }
    }

    /**
     * set_bodyattributes
     *
     * @todo Finish documenting this function
     */
    protected function set_bodyattributes()  {
        if (! isset($this->bodyattributes)) {
            $this->bodyattributes = '';
        }
    }

    /**
     * set_bodycontent
     *
     * @todo Finish documenting this function
     */
    protected function set_bodycontent()  {
        if (! isset($this->bodycontent)) {
            $this->bodycontent = '';
        }
    }

    /**
     * postprocessing
     *
     * @todo Finish documenting this function
     */
    protected function postprocessing()  {
        // procesessing for this output format after content has been retrieved from cache
        // This is intended for fixes to $this->headcontent and $this->bodycontent,
        // that are not to be included in the cached data, e.g. $this->fix_title_icons()
        // If you want to fix $this->headcontent and $this->bodycontent before caching,
        // add your own "set_bodycontent()" method
        foreach ($this->javascripts as $script) {
            $this->page->requires->js('/'.$script, true);
        }
    }

    /**
     * fix_title
     *
     * @todo Finish documenting this function
     */
    public function fix_title()  {
        if (preg_match($this->tagpattern('h2'), $this->bodycontent, $matches, PREG_OFFSET_CAPTURE)) {
            // $matches: <h2 $matches[1]>$matches[2]</h2>
            $start = $matches[2][1];
            $length = strlen($matches[2][0]);
            $this->bodycontent = substr_replace($this->bodycontent, $this->get_title(), $start, $length);
        }
    }

    /**
     * fix_title_icons
     *
     * @todo Finish documenting this function
     */
    public function fix_title_icons()  {
        // add task edit icons if the current user is a teacher/administrator
        if ($this->TC->can->manage()) {
            if (preg_match($this->tagpattern('h2'), $this->bodycontent, $matches, PREG_OFFSET_CAPTURE)) {
                // $matches: <h2 $matches[1]>$matches[2]</h2>
                $start = $matches[2][1] + strlen($matches[2][0]);
                $editicon = $this->taskedit_icon(); // modedit_icon()
                $this->bodycontent = substr_replace($this->bodycontent, $editicon, $start, 0);
            }
        }
    }

   /////////////////////////////////////////////////////////////////////
    // functions to merge external page with Moodle page               //
    /////////////////////////////////////////////////////////////////////

    /**
     * generates the header for this taskchain attempt
     *
     * @return string $output
     */
    public function taskchain_header()  {
        $header = $this->header();
        // $this->xmldeclaration
        // $this->doctype
        // $this->htmlattributes
        // $this->headattributes
        if ($pos = strpos($header, '</head>')) {
            $header = substr_replace($header, $this->headcontent, $pos, 0);
        }
        // $this->bodyattributes
        return $header;
    }

    /**
     * generates the main content for this taskchain attempt
     *
     * @return string $output
     */
    public function taskchain_content()  {
        return $this->bodycontent;
    }

    /**
     * generates the footer for this taskchain attempt
     *
     * @return string $output
     */
    public function taskchain_footer()  {
        return $this->footer();
    }

    /**
     * outputs the main content for this page
     * (i.e. what comes between the header and footer)
     *
     * @return string $output
     */
    public function maincontent()  {
        return $this->bodycontent;
    }

    /////////////////////////////////////////////////////////////////////
    // functions to generate frames                                    //
    /////////////////////////////////////////////////////////////////////

    /**
     * basetag
     *
     * @uses $CFG
     * @return xxx
     * @todo Finish documenting this function
     */
    public function basetag() {
        global $CFG;
        if (empty($CFG->framename)) {
            $framename = '_top';
        } else {
            $framename = $CFG->framename;
        }
        return html_writer::empty_tag('base', array('target'=>$framename, 'href'=>''));
        // Note: href is required for strict xhtml
    }

    /**
     * fix_targets
     *
     * @uses $CFG
     * @todo Finish documenting this function
     */
    public function fix_targets()  {
        global $CFG;
        if (empty($CFG->framename)) {
            $framename = '_top';
        } else {
            $framename = $CFG->framename;
        }
        $this->bodycontent .= ''
            .'<script type="text/javascript">'."\n"
            .'//<![CDATA['."\n"
            ."function taskchain_fix_targets() {\n"
            ."	var obj = document.getElementsByTagName('a');\n"
            ."	if (obj) {\n"
            ."		var i_max = obj.length;\n"
            ."		for (var i=0; i<i_max; i++) {\n"
            ."			if (obj[i].href && ! obj[i].target) {\n"
            ."				obj[i].target = '$framename';\n"
            ."			}\n"
            ."		}\n"
            ."		var obj = null;\n"
            ."	}\n"
            ."	var obj = document.getElementsByTagName('form');\n"
            ."	if (obj) {\n"
            ."		var i_max = obj.length;\n"
            ."		for (var i=0; i<i_max; i++) {\n"
            ."			if (obj[i].action && ! obj[i].target) {\n"
            ."				obj[i].target = '$framename';\n"
            ."			}\n"
            ."		}\n"
            ."		var obj = null;\n"
            ."	}\n"
            ."}\n"
            ."HP_add_listener(window, 'load', taskchain_fix_targets);\n"
            .'//]]>'."\n"
            .'</script>'."\n"
        ;
    }

    /**
     * print frameset containing "top" and "main" frames
     *
     * @uses $CFG
     */
    public function print_frameset()  {
        global $CFG;

        $charset = 'utf-8';
        $direction = get_string('thisdirection', 'langconfig');

        $title = format_text($this->TC->task->name);
        $title_top = get_string('navigation_frame', 'mod_taskchain');
        $title_main = get_string('modulename', 'mod_taskchain');

        $src_top = $this->TC->url->attempt_frame('top');
        $src_main = $this->TC->url->attempt_frame('main');

        if (empty($CFG->taskchain_lockframe)) {
            $lock_frameset = '';
            $lock_top = '';
            $lock_main = '';
        } else {
            $lock_frameset = ' border="0" frameborder="0" framespacing="0"';
            $lock_top = ' noresize="noresize" scrolling="no"';
            $lock_main = ' noresize="noresize"';
        }

        if (empty($CFG->taskchain_frameheight)) {
            $rows =  85; // default
        } else {
            $rows = $CFG->taskchain_frameheight;
        }

        echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">'."\n";
        echo '<html dir="'.$direction.'">'."\n";
        echo '<head>'."\n";
        echo '<meta http-equiv="content-type" content="text/html; charset='.$charset.'" />'."\n";
        echo $this->basetag()."\n";
        echo '<title>'.$title.'</title>'."\n";
        echo '</head>'."\n";
        echo '<frameset rows="'.$rows.',*".'.$lock_frameset.'>'."\n";
        echo '<frame title="'.$title_top.'" src="'.$src_top.'"'.$lock_top.' />'."\n";
        echo '<frame title="'.$title_main.'" src="'.$src_main.'"'.$lock_main.' />'."\n";
        echo '<noframes>'."\n";
        echo '<p>'.get_string('framesetinfo').'</p>'."\n";
        echo '<ul>'."\n";
        echo '<li><a href="'.$src_top.'">'.$title_top.'</a></li>'."\n";
        echo '<li><a href="'.$src_main.'">'.$title_main.'</a></li>'."\n";
        echo '</ul>'."\n";
        echo '</noframes>'."\n";
        echo '</frameset>'."\n";
        echo '</html>'."\n";
    }

    /**
     * print "top" frame, containing Moodle navigation bar
     */
    public function print_topframe()  {
        echo $this->header();
        echo $this->footer();
    }

    /**
     * print a page embedded in an object in a standard Moodle page
     *
     * for XHTML 1.0 Strict compatability, the embedded page should be implemented
     * using an <object> not an <iframe>. However, IE <object>'s are problematic
     * (links and forms cannot escape), so we use conditional comments to display
     * an <iframe> in IE and an <object> in other browsers
     */
    public function print_embed_object_page()  {
        // external javascript to adjust height of iframe
        $this->page->requires->js('/mod/taskchain/attempt/iframe.js', true);

        echo $this->header();

        // set object attributes
        $id = $this->embed_object_id;
        $width = '100%';
        $height = '100%';
        $onload_function = $this->embed_object_onload;
        $src = $this->TC->url->attempt_frame('main');

        // print the html element to hold the embedded html page
        // Note: the iframe in IE needs a "name" attribute for the resizing to work
        echo '<!--[if IE]>'."\n";
        echo '<iframe name="'.$id.'" id="'.$id.'" src="'.$src.'" width="'.$width.'" height="'.$height.'"></iframe>'."\n";
        echo '<![endif]-->'."\n";
        echo '<!--[if !IE]> <-->'."\n";
        echo '<object id="'.$id.'" type="text/html" data="'.$src.'" width="'.$width.'" height="'.$height.'"></object>'."\n";
        echo '<!--> <![endif]-->'."\n";

        // javascript to add onload event handler - we do this here because
        // an object tag should have no onload attribute in XHTML 1.0 Strict
        echo '<script type="text/javascript">'."\n";
        echo '//<![CDATA['."\n";
        echo "var obj = document.getElementById('$id');\n";
        echo "if (obj) {\n";
        echo "	if (obj.addEventListener) {\n";
        echo "		obj.addEventListener('load', $onload_function, false);\n";
        echo "	} else if (obj.attachEvent) {\n";
        echo "		obj.attachEvent('onload', $onload_function);\n";
        echo "	} else {\n";
        echo "		obj['onload'] = $onload_function;\n";
        echo "	}\n";
        echo "}\n";
        echo "obj = null;\n";
        echo '//]]>'."\n";
        echo '</script>'."\n";

        echo $this->footer();
    }

    /////////////////////////////////////////////////////////////////////
    // functions to set and get cached content                         //
    /////////////////////////////////////////////////////////////////////

    /**
     * get_cache_md5key
     *
     * @uses $CFG
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_cache_md5key() {
        global $CFG;
        return md5($this->TC->task->outputformat . $this->page->theme->name . $CFG->wwwroot);
    }

    /**
     * get_fields_from_cache
     *
     * @uses $CFG
     * @uses $DB
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_fields_from_cache()  {
        global $CFG, $DB;

        if (isset($this->cache_uptodate)) {
            return $this->cache_uptodate;
        }

        // assume cache is not up-to-date
        $this->cache_uptodate = false;

        if (empty($CFG->taskchain_enablecache)) {
            return false; // cache not enabled
        }

        if (! self::USE_CACHE) {
            return false; // this renderer doesn't use a cache
        }

        $select = 'taskid = ? AND md5key = ?';
        $params = array($this->TC->task->id, $this->get_cache_md5key());
        if (! $this->cache = $DB->get_record_select('taskchain_cache', $select, $params)) {
            return false; // no cached content for this task (+ outputformat + currenttheme + wwwroot)
        }

        foreach ($this->cache_CFG_fields as $field) {
            if ($this->cache->$field != $CFG->$field) {
                return false; // $CFG settings have changed
            }
        }
        foreach ($this->cache_task_fields as $field) {
            if ($this->cache->$field != $this->TC->task->$field) {
                return false; // task settings have changed
            }
        }

        // custom fields
        $fileareas = array('source', 'config');
        foreach ($fileareas as $filearea) {
            if (isset($this->TC->task->$filearea) && $this->TC->task->$filearea) {
                $timemodified = $this->cache->timemodified;
                $repositoryid = $this->cache->{$filearea.'repositoryid'};
                $lastmodified = $this->cache->{$filearea.'lastmodified'};
                $etag         = $this->cache->{$filearea.'etag'};
                if ($timemodified < $this->TC->task->$filearea->filemtime($lastmodified, $etag)) {
                    return false; // file has been modified
                }
                if (method_exists($this->TC->task->$filearea->file, 'get_repository_id')) {
                    if ($repositoryid != $this->TC->task->$filearea->file->get_repository_id()) {
                        return false; // file repository id has changed
                    }
                }
            }
        }

        if ($this->TC->task->useglossary) {
            $select = 'course = ? AND module = ? AND action IN (?, ?, ?, ?) AND time > ?';
            $params = array(
                $this->TC->course->id, 'glossary',
                'add entry','approve entry','update entry','delete entry',
                $this->cache->timemodified
            );
            if ($DB->record_exists_select('log', $select, $params)) {
                return false; // glossary entries (for this course) have been modified
            }
        }

        // if we get this far then the cache content is uptodate

        // transfer cache content to this task object
        $content = unserialize(base64_decode($this->cache->content));
        foreach ($this->cache_content_fields as $field) {
            $this->$field = $content->$field;
        }
        $this->cache_uptodate = true;
        return $this->cache_uptodate;
    }

    /**
     * set_fields_in_cache
     *
     * @uses $CFG
     * @uses $DB
     * @return xxx
     * @todo Finish documenting this function
     */
    public function set_fields_in_cache()  {
        global $CFG, $DB;

        if (empty($CFG->taskchain_enablecache)) {
            return; // cache not enabled
        }

        if ($this->cache_uptodate) {
            return; // cache is already uptodate
        }

        if (! $this->cache) {
            $this->cache = new stdClass();
        }

        // add special fields to cache record
        $this->cache->taskid = $this->TC->task->id;
        $this->cache->md5key = $this->get_cache_md5key();
        $this->cache->timemodified = time();

        // transfer $CFG fields to cache record
        foreach ($this->cache_CFG_fields as $field) {
            $this->cache->$field = trim($CFG->$field);
        }

        // transfer task fields to cache record
        foreach ($this->cache_task_fields as $field) {
            $this->cache->$field = $this->TC->task->$field;
        }

        // transfer remote access fields to cache record
        foreach ($this->cache_remote_fields as $field) {
            $sourcefield = 'source'.$field;
            $configfield = 'config'.$field;
            $this->cache->$sourcefield = ''; // $this->TC->task->source->$field;
            $this->cache->$configfield = ''; // $this->TC->task->source->config->$field;
        }

        $fileareas = array('source', 'config');
        foreach ($fileareas as $filearea) {
            $this->cache->{$filearea.'repositoryid'} = 0; // default
            if (isset($this->TC->task->$filearea->file)) {
                if (method_exists($this->TC->task->$filearea->file, 'get_repository_id')) {
                    if ($repositoryid = $this->TC->task->$filearea->file->get_repository_id()) {
                        $this->cache->{$filearea.'repositoryid'} = $repositoryid;
                    }
                }
            }
        }

        // create content object
        $content = new stdClass();
        foreach ($this->cache_content_fields as $field) {
            $content->$field = $this->$field;
        }

        // serialize the $content object
        $this->cache->content = base64_encode(serialize($content));

        // add / update the cache record
        if (isset($this->cache->id)) {
            if (! $DB->update_record('taskchain_cache', $this->cache)) {
                print_error('error_updaterecord', 'taskchain', '', 'taskchain_cache');
            }
        } else {
            if (! $this->cache->id = $DB->insert_record('taskchain_cache', $this->cache)) {
                print_error('error_insertrecord', 'taskchain', '', 'taskchain_cache');
            }
        }

        // cache record was successfully updated/inserted
        $this->cache_uptodate = true;
        return $this->cache_uptodate;
    }

    /**
     * get_name
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_name()  {
        return $this->TC->task->source->get_name();
    }

    /**
     * get_title
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_title()  {
        switch ($this->TC->task->title & mod_taskchain::TITLE_SOURCE) {
            case mod_taskchain::TEXTSOURCE_FILE:
                $title = $this->TC->task->source->get_title();
                break;
            case mod_taskchain::TEXTSOURCE_FILENAME:
                $title = basename($this->TC->task->sourcefile);
                break;
            case mod_taskchain::TEXTSOURCE_FILEPATH:
                $title = ltrim($this->TC->task->sourcefile, '/');
                break;
            case mod_taskchain::TEXTSOURCE_SPECIFIC:
            default:
                $title = $this->TC->task->name;
                $title = format_string($title);
                // format_string() is declared in lib/weblib.php
                // it will pass $title through filters (e.g. multilang)
                // and may also clean (i.e. remove tags from) it
        }

        if ($this->TC->task->title & mod_taskchain::TITLE_CHAINNAME) {
            $title = $this->TC->task->name.': '.$title;
        }
        if ($this->TC->task->title & mod_taskchain::TITLE_SORTORDER) {
            $title .= ' ('.$this->TC->task->sortorder.')';
        }

        $title = mod_taskchain::textlib('utf8_to_entities', $title);

        return $title;
    }

    /////////////////////////////////////////////////////////////////////
    // utility functions to indicate whether user can resume, restart  //
    /////////////////////////////////////////////////////////////////////

    // does this output format allow task attempts to be resumed?

    /**
     * provide_resume
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function provide_resume()  {
        return false;
    }

    // does this output format allow a clickreport
    // show a click trail of what students clicked

    /**
     * provide_clickreport
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function provide_clickreport()  {
        return false;
    }

    // can the current chain/task attempt be paused and resumed later?

    /**
     * can_resume
     *
     * @uses $this->TC
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    public function can_resume($type)  {
        if ($type=='chain' || ($type=='task' && $this->provide_resume())) {
            if (isset($this->TC->$type) && $this->TC->$type->allowresume) {
                return true;
            }
        }
        return false;
    }

    // can the current chain/task be restarted after the current attempt finishes?

    /**
     * can_restart
     *
     * @uses $this->TC
     * @param xxx $type
     * @return xxx
     * @todo Finish documenting this function
     */
    public function can_restart($type)  {
        if (isset($this->TC->$type) && $this->TC->$type->attemptlimit) {
            if ($attempts = $this->TC->get_attempts($type)) {
                if (count($attempts) >= $this->TC->$type->attemptlimit) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * can_continue
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function can_continue()  {
        if ($this->can_resume('chain')) {
            if ($this->can_resume('task')) {
                return mod_taskchain::CONTINUE_RESUMETASK;
            } else if ($this->can_restart('task')) {
                return mod_taskchain::CONTINUE_RESTARTTASK;
            }
        }
        if ($this->can_restart('chain')) {
            return mod_taskchain::CONTINUE_RESTARTCHAIN;
        } else {
            return mod_taskchain::CONTINUE_ABANDONCHAIN;
        }
    }

    /**
     * can_clickreport
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function can_clickreport()  {
        if ($this->provide_clickreport() && isset($this->TC) && $this->TC->task->clickreporting) {
            return true;
        } else {
            return false;
        }
    }

    /////////////////////////////////////////////////////////////////////
    // utility functions for extracting and cleaning html              //
    /////////////////////////////////////////////////////////////////////

    /**
     * remove_blank_lines
     *
     * @param xxx $str
     * @return xxx
     * @todo Finish documenting this function
     */
    public function remove_blank_lines($str)  {
        // standardize line endings and remove trailing white space and blank lines
        return preg_replace('/\s+[\r\n]/s', "\n", $str);
    }

    /**
     * single_line
     *
     * @param xxx $str
     * @return xxx
     * @todo Finish documenting this function
     */
    public function single_line($str)  {
        return trim(preg_replace('/\s+/s', ' ', $str));
    }

    /**
     * tagpattern
     *
     * @param xxx $tag
     * @param xxx $attribute (optional, default='')
     * @param xxx $returncontent (optional, default=true)
     * @param xxx $before (optional, default='')
     * @param xxx $after (optional, default='')
     * @return xxx
     * @todo Finish documenting this function
     */
    public function tagpattern($tag, $attribute='', $returncontent=true, $before='', $after='')  {
        // $0 : entire match
        // if $attribute is empty
        //     $1 : all tag attrbutes
        //     $2 : content (if required)
        // if $attribute is NOT empty
        //     $1 : all tag attrbutes
        //     $2 : first quote of required attibute
        //     $3 : value of required attibute
        //     $4 : closing quote of required attibute
        //     $5 : content (if required)
        if ($attribute) {
            $attribute .= '=(["\'])(.*?)(\\2)[^>]*';
        }
        if ($returncontent) {
            $content = '(.*?)<\/'.$tag.'>';
        } else {
            $content = '';
        }
        return '/'.$before.'<'.$tag.'([^>]*'.$attribute.')>'.$content.$after.'/is';
    }

    /////////////////////////////////////////////////////////////////////
    // utility functions for fixing css, media, onload, and urls       //
    /////////////////////////////////////////////////////////////////////

    /**
     * fix_css_definitions
     *
     * @param xxx $match
     * @return xxx
     * @todo Finish documenting this function
     */
    public function fix_css_definitions($match)  {
        global $CFG;

        $container = '#'.$this->themecontainer;
        $css_selector = $match[1];
        $css_definition = $match[2];

        // standardize indent to a single tab
        $css_definition = preg_replace('/^[\t ]*(?=[^\n\r\t ])/m', "\t", $css_definition);

        // additional CSS for list items
        $listitem_css = '';

        $selectors = array();
        foreach (explode(',', $css_selector) as $selector) {
            if ($selector = trim($selector)) {
                switch (true) {

                    case preg_match('/^html\b/i', $selector):
                        // leave "html" as it is
                        $selectors[] = "$selector";
                        break;

                    case preg_match('/^body\b/i', $selector):

                        // by default, we do nothing here, so that
                        // HP styles do not affect the Moodle theme

                        // if this site is set to enable HP body styles
                        // we replace "body" with the container element

                        if (empty($CFG->taskchain_bodystyles)) {
                            $bodystyles = 0;
                        } else {
                            // reduce body styles using logical OR
                            $callback = array($this, 'fix_css_reduce_bodystyles');
                            $bodystyles = explode(',', $CFG->taskchain_bodystyles);
                            $bodystyles = array_reduce($bodystyles, $callback, 0);
                        }

                        // remove font, margin, backgroud and color from the css definition
                        $search = array();
                        if (! ($bodystyles & mod_taskchain::BODYSTYLES_BACKGROUND)) {
                            // background-color, background-image
                            $search[] = '(?:background[a-z-]*)';
                        }
                        if (! ($bodystyles & mod_taskchain::BODYSTYLES_COLOR)) {
                            // color (the text color)
                            $search[] = '(?:color[a-z-]*)';
                        }
                        if (! ($bodystyles & mod_taskchain::BODYSTYLES_FONT)) {
                            // font-size, font-family
                            $search[] = '(?:font[a-z-]*)';
                        }
                        if (! ($bodystyles & mod_taskchain::BODYSTYLES_MARGIN)) {
                            // margin-left, margin-right
                            $search[] = '(?:margin[a-z-]*)';
                        }
                        if ($search = implode('|', $search)) {
                            $search = "/[ \t]+($search)[^;]*;[ \t]*[\n\r]*/";
                            $css_definition = preg_replace($search, '', $css_definition);
                        }
                        if (trim($css_definition)) {
                            $selectors[] = "$container";
                        }
                        break;

                    default:
                        // we need to do some special processing of CSS for list items
                        // override standard Moodle 2.0 setting of li {list-style-type }
                        if (preg_match('/(ol|ul)([.#]\w+)?$/', $selector)) {
                            // CSS for a list ("ol" or "ul")
                            $search = '/\s*'.'list-style-type'.'\s*:\s*'.'[a-zA-Z0-9_-]+/';
                            if (preg_match($search, $css_definition, $matches)) {
                                $listitem_css .= "\n$container $selector li {\n".$matches[0].";\n}";
                            }
                        } else {
                            // we need to override the Moodle theme's background-image of buttons
                            // because these have hitherto played an important role in HP styles
                            $count = 0;
                            $selector = preg_replace('/\.FuncButton/', 'button$0', $selector, -1, $count);
                            if ($count && strpos($css_definition, 'background-image')===false) {
                                $css_definition .= "\n\tbackground-image: none;\n";
                            }
                        }

                        // restrict other CSS selectors to affect only the content of the container element
                        $selectors[] = "$container $selector";
                }
            }
        }
        if (empty($selectors)) {
            return '';
        } else {
            return implode(",\n", $selectors)."\n".'{'.$css_definition.'}'.$listitem_css;
        }
    }

    /**
     * fix_css_reduce_bodystyles
     *
     * @param xxx $carry
     * @param xxx $item
     * @return xxx
     * @todo Finish documenting this function
     */
    function fix_css_reduce_bodystyles($carry, $item) {
        return ($carry | $item);
    }

    /**
     * fix_onload
     *
     * @param xxx $onload
     * @param xxx $script_tags (optional, default=false)
     * @return xxx
     * @todo Finish documenting this function
     */
    function fix_onload($onload, $script_tags=false) {
        static $taskid = 0;

        $str = '';
        if ($script_tags) {
            $str .= "\n".'<script type="text/javascript">'."\n"."//<![CDATA[\n";
        }
        if ($taskid && $taskid==$this->TC->task->id) {
            // do nothing
        } else {
            // only do this once per task
            $taskid = $this->TC->task->id;

            if ($this->TC->task->allowpaste) {
                $allowpaste = 'true';
            } else {
                $allowpaste = 'false';
            }
            $str .= ''
                // By default, pasting of answers is NOT allowed.
                // To allow it: window.allow_paste_input = true;
                ."function HP_setup_input_and_textarea() {\n"
                ."	if (window.allow_paste_input || window.enable_paste_input || $allowpaste) {\n"
                ."		var disablepaste = false;\n"
                ."	} else {\n"
                ."		var disablepaste = true;\n"
                ."	}\n"
                ."	var obj = document.getElementsByTagName('input');\n"
                ."	if (obj) {\n"
                ."		var i_max = obj.length;\n"
                ."		for (var i=0; i<i_max; i++) {\n"
                ."			if (obj[i].type=='text') {\n"
                ."				if (disablepaste) {\n"
                ."					HP_add_listener(obj[i], 'drop', HP_disable_event);\n"
                ."					HP_add_listener(obj[i], 'paste', HP_disable_event);\n"
                ."				}\n"
                ."				HP_add_listener(obj[i], 'focus', HP_send_results);\n" // keydown, mousedown ?
                ."			}\n"
                ."		}\n"
                ."	}\n"
                ."	var obj = document.getElementsByTagName('textarea');\n"
                ."	if (obj) {\n"
                ."		var i_max = obj.length;\n"
                ."		for (var i=0; i<i_max; i++) {\n"
                ."			if (disablepaste) {\n"
                ."				HP_add_listener(obj[i], 'drop', HP_disable_event);\n"
                ."				HP_add_listener(obj[i], 'paste', HP_disable_event);\n"
                ."			}\n"
                ."			HP_add_listener(obj[i], 'focus', HP_send_results);\n"
                ."		}\n"
                ."	}\n"
                ."	obj = null;\n"
                ."}\n"

                ."HP_add_listener(window, 'load', HP_setup_input_and_textarea);\n"

                // ensure keydown (not keypress) event handler is assigned
                // to prevent leaving page when user hits delete key
                ."if (window.SuppressBackspace) {\n"
                ."	HP_add_listener(window, 'keydown', SuppressBackspace);\n"
                ."}\n"
            ;
        }
        $onload_oneline = preg_replace('/\s+/s', ' ', $onload);
        $onload_oneline = preg_replace("/[\\']/", '\\\\$0', $onload_oneline);
        $onload_oneline = preg_replace('/^(\w+)\(\)$/', '$1', $onload_oneline);
        if (preg_match('/^(\w+)$/', $onload_oneline)) {
            $str .= "HP_add_listener(window, 'load', $onload_oneline);\n";
        } else {
            $str .= "HP_add_listener(window, 'load', '$onload_oneline');\n";
        }
        if ($script_tags) {
            $str .= "//]]>\n"."</script>\n";
        }
        return $str;
    }

    /**
     * fix_mediafilter
     *
     * @uses $CFG
     * @return xxx
     * @todo Finish documenting this function
     */
    public function fix_mediafilter()  {
        global $CFG;

        if (! $this->TC->task->usemediafilter) {
            return false;
        }

        if (! mod_taskchain::load_mediafilter_filter($this->TC->task->usemediafilter)) {
            return false;
        }
        $mediafilterclass = 'taskchain_mediafilter_'.$this->TC->task->usemediafilter;
        $mediafilter = new $mediafilterclass($this);

        $mediafilter->fix('headcontent', $this);
        $mediafilter->fix('bodycontent', $this);

        if ($mediafilter->js_inline) {

            if (strpos($mediafilter->js_inline, 'add_audio_player')) {
                if ($this->fix_audio_immediately) {
                    $mediafilter->js_inline .= ''
                        .'<script type="text/javascript">'."\n"
                        ."//<![CDATA[\n"
                        .$this->fix_audio_loader()
                        ."//]]>\n"
                        .'</script>'."\n"
                    ;
                }
            }

            // remove the internal </script><script ... > joins from the inline javascripts (js_inline)
            $search = '/(?:\s*\/\/\]\]>)?'.'\s*<\/script>\s*<script type="text\/javascript">'.'(?:\s*\/\/<!\[CDATA\[[ \t]*)?/is';
            $mediafilter->js_inline = preg_replace($search, '', $mediafilter->js_inline);

            // extract urls of deferred scripts from $mediafilter->js_external
            if (preg_match_all($this->tagpattern('script'), $mediafilter->js_external, $scripts, PREG_OFFSET_CAPTURE)) {
                $deferred_js = array();
                $strlen_wwwroot = strlen($CFG->wwwroot);
                foreach (array_reverse($scripts[0]) as $script) {
                    // $script [0] => matched string, [1] => offset to start of matched string
                    $remove = false;
                    if (strpos($script[0], 'type="text/javascript"')) {
                        if (preg_match('/src="(.*?)"/i', $script[0], $matches)) {
                            if (strpos($script[0], 'defer="defer"')===false) {
                                $inhead = true;
                            } else {
                                $inhead = false;
                            }
                            // we do not add scripts with $this->page->requires->js() because
                            // they will not then be added when content is retrieved from cache
                            //if (substr($matches[1], 0, $strlen_wwwroot)==$CFG->wwwroot) {
                            //    $this->page->requires->js(substr($matches[1], $strlen_wwwroot), $inhead);
                            //    $remove = true;
                            //} else
                            if ($inhead) {
                                // leave this script where it is (i.e. in the head)
                            } else {
                                array_unshift($deferred_js, '"'.addslashes_js($matches[1]).'"');
                                $remove = true;
                            }
                        }
                    }
                    if ($remove) {
                        $mediafilter->js_external = substr_replace($mediafilter->js_external, '', $script[1], strlen($script[0]));
                    }
                }
                $deferred_js = implode(',', array_unique($deferred_js));
            } else {
                $deferred_js = '';
            }
            if ($deferred_js) {
                $deferred_js = ''
                    .'  // load deferred scripts'."\n"
                    .'  var head = document.getElementsByTagName("head")[0];'."\n"
                    .'  var urls = new Array('.$deferred_js.');'."\n"
                    .'  for (var i=0; i<urls.length; i++) {'."\n"
                    .'    var script = document.createElement("script");'."\n"
                    .'    script.type = "text/javascript";'."\n"
                    .'    script.src = urls[i];'."\n"
                    .'    head.appendChild(script);'."\n"
                    .'  }'."\n"
                ;
            }

            $functions = '';
            if (preg_match_all('/(?<=function )\w+/', $mediafilter->js_inline, $names)) {
                foreach ($names[0] as $name) {
                    list($start, $finish) = $this->locate_js_block('function', $name, $mediafilter->js_inline, true);
                    if ($finish) {
                        $functions .= trim(substr($mediafilter->js_inline, $start, ($finish - $start)))."\n";
                        $mediafilter->js_inline = substr_replace($mediafilter->js_inline, '', $start, ($finish - $start));
                    }
                }
            }

            // put all the inline javascript into one single function called "taskchain_mediafilter_loader()",
            // which also loads up any deferred js, and force this function to be run when the page has loaded
            $onload = 'taskchain_mediafilter_loader()';
            $search = '/(\/\/<!\[CDATA\[)(.*)(\/\/\]\]>)/s';
            $replace = '$1'."\n"
                .$functions
                .'function '.$onload.'{'
                .'$2'
                //."\n"
                .$deferred_js // load deferred scripts, if any
                .$this->fix_mediafilter_onload_extra()
                .'}'."\n"
                .$this->fix_onload($onload)
                .'$3'
            ;
            $mediafilter->js_inline = preg_replace($search, $replace, $mediafilter->js_inline, 1);

            // append the inline javascripts to the end of the bodycontent
            $this->bodycontent .= $mediafilter->js_inline;
        }

        if ($mediafilter->js_external) {
            // append the external javascripts to the head content
            $this->headcontent .= $mediafilter->js_external;
        }
    }

    /**
     * fix_audio_loader
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function fix_audio_loader()  {
        return ''
            ."	if (window.M && M.util && M.util.audio_players) {\n"
            ."		var i_max = (M.util.audio_players.length - 1);\n"
            ."		for (var i=i_max; i>=0; i--) {\n"
            ."			var id = M.util.audio_players[i].id;\n"
            ."			if (document.getElementById(id)==null) {\n"
            ."				M.util.audio_players.splice(i, 1);\n"
            ."			}\n"
            ."		}\n"
            ."		M.util.load_flowplayer();\n"
            ."	}\n"
        ;
    }

    /**
     * fix_mediafilter_onload_extra
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function fix_mediafilter_onload_extra()  {
        return '';
    }

    /**
     * fix_relativeurls
     *
     * @param xxx $str (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function fix_relativeurls($str=null)  {
        // elements of the regular expression which will search for the URLs

        // tags may be present in a number of formats:
        //  * plain ascii text     : < ... >
        //  * javascript unicode   : \u003C ... \u003CE
        //  * html entities        : &lt; ... &gt;
        //  * html entities in xml : &amp;#x003C; ... &amp;#x003E;
        $tagopen = '(?:(<)|(\\\\u003C)|(&lt;)|(&amp;#x003C;))'; // left angle bracket
        $tagclose = '(?(2)>|(?(3)\\\\u003E|(?(4)&gt;|(?(5)&amp;#x003E;))))'; //  right angle bracket (to match left angle bracket)

        $space = '\s+'; // at least one space
        $equals = '\s*=\s*'; // equals sign (+ white space)
        $anychar = '(?:[^>]*?)'; // any character

        // quotes around the attribute value may appear in a number of formats:
        //  * plain ascii text     : " ... "
        //                         : ' ... '
        //  * escaped javascript   : \" ... \"
        //                         : \' ... \'
        //  * html entities        : &quot; ... &quot;
        //                         : &apos; ... &apos;
        //  * html entities in xml : &amp;quot; ... &amp;quot;
        //                         : &amp;apos; ... &amp;apos;
        $quoteopen  = '("|\\\\"|&quot;|&amp;quot;'."|'|\\\\'|&apos;|&amp;apos;".')'; // open quote
        $quoteclose = '\\6'; // close quote (to match open quote)
        $url        = '.*?'; // chars between quotes (non-greedy)

        // define which attributes of which HTML tags to search for URLs
        $tags = array(
            // tag  =>  attribute containing url
            'a'      => 'href',
            'area'   => 'href', // <area href="sun.htm" ... shape="..." coords="..." />
            'embed'  => 'src',
            'iframe' => 'src',
            'img'    => 'src',
            'input'  => 'src', // <input type="image" src="..." >
            'link'   => 'href',
            'object' => 'data',
            'param'  => 'value',
            'script' => 'src',
            'source' => 'src', // HTML5
            'track'  => 'src', // HTML5
            '(?:table|th|td)' => 'background',
            '[a-z]+' => 'style'
        );

        foreach ($tags as $tag=>$attribute) {
            $search = "/($tagopen$tag$space$anychar$attribute$equals$quoteopen)($url)($quoteclose$anychar$tagclose)/is";
            if ($attribute=='style') {
                $callback = array($this, 'convert_urls_css');
            } else if ($tag=='param') {
                $callback = array($this, 'convert_url_param');
            } else {
                $callback = array($this, 'convert_url_relative');
            }
            if (is_string($str)) {
                $str = preg_replace_callback($search, $callback, $str);
            } else {
                $this->headcontent = preg_replace_callback($search, $callback, $this->headcontent);
                $this->bodycontent = preg_replace_callback($search, $callback, $this->bodycontent);
            }
        }

        if (is_string($str)) {
            return $str;
        }

        // replace relative URLs in stylesheets
        $search = '/'.'(<style[^>]*>)'.'(.*?)'.'(<\/style>)'.'/is';
        $callback = array($this, 'convert_urls_css');
        $this->headcontent = preg_replace_callback($search, $callback, $this->headcontent);
        $this->bodycontent = preg_replace_callback($search, $callback, $this->bodycontent);

        // replace relative URLs in <a ... onclick="window.open('...')...">...</a>
        $search = '/'.'('.'onclick="'."window.open\('".')'."([^']*)".'('."'[^\)]*\);return false;".'")'.'/is';
        $callback = array($this, 'convert_url');
        $this->bodycontent = preg_replace_callback($search, $callback, $this->bodycontent);
    }

    /**
     * convert_urls_css
     *
     * @param xxx $match
     * @return xxx
     * @todo Finish documenting this function
     */
    public function convert_urls_css($match)  {
        $before = $match[1];
        $css    = $match[count($match) - 2];
        $after  = $match[count($match) - 1];

        $search = '/(url\(['."'".'"]?)(.+?)(['."'".'"]?\))/is';
        $callback = array($this, 'convert_url');
        return $before.preg_replace_callback($search, $callback, $css).$after;
    }

    /**
     * convert_url_param
     *
     * @param xxx $match
     * @return xxx
     */
    public function convert_url_param($match)  {
        // make sure the param "name" attribute is one we know about
        $quote = $match[6];
        $search = "/name\s*=\s*$quote(?:data|movie|src|url|FlashVars)$quote/i";
        if (preg_match($search, $match[0])) {
            return $this->convert_url_relative($match);
        } else {
            return $match[0];
        }
    }

    /**
     * convert_url_relative
     *
     * @param xxx $match
     * @return xxx
     * @todo Finish documenting this function
     */
    public function convert_url_relative($match)  {
        if (is_string($match)) {
            $before = '';
            $url    = $match;
            $after  = '';
        } else {
            $before = $match[1];
            $url    = $match[count($match) - 2];
            $after  = $match[count($match) - 1];
        }

        switch (true) {
            case preg_match('/^'.'\w+=[^&]+'.'('.'&((amp;#x0026;)?amp;)?'.'\w+=[^&]+)*'.'$/', $url):
                // catch <PARAM name="FlashVars" value="TheSound=soundfile.mp3">
                //  ampersands can appear as "&", "&amp;" or "&amp;#x0026;amp;"
                $query = $url;
                $url = '';
                $fragment = '';
                break;

            case preg_match('/^'.'([^?]*)'.'((?:\?[^#]*)?)'.'((?:#.*)?)'.'$/', $url, $matches):
                // parse the $url into $matches
                //  [1] path
                //  [2] query string, if any
                //  [3] anchor fragment, if any
                $url = $matches[1];
                $query = $matches[2];
                $fragment = $matches[3];
                break;

            default:
                // there appears to be no query or fragment in this url
                $query = '';
                $fragment = '';
        } // end switch

        // convert the filepath part of the url
        if ($url) {
            $url = $this->convert_url($url);
        }

        // convert urls, if any, in the query string
        if ($query) {
            $search = '/'.'((?:file|song_url|src|thesound|mp3)=)([^&]+)(&|$)/is';
            $callback = array($this, 'convert_url');
            $query = preg_replace_callback($search, $callback, $query);
        }

        // return the reconstructed tag (with converted url)
        return $before.$url.$query.$fragment.$after;
    }

    /**
     * convert_url
     *
     * @uses $CFG
     * @param xxx $match
     * @return xxx
     * @todo Finish documenting this function
     */
    public function convert_url($match)  {
        global $CFG;

        if (is_string($match)) {
            $before = '';
            $url    = $match;
            $after  = '';
        } else {
            $before = $match[1];
            $url    = $match[count($match) - 2];
            $after  = $match[count($match) - 1];
        }

        $source = $this->TC->task->get_source();
        $baseurl = $source->baseurl;
        $sourcefile = $source->filepath;

        if ($CFG->slasharguments) {
            $file_php = 'file.php';
        } else {
            $file_php = 'file.php?file=';
        }
        $pluginfile_php = 'plugin'.$file_php;

        // %domainfiles% is not needed, because the same effect can be achieved using simply "/my-file.html"
        //if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') {
        //    $http = 'https';
        //} else {
        //    $http = 'http';
        //}
        //$url = str_replace('%domainfiles%', $http.'://'.$_SERVER[SERVER_NAME].'/', $url);

        // %taskfiles% is not needed, because this is the default behavior for a relative URL
        // $url = str_replace('%taskfiles%', $CFG->wwwroot.'/'.$baseurl, $url);

        // substitute %wwwroot%, %sitepage/files%, %coursepage/files%, %modulepage/files%
        $replace_pairs = array(
            '%wwwroot%' => $CFG->wwwroot,
            '%sitepage%' => $CFG->wwwroot.'/course/view.php?id='.SITEID,
            '%sitefiles%' => $CFG->wwwroot.'/'.$file_php.'/'.SITEID,
            '%coursepage%' => $CFG->wwwroot.'/course/view.php?id='.$this->TC->course->id,
            '%coursefiles%' => $CFG->wwwroot.'/'.$file_php.'/'.$this->TC->course->id,
            '%modulepage%' => $CFG->wwwroot.'/mod/taskchain/view.php?id='.$this->TC->coursemodule->id,
            '%modulefiles%' => $CFG->wwwroot.'/'.$pluginfile_php.'/'.$this->TC->coursemodule->context->id.'/mod_taskchain/sourcefile'
        );
        $url = strtr($url, $replace_pairs);

        if (preg_match('/^(?:\/|(?:[a-zA-Z0-9]+:))/', $url)) {
            // no processing  - this is already an absolute url (http:, mailto:, javascript:, etc)
            return $before.$url.$after;
        }

        // get the subdirectory, $dir, of the task $sourcefile
        $dir = dirname($sourcefile);

        if ($baseurl=='' && preg_match('/^https?:\/\//', $dir)) {
            $url = $dir.'/'.$url;
        } else {
            // remove leading "./" and "../"
            while (preg_match('/^(\.{1,2})\/(.*)$/', $url, $matches)) {
                if ($matches[1]=='..') {
                    $dir = dirname($dir);
                }
                $url = $matches[2];
            }
            // add subdirectory, $dir, to $baseurl, if necessary
            if ($dir=='' || $dir=='/' || $dir=='.' || $dir=='..') {
                // do nothing
            } else {
                $baseurl .= '/'.trim($dir, '/');
            }
            // prefix $url with $baseurl
            $url = "$baseurl/$url";
        }

        return $before.$url.$after;
    }
}
