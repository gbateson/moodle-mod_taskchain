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
 * @copyright  2012 Gordon Bateson <gordonbateson@gmail.com>
 *             credit and thanks to Robin de vries <robin@celp.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** include required files */
require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

/**
 * TaskChain conversion handler
 */
class moodle1_mod_taskchain_handler extends moodle1_mod_handler {

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

    /** path (below temp dir) to current xml folder */
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
    /**
     * get_paths
     */
    public function get_paths() {

        $userinfo = true; // are we including userinfo?

        // shortcut to TASKCHAIN tags
        $taskchain = '/MOODLE_BACKUP/COURSE/MODULES/MOD/TASKCHAIN';

        $paths = array(
            new convert_path('taskchain',            $taskchain, array('dropfields' => array('modtype', 'course'))),
            new convert_path('taskchain_unit',       $taskchain.'/UNIT', array('newfields' => array('entryformat' => '0', 'exitformat' => '0'))),
            new convert_path('taskchain_quizzes',    $taskchain.'/UNIT/QUIZZES'),
            new convert_path('taskchain_quiz',       $taskchain.'/UNIT/QUIZZES/QUIZ'),
            new convert_path('taskchain_conditions', $taskchain.'/UNIT/QUIZZES/QUIZ/CONDITIONS'),
            new convert_path('taskchain_condition',  $taskchain.'/UNIT/QUIZZES/QUIZ/CONDITIONS/CONDITION')
        );

        if ($userinfo) {
            // Note that STRINGS and RESPONSES need to be relocated
            // (1) Moodle1: TASKCHAIN/UNIT/QUIZZES/QUIZ/STRINGS
            //     Moodle2: TASKCHAIN/STRINGS
            // (2) Moodle1: TASKCHAIN/.../QUIZ/QUIZ_ATETMPTS/QUIZ_ATTEMPT/RESPONSES
            //     Moodle2: TASKCHAIN/.../QUIZ/QUESTIONS/QUESTION/RESPONSES
            $paths = array_merge($paths, array(
                new convert_path('taskchain_quizscores',   $taskchain.'/UNIT/QUIZZES/QUIZ/QUIZ_SCORES'),
                new convert_path('taskchain_quizscore',    $taskchain.'/UNIT/QUIZZES/QUIZ/QUIZ_SCORES/QUIZ_SCORE', array('renamefields' => array('unumber' => 'cnumber'))),
                new convert_path('taskchain_quizattempts', $taskchain.'/UNIT/QUIZZES/QUIZ/QUIZ_ATTEMPTS'),
                new convert_path('taskchain_quizattempt',  $taskchain.'/UNIT/QUIZZES/QUIZ/QUIZ_ATTEMPTS/QUIZ_ATTEMPT', array('renamefields' => array('unumber' => 'cnumber', 'qnumber' => 'tnumber'))),
                new convert_path('taskchain_responses',    $taskchain.'/UNIT/QUIZZES/QUIZ/QUIZ_ATTEMPTS/QUIZ_ATTEMPT/RESPONSES'),
                new convert_path('taskchain_response',     $taskchain.'/UNIT/QUIZZES/QUIZ/QUIZ_ATTEMPTS/QUIZ_ATTEMPT/RESPONSES/RESPONSE'),
                new convert_path('taskchain_questions',    $taskchain.'/UNIT/QUIZZES/QUIZ/QUESTIONS'),
                new convert_path('taskchain_question',     $taskchain.'/UNIT/QUIZZES/QUIZ/QUESTIONS/QUESTION'),
                new convert_path('taskchain_strings',      $taskchain.'/UNIT/QUIZZES/QUIZ/STRINGS'),
                new convert_path('taskchain_string',       $taskchain.'/UNIT/QUIZZES/QUIZ/STRINGS/STRING', array('dropfields' => array('md5key'))),
                new convert_path('taskchain_unitattempts', $taskchain.'/UNIT/UNIT_ATTEMPTS'),
                new convert_path('taskchain_unitattempt',  $taskchain.'/UNIT/UNIT_ATTEMPTS/UNIT_ATTEMPT', array('renamefields' => array('unumber' => 'cnumber'))),
                new convert_path('taskchain_unitgrades',   $taskchain.'/UNIT_GRADES'),
                new convert_path('taskchain_unitgrade',    $taskchain.'/UNIT_GRADES/UNIT_GRADE')
            ));
        }

        return $paths;
    }

