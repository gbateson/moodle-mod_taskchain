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
 * Data generator for mod_taskchain (used by PHPUnit and Behat tests).
 *
 * @package   mod_taskchain
 * @copyright 2026 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mod_taskchain data generator
 *
 * Create a TaskChain activity with one task for each source file, for example:
 *
 *     $taskchain = $this->getDataGenerator()->create_module('taskchain', [
 *         'course' => $course->id,
 *         'sourcefile' => 'mod/taskchain/tests/fixtures/hp7_jquiz.jqz',
 *     ]);
 *
 * The "sourcefile" may also be a comma-separated list of files, or an array of files,
 * in which case a chain of several tasks is created (in the order of the file names).
 *
 * @package   mod_taskchain
 * @copyright 2026 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_taskchain_generator extends testing_module_generator {
    /** @var string default source file, relative to $CFG->dirroot */
    const DEFAULT_SOURCEFILE = 'mod/taskchain/tests/fixtures/hp7_jquiz.jqz';

    /**
     * Create a new instance of the TaskChain activity.
     *
     * The "sourcefile" field is the path, relative to $CFG->dirroot, of a Hot Potatoes file,
     * or a list of such paths. The files are added to the activity, and a task is created
     * for each file. The type of each file is detected automatically. If an "outputformat"
     * is given, it is used for all the tasks.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass record from the taskchain table, with the extra properties "cmid" and "chainid"
     */
    public function create_instance($record = null, $options = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/taskchain/lib.php');
        require_once($CFG->dirroot . '/mod/taskchain/locallib.php');

        $record = (object)(array)$record;

        if (empty($record->sourcefile)) {
            $sourcefiles = [self::DEFAULT_SOURCEFILE];
        } else if (is_array($record->sourcefile)) {
            $sourcefiles = $record->sourcefile;
        } else {
            $sourcefiles = array_map('trim', explode(',', $record->sourcefile));
        }
        unset($record->sourcefile);

        $outputformat = (isset($record->outputformat) ? $record->outputformat : '');
        unset($record->outputformat);

        $defaults = [
            'namesource' => mod_taskchain::TEXTSOURCE_SPECIFIC,
            'tasknamessource' => mod_taskchain::TEXTSOURCE_FILE,
            'addtype' => mod_taskchain::ADDTYPE_CHAINFOLDER,
            'entrytext' => '',
            'exittext' => '',
            'entrypage' => 0,
            'exitpage' => 0,
            'sourceitemid' => 0,
            'configitemid' => 0,
            'attemptgrademethod' => mod_taskchain::GRADEMETHOD_TOTAL,
            'grademethod' => mod_taskchain::GRADEMETHOD_HIGHEST,
            'gradeignore' => 0,
            'gradelimit' => 100,
            'gradeweighting' => 100,
            'allowresume' => 1,
        ];
        foreach ($defaults as $name => $value) {
            if (! isset($record->$name)) {
                $record->$name = $value;
            }
        }

        // The TaskChain and its chain are created here, but there are no tasks yet,
        // because there are no files in the "sourcefile" file area of the new activity.
        $instance = parent::create_instance($record, (array)$options);

        // Add the source files to the activity.
        $context = context_module::instance($instance->cmid);
        $fs = get_file_storage();
        foreach (array_values($sourcefiles) as $i => $sourcefile) {
            $filerecord = [
                'contextid' => $context->id,
                'component' => 'mod_taskchain',
                'filearea' => 'sourcefile',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => basename($sourcefile),
                'sortorder' => ($i == 0 ? 1 : 0), // The first file is the "main" file.
            ];
            $fs->create_file_from_pathname($filerecord, $CFG->dirroot . '/' . $sourcefile);
        }

        // Add a task for each source file.
        $chain = $DB->get_record('taskchain_chains', [
            'parenttype' => mod_taskchain::PARENTTYPE_ACTIVITY,
            'parentid' => $instance->id,
        ], '*', MUST_EXIST);
        $data = (object)[
            'coursemodule' => $instance->cmid,
            'addtype' => mod_taskchain::ADDTYPE_CHAINFOLDER,
            'namesource' => mod_taskchain::TEXTSOURCE_SPECIFIC,
            'tasknamessource' => mod_taskchain::TEXTSOURCE_FILE, // Get task names from the source files.
            'name' => $instance->name,
        ];
        $mform = null;
        taskchain_add_tasks($data, $mform, $chain);

        if ($outputformat) {
            $DB->set_field('taskchain_tasks', 'outputformat', $outputformat, ['chainid' => $chain->id]);
        }

        $instance->chainid = $chain->id;
        return $instance;
    }
}
