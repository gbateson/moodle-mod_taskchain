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
 * Provides support for the conversion of moodle1 backup to the moodle2 format
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** include required files */
require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

/**
 * TaskChain conversion handler
 */
class moodle1_mod_taskchain_handler extends moodle1_mod_handler {

    /** maximum size of moodle.xml that can be read using file_get_contents (256 KB) */
    const SMALL_FILESIZE = 256000;

    /** id of current context */
    protected $contextid  =  0;

    /** id of current course_module */
    protected $moduleid   =  0;

    /** id of current taskchain */
    protected $instanceid =  0;

    /** id of current taskchain chain */
    protected $chainid =  0;

    /** id of current taskchain condition */
    protected $conditionid =  0;

    /** all <STRING> tags for the current chain */
    protected $strings =  array();

    /** all <RESPONSE> tags for the current task */
    protected $responses =  array();

    /** id of current task attempt */
    protected $attemptid =  0;

    /** user id of current task attempt */
    protected $attemptuserid =  0;

    /** path below "$CFG->datadir/temp/" to current xml folder */
    protected $xmlfolder  = '';

    /**
     * Declare the paths in moodle.xml we are able to convert
     *
     * The method returns list of {@link convert_path} instances. For each path returned,
     * at least one of on_xxx_start(), process_xxx() and on_xxx_end() methods must be
     * defined. The method process_xxx() is not executed if the associated path element is
     * empty (i.e. it contains none elements or sub-paths only).
     *
     * Note that the path /MOODLE_BACKUP/COURSE/MODULES/MOD/TASKCHAIN does not
     * actually exist in the file. The last element with the module name was
     * appended by the moodle1_converter class.
     *
     * @return array of {@link convert_path} instances
     */
    public function get_paths() {
        global $CFG;

        if (! empty($CFG->taskchainconvertquizport)) {
            $this->convert_quizport_to_taskchain();
        }

        // shortcut to TASKCHAIN tags
        $taskchain = '/MOODLE_BACKUP/COURSE/MODULES/MOD/TASKCHAIN';

        return array(
            new convert_path('taskchain',              $taskchain, array('dropfields' => array('modtype', 'course'))),
            new convert_path('taskchain_unit',         $taskchain.'/UNIT', array('newfields' => array('entryformat' => '0', 'exitformat' => '0'))),
            new convert_path('taskchain_quizzes',      $taskchain.'/UNIT/QUIZZES'),
            new convert_path('taskchain_quiz',         $taskchain.'/UNIT/QUIZZES/QUIZ'),
            new convert_path('taskchain_conditions',   $taskchain.'/UNIT/QUIZZES/QUIZ/CONDITIONS'),
            new convert_path('taskchain_condition',    $taskchain.'/UNIT/QUIZZES/QUIZ/CONDITIONS/CONDITION', array('renamefields' => array('conditionquizid' => 'conditiontaskid', 'nextquizid' => 'nexttaskid'))),
            // the paths below this line contain the user info
            new convert_path('taskchain_quizscores',   $taskchain.'/UNIT/QUIZZES/QUIZ/QUIZ_SCORES'),
            new convert_path('taskchain_quizscore',    $taskchain.'/UNIT/QUIZZES/QUIZ/QUIZ_SCORES/QUIZ_SCORE', array('renamefields' => array('unumber' => 'cnumber'))),
            new convert_path('taskchain_quizattempts', $taskchain.'/UNIT/QUIZZES/QUIZ/QUIZ_ATTEMPTS'),
            new convert_path('taskchain_quizattempt',  $taskchain.'/UNIT/QUIZZES/QUIZ/QUIZ_ATTEMPTS/QUIZ_ATTEMPT', array('renamefields' => array('unumber' => 'cnumber', 'qnumber' => 'tnumber'))),
            // RESPONSES need to be merged (by question id) and relocated
            //     Moodle1: TASKCHAIN/.../QUIZ/QUIZ_ATETMPTS/QUIZ_ATTEMPT/RESPONSES
            //     Moodle2: TASKCHAIN/.../QUIZ/QUESTIONS/QUESTION/RESPONSES
            new convert_path('taskchain_response',     $taskchain.'/UNIT/QUIZZES/QUIZ/QUIZ_ATTEMPTS/QUIZ_ATTEMPT/RESPONSES/RESPONSE'),
            new convert_path('taskchain_questions',    $taskchain.'/UNIT/QUIZZES/QUIZ/QUESTIONS'),
            new convert_path('taskchain_question',     $taskchain.'/UNIT/QUIZZES/QUIZ/QUESTIONS/QUESTION'),
            // STRINGS need to be merged and relocated
            //     Moodle1: TASKCHAIN/UNIT/QUIZZES/QUIZ/STRINGS
            //     Moodle2: TASKCHAIN/STRINGS
            new convert_path('taskchain_string',       $taskchain.'/UNIT/QUIZZES/QUIZ/STRINGS/STRING', array('dropfields' => array('md5key'))),
            new convert_path('taskchain_unitattempts', $taskchain.'/UNIT/UNIT_ATTEMPTS'),
            new convert_path('taskchain_unitattempt',  $taskchain.'/UNIT/UNIT_ATTEMPTS/UNIT_ATTEMPT', array('renamefields' => array('unumber' => 'cnumber'))),
            new convert_path('taskchain_unitgrades',   $taskchain.'/UNIT_GRADES'),
            new convert_path('taskchain_unitgrade',    $taskchain.'/UNIT_GRADES/UNIT_GRADE')
        );
    }