    /**
     * This is executed every time we find /MOODLE_BACKUP/COURSE/MODULES/MOD/TASKCHAIN
     */
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
     * process_taskchain_unit
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
     * quizzes
     */
    public function on_taskchain_quizzes_start() {
        $this->xmlwriter->begin_tag('tasks');
    }
    public function on_taskchain_quiz_start() {
        $this->responses = array();
        $this->xmlwriter->begin_tag('task');
    }
    public function process_taskchain_quiz($data) {
        $this->fix_sourcefile($data);
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
     * conditions
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
     * quizscores
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
     * quizattempts
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
     * responses
     */
    public function on_taskchain_responses_start() {
        // do nothing
    }
    public function on_taskchain_response_start() {
        // do nothing
    }
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
    public function on_taskchain_response_end() {
        // do nothing
    }
    public function on_taskchain_responses_end() {
        // do nothing
    }

    /**
     * questions
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
     * strings
     */
    public function on_taskchain_strings_start() {
        // do nothing
    }
    public function on_taskchain_string_start() {
        // do nothing
    }
    public function process_taskchain_string($data) {
        if ($id = $data['id']) {
            $this->strings[$id] = $data;
        }
    }
    public function on_taskchain_string_end() {
        // do nothing
    }
    public function on_taskchain_strings_end() {
        // do nothing
    }

    /**
     * unitattempts
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
     * unitgrades
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
     * fix_sourcefile
     *
     * @param array $data (passed by reference)
     */
    public function fix_sourcefile(&$data) {
        // set $path, $filepath and $filename
        $is_url = preg_match('|^https?://|', $data['sourcefile']);
        if ($is_url) {

            $backupinfo = $this->converter->get_stash('backup_info');
            $originalcourseinfo = $this->converter->get_stash('original_course_info');

            $original_baseurl = $backupinfo['original_wwwroot'].'/'.$originalcourseinfo['original_course_id'].'/';
            unset($backupinfo, $originalcourseinfo);

            // if the URL is for a file in the original course files folder
            // then convert it to a simple path, by removing the original base url
            $search = '/^'.preg_quote($original_baseurl, '/').'/';
            if (preg_match($search, $data['sourcefile'])) {
                $data['sourcefile'] = substr($data['sourcefile'], strlen($original_baseurl));
                $is_url = false;
            }
        }

        if ($is_url) {
            $data['sourcetype'] = $this->get_taskchain_sourcetype($data['sourcefile']);
        } else {
            $filename = basename($data['sourcefile']);
            $filepath = dirname($data['sourcefile']);
            $filepath = trim($filepath, './');
            if ($filepath=='') {
                $filepath = '/';
            } else {
                $filepath = '/'.$filepath.'/';
            }
            $data['sourcefile'] = $filepath.$filename;
            $path = 'course_files'.$filepath.$filename;

            // get a fresh new file manager for this instance
            $this->fileman = $this->converter->get_file_manager($this->contextid, 'mod_taskchain');

            // migrate taskchain file
            $this->fileman->filearea = 'sourcefile';
            $this->fileman->itemid   = 0;
            $id = $this->fileman->migrate_file($path, $filepath, $filename);

            // get stashed taskchain $filerecord
            $filerecord = $this->fileman->converter->get_stash('files', $id);

            // seems like there should be a way to get the file content
            // using the $filerecord, but I can't see how to do it,
            // so for now we determine the $fullpath and read from that

            // set sourcetype
            $fullpath = $this->fileman->converter->get_tempdir_path().'/'.$path;
            $data['sourcetype'] = $this->get_taskchain_sourcetype($fullpath, $filerecord);
        }
    }

    /**
     * get_taskchain_sourcetype
     *
     * given $fullpath to temporary imported Hot Potatoes file
     * this function returns the TaskChain/TaskChain sourcetype of the file
     *
     * Where possible, the sourcetype will be determined from the file name extension
     * but in some cases, notably html files, it may be necessary to read the file
     * and analyze its contents in order to determine the sourcetype
     */
    public function get_taskchain_sourcetype($fullpath, $filerecord=null) {
        if ($pos = strrpos($fullpath, '.')) {
            $filetype = substr($fullpath, $pos+1);
            switch ($filetype) {
                case 'jcl': return 'hp_6_jcloze_xml';
                case 'jcw': return 'hp_6_jcross_xml';
                case 'jmt': return 'hp_6_jmatch_xml';
                case 'jmx': return 'hp_6_jmix_xml';
                case 'jqz': return 'hp_6_jquiz_xml';
                case 'rhb': return 'hp_6_rhubarb_xml';
                case 'sqt': return 'hp_6_sequitur_xml';
            }
        }

        // cannot detect sourcetype from filename alone
        // so we must open the file and examine the contents
        if ($filerecord) {
            $fs = get_file_storage();
            $sourcefile = $fs->create_file_from_pathname($filerecord, $fullpath);
            $sourcetype = mod_taskchain::get_sourcetype($sourcefile);
            $sourcefile->delete();
            return $sourcetype;
        }

        // could not detect sourcetype
        return '';
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
}
