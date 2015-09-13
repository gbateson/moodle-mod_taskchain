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
 * mod/taskchain/source/class.php
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
require_once($CFG->dirroot.'/lib/xmlize.php');

/**
 * taskchain_source
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_source {
    /** @var stored_file object representing stored file */
    public $file;

    /** @var int the course id associated with this object */
    public $courseid = 0;

    /** @var int the file's location (0 : site files, 1 : course files) */
    public $location = 0;

    /** @var int the course folder within the Moodle data folder (either $courseid or SITEID) */
    public $coursefolder = 0;

    /** @var string the full path to the course folder (i.e. $CFG->dataroot.'/'.$coursefolder) */
    public $basepath = '';

    /** @var string the file's path (relative to $basepath) */
    public $filepath = '';

    /** @var string the full path to this file (i.e. $basepath.'/'.$filepath) */
    public $fullpath = '';

    /** @var string the full path to the folder containing this file */
    public $dirname  = '';

    /** @var string the file name */
    public $filename = '';

    /** @var string the URL of this file */
    public $url = '';

    /** @var string the base url for this file */
    public $baseurl = '';

    /** @var string the contents of the source file */
    public $filecontents;

    /*
     * properties for efficiently fetching remotely hosted files using Conditional GET
     */

    /** @var string remote server's representation of time file was last modified */
    public $lastmodified = '';

    /** @var string (md5?) key indentifying remote file */
    public $etag = '';

    /** @var string remote server's representation of current time */
    public $date = '';

    /*
     * properties for a chain source file (e.g. a Hot Potatoes Masher file)
     */

    /** @var string the chain name extracted from the source file */
    public $chainname;

    /** @var string the chain entry text, extracted from the source file */
    public $chainentrytext;

    /** @var string the chain exit text, extracted from the source file */
    public $chainexittext;

    /** @var string array of taskchain_source objects for tasks in this chain */
    public $sources;

    /*
     * properties of the icon for this source file type
     */

    /** @var string the path (below $CFG->wwwroot) to the icon for this file */
    public $icon = 'mod/taskchain/icon.gif';

    /** @var string the display width for this file's icon */
    public $iconwidth = '16';

    /** @var string the display height for this file's icon */
    public $iconheight = '16';

    /** @var string the css class this file's icon */
    public $iconclass = 'icon';

    /**
     * output formats for this file type
     */

    /** @var string output formats which can use this file type */
    public $outputformats;

    /** @var string the best output format type for this file */
    public $best_outputformat;

    /**
     * properties of the task file - each one has a correspinding get_xxx() function
     */

    /** @var string the name of the task that is displayed on the list of tasks in this chain */
    public $name;

    /** @var string the title the is displayed when this task is viewed in a browser */
    public $title;

    /** @var string the text, if any, that could be used on the chain's entry page */
    public $entrytext;

    /** @var string the text, if any, that could be used on the chain's entry page */
    public $exittext;

    /** @var string the next task, if any, in this chain */
    public $nexttask;

    /**
     * Creates a taskchain_source object and can optionally prepare the file contents
     * ready to be passed to a TaskChain output format classs.
     *
     * @uses $CFG
     * @param stdclass $file either Moodle stored_file object representing the file, or the contents of a file
     * @param xxx $TC
     * @todo Finish documenting this function
     */
    public function __construct($file) {
        global $CFG, $TC;

        if (empty($TC)) {
            require_once($CFG->dirroot.'/mod/taskchain/locallib.php');
            $TC = new mod_taskchain();
        }
        $this->TC = &$TC;

        if ($this->file = $file) {
            $this->filepath = $this->file->get_filepath().$this->file->get_filename();
        }

        if (is_object($TC->course)) {
            $this->courseid = $TC->course->id;
        } else {
            // probably we are adding a new TaskChain
            $this->courseid = $TC->course;
        }

        if (isset($TC->sourcelocation)) {
            $this->location = $TC->sourcelocation;
        } else {
            $this->location = 0; // LOCATION_COURSEFILES
        }
        if ($this->location==1) {
            // 1=LOCATION_SITEFILES
            // only used on TaskChains upgraded from Moodle 1.9
            // and even then, it would be unusual on most sites
            $this->coursefolder = SITEID;
        } else {
            $this->coursefolder = $this->courseid;
        }
        $this->basepath = $CFG->dataroot.'/'.$this->coursefolder;
        $this->fullpath = $this->basepath.'/'.$this->filepath;

        $this->dirname  = dirname($this->fullpath);
        $this->filename = basename($this->fullpath);

        if ($CFG->slasharguments) {
            $file_php = 'file.php';
        } else {
            $file_php = 'file.php?file=';
        }
        $pluginfile_php = 'plugin'.$file_php;

        if (isset($TC->coursemodule->context)) {
            $contextid = $TC->coursemodule->context->id;
            $this->baseurl = $CFG->wwwroot.'/'.$pluginfile_php.'/'.$contextid.'/mod_taskchain/sourcefile';
        } else {
            // no context - shouldn't happen !!
            $this->baseurl = '';
        }

        $this->legacy_baseurl = $CFG->wwwroot.'/'.$file_php.'/'.$this->coursefolder;
    }

    /*
     * This function will return the main file found the specified $component's $filearea
     *
     * @param stdclass $data recently submitted via form (passed by reference)
     * @param stdclass $context a Moodle context record
     * @param string $component typically 'mod_taskchain'
     * @param string $filearea typically 'configfile'
     * @return stdclass $config
     */
    static public function get_config(&$data, $context, $component, $filearea) {
        $config = (object)array(
            'url' => '',
            'filepath' => '',
            'location' => '',
        );
        if ($mainfile = taskchain_pluginfile_mainfile($context, $component, $filearea)) {
            $config->filepath = $mainfile->get_filepath().$mainfile->get_filename();
            $config->location = 0; // LOCATION_COURSEFILES
        }
        return $config;
    }

    /*
     * This function will collect a list of sources associated with this TaskChain
     *
     * The filearea for this TaskChain will be searched for ...
     *    a chain folder, a list of task files within the folder is returned
     *    a chain file, a list of task files listed in the file is returned
     *    the head of a task chain, a list of all tasks in the chain is returned
     *
     * @param stdclass $data recently submitted via form (passed by reference)
     * @param stdclass $context a Moodle context record
     * @param string $component typically 'mod_taskchain'
     * @param string $filearea typically 'sourcefile'
     * @param int $type see "available_addtypes_list()" in locallib.php
     * @return array $sources array of recognized TaskChain files
     */
    static public function get_sources(&$data, $context, $component, $filearea, $type) {

        switch ($type) {

            case mod_taskchain::ADDTYPE_TASKFILE:
                return self::get_sources_from_taskfile($data, $context, $component, $filearea);

            case mod_taskchain::ADDTYPE_TASKCHAIN:
                return self::get_sources_from_taskchain($data, $context, $component, $filearea, true);

            case mod_taskchain::ADDTYPE_CHAINFILE:
                return self::get_sources_from_chainfile($data, $context, $component, $filearea);

            case mod_taskchain::ADDTYPE_CHAINFOLDER:
                return self::get_sources_from_chainfolder($data, $context, $component, $filearea);

            case mod_taskchain::ADDTYPE_CHAINFOLDERS:
                return self::get_sources_from_chainfolder($data, $context, $component, $filearea, true);

            // the default action is to search for all task files in the main directory
            // if none are found, we search for a unit file in the main directory
            case mod_taskchain::ADDTYPE_AUTO:
            default:
                if ($sources = self::get_sources_from_chainfolder($data, $context, $component, $filearea)) {
                    return $sources;
                }
                if ($sources = self::get_sources_from_chainfile($data, $context, $component, $filearea)) {
                    return $sources;
                }
                return false; // no recognized sources found
        }
    }

    /*
     * This function will return either an array of task sources
     * within this filearea, or false if no sources are found
     *
     * @return an array of sources if any are found, or false otherwise
     */
    static public function get_sources_from_taskfile(&$data, $context, $component, $filearea) {
        if (! $mainfile = taskchain_pluginfile_mainfile($context, $component, $filearea)) {
            return false;
        }
        if (! $result = self::is('is_taskfile', $mainfile, $data)) {
            return false;
        }
        return array($result);
    }

    /**
     * returns an array of taskchain_source objects if $filename is a head of a task chain, or false otherwise
     *
     * @param xxx $data
     * @param xxx $context
     * @param xxx $component
     * @param xxx $filearea
     * @param xxx $gettaskchain
     * @return xxx
     * @todo Finish documenting this function
     */
    static public function get_sources_from_taskchain(&$data, $context, $component, $filearea, $gettaskchain)  {
        $sources = array();

        if (! $file = taskchain_pluginfile_mainfile($context, $component, $filearea)) {
            return false;
        }

        while ($file && ($task = self::is('is_taskfile', $file, $data))) {

            // add this task
            $sources[] = $task;

            if ($gettaskchain) {
                // get next task (if any)
                if ($file = $task->get_nexttask($context, $component, $filearea)) {
                    // to prevent infinite loops on chains, we check that
                    // the next task is not one of the earlier tasks
                    foreach ($sources as $source) {
                        if ($source->filepath==$file->filepath) {
                            $file = false;
                        }
                    }
                }
            } else {
                // force end of loop
                $file = false;
            }
        }

        if (count($sources)) {
            return $sources;
        } else {
            return false;
        }
    }

    /**
     * get_sources_from_chainfile
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    static public function get_sources_from_chainfile(&$data, $context, $component, $filearea)  {
        $sources = array();

        if (! $mainfile = taskchain_pluginfile_mainfile($context, $component, $filearea)) {
            return false;
        }

        if (! $files = self::is('is_chainfile', $mainfile, $data)) {
            return false;
        }

        foreach ($files as $file) {
            if ($task = self::is('is_taskfile', $file, $data)) {
                $sources[] = $task;
            }
        }

        if (count($sources)) {
            return $sources;
        } else {
            return false;
        }
    }

    /*
     * This function will return either an array of task files
     * within this filearea, or false if there are no such files
     *
     * @return mixed
     * array $sources TaskChain task files in this folder
     * boolean : false : no task files found
     */
    static public function get_sources_from_chainfolder(&$data, $context, $component, $filearea, $recursive=false) {

        if (! $mainfile = taskchain_pluginfile_mainfile($context, $component, $filearea)) {
            return false;
        }

        // if the $mainfile was a zip or tgz file, unpack it
        if ($mimetype = self::get_file_packer_mimetype($mainfile)) {
            $mainfile->extract_to_storage(get_file_packer($mimetype),
                                          $mainfile->get_contextid(),
                                          $mainfile->get_component(),
                                          $mainfile->get_filearea(),
                                          $mainfile->get_itemid(),
                                          $mainfile->get_filepath());
            $recursive = true;
        }

        if (! $directory = $mainfile->get_parent_directory()) {
            return false;
        }

        $fs = get_file_storage();
        $files = $fs->get_directory_files($directory->get_contextid(),
                                          $directory->get_component(),
                                          $directory->get_filearea(),
                                          $directory->get_itemid(),
                                          $directory->get_filepath(),
                                          $recursive,
                                          false); // i.e. exclude dirs

        if (empty($files)) {
            return false;
        }

        // TODO: we need a way to find out if we are updating
        // 2015-09-13 this boolean flag doesn't appear to be necessary because
        //            we never come this way when updating a TaskChain or task
        // $is_update = (false);

        $sources = array();
        foreach ($files as $file) {
            // 2015-09-13 this if-block doesn't appear to be necessary
            // if ($is_update && $mainfile->get_source()==$file->get_source()) {
            //     continue; // this is the $mainfile
            // }
            if ($result = self::is('is_taskfile', $file, $data)) {
                $sources[] = $result;
            }
        }

        self::get_sources_from_chainfolder_external($data, $context, $component, $filearea, $sources, $mainfile, $recursive);

        if (count($sources)) {
            return $sources;
        } else {
            return false;
        }
    }

    /*
     * Given a stored file, this function will return either
     * a mimetype that can be passed to the Moodle file_packer
     * or, if the $file is not a packed file, an empty string
     *
     * @return string
     */
    static public function get_file_packer_mimetype($file) {
        $filetype = $file->get_filename();
        $filetype = substr($filetype, -4);
        $filetype = strtolower($filetype);
        switch ($filetype) {
            case '.zip': return 'application/zip';
            case '.tgz': return 'application/x-tgz';
        }
        return ''; // not recognized as a packed file
    }

    /**
     * appends taskchain_source objects to $sources for any sources that are found in the external chainfolder
     *
     * @param array $sources (passed by reference)
     * @param stored_file $mainfile
     * @param boolean $recursive
     * @return void, but may append items to $sources
     */
    static public function get_sources_from_chainfolder_external(&$data, $context, $component, $filearea, &$sources, $mainfile, $recursive) {
        global $DB;

        // get repository - cautiously :-)
        if (! $mainfile) {
            return false; // no main file - shouldn't happen !!
        }
        if (! method_exists($mainfile, 'get_repository_id')) {
            return false; // Moodle 2.0 - 2.2
        }
        if (! $repositoryid = $mainfile->get_repository_id()) {
            return false; // not from an external repository
        }
        // if we are adding a TaskChain using files from a "Private files" repository
        // we must use the course context, because the get_repository_by_id() method
        // will try to join the "course_modules" table and the "taskchain" table
        // but the "taskchain" record has not yet been setup, so we get an error
        if ($context->contextlevel==CONTEXT_MODULE) {
            if ($cm = $DB->get_record('course_modules', array('id'=>$context->instanceid))) {
                if ($cm->instance==0) {
                    $context = mod_taskchain::context(CONTEXT_COURSE, $data->course);
                }
            }
        }
        if (! $repository = repository::get_repository_by_id($repositoryid, $context)) {
            return false; // $repository is not accessible in this context - shouldn't happen !!
        }

        $file_record = array(
            'contextid' => $mainfile->get_contextid(),
            'component' => $component,
            'filearea'  => $filearea,
            'itemid'    => $mainfile->get_itemid(),
            'filepath'  => $mainfile->get_filepath(),
            'filename'  => '',
            'source'    => '',
            'sortorder' => 0, // may be filled in later
            'author'    => $mainfile->get_author(),
            'license'   => $mainfile->get_license()
        );

        // get repository type
        switch (true) {
            case isset($repository->options['type']):
                $type = $repository->options['type'];
                break;
            case isset($repository->instance->typeid):
                $type = repository::get_type_by_id($repository->instance->typeid);
                $type = $type->get_typename();
                break;
            default:
                $type = ''; // shouldn't happen !!
        }

        switch ($type) {
            case 'filesystem':
                $path       = dirname($mainfile->get_reference());
                $encodepath = false;
                break;
            case 'user':
            case 'coursefiles':
                $params     = file_storage::unpack_reference($mainfile->get_reference(), true);
                $path       = $params['filepath'];
                $encodepath = true;
                break;
            default:
                echo 'unknown repository type in get_sources_from_chainfolder_external(): '.$type;
                die;
        }

        // setup $params for path encoding, if necessary
        $params = array();
        if ($encodepath) {
            $listing = $repository->get_listing();
            switch (true) {
                case isset($listing['list'][0]['source']): $param = 'source'; break; // file
                case isset($listing['list'][0]['path']):   $param = 'path';   break; // dir
                default: return false; // shouldn't happen !!
            }
            $params = $listing['list'][0][$param];
            $params = json_decode(base64_decode($params), true);
        }

        // get file storage
        $fs = get_file_storage();

        // remove leading and trailing slashes from path
        $path = trim($path, '/');

        // search and replace strings to convert
        // repository base path to $filearea base path
        $search = '/'.$path.($path=='' ? '' : '/');
        $search = '/'.preg_quote($search, '/').'/';
        $replace = $mainfile->get_filepath();

        // encode $params, if necessary
        if ($encodepath) {
            $path = base64_encode(json_encode($params));
        }

        $listing = $repository->get_listing($path);
        foreach ($listing['list'] as $file) {

            if (empty($file['source'])) {
                continue; // a directory
            }

            // decode $file['source'], if necessary
            if ($encodepath) {
                $file['source'] = json_decode(base64_decode($file['source']), true);
                $file['source'] = trim($file['source']['filepath'], '/').'/'.$file['source']['filename'];
            }

            $file_record['filename'] = basename($file['source']);
            $file_record['filepath'] = dirname($file['source']);

            if ($file_record['filepath'] = trim($file_record['filepath'], '/')) {
                $file_record['filepath'] = '/'.$file_record['filepath'].'/';
            } else {
                $file_record['filepath'] = '/';
            }

            // skip hidden files and folders
            if (substr($file_record['filename'], 0, 1)=='.') {
                continue;
            }

            // replace repository base path with $filearea base path
            $file_record['filepath'] = preg_replace($search, $replace, $file_record['filepath'], 1);

            // skip files that have already been fetched from the repository to this $filearea
            if ($fs->get_file($file_record['contextid'], $component, $filearea, 0, $file_record['filepath'], $file_record['filename'])) {
                continue; // file is already in $filearea
            }

            // prepare $reference
            if ($encodepath) {
                $params['filename'] = $file_record['filename'];
                $reference = file_storage::pack_reference($params);
            } else {
                $reference = $file['source'];
            }
            $file_record['source'] = $reference;

            // import file, and check to see if it is a task file
            if ($file = $fs->create_file_from_reference($file_record, $repositoryid, $reference)) {
                if ($result = self::is('is_taskfile', $file, $data)) {
                    $sources[] = $result;
                } else {
                    $file->delete(); // not a task file
                }
            }
        }
    }

    /*
     * Given a class method name, a full path to a file and relative path to plugins directory,
     * this function will get task type classes from the plugins directory (and subdirectories),
     * and search the classes for a method which returns a non-empty result
     *
     * @param string $methodname "is_taskfile" or "is_chainfile"
     * @param stored_file $file
     * @return string : $class name if $file is the required type; otherwise ""
     */
    static public function is($methodname, $file, &$data) {
        $classes = mod_taskchain::get_classes('taskchainsource');
        foreach ($classes as $class) {

            $object = new $class($file);
            if (method_exists($object, $methodname) && ($result = $object->$methodname())) {

                // if this is the first chain/task file to be recognized, then store the name
                // because if $form->namesource==TASKCHAIN_NAMESOURCE_TASK,
                // $this->chainname may be used later as the name of the TaskChain activity

                $fields = array('name', 'entrytext', 'exittext');
                foreach ($fields as $field) {

                    $fieldsource = $field.'source';
                    $chainfield = 'chain'.$field;
                    $fieldmethod = 'get_'.$field;

                    if (isset($data->$fieldsource) && empty($data->$chainfield)) {
                        switch ($data->$fieldsource) {
                            case mod_taskchain::TEXTSOURCE_FILE:
                                $data->$chainfield = $object->$fieldmethod();
                                break;

                            case mod_taskchain::TEXTSOURCE_FILEPATH:
                                $data->$chainfield = $file->get_filepath();
                                $data->$chainfield = str_replace(array('/', '\\'), ' ', $data->$chainfield);
                                $data->$chainfield = trim($data->$chainfield);
                                break;

                            case mod_taskchain::TEXTSOURCE_FILENAME:
                                $data->$chainfield = $file->get_filename();
                                break;
                        }
                    }
                }

                if (is_object($result) || is_array($result)) {
                    return $result;
                } else {
                    return $object;
                }
            }
        }
        return false;
    }

    /*
     * Returns source/output type of this file
     *
     * @param string a taskchain file/output class name
     * @return string class name without the leading "taskchain_source_"
     */
    public function get_type($class='') {
        if ($class=='') {
            $class = get_class($this);
        }
        return preg_replace('/^taskchain_[a-z]+_/', '', $class);
    }

    /*
     * Returns true if $sourcefile is task file, or false otherwise
     *
     * @param stdclass $sourcefile a Moodle stored_file object representing the source file
     * @return boolean true if the file is a recognized task file, or false otherwise
     */
    public function is_taskfile() {
        return false;
    }

    /*
     * Returns array of filepaths if $sourcefile is a chain file, or false otherwise
     *
     * @param stdclass $sourcefile a Moodle stored_file object representing the source file
     * @return boolean true if the file is a recognized task file, or false otherwise
     */
    public function is_chainfile() {
        return false;
    }

    /**
     * returns name of task that is displayed in the list of tasks
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_name() {
        return '';
    }

    /**
     * returns title of task when it is viewed in a browser
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_title() {
        return '';
    }

    /**
     * returns the entry text for a task
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_entrytext() {
        return '';
    }

    /**
     * returns the exit text for a task
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_exittext() {
        return '';
    }

    /**
     * returns $file of next task if there is one, or false otherwise
     *
     * @param xxx $context
     * @param xxx $component
     * @param xxx $filearea
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_nexttask($context, $component, $filearea) {
        return false;
    }

    // returns an <img> tag for the icon for this source file type

    /**
     * get_icon
     *
     * @uses $CFG
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_icon() {
        global $CFG;
        if (preg_match('/^(?:https?:)?\/+/', $this->icon)) {
            $icon = $this->icon;
        } else {
            $icon = $CFG->wwwroot.'/'.$this->icon;
        }
        return '<img src="'.$icon.'" width="'.$this->iconwidth.'" height="'.$this->iconheight.'" class="'.$this->iconclass.'" />';
    }

    // property access functions

    /**
     * returns file (=either url or filepath)
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_file() {
        if ($this->location==mod_taskchain::LOCATION_WWW) {
            return $this->url;
        }
        if ($this->filepath) {
            return $this->filepath;
        }
        return false;
    }

    /**
     * returns location (0 : coursefiles; 1 : site files; false : undefined) of task source file
     *
     * @param xxx $courseid
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_location($courseid)  {
        if ($this->coursefolder) {
            if ($this->coursefolder==$courseid) {
                return mod_taskchain::LOCATION_COURSEFILES;
            }
            if ($this->coursefolder==SITEID) {
                return mod_taskchain::LOCATION_SITEFILES;
            }
        }
        if ($this->url) {
            return mod_taskchain::LOCATION_WWW;
        }
        return 0;
    }

    /**
     * filemtime
     *
     * @param xxx $lastmodified
     * @param xxx $etag
     * @return xxx
     * @todo Finish documenting this function
     */
    public function filemtime($lastmodified, $etag)  {
        if (is_object($this->file)) {
            if (method_exists($this->file, 'referencelastsync')) {
                $time = $this->file->referencelastsync(); // Moodle >= 2.3
            } else {
                $time = $this->file->get_timemodified(); // Moodle <= 2.2
            }
            if ($path = $this->get_real_path()) {
                $time = max($time, filemtime($path));
            }
            return $time;
        }
        if ($this->url) {
            $headers = array(
                'If-Modified-Since'=>$lastmodified, 'If-None-Match'=>$etag
                // 'If-Modified-Since'=>'Wed, 23 Apr 2008 17:53:50 GMT',
                // 'If-None-Match'=>'"52237ffc6aa5c81:16d9"'
            );
            if ($this->get_filecontents_url($headers)) {
                if ($this->lastmodified) {
                    $filemtime = strtotime($this->lastmodified);
                } else {
                    $filemtime = strtotime($lastmodified);
                }
                if ($this->date) {
                    $filemtime += (time() - strtotime($this->date));
                }
                return $filemtime;
            } else {
                debugging('remote file not accesisble: '.$this->url, DEBUG_DEVELOPER);
                return 0;
            }
        }
        // not a local file or a remote file ?!
        return 0;
    }

    /**
     * get_filecontents
     *
     * @return boolean true if file content is present, otherwise false
     * @todo Finish documenting this function
     */
    public function get_filecontents() {
        global $DB;

        if (isset($this->filecontents)) {
            return ($this->filecontents ? true : false);
        }

        // initialize $this->filecontent
        $this->filecontents = false;

        if ($this->location==mod_taskchain::LOCATION_WWW) {
            if (! $this->url) {
                // no url given - shouldn't happen
                return false;
            }
            if (! $this->get_filecontents_url()) {
                // url is (no longer) accessible
                return false;
            }
        } else {

            if (! $this->file) {
                // no file object - shouldn't happen !!
                throw new moodle_exception('source file object not found: class='.get_class($this));
                return false;
            }

            if (! $this->filecontents = $this->file->get_content()) {
                if (! $path = $this->get_real_path()) {
                    throw new moodle_exception('could not fetch file contents: class='.get_class($this->file).', file='.$this->file->get_filepath().$this->file->get_filename());
                    return false;
                }
                $this->filecontents = file_get_contents($path);
            }
        }

        // file contents were successfully read

        // detect BOMs - http://en.wikipedia.org/wiki/Byte_order_mark
        switch (true) {
            case substr($this->filecontents, 0, 4)=="\xFF\xFE\x00\x00":
                $start = 4;
                $encoding = 'UTF-32LE';
                break;
            case substr($this->filecontents, 0, 4)=="\x00\x00\xFE\xFF":
                $start = 4;
                $encoding = 'UTF-32BE';
                break;
            case substr($this->filecontents, 0, 2)=="\xFF\xFE":
                $start = 2;
                $encoding = 'UTF-16LE';
                break;
            case substr($this->filecontents, 0, 2)=="\xFE\xFF":
                $start = 2;
                $encoding = 'UTF-16BE';
                break;
            case substr($this->filecontents, 0, 3)=="\xEF\xBB\xBF":
                $start = 3;
                $encoding = 'UTF-8';
                break;
            default:
                $start = 0;
                $encoding = '';
        }

        // remove BOM, if necessary
        if ($start) {
            $this->filecontents = substr($this->filecontents, $start);
        }

        // convert to UTF-8, if necessary
        if ($encoding=='' || $encoding=='UTF-8') {
            // do nothing
        } else {
            $this->filecontents = mod_taskchain::textlib('convert', $this->filecontents, $encoding);
        }

        return true;
    }

    /**
     * get_real_path
     *
     * @return string
     */
    public function get_real_path($file=null) {
        global $CFG, $PAGE;

        if ($file===null) {
            $file = $this->file;
        }

        // sanity check
        if (empty($file)) {
            return '';
        }

        // set default path (= cached file in filedir)
        $hash = $file->get_contenthash();
        $path = $CFG->dataroot.'/filedir/'.$hash[0].$hash[1].'/'.$hash[2].$hash[3].'/'.$hash;

        if (! method_exists($file, 'get_repository_id')) {
            return $path; // Moodle <= 2.2
        }
        if (! $repositoryid = $file->get_repository_id()) {
            return $path; // shoudn't happen !!
        }

        if (! $repository = repository::get_repository_by_id($repositoryid, $PAGE->context)) {
            return $path; // shouldn't happen
        }

        // get repository $type
        switch (true) {
            case isset($repository->options['type']):
                $type = $repository->options['type'];
                break;
            case isset($repository->instance->typeid):
                $type = repository::get_type_by_id($repository->instance->typeid);
                $type = $type->get_typename();
                break;
            default:
                $type = ''; // shouldn't happen !!
        }

        // get path according to repository $type
        switch ($type) {
            case 'filesystem':
                if (method_exists($repository, 'get_rootpath')) {
                    $path = $repository->get_rootpath().'/'.$file->get_reference();
                } else if (isset($repository->root_path)) {
                    $path = $repository->root_path.'/'.$file->get_reference();
                }
                break;
            case 'user':
            case 'coursefiles':
                // use the the default $path
                break;
        }

        return $path;
    }

    /**
     * get_filecontents_url
     *
     * @uses $CFG
     * @param xxx $headers (optional, default=null)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_filecontents_url($headers=null)  {
        global $CFG;
        require_once($CFG->dirroot.'/lib/filelib.php');

        $fullresponse = download_file_content($this->url, $headers, null, true);
        foreach ($fullresponse->headers as $header) {
            if ($pos = strpos($header, ':')) {
                $name = trim(substr($header, 0, $pos));
                $value = trim(substr($header, $pos+1));
                switch ($name) {
                    case 'Last-Modified': $this->lastmodified = trim($value); break;
                    case 'ETag'         : $this->etag         = trim($value); break;
                    case 'Date'         : $this->date         = trim($value); break;
                }
            }
        }
        if ($fullresponse->status==200) {
            $this->filecontents = $fullresponse->results;
            return true;
        }
        if ($fullresponse->status==304) {
            return true;
        }
        return false;
    }

    /**
     * compact_filecontents
     *
     * @param array $tags (optional, default=null) specific tags to remove comments from
     * @todo Finish documenting this function
     */
    public function compact_filecontents($tags=null) {
        if (isset($this->filecontents)) {
            if ($tags) {
                $callback = array($this, 'compact_filecontents_callback');
                foreach ($tags as $tag) {
                    $search = '/(?<=<'.$tag.'>).*(?=<\/'.$tag.'>)/is';
                    $this->filecontents = preg_replace_callback($search, $callback, $this->filecontents);
                }
            }
            $this->filecontents = preg_replace('/(?<=>)'.'\s+'.'(?=<)/s', '', $this->filecontents);
        }
    }

    /**
     * compact_filecontents_callback
     *
     * @todo Finish documenting this function
     */
    public function compact_filecontents_callback($match) {
        $search = array(
            '/\/\/[^\n\r]*/',  // single line js comments
            '/\/\*.*?\*\//s',  // multiline comments (js and css)
        );
        return preg_replace($search, '', $match[0]);
    }

    /**
     * get_sibling_filecontents
     *
     * @param xxx $filename
     * @param xxx $xmlize (optional, default=false)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_sibling_filecontents($filename, $xmlize=false) {
        $filecontents = '';

        if (is_object($this->file)) {
            $fs = get_file_storage();

            $contextid = $this->file->get_contextid();
            $component = $this->file->get_component();
            $filearea  = $this->file->get_filearea();
            $filepath  = $this->file->get_filepath();

            if ($file = $fs->get_file($contextid, $component, $filearea, 0, $filepath, $filename)) {
                // file already exists in this filearea
            } else {
                // try to locate and import the sibling file from the file repository
                if ($this->TC->coursemodule) {
                    $context = $this->TC->coursemodule->context;
                } else if ($this->TC->course) {
                    $context = $this->TC->course->context;
                }
                $file = taskchain_pluginfile_externalfile($context, $component, $filearea, $filepath, $filename);
            }

            if ($file) {
                if (! $filecontents = $file->get_content()) {
                    if ($path = $this->get_real_path($file)) {
                        $filecontents = file_get_contents($path);
                    }
                }
            }
        }

        if ($xmlize) {
            if (empty($filecontents)) {
                $filecontents = array();
            } else {
                $filecontents = xmlize($filecontents, 0);
            }
        }

        return $filecontents;
    }

    /**
     * return best output format for this file type
     * (eventually this should take account of current device and browser)
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_best_outputformat() {
        if (! isset($this->best_outputformat)) {
            // the default outputformat is the class name
            // without the leading "taskchain_source_"
            $this->best_outputformat = $this->get_type();
        }
        return $this->best_outputformat;
    }

    /**
     * synchonize file and Moodle settings
     *
     * @param xxx $task (passed by reference)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function synchronize_moodle_settings(&$task)  {
        return false;
    }
}