    /**
     * process_taskchain
     */
    public function process_taskchain($data) {
        global $CFG;

        // get the course module id and context id
        $this->instanceid = $data['id'];
        $currentcminfo    = $this->get_cminfo($this->instanceid);

        $this->moduleid  = $currentcminfo['id'];
        $this->contextid = $this->converter->get_contextid(CONTEXT_MODULE, $this->moduleid);
        $this->xmlfolder = 'activities/taskchain_'.$this->moduleid;
        $this->strings   = array();

        // start writing taskchain.xml
        $this->open_xml_writer($this->xmlfolder.'/taskchain.xml');
        $this->xmlwriter->begin_tag('activity', array('id' => $this->instanceid, 'moduleid' => $this->moduleid, 'modulename' => 'taskchain', 'contextid' => $this->contextid));
        $this->xmlwriter->begin_tag('taskchain', array('id' => $this->instanceid));

        // write out $data fields to the xml file
        foreach ($data as $field => $value) {
            if ($field <> 'id') {
                $this->xmlwriter->full_tag($field, $value);
            }
        }

        return $data;
    }

    /**
     * on_taskchain_end
     */
    public function on_taskchain_end() {
        // add <STRINGS>
        if (count($this->strings)) {
            $this->xmlwriter->begin_tag('strings');
            foreach ($this->strings as $string) {
                $this->xmlwriter->begin_tag('string');
                foreach ($string as $field => $value) {
                    $this->xmlwriter->full_tag($field, $value);
                }
                $this->xmlwriter->end_tag('string');
            }
            $this->xmlwriter->end_tag('strings');
            $this->strings = array();
        }

        // close taskchain.xml
        $this->xmlwriter->end_tag('taskchain');
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();

        // write inforef.xml
        $this->open_xml_writer($this->xmlfolder.'/inforef.xml');
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        if (isset($this->fileman) && $this->fileman) {
            foreach ($this->fileman->get_fileids() as $fileid) {
                $this->write_xml('file', array('id' => $fileid));
            }
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');
        $this->close_xml_writer();
    }

    /**
     * convert unit to chain
     * and add an id as this is expected
     * in restore_taskchain_stepslib.php
     */
    public function on_taskchain_unit_start() {
        $this->xmlwriter->begin_tag('chain');
    }
    public function process_taskchain_unit($data) {
        $data['id'] = $this->get_new_chainid();
        foreach ($data as $field => $value) {
            $this->xmlwriter->full_tag($field, $value);
        }
    }
    public function on_taskchain_unit_end() {
        $this->xmlwriter->end_tag('chain');
    }

    /**
     * convert quiz(zes) to task(s)
     */
    public function on_taskchain_quizzes_start() {
        $this->xmlwriter->begin_tag('tasks');
    }
    public function on_taskchain_quiz_start() {
        $this->responses = array();
        $this->xmlwriter->begin_tag('task');
    }
    public function process_taskchain_quiz($data) {
        $this->fix_fileareas($data);
        foreach ($data as $field => $value) {
            $this->xmlwriter->full_tag($field, $value);
        }
    }
    public function on_taskchain_quiz_end() {
        $this->xmlwriter->end_tag('task');
    }
    public function on_taskchain_quizzes_end() {
        $this->xmlwriter->end_tag('tasks');
    }

    /**
     * transfer condition(s) as they are
     * but add an id as this is expected
     * in restore_taskchain_stepslib.php
     */
    public function on_taskchain_conditions_start() {
        $this->xmlwriter->begin_tag('conditions');
    }
    public function on_taskchain_condition_start() {
        $this->xmlwriter->begin_tag('condition');
    }
    public function process_taskchain_condition($data) {
        $data['id'] = $this->get_new_conditionid();
        foreach ($data as $field => $value) {
            $this->xmlwriter->full_tag($field, $value);
        }
    }
    public function on_taskchain_condition_end() {
        $this->xmlwriter->end_tag('condition');
    }
    public function on_taskchain_conditions_end() {
        $this->xmlwriter->end_tag('conditions');
    }

    /**
     * convert quizscore(s) to taskscore(s)
     */
    public function on_taskchain_quizscores_start() {
        $this->xmlwriter->begin_tag('taskscores');
    }
    public function on_taskchain_quizscore_start() {
        $this->xmlwriter->begin_tag('taskscore');
    }
    public function process_taskchain_quizscore($data) {
        foreach ($data as $field => $value) {
            $this->xmlwriter->full_tag($field, $value);
        }
    }
    public function on_taskchain_quizscore_end() {
        $this->xmlwriter->end_tag('taskscore');
    }
    public function on_taskchain_quizscores_end() {
        $this->xmlwriter->end_tag('taskscores');
    }

    /**
     * convert quizattempt(s) to taskattempt(s)
     */
    public function on_taskchain_quizattempts_start() {
        $this->xmlwriter->begin_tag('taskattempts');
    }
    public function on_taskchain_quizattempt_start() {
        $this->xmlwriter->begin_tag('taskattempt');
    }
    public function process_taskchain_quizattempt($data) {
        $this->attemptid  = $data['id'];
        $this->attemptuserid  = $data['userid'];
        foreach ($data as $field => $value) {
            $this->xmlwriter->full_tag($field, $value);
        }
    }
    public function on_taskchain_quizattempt_end() {
        $this->xmlwriter->end_tag('taskattempt');
    }
    public function on_taskchain_quizattempts_end() {
        $this->xmlwriter->end_tag('taskattempts');
    }

    /**
     * cache response(s) by question id, so they can
     * be attached to the relevant question later on
     */
    public function process_taskchain_response($data) {
        if ($userid = $this->attemptuserid) {
            $data['userid'] = $userid;
            if ($attemptid = $this->attemptuserid) {
                $data['attemptid'] = $attemptid;
                if ($id = $data['questionid']) {
                    unset($data['questionid']);
                    if (! isset($this->responses[$id])) {
                        $this->responses[$id] = array();
                    }
                    $this->responses[$id][] = $data;
                }
            }
        }
    }

    /**
     * transfer question(s) as they are, but
     * add cached responses if there are any
     */
    public function on_taskchain_questions_start() {
        $this->xmlwriter->begin_tag('questions');
    }
    public function on_taskchain_question_start() {
        $this->xmlwriter->begin_tag('question');
    }
    public function process_taskchain_question($data) {
        // add <QUESTION> fields
        foreach ($data as $field => $value) {
            $this->xmlwriter->full_tag($field, $value);
        }
        // add <RESPONSES>, if necessary
        if ($id = $data['id']) {
            if (count($this->responses[$id])) {
                $this->xmlwriter->begin_tag('responses');
                foreach ($this->responses[$id] as $response) {
                    $this->xmlwriter->begin_tag('response');
                    foreach ($response as $field => $value) {
                        $this->xmlwriter->full_tag($field, $value);
                    }
                    $this->xmlwriter->end_tag('response');
                }
                $this->xmlwriter->end_tag('responses');
                unset($this->responses[$id]);
            }
        }
    }
    public function on_taskchain_question_end() {
        $this->xmlwriter->end_tag('question');
    }
    public function on_taskchain_questions_end() {
        $this->xmlwriter->end_tag('questions');
    }

    /**
     * cache the string tags, so that they can be
     * appended to the <taskchain> tag later on
     *
     * @param array $data
     */
    public function process_taskchain_string($data) {
        if ($id = $data['id']) {
            $this->strings[$id] = $data;
        }
    }

    /**
     * convert unitattempt(s) to chainattempt(s)
     */
    public function on_taskchain_unitattempts_start() {
        $this->xmlwriter->begin_tag('chainattempts');
    }
    public function on_taskchain_unitattempt_start() {
        $this->xmlwriter->begin_tag('chainattempt');
    }
    public function process_taskchain_unitattempt($data) {
        foreach ($data as $field => $value) {
            $this->xmlwriter->full_tag($field, $value);
        }
    }
    public function on_taskchain_unitattempt_end() {
        $this->xmlwriter->end_tag('chainattempt');
    }
    public function on_taskchain_unitattempts_end() {
        $this->xmlwriter->end_tag('chainattempts');
    }

    /**
     * convert unitgrade(s) to chaingrade(s)
     */
    public function on_taskchain_unitgrades_start() {
        $this->xmlwriter->begin_tag('chaingrades');
    }
    public function on_taskchain_unitgrade_start() {
        $this->xmlwriter->begin_tag('chaingrade');
    }
    public function process_taskchain_unitgrade($data) {
        foreach ($data as $field => $value) {
            $this->xmlwriter->full_tag($field, $value);
        }
    }
    public function on_taskchain_unitgrade_end() {
        $this->xmlwriter->end_tag('chaingrade');
    }
    public function on_taskchain_unitgrades_end() {
        $this->xmlwriter->end_tag('chaingrades');
    }

    /**
     * fix_fileareas
     *
     * @param array $data (passed by reference)
     */
    public function fix_fileareas(&$data) {

        $fileareas = array('sourcefile', 'configfile');
        foreach ($fileareas as $filearea) {

            if (empty($data[$filearea])) {
                continue;
            }

            $is_url = preg_match('|^https?://|', $data[$filearea]);
            if ($is_url) {

                $backupinfo = $this->converter->get_stash('backup_info');
                $originalcourseinfo = $this->converter->get_stash('original_course_info');

                $original_baseurl = $backupinfo['original_wwwroot'].'/'.$originalcourseinfo['original_course_id'].'/';
                unset($backupinfo, $originalcourseinfo);

                // if the URL is for a file in the original course files folder
                // then convert it to a simple path, by removing the original base url
                $search = '/^'.preg_quote($original_baseurl, '/').'/';
                if (preg_match($search, $data[$filearea])) {
                    $data[$filearea] = substr($data[$filearea], strlen($original_baseurl));
                    $is_url = false;
                }

            } else {

                $filename = basename($data[$filearea]);
                $filepath = dirname($data[$filearea]);
                $filepath = trim($filepath, './');
                if ($filepath=='') {
                    $filepath = '/';
                } else {
                    $filepath = '/'.$filepath.'/';
                }
                $data[$filearea] = $filepath.$filename;
                $path = 'course_files'.$filepath.$filename;

                // get a fresh new file manager for this instance
                $this->fileman = $this->converter->get_file_manager($this->contextid, 'mod_taskchain');

                // migrate taskchain file
                $this->fileman->filearea = $filearea;
                $this->fileman->itemid   = 0;
                $id = $this->fileman->migrate_file($path, $filepath, $filename);

                // get stashed taskchain $filerecord
                $filerecord = $this->fileman->converter->get_stash('files', $id);
            }
        }
    }

    /**
     * get_new_chainid
     */
    public function get_new_chainid() {
        $this->chainid++;
        return $this->chainid;
    }

    /**
     * get_new_conditionid
     */
    public function get_new_conditionid() {
        $this->conditionid++;
        return $this->conditionid;
    }

    /*
     * convert_quizport_to_taskchain
     *
     * open moodle.xml and convert all references to the QuizPort module
     * or QuizPort activities, to refer instead to the TaskChain module
     */
    public function convert_quizport_to_taskchain() {

        // these are the substitutions we want to make in moodle.xml
        $search   = array('<NAME>quizport</NAME>',  'mod/quizport/',  'mod/quizport:',  '<TYPE>quizport</TYPE>',  '<NAME>mod_quizport_',  '<NAME>quizport_',  '<ITEMMODULE>quizport</ITEMMODULE>',  '<MODTYPE>quizport</MODTYPE>',  '<USEMEDIAFILTER>quizport</USEMEDIAFILTER>');
        $replace  = array('<NAME>taskchain</NAME>', 'mod/taskchain/', 'mod/taskchain:', '<TYPE>taskchain</TYPE>', '<NAME>mod_taskchain_', '<NAME>taskchain_', '<ITEMMODULE>taskchain</ITEMMODULE>', '<MODTYPE>taskchain</MODTYPE>', '<USEMEDIAFILTER>taskchain</USEMEDIAFILTER>');

        $tempdir = $this->converter->get_tempdir_path();
        $moodle_xml = $tempdir.'/moodle.xml';
        $moodle_tmp = $tempdir.'/moodle.tmp';

        if (file_exists($moodle_xml)) {
            if (filesize($moodle_xml) < self::SMALL_FILESIZE) {
                $contents = file_get_contents($moodle_xml);
                $contents = str_replace($search, $replace, $contents);
                file_put_contents($moodle_xml, $contents);
            } else {
                // xml file is large, maybe entire Moodle 1.9 site,
                // so we process it one line at a time (slower but safer)
                if ($file_xml = fopen($moodle_xml, 'r')) {
                    if ($file_tmp = fopen($moodle_tmp, 'w')) {
                        while (! feof($file_xml)) {
                            if ($line = fgets($file_xml)) {
                                fputs($file_tmp, str_replace($search, $replace, $line));
                            }
                        }
                        fclose($file_tmp);
                    }
                    fclose($file_xml);
                }
                if ($file_xml && $file_tmp) {
                    unlink($moodle_xml);
                    rename($moodle_tmp, $moodle_xml);
                }
            }
        }
    }
}
